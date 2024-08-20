<?php
$user = $conn->query("SELECT * FROM users where id ='" . $_settings->userdata('id') . "'");

$month = isset($_GET['month']) ? $_GET['month'] : date("Y-m");

// Fetch program names
$programs = $conn->query("SELECT id, name FROM `program_list` WHERE delete_flag = 0 AND `status` = 1");
$program_arr = [];
while ($row = $programs->fetch_assoc()) {
    $program_arr[$row['id']] = $row['name'];
}

// Fetch grouped collections
$grouped_collections = $conn->query("
    SELECT 
        m.program_id, 
        m.year, 
        m.set, 
        SUM(c.total_amount) as total_amount 
    FROM 
        collection_list c 
    INNER JOIN 
        student_list m 
    ON 
        c.member_id = m.id 
    WHERE 
        date_format(c.date_collected, '%Y-%m') = '{$month}' 
    GROUP BY 
        m.program_id, m.year, m.set
    ORDER BY 
        m.program_id ASC, m.year ASC, m.set ASC
");

// Fetch categories
$categories = $conn->query("SELECT id, name FROM `category_list` WHERE delete_flag = 0 AND `status` = 1 ORDER BY `name` ASC");
$category_arr = [];
while ($row = $categories->fetch_assoc()) {
    $category_arr[$row['id']] = $row['name'];
}

// Fetch collection data for the third table
$collection_data = [];
$query = "
    SELECT 
        s.program_id, 
        s.year, 
        s.set, 
        ci.category_id, 
        SUM(ci.fee) as total_fee 
    FROM 
        collection_items ci
    INNER JOIN 
        collection_list cl ON ci.collection_id = cl.id
    INNER JOIN 
        student_list s ON cl.member_id = s.id 
    WHERE 
        date_format(cl.date_collected, '%Y-%m') = '{$month}'
    GROUP BY 
        s.program_id, s.year, s.set, ci.category_id
    ORDER BY 
        s.program_id ASC, s.year ASC, s.set ASC
";

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $collection_data[$row['program_id']][$row['year']][$row['set']][$row['category_id']] = $row['total_fee'];
}

// Fetch distinct combinations of program, year, and set for the dropdown
$combinations = $conn->query("
    SELECT DISTINCT 
        s.program_id, 
        s.year, 
        s.set 
    FROM 
        student_list s 
    INNER JOIN 
        collection_list cl ON s.id = cl.member_id
    INNER JOIN 
        collection_items ci ON cl.id = ci.collection_id
    ORDER BY 
        s.program_id, s.year, s.set
");

$combination_options = [];
while ($row = $combinations->fetch_assoc()) {
    $combination_options[] = $row;
}

// Handle the AJAX request
if (isset($_POST['programYearSet'])) {
    $programYearSet = explode('-', $_POST['programYearSet']);
    $program_id = $programYearSet[0];
    $year = $programYearSet[1];
    $set = $programYearSet[2];

    // Fetch data based on the selected combination
    $result = $conn->query("
       SELECT 
        CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS student_name, 
        ci.category_id, 
        SUM(ci.fee) as total_fee 
    FROM 
        student_list s
    LEFT JOIN 
        collection_list cl ON s.id = cl.member_id AND cl.date_collected IS NOT NULL
    LEFT JOIN 
        collection_items ci ON cl.id = ci.collection_id 
    WHERE 
        s.program_id = '$program_id' 
        AND s.year = '$year' 
        AND s.set = '$set'
    GROUP BY 
        s.id, ci.category_id
    ORDER BY 
        s.lastname ASC, s.firstname ASC
    ");

    // Start output buffering to capture the HTML output
    ob_start();

    // Display the data in a table
    echo '<table class="table table-hover  table-bordered table-stripped " style="margin-top: 0px;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Student Name</th>';
    foreach ($category_arr as $category_name) {
        echo '<th>' . $category_name . '</th>';
    }
    echo '<th>Total</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $student_data = [];
    $grand_total = 0; // Initialize grand total variable

    while ($row = $result->fetch_assoc()) {
        $student_name = $row['student_name'];
        $category_id = $row['category_id'];
        $total_fee = $row['total_fee'];

        if (!isset($student_data[$student_name])) {
            $student_data[$student_name] = [
                'categories' => [],
                'total' => 0
            ];
        }

        $student_data[$student_name]['categories'][$category_id] = $total_fee ? $total_fee : 0;
        $student_data[$student_name]['total'] += $total_fee ? $total_fee : 0;

        // Add to grand total
        $grand_total += $total_fee ? $total_fee : 0;
    }

    foreach ($student_data as $student_name => $data) {
        echo '<tr>';
        echo '<td>' . $student_name . '</td>';
        foreach ($category_arr as $category_id => $category_name) {
            echo '<td>' . (isset($data['categories'][$category_id]) ? number_format($data['categories'][$category_id], 2) : '0.00') . '</td>';
        }
        echo '<td>' . number_format($data['total'], 2) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '<tfoot>';
    echo '<tr>';
    echo '<th colspan="' . (count($category_arr) + 1) . '">Total</th>';
    echo '<th>' . number_format($grand_total, 2) . '</th>';
    echo '</tr>';
    echo '</tfoot>';
    echo '</table>';

    // Send the captured output as the response
    echo ob_get_clean();
    exit; // Terminate the script after sending the response
}

?>


<div class="content  ">
    <div class="card card-outline card-navy shadow rounded-0">
        <div class="card-header">
            <h5 class="card-title">Collection Reports</h5>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <div class="">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="collectionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="detailed-tab" data-toggle="tab" href="#detailed" role="tab" aria-controls="detailed" aria-selected="true">Monthly</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="false">Total per Set</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="category-tab" data-toggle="tab" href="#category" role="tab" aria-controls="category" aria-selected="false">Total per Collection</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="studentperset" data-toggle="tab" href="#studperset" role="tab" aria-controls="studperset" aria-selected="false">Total per Student by Set</a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="collectionTabsContent">

                        <!-- Detailed Collection Tab -->
                        <div class="tab-pane fade show active" id="detailed" role="tabpanel" aria-labelledby="detailed-tab">
                            <form action="" id="filter">
                                <div class="row align-items-end">
                                    <div class="col-lg-3 col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label for="month" class="control-label">Month</label>
                                            <input type="month" name="month" id="month" value="<?= $month ?>" class="form-control rounded-0" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <button class="btn btn-primary btn-flat btn-sm"><i class="fa fa-filter"></i> Filter</button>
                                            <button class="btn btn-light border btn-flat btn-sm" type="button" id="print"><i class="fa fa-print"></i> Print</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div id="outprint" class="mt-3">
                                <table class="table table-bordered table-stripped">
                                    <colgroup>
                                        <col width="5%">
                                        <col width="15%">
                                        <col width="15%">
                                        <col width="30%">
                                        <col width="50%">
                                    </colgroup>
                                    <thead>
                                        <tr class="">
                                            <th class="text-center align-middle py-1">#</th>
                                            <th class="text-center align-middle py-1">Date Created</th>
                                            <th class="text-center align-middle py-1">Date Collected</th>
                                            <!-- <th class="text-center align-middle py-1">Collected By</th> -->
                                            <th class="text-center align-middle py-1">Student</th>
                                            <th class="text-center align-middle py-1">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        $total = 0;
                                        $phases = $conn->query("SELECT * FROM `program_list` where id in (SELECT program_id FROM `student_list` where id in (SELECT member_id FROM collection_list where  date_format(date_collected,'%Y-%m') = '{$month}'))");
                                        $phase_arr = array_column($phases->fetch_all(MYSQLI_ASSOC), 'name', 'id');
                                        if ($_settings->userdata('id') == 1) {
                                            // All collection for the month
                                            $qry = $conn->query("SELECT c.*,CONCAT(m.firstname, ' ', COALESCE(m.middlename,''), ' ', m.lastname) as fullname, m.program_id,m.year,m.set from `collection_list` c inner join student_list m on c.member_id = m.id where date_format(c.date_collected,'%Y-%m') = '{$month}' order by date(c.date_collected) desc,(CONCAT(m.firstname, ' ', COALESCE(m.middlename,''), ' ', m.lastname)) asc ");
                                        } else {
                                            // Specific collection for the user
                                            $qry = $conn->query("SELECT c.*, CONCAT(m.firstname, ' ', COALESCE(m.middlename,''), ' ', m.lastname) AS fullname, m.program_id, m.year, m.set 
                                            FROM `collection_list` c 
                                            INNER JOIN `student_list` m ON c.member_id = m.id 
                                            WHERE date_format(c.date_collected, '%Y-%m') = '{$month}' 
                                            AND c.collected_by = '{$_settings->userdata('id')}' 
                                            ORDER BY date(c.date_collected) DESC, (CONCAT(m.firstname, ' ', COALESCE(m.middlename,''), ' ', m.lastname)) ASC");
                                        }
                                        while ($row = $qry->fetch_assoc()) :
                                            $total += $row['total_amount'];
                                        ?>
                                            <tr>
                                                <td class="text-center align-middle px-2 py-1"><?php echo $i++; ?></td>
                                                <td class="align-middle px-2 py-1"><?php echo date("Y-m-d H:i", strtotime($row['date_created'])) ?></td>
                                                <td class="align-middle px-2 py-1"><?php echo date("D F d, Y", strtotime($row['date_collected'])) ?></td>
                                                <!-- <td class="align-middle px-2 py-1"><?php echo ucwords($row['collected_by']) ?></td> -->
                                                <td class="align-middle px-2 py-1">
                                                    <div><?php echo ucwords($row['fullname']) ?></div>
                                                    <small class="text-muted"><?= ucwords((isset($phase_arr[$row['program_id']]) ? $phase_arr[$row['program_id']] : "N/A"))
                                                                                    . '- ' . $row['year'] . '' . $row['set']
                                                                                ?></small>
                                                </td>
                                                <td class="text-right align-middle px-2 py-1"><?php echo format_num($row['total_amount']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="text-center px-1 py-1 align-middle" colspan="4">Total</th>
                                            <th class="text-right px-1 py-1 align-middle"><?= format_num($total) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Summary by Category Tab -->
                        <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="table-title">
                                        <h5>Total Collection per Set</h5> <!-- Optional title, you can remove if not needed -->
                                    </div>
                                    <button class="btn btn-light border btn-flat btn-sm" type="button" id="print-summary">
                                        <i class="fa fa-print"></i> Print
                                    </button>
                                </div>
                                <table class="table table-hover table-bordered table-stripped" id="summaryTable">
                                    <thead>
                                        <tr>
                                            <th>Program</th>
                                            <th>Year</th>
                                            <th>Set</th>
                                            <th>Total Collected</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $overall_total = 0; // Initialize overall total variable
                                        while ($row = $grouped_collections->fetch_assoc()) :
                                            $overall_total += $row['total_amount']; // Add to overall total
                                        ?>
                                            <tr>
                                                <td><?php echo isset($phase_arr[$row['program_id']]) ? $phase_arr[$row['program_id']] : 'N/A'; ?></td>
                                                <td><?php echo $row['year']; ?></td>
                                                <td><?php echo $row['set']; ?></td>
                                                <td><?php echo format_num($row['total_amount']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-right">Overall Total</th>
                                            <th><?php echo format_num($overall_total); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Category by Category Tab -->
                        <div class="tab-pane fade" id="category" role="tabpanel" aria-labelledby="category-tab">
                            <div class="mt-3">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="table-title">
                                        <h5>Total per Collection</h5> <!-- Optional title, you can remove if not needed -->
                                    </div>
                                    <button class="btn btn-light border btn-flat btn-sm mb-2" type="button" id="print-category"><i class="fa fa-print"></i> Print</button>
                                </div>
                                <table class="table table-hover  table-bordered table-stripped" id="categoryTable">
                                    <thead>
                                        <tr>
                                            <th>Set</th>
                                            <?php foreach ($category_arr as $category_name) : ?>
                                                <th><?= $category_name ?></th>
                                            <?php endforeach; ?>
                                            <th>Total</th> <!-- Add Total column header -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Initialize totals array
                                        $totals = array_fill_keys(array_keys($category_arr), 0);
                                        $grand_total = 0;

                                        foreach ($collection_data as $program_id => $years) :
                                            foreach ($years as $year => $sets) :
                                                foreach ($sets as $set => $categories) :
                                                    $row_total = 0;
                                        ?>
                                                    <tr>
                                                        <td>
                                                            <?= isset($program_arr[$program_id]) ? $program_arr[$program_id] : 'Unknown Program'; ?>
                                                            <?= $year; ?>
                                                            <?= $set; ?>
                                                        </td>
                                                        <?php foreach ($category_arr as $category_id => $category_name) : ?>
                                                            <?php
                                                            $amount = isset($categories[$category_id]) ? $categories[$category_id] : 0;
                                                            $totals[$category_id] += $amount; // Add to total for this category
                                                            $row_total += $amount; // Add to row total
                                                            ?>
                                                            <td><?= number_format($amount, 2); ?></td>
                                                        <?php endforeach; ?>
                                                        <td><?= number_format($row_total, 2); ?></td> <!-- Display row total -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <?php foreach ($totals as $total) : ?>
                                                <th><?= number_format($total, 2); ?></th>
                                            <?php endforeach; ?>
                                            <th><?= number_format(array_sum($totals), 2); ?></th> <!-- Display grand total -->
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Search by set -->
                        <div class="tab-pane fade" id="studperset" role="tabpanel" aria-labelledby="studentperset">
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="table-title">
                                        <h5>Total per Student by Set</h5> <!-- Optional title, you can remove if not needed -->
                                    </div>
                                    <button class="btn btn-light border btn-flat btn-sm mb-2" type="button" id="print-studperset"><i class="fa fa-print"></i> Print</button>
                                </div>

                                <form id="filterForm">
                                    <div class="form-group">
                                        <label for="programYearSet">Select Program, Year, and Set:</label>
                                        <select class="form-control" id="programYearSet" name="programYearSet">
                                            <option value="">Select</option>
                                            <?php foreach ($combination_options as $option) : ?>
                                                <option value="<?= $option['program_id'] . '-' . $option['year'] . '-' . $option['set']; ?>">
                                                    <?= isset($program_arr[$option['program_id']]) ? $program_arr[$option['program_id']] : 'Unknown Program'; ?>
                                                    - Year <?= $option['year']; ?>, Set <?= $option['set']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" id="loadDataBtn" class="btn btn-primary mb-4">Load Set</button>
                                </form>

                                <div id="tableContainer" class="">

                                    <!-- The table will be dynamically loaded here based on the selected program, year, and set -->
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<noscript id="print-header">
    <style>
        #sys_logo {
            width: 5em !important;
            height: 5em !important;
            object-fit: scale-down !important;
            object-position: center center !important;
        }
    </style>
    <div class="d-flex align-items-center">
        <div class="col-auto text-center pl-4">
            <img src="<?= validate_image($_settings->info('logo')) ?>" alt=" System Logo" id="sys_logo" class="img-circle border border-dark">
        </div>
        <div class="col-auto flex-shrink-1 flex-grow-1 px-4">
            <h4 class="text-center m-0"><?= $_settings->info('name') ?></h4>
            <h3 class="text-center m-0"><b>Collection Report</b></h3>
            <h5 class="text-center m-0">For the Month of <?= date("F Y", strtotime($month)) ?></h5>
        </div>
    </div>
    <hr>
</noscript>

<script>
    $(function() {
        // Handle tab click to add/remove bg-gradient-secondary class
        $('#collectionTabs a').on('click', function() {
            $('#collectionTabs a').removeClass('bg-gradient-secondary');
            $(this).addClass('bg-gradient-secondary');
        });

        $('#filter').submit(function(e) {
            e.preventDefault();
            location.href = "./?page=reports/collections&" + $(this).serialize();
        });

        // Function to handle printing for each tab
        function printTable(tableId, title) {
            start_loader();

            // Clone the head and content of the table to print
            var head = $('head').clone();
            var p = $(tableId).clone();
            var el = $('<div>');

            // Clone the noscript content and include it in the print document
            var header = $($('noscript#print-header').html()).clone();
            head.find('title').text(title + " - Print View");

            // Create a title element to include above the table in the print view
            var tableTitle = $('<h3>').text(title).css({
                'text-align': 'center',
                'margin-bottom': '20px'
            });

            // Append the cloned head, noscript header, title, and table content to the new document
            el.append(head);
            el.append(header); // Append the noscript header content
            el.append(tableTitle); // Append the table title
            el.append(p);

            // Open a new window and write the content to it
            var nw = window.open("", "_blank", "width=1000,height=900,top=50,left=75");
            nw.document.write(el.html());
            nw.document.close();

            // Delay printing to allow the content to fully render
            setTimeout(() => {
                nw.print();
                setTimeout(() => {
                    nw.close();
                    end_loader();
                }, 200);
            }, 500);
        }



        // Bind print button for the detailed collection tab
        $('#print').click(function() {
            printTable('#outprint', "Collection Monthly Report");
        });

        // Bind print button for the summary tab
        $('#print-summary').click(function() {
            printTable('#summaryTable', "Summary by Set");
        });

        // Bind print button for the category tab
        $('#print-category').click(function() {
            printTable('#categoryTable', "Total per Collection");
        });

        // Bind print button for the search by set tab
        $('#print-studperset').click(function() {
            printTable('#tableContainer', "Total per Student by Set");
        });

        // Load data for "Search by Set" tab
        $('#loadDataBtn').click(function() {
            var selectedValue = $('#programYearSet').val();

            if (selectedValue !== "") {
                $.ajax({
                    url: '', // The same PHP file will handle the request
                    type: 'POST',
                    data: {
                        programYearSet: selectedValue
                    },
                    success: function(response) {
                        // Split the selected value to get program, year, and set
                        var splitValue = selectedValue.split('-');
                        var program_id = splitValue[0];
                        var year = splitValue[1];
                        var set = splitValue[2];

                        // Fetch the program name from the program_arr
                        var program_name = "Unknown Program";
                        <?php foreach ($program_arr as $id => $name) : ?>
                            if (program_id == <?= $id ?>) {
                                program_name = "<?= $name ?>";
                            }
                        <?php endforeach; ?>

                        // Create the header text
                        var headerText = program_name + " " + year + set;

                        // Append the header and the table content
                        $('#tableContainer').html(
                            '<div class="table-header"><h5>' + headerText + '</h5></div>' +
                            response
                        );
                    }
                });
            } else {
                alert('Please select a valid option.');
            }
        });
    });
</script>
<style>
    /* Hover effect for all tabs */
    .nav-tabs .nav-link:hover {
        color: #fff;
        background-color: #1a0120;
    }

    .nav-tabs .nav-link {
        color: #343a40;
    }

    .bg-gradient-secondary {
        color: white !important;
    }

    #tableContainer .content-wrapper {
        margin-left: 0px !important;
        margin-top: 0px !important;
        background-color: white;
        padding-top: 0px !important;
    }
</style>