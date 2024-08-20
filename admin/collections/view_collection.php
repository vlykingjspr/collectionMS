<?php
require_once('./../../config.php');
if (isset($_GET['id']) && $_GET['id'] > 0) {
	$qry = $conn->query("SELECT c.*, 
                            CONCAT(m.lastname, ', ', m.firstname, ' ', COALESCE(m.middlename, '')) as fullname, 
                            CONCAT(u.firstname, ' ', u.lastname) as collected_by_name 
                     FROM `collection_list` c 
                     INNER JOIN `student_list` m ON c.member_id = m.id 
                     INNER JOIN `users` u ON c.collected_by = u.id 
                     WHERE c.id = '{$_GET['id']}'
                     ORDER BY c.date_created DESC");
	if ($qry->num_rows > 0) {
		foreach ($qry->fetch_assoc() as $k => $v) {
			$$k = $v;
		}
	} else {
?>
		<center>Unknown collection</center>
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
	#uni_modal .modal-footer {
		display: none
	}
</style>
<div class="container-fluid">
	<div class="text-right">
		<button class="btn btn-primary bg-gradient-primary btn-sm btn-flat no-print mb-2" style="border: none;" type="button" id="print_receipt"><i class="fa fa-print"></i> Print</button>
	</div>


	<div class="row prints">

		<div class="col-md-6">
			<dl>
				<dt class="text-muted">Ref. Code</dt>
				<dd class="pl-3" style="color: red; font-size:1.5em; font-weight:bold;"><?= isset($code) ? $code : "" ?></dd>
				<dt class="text-muted">Student Name</dt>
				<dd class="pl-3"><?= isset($fullname) ? ($fullname) : "" ?></dd>
				<dt class="text-muted">Date Collected</dt>
				<dd class="pl-3"><?= isset($date_collected) ? date("D F d, Y", strtotime($date_collected)) : "" ?></dd>
				<dt class="text-muted">Collected By</dt>
				<!-- <dd class="pl-3"><?= isset($collected_by) ? ($collected_by) : "" ?></dd> -->
				<dd class="pl-3"><?= isset($collected_by_name) ? $collected_by_name : "" ?></dd>
			</dl>
		</div>
		<div class="col-md-6">
			<table class="table table-stripped table-bordered">
				<thead>
					<tr>
						<th class="text-center">Category</th>
						<th class="text-center">Fee</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$collection = $conn->query("SELECT i.*,c.name as `category` FROM `collection_items` i inner join category_list c on i.category_id = c.id where i.collection_id = '{$id}' ");
					while ($row = $collection->fetch_assoc()) :
					?>
						<tr>
							<td class="px-2 py-1 align-middle"><?= $row['category'] ?></td>
							<td class="px-2 py-1 align-middle text-right"><?= format_num($row['fee']) ?></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
				<tfoot>
					<tr>
						<th class="px-1 py-1 text-center">Total</th>
						<th class="px-1 py-1 text-right"><?= format_num($total_amount) ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
	<div class="clear-fix mb-3"></div>

	<div class="text-right">

		<button class="btn btn-default bg-gradient-dark btn-sm btn-flat no-print" type="button" data-dismiss="modal"><i class="fa f-times"></i> Close</button>

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
					@media print{
						.no-print{
							display:none;
						}
						.prints{
							width:100%;
							max-width:100%;
						}
						.prints .col-md-6{
							width:50%;
							float:left;
						}
						.prints .col-md-6 table{
							width:100%;
						}
						.prints .col-md-6 table th,
						.prints .col-md-6 table td{
							border:1px solid;
						}
						.prints .col-md-6 table th{
							background: #f1f1f1;
						}
					}
                </style>
            `);

			var modalContent = $('#uni_modal .container-fluid').clone();
			var el = $('<div>');
			var header = $($('noscript#print-header').html()).clone();
			el.append(head);
			el.append(header);
			el.append(modalContent);

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