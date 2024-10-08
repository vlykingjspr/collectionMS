<?php
require_once('./../../config.php');

// Fetch logged-in user data
$user = $conn->query("SELECT * FROM users where id ='" . $_settings->userdata('id') . "'");

// Check if a collection ID is provided and fetch the collection details
if (isset($_GET['id']) && $_GET['id'] > 0) {
    $qry = $conn->query("SELECT * from `collection_list` where id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        foreach ($qry->fetch_assoc() as $k => $v) {
            $$k = $v;
        }
        $logged_in_user_id = $_SESSION['id'];
    } else {
?>
        <center>Unknown Collection ID</center>
        <style>
            #uni_modal .modal-footer {
                display: none
            }
        </style>
        <div class="text-right">
            <button class="btn btndefault bg-gradient-dark btn-flat" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
        </div>
<?php
        exit;
    }
}
?>
<style>
    #submit {
        display: none;
    }

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
                    <input name="collected_by" id="collected_by" type="text" class="form-control form-control-sm rounded-0" value="<?php echo $_settings->userdata('firstname') ?>" readonly>
                </div>
                <input type="hidden" name="collected_by" value="<?php echo $_settings->userdata('id') ?>">
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
                    <tbody id="category-list">
                        <!-- Dynamic category list will be rendered here -->
                    </tbody>
                </table>
                <div class="form-group">
                    <label for="total_amount" class="control-label">Total Collection</label>
                    <input name="total_amount" id="total_amount" type="text" class="form-control form-control-sm rounded-0" value="<?php echo isset($total_amount) ? $total_amount : 0; ?>" readonly tabindex="-1">
                </div>
                <div class="form-group">
                    <label for="cash" class="control-label">Cash</label>
                    <input name="cash" id="cash" type="number" class="form-control form-control-sm rounded-0" oninput="calculateChange()" value="" required>
                </div>
                <div class="form-group">
                    <label for="change" class="control-label">Change</label>
                    <input name="change" id="change" type="text" class="form-control form-control-sm rounded-0" readonly tabindex="-1">
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    function _checkAll() {
        var total = $('.check-item').length;
        var checked = $('.check-item:checked').length;
        if (total == checked) {
            $('#checkall').prop('checked', true);
        } else {
            $('#checkall').prop('checked', false);
        }
    }

    function calculateChange() {
        var totalAmount = parseFloat(document.getElementById('total_amount').value.replace('Php', '')) || 0;
        var cash = parseFloat(document.getElementById('cash').value) || 0;
        var change = cash - totalAmount;

        if (change < 0) {
            document.getElementById('change').value = 'Invalid payment';
        } else {
            document.getElementById('change').value = change.toFixed(2);
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

    function fetchCategories(member_id) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=get_categories", // Your PHP endpoint to fetch categories
            method: 'POST',
            data: {
                member_id: member_id
            },
            dataType: 'json',
            success: function(resp) {
                var html = '';
                resp.categories.forEach(function(category) {
                    html += `
                        <tr>
                            <td class="px-2 py-1 align-middle text-center">
                                <input type="hidden" class="fee" name="fee[${category.id}]" value="${category.fee}">
                                <div class="custom-control custom-checkbox">
                                    <input name="category_id[${category.id}]" class="custom-control-input custom-control-input-primary custom-control-input-outline check-item" type="checkbox" id="cat_${category.id}" value="${category.id}">
                                    <label for="cat_${category.id}" class="custom-control-label"></label>
                                </div>
                            </td>
                            <td class="px-2 py-1 align-middle">${category.name}</td>
                            <td class="px-2 py-1 align-middle">${category.fee}</td>
                        </tr>
                    `;
                });
                $('#category-list').html(html);
                calc_total();
                bindCheckboxEvents();
            }
        });
    }

    function bindCheckboxEvents() {
        $('.check-item').change(function() {
            _checkAll();
            calc_total();
        });

        $('#checkall').change(function() {
            if ($(this).is(':checked') == true) {
                $('.check-item').prop('checked', true).trigger('change');
            } else {
                $('.check-item').prop('checked', false).trigger('change');
            }
            _checkAll();
            calc_total();
        });
    }

    function validateForm() {
        var member_id = $('#member_id').val();
        var date_collected = $('#date_collected').val();
        var collected_by = $('#collected_by').val();
        var total_amount = parseFloat($('#total_amount').val()) || 0;
        var cash = parseFloat($('#cash').val()) || 0;

        // Check if all required fields are filled
        var allFieldsFilled = member_id && date_collected && collected_by;

        // Check if cash is greater than or equal to the total amount
        var isCashSufficient = cash >= total_amount;

        // Show or hide the submit button based on the validation
        if (allFieldsFilled && isCashSufficient && total_amount > 0) {
            $('#submit').show();
        } else {
            $('#submit').hide();
        }
    }

    $(document).ready(function() {
        // Initial form validation check
        validateForm();

        // Bind validation to input changes
        $('#member_id, #date_collected, #cash').on('input change', function() {
            validateForm();
        });

        // Bind checkbox event to recalculate total and validate form
        $(document).on('change', '.check-item', function() {
            calc_total();
            validateForm();
        });
        $('#uni_modal').on('shown.modal.bs', function() {
            $('.select2').select2({
                placeholder: 'Please Select Here',
                width: '100%',
                dropdownParent: $('#uni_modal')
            });
        });

        $('#member_id').change(function() {
            var member_id = $(this).val();
            fetchCategories(member_id);
        });

        // bindCheckboxEvents();

        $('#uni_modal #collection-form').submit(function(e) {
            e.preventDefault();
            var _this = $(this);

            // Recalculate total before submission to ensure it's correct
            calc_total();
            validateForm();

            if (!$('#submit').is(':visible')) {
                return false;
            }


            $('.err-msg').remove();
            var el = $('<div>');
            el.addClass("alert err-msg");
            el.hide();

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
                    console.error(err);
                    el.addClass('alert-danger').text("An error occurred");
                    _this.prepend(el);
                    el.show('.modal');
                    end_loader();
                },
                success: function(resp) {
                    if (typeof resp == 'object' && resp.status == 'success') {
                        location.reload();
                    } else if (resp.status == 'failed' && !!resp.msg) {
                        el.addClass('alert-danger').text(resp.msg);
                        _this.prepend(el);
                        el.show('.modal');
                    } else {
                        el.text("An error occurred");
                        console.error(resp);
                    }
                    $("html, body").scrollTop(0);
                    end_loader();
                }
            });
        });


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
        });
    });
</script>