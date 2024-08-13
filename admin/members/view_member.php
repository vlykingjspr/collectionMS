<?php
if (isset($_GET['id']) && $_GET['id'] > 0) {
    $qry = $conn->query("SELECT m.*,p.name as `phase`,CONCAT(m.firstname, ' ',  COALESCE(CONCAT(m.middlename, ' '), ''), 
                  m.lastname) as fullname from `student_list` m inner join program_list p on m.program_id = p.id where m.id = '{$_GET['id']}' and m.delete_flag = 0 ");
    if ($qry->num_rows > 0) {
        foreach ($qry->fetch_assoc() as $k => $v) {
            $$k = $v;
        }
    } else {
        echo "<script> alert('Unkown Member ID.'); location.replace('./?page=members') </script>";
        exit;
    }
}
?>

<style>
    .collection_text p {
        margin: unset !important;
    }

    .incharge {
        display: none;
    }
</style>
<div class="content py-3">
    <div class="card card-outline card-primary rounded-0 shadow">
        <div class="card-header">
            <h5 class="card-title">Student Detais</h5>
            <div class="card-tools">
                <button class="btn btn-flat btn-sm btn-primary" id="edit_data"><i class="fa fa-edit"></i> Edit</button>
                <button class="btn btn-flat btn-sm btn-danger" id="delete_data"><i class="fa fa-trash"></i> Delete</button>
                <a class="btn btn-flat btn-sm btn-light border" href="./?page=members"><i class="fa fa-angle-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="containder-fluid" id="print1">
                <div class="row">
                    <div class="col-lg-4 col-md-5 col-sm-12">
                        <div class="callout callout-info rounded-0 shadow">
                            <dl>
                                <dt class="text-muted">Fullname</dt>
                                <dd class="pl-3"><b><?= isset($fullname) ? ucwords($fullname) : 'N/A' ?></b></dd>
                                <dt class="text-muted">School ID </dt>
                                <dd class="pl-3"><b><?= isset($school_id) ? $school_id : 'N/A' ?></b></dd>
                                <dt class="text-muted">Rfid </dt>
                                <dd class="pl-3"><b><?= isset($rfid) ? $rfid : 'N/A' ?></b></dd>
                                <dt class="text-muted">Program</dt>
                                <dd class="pl-3"><b><?= isset($phase) ? $phase : 'N/A' ?></b></dd>
                                <dt class="text-muted">Year Level</dt>
                                <dd class="pl-3"><b><?= isset($year) ? $year : 'N/A' ?></b></dd>
                                <dt class="text-muted">Set</dt>
                                <dd class="pl-3"><b><?= isset($set) ? $set : 'N/A' ?></b></dd>
                                <dt class="text-muted stat">Status</dt>
                                <dd class="pl-3">
                                    <?php if ($status == 1) : ?>
                                        <span class="badge badge-success bg-gradient-success rounded-pill px-3">Active</span>
                                    <?php else : ?>
                                        <span class="badge badge-danger bg-gradient-danger rounded-pill px-3">Inactive</span>
                                    <?php endif; ?>
                                </dd>
                                <dt class="incharge">Officer In-charge: __________________</dt>
                            </dl>
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-7 col-sm-12">
                        <div class="d-flex mb-2 align-items-end">
                            <div class="col-auto flex-shrink-1 flex-grow-1 headtitle">
                                <h4>Collection(s)</h4>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-flat btn-sm btn-default bg-navy" id="new_collection"><i class="fa fa-plus"></i> Add Collection</button>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-flat btn-sm btn-default bg-gradient-primary" id="print_receipt"><i class="fa fa-print"></i> Print</button>
                            </div>

                        </div>
                        <hr class="hr1">
                        <div class="list-group print" id="collection-list">
                            <?php
                            if (isset($id)) :
                                $total_fee = 0; // Initialize total fee
                                $collection = $conn->query("
        SELECT cl.id as collection_id, cl.code, cl.date_collected, cl.total_amount, 
               GROUP_CONCAT(CONCAT(cat.name, ' (Php ', ci.fee, ')') SEPARATOR ', ') as categories,
               SUM(ci.fee) as total_collection_fee
        FROM `collection_list` cl
        LEFT JOIN `collection_items` ci ON cl.id = ci.collection_id
        LEFT JOIN `category_list` cat ON ci.category_id = cat.id
        WHERE cl.member_id = '{$id}'
        GROUP BY cl.id
        ORDER BY date(cl.date_collected) DESC
    ");
                                while ($row = $collection->fetch_assoc()) :
                                    $total_fee += $row['total_collection_fee']; // Accumulate the total fee
                            ?>
                                    <div class="list-group-item collection-item">
                                        <div class="d-flex align-items-top justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="collection_text h6 mr-3" style="margin-bottom: 0px;"><?= ($row['code']) ?></div>
                                                <div class="collection_text "><?= $row['categories'] ?> </div>
                                            </div>
                                            <div class="burger">
                                                <div class="dropleft">
                                                    <a class="text-reset text-decoration-one" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v"></i>
                                                    </a>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item view_collection" href="javascript:void(0)" data-id="<?= isset($row['collection_id']) ? $row['collection_id']  : '' ?>">View</a>
                                                        <a class="dropdown-item edit_collection" href="javascript:void(0)" data-id="<?= isset($row['collection_id']) ? $row['collection_id']  : '' ?>">Edit</a>
                                                        <a class="dropdown-item delete_collection" href="javascript:void(0)" data-id="<?= isset($row['collection_id']) ? $row['collection_id']  : '' ?>">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-top">
                                            <div class="col-auto flex-shrink-1 flex-grow-1">
                                                <div class="collection_text text-muted"><?= date("D F d, Y", strtotime($row['date_collected'])) ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="text-right font-weight-bolder h4"><?= format_num($row['total_amount']) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                endwhile;
                                ?>
                                <!-- Display Total Fees at the Bottom -->
                                <div class="list-group-item collection-item">
                                    <div class="d-flex align-items-top justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="collection_text h5 font-weight-bold mr-3">Total Collection:</div>
                                            <div class="collection_text h5 font-weight-bold"><?= "Php " . format_num($total_fee) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        $('#edit_data').click(function() {
            uni_modal('Update Student Details - <b><?= isset($code) ? $code : "" ?></b>', "members/manage_member.php?id=<?= isset($id) ? $id : '' ?>", 'mid-large')
        })
        $('#delete_data').click(function() {
            _conf("Are you sure to delete <b><?= isset($code) ? $code : "" ?></b> Member permanently?", "delete_member", ['<?= isset($id) ? $id : '' ?>'])
        })
        $('#new_collection').click(function() {
            uni_modal('New Collection', "collections/manage_collection.php?mid=<?= isset($id) ? $id : '' ?>", 'large')
        })
        $('.view_collection').click(function() {
            uni_modal('View Collection', "collections/view_collection.php?mid=<?= isset($id) ? $id : '' ?>&id=" + $(this).attr('data-id'), 'mid-large')
        })
        $('.edit_collection').click(function() {
            uni_modal('Edit Collection', "collections/manage_collection.php?mid=<?= isset($id) ? $id : '' ?>&id=" + $(this).attr('data-id'), 'large')
        })
        $('.delete_collection').click(function() {
            _conf("Are you sure to delete this Collection permanently?", "delete_collection", [$(this).attr('data-id')])
        })
    })

    function delete_member($id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_member",
            method: "POST",
            data: {
                id: $id
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occured.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp == 'object' && resp.status == 'success') {
                    location.href = './?page=members';
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        })
    }

    function delete_collection($id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_collection",
            method: "POST",
            data: {
                id: $id
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occured.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp == 'object' && resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        })
    }
</script>

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
            <h3 class="text-center m-0"><b>Collection Receipt</b></h3>


        </div>
    </div>
    <hr>
</noscript>
<script>
    $(document).ready(function() {
        $('#print_receipt').click(function() {
            var head = $('head').clone();

            head.append(`
                <style>
                    @media print {
                        .no-print, #edit_data, #delete_data, #new_collection, .view_collection, .edit_collection, .delete_collection, #print_receipt {
                            display: none;
                        }
                            .prints .headtitle{
                            display:none;}
                            .prints .hr1{
                            display:none;}
                        .prints {
                            display: flex;
                            justify-content: space-between;
                        }
                        /* Adjust the column widths */
                        .prints .col-left {
                            width: 25%;
                            box-sizing: border-box;
                            padding-right: 2%;
                        }
                        .prints .col-right {
                            width: 75%;
                            box-sizing: border-box;
                            padding-left: 2%;
                        }
                        .prints .callout {
                            background-color: #fff !important;
                            box-shadow: none !important;
                            border-left: none !important;
                            padding: 0 !important;
                        }
                        .prints .card {
                            border: none;
                            box-shadow: none;
                        }
                        .prints .card-body {
                            padding: 0;
                        }
                            .prints .card {
                         border: none;
                         box-shadow: none;
                         background: none;
                        }
                         .prints .burger{
                            display: none;
                        }
                        .prints .collection-item {
                            border-bottom: 1px solid #ccc;
                            padding-bottom: 10px;
                            margin-bottom: 10px;
                            page-break-inside: avoid;
                        }
                        .prints h5.card-title {
                            font-size: 20px;
                            margin-bottom: 10px;
                        }
                        .prints dl {
                            margin-bottom: 10px;
                        }
                        .prints dt {
                            font-weight: bold;
                            margin-bottom: 5px;
                            line-height: 1.2;
                        }
                        .prints dd {
                            margin-left: 15px;
                            margin-bottom: 5px;
                            line-height: 1.2;
                        }
                        .prints .text-right {
                            text-align: right;
                        }
                        /* Remove the status in print */
                        .prints .badge  {
                            display: none;
                        }
                        .prints .stat{
                            display: none;
                        }
                        .prints .list-group-item {
                            border: none;
                            padding: 0;
                            margin: 0;
                        }
                            .incharge {
        display: flex;
    }
                    }
                </style>
            `);

            var printContent = $('<div class="prints"></div>');
            var leftColumn = $('<div class="col-left"></div>').append($('#print1 .col-lg-4').clone());
            var rightColumn = $('<div class="col-right"></div>').append($('#print1 .col-lg-8').clone());

            printContent.append(leftColumn);
            printContent.append(rightColumn);

            var el = $('<div>');
            var header = $($('noscript#print-header').html()).clone();
            el.append(head);
            el.append(header);
            el.append(printContent);

            var nw = window.open("", "_blank", "width=1000,height=900,top=50,left=75");
            nw.document.write(el.html());
            nw.document.close();

            setTimeout(() => {
                nw.print();
                setTimeout(() => {
                    nw.close();
                }, 200);
            }, 500);
        });
    });
</script>