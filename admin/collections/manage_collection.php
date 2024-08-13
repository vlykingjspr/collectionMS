<?php
require_once('./../../config.php');

$member_id = isset($_GET['mid']) ? $_GET['mid'] : null;
$collected_categories = [];

if ($member_id) {
    // Query to find all collected categories for the selected member
    $collected_qry = $conn->query("
        SELECT ci.category_id 
        FROM collection_items ci 
        INNER JOIN collection_list cl ON ci.collection_id = cl.id 
        WHERE cl.member_id = '{$member_id}'
    ");

    // Debugging: Check what category IDs are being retrieved
    while ($row = $collected_qry->fetch_assoc()) {
        $collected_categories[] = $row['category_id'];
    }

    // Debugging output
    // Uncomment the following line to see the collected categories

    echo ("." . print_r($collected_categories));
}
$category = $conn->query("
    SELECT * 
    FROM `category_list` 
    WHERE delete_flag = 0 AND `status` = 1 
    ORDER BY `name` ASC
");
?>
<style>
    .disabled-text {
        color: #ccc;
        /* Light grey color to indicate disabled state */
        pointer-events: none;
        /* Disable text selection */
    }
</style>
<div class="container-fluid">
    <form action="" id="collection-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="row">
            <div class="col-md-6">
                <?php if (isset($_GET['mid'])) : ?>
                    <input type="hidden" name="member_id" value="<?= $_GET['mid'] ?>">
                <?php else : ?>
                    <div class="form-group">
                        <label for="member_id" class="control-label">Member</label>
                        <select name="member_id" id="member_id" class="form-control form-control-sm rounded-0 select2" required>
                            <option value="" disabled <?= !isset($member_id) ? "selected" : "" ?>></option>
                            <?php
                            $member = $conn->query("SELECT *,CONCAT(firstname, ' ',COALESCE(middlename,''), ' ' ,lastname) as fullname FROM `student_list` where delete_flag = 0 and `status` = 1 " . (isset($memeber_id) ? " or memeber_id = '{$member_id}' " : "") . " order by CONCAT(firstname, ' ',COALESCE(middlename,''), ' ' ,lastname) asc ");
                            while ($row = $member->fetch_assoc()) :
                            ?>
                                <option value="<?= $row['id'] ?>" <?= isset($member_id) && $member_id == $row['id'] ? "selected" : "" ?>><?= $row['fullname'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="date_collected" class="control-label">Date Collected</label>
                    <input name="date_collected" id="date_collected" type="date" class="form-control form-control-sm rounded-0" value="<?php echo isset($date_collected) ? $date_collected : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="collected_by" class="control-label">Collected By</label>
                    <input name="collected_by" id="collected_by" type="text" class="form-control form-control-sm rounded-0" value="<?php echo isset($collected_by) ? $collected_by : ''; ?>" required>
                </div>

            </div>
            <div class="col-md-6">
                <table class="table table-stripped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input custom-control-input-primary custom-control-input-outline" type="checkbox" id="checkall">
                                    <label for="checkall" class="custom-control-label">All</label>
                                </div>
                            </th>
                            <th class="text-center">Category</th>
                            <th class="text-center">Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $category->fetch_assoc()) :
                            $checked = in_array($row['id'], $collected_categories) ? 'checked' : '';
                            $disabled = in_array($row['id'], $collected_categories) ? 'disabled' : '';
                        ?>
                            <tr>
                                <td class="px-2 py-1 align-middle text-center">
                                    <input type="hidden" class="fee" name="fee[<?= $row['id'] ?>]" value="<?= $row['fee'] ?>">
                                    <div class="custom-control custom-checkbox">
                                        <input name="category_id[<?= $row['id'] ?>]" class="custom-control-input custom-control-input-primary custom-control-input-outline check-item" type="checkbox" id="cat_<?= $row['id'] ?>" value="<?= $row['id'] ?>" <?= $checked ?> <?= $disabled ?>>
                                        <label for="cat_<?= $row['id'] ?>" class="custom-control-label"></label>
                                    </div>
                                </td>
                                <td class="px-2 py-1 align-middle category-text <?= $disabled ? 'disabled-text' : '' ?>"><?= $row['name'] ?></td>
                                <td class="px-2 py-1 align-middle fee-text <?= $disabled ? 'disabled-text' : '' ?>"><?= format_num($row['fee']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>


                </table>
                <div class="form-group">
                    <label for="total_amount" class="control-label">Total Collection</label>
                    <input name="total_amount" id="total_amount" type="text" class="form-control form-control-sm rounded-0" value="<?php echo isset($total_amount) ? $total_amount : 0; ?>" readonly tabindex="-1">
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    function _checkAll() {
        var total = $('.check-item').length
        var checked = $('.check-item:checked').length
        if (total == checked) {
            $('#checkall').prop('checked', true)
        } else {
            $('#checkall').prop('checked', false)
        }
    }

    function calc_total() {
        var total = 0;
        $('.check-item:checked').each(function() {
            var tr = $(this).closest('tr')
            var fee = tr.find('input.fee').val()
            total += parseFloat(fee)
        })
        $('#total_amount').val(total)
    }
    $(document).ready(function() {
        $('#uni_modal').on('shown.modal.bs', function() {
            $('.select2').select2({
                placeholder: 'Please Select Here',
                width: '100%',
                dropdownParent: $('#uni_modal')
            })
        })
        _checkAll()
        calc_total();
        $('.check-item').change(function() {
            _checkAll();
            calc_total();
        })
        $('#checkall').change(function() {
            if ($(this).is(':checked') == true) {
                $('.check-item').prop('checked', true).trigger('change')
            } else {
                $('.check-item').prop('checked', false).trigger('change')
            }
            _checkAll();
            calc_total();
        })
        $('#uni_modal #collection-form').submit(function(e) {
            e.preventDefault();
            var _this = $(this)
            $('.err-msg').remove();
            var el = $('<div>')
            el.addClass("alert err-msg")
            el.hide()
            if (_this[0].checkValidity() == false) {
                _this[0].reportValidity();
                return false;
            }
            if ($('.check-item:checked').length <= 0) {
                alert_toast("Please Select at least 1 category first.", 'error');
                return false;
            }
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_collection",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.error(err)
                    el.addClass('alert-danger').text("An error occured");
                    _this.prepend(el)
                    el.show('.modal')
                    end_loader();
                },
                success: function(resp) {
                    if (typeof resp == 'object' && resp.status == 'success') {
                        location.reload();
                    } else if (resp.status == 'failed' && !!resp.msg) {
                        el.addClass('alert-danger').text(resp.msg);
                        _this.prepend(el)
                        el.show('.modal')
                    } else {
                        el.text("An error occured");
                        console.error(resp)
                    }
                    $("html, body").scrollTop(0);
                    end_loader()

                }
            })
        })

        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ol', 'ul', 'paragraph', 'height']],
                ['table', ['table']],
                ['view', ['undo', 'redo', 'fullscreen', 'codeview', 'help']]
            ]
        })
    })
    $(document).ready(function() {
        function toggleCategoryElements(memberSelected) {
            if (!memberSelected) {
                // Disable all category checkboxes, texts, and fees if no member is selected
                $('.check-item').prop('disabled', true).prop('checked', false);
                $('#checkall').prop('disabled', true).prop('checked', false);
                $('.category-text, .fee-text').addClass('disabled-text');
            } else {
                // Enable all category checkboxes, texts, and fees
                $('.check-item').prop('disabled', false);
                $('#checkall').prop('disabled', false);
                $('.category-text, .fee-text').removeClass('disabled-text');

                // Fetch the categories that the member already has
                $.ajax({
                    url: 'get_collected_categories.php', // Ensure this path is correct
                    type: 'GET',
                    data: {
                        member_id: memberSelected
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('AJAX Response:', response); // Debugging line

                        // Response is an array of collected category IDs
                        $('.check-item').each(function() {
                            var category_id = $(this).val();
                            if (response.includes(parseInt(category_id))) {
                                $(this).prop('checked', true).prop('disabled', true);
                                $(this).closest('tr').find('.category-text, .fee-text').addClass('disabled-text');
                            } else {
                                $(this).prop('checked', false).prop('disabled', false);
                                $(this).closest('tr').find('.category-text, .fee-text').removeClass('disabled-text');
                            }
                        });
                        calc_total();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error); // Debugging line
                        console.log('Response Text:', xhr.responseText); // Check full response
                    }
                });
            }
        }

        // Call the function on page load if a member is already selected
        var initialMemberSelected = $('#member_id').val();
        toggleCategoryElements(initialMemberSelected);

        // Add event listener to member select dropdown
        $('#member_id').change(function() {
            var memberSelected = $(this).val();
            toggleCategoryElements(memberSelected);
        });

        // Existing functionality for check all and calculate total
        _checkAll();
        calc_total();
        $('.check-item').change(function() {
            _checkAll();
            calc_total();
        });
        $('#checkall').change(function() {
            if ($(this).is(':checked') == true) {
                $('.check-item').prop('checked', true).trigger('change')
            } else {
                $('.check-item').prop('checked', false).trigger('change')
            }
            _checkAll();
            calc_total();
        });

        // Form submission and other setup code...
    });
</script>