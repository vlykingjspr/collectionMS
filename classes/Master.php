<?php
require_once('../config.php');
class Master extends DBConnection
{
	private $settings;
	public function __construct()
	{
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct()
	{
		parent::__destruct();
	}
	function capture_err()
	{
		if (!$this->conn->error)
			return false;
		else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_category()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}

		$check = $this->conn->query("SELECT * FROM `category_list` where `name` = '{$name}' and delete_flag = 0 " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exists.";
		} else {
			if (empty($id)) {
				$sql = "INSERT INTO `category_list` set {$data} ";
			} else {
				$sql = "UPDATE `category_list` set {$data} where id = '{$id}' ";
			}
			$save = $this->conn->query($sql);
			if ($save) {
				$resp['status'] = 'success';
				if (empty($id))
					$resp['msg'] = " New Category successfully saved.";
				else
					$resp['msg'] = " Category successfully updated.";
			} else {
				$resp['status'] = 'failed';
				$resp['err'] = $this->conn->error . "[{$sql}]";
			}
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_category()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `category_list` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Category successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_phase()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}

		$check = $this->conn->query("SELECT * FROM `program_list` where `name` = '{$name}' and delete_flag = 0 " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = " Phase already exists.";
		} else {
			if (empty($id)) {
				$sql = "INSERT INTO `program_list` set {$data} ";
			} else {
				$sql = "UPDATE `program_list` set {$data} where id = '{$id}' ";
			}
			$save = $this->conn->query($sql);
			if ($save) {
				$resp['status'] = 'success';
				if (empty($id))
					$resp['msg'] = " New Phase successfully saved.";
				else
					$resp['msg'] = " Phase successfully updated.";
			} else {
				$resp['status'] = 'failed';
				$resp['err'] = $this->conn->error . "[{$sql}]";
			}
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_phase()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `program_list` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Phase successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_member()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `student_list` where program_id = '{$program_id}' and `year` = '{$year}' and `set` = '{$set}' and delete_flag = 0 " . (!empty($id) ? " and id != '{$id}'" : ""))->num_rows;

		if (empty($id)) {
			$sql = "INSERT INTO `student_list` set {$data} ";
		} else {
			$sql = "UPDATE `student_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if ($save) {
			$eid = empty($id) ? $this->conn->insert_id : $id;
			$resp['eid'] = $eid;
			$resp['status'] = 'success';
			if (empty($id))
				$resp['msg'] = " New Student successfully saved.";
			else
				$resp['msg'] = " Student successfully updated.";
		} else {
			$resp['status'] = 'failed';
			if (empty($id))
				$resp['msg'] = " Student has failed to save.";
			else
				$resp['msg'] = " Student has failed to update.";
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}


		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_member()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `student_list` set `delete_flag` = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Student successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_collection()
	{
		if (empty($_POST['id'])) {
			$prefix = date("Ym-");
			$code = sprintf("%'.05d", 1);
			while (true) {
				$check = $this->conn->query("SELECT * FROM `collection_list` where code = '{$prefix}{$code}' ")->num_rows;
				if ($check > 0) {
					$code = sprintf("%'.05d", ceil($code) + 1);
				} else {
					break;
				}
			}
			$_POST['code'] = $prefix . $code;
		}

		// Extract values from POST data
		extract($_POST);

		// Debugging: Log total_amount value to see if it's being received correctly
		error_log("Total Amount Received: " . $total_amount);

		$data = "";
		$c_fields = ['code', 'member_id', 'total_amount', 'date_collected', 'collected_by', 'cash'];
		foreach ($_POST as $k => $v) {
			if (in_array($k, $c_fields)) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}

		if (empty($id)) {
			$sql = "INSERT INTO `collection_list` set {$data} ";
		} else {
			$sql = "UPDATE `collection_list` set {$data} where id = '{$id}' ";
		}

		$save = $this->conn->query($sql);

		if ($save) {
			$cid = empty($id) ? $this->conn->insert_id : $id;
			$resp['cid'] = $cid;
			$data = "";
			foreach ($category_id as $k => $v) {
				if (!empty($data)) $data .= ",";
				$data .= "('{$cid}','{$v}','{$fee[$k]}')";
			}
			if (!empty($data)) {
				$this->conn->query("DELETE FROM `collection_items` where collection_id = '{$cid}'");
				$sql2 = "INSERT INTO `collection_items` (`collection_id`,`category_id`,`fee`) VALUES {$data}";
				$save2 = $this->conn->query($sql2);
				if ($sql2) {
					$resp['status'] = 'success';
					if (empty($id))
						$resp['msg'] = " New Collection successfully saved.";
					else
						$resp['msg'] = " Collection successfully updated.";
				} else {
					$resp['status'] = 'failed';
					if (empty($id)) {
						$this->conn->query("DELETE FROM `collection_list` where id = '{$qid}'");
						$resp['msg'] = " Collection has failed save.";
					} else {
						$resp['msg'] = " Collection has failed update.";
					}
					$resp['error'] = $this->conn->error;
				}
			}
		} else {
			$resp['status'] = 'failed';
			if (empty($id))
				$resp['msg'] = " Collection has failed to save.";
			else
				$resp['msg'] = " Collection has failed to update.";
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}

	function get_categories()
	{
		$member_id = $_POST['member_id'];

		// Fetch categories that are not already associated with the member
		$category_query = "
			SELECT * 
			FROM `category_list` 
			WHERE delete_flag = 0 
			AND `status` = 1 
			AND id NOT IN (
				SELECT category_id 
				FROM `collection_items` 
				WHERE collection_id IN (
					SELECT id 
					FROM `collection_list` 
					WHERE member_id = '$member_id'
				)
			)
			ORDER BY `name` ASC
		";

		$categories = $this->conn->query($category_query);
		$category_list = [];

		while ($row = $categories->fetch_assoc()) {
			$category_list[] = [
				'id' => $row['id'],
				'name' => $row['name'],
				'fee' => number_format($row['fee'], 2)  // Formatting fee as per your needs
			];
		}

		// Return the list of categories as a JSON response
		echo json_encode(['categories' => $category_list]);
		exit;
	}
	function delete_collection()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `collection_list` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Collection successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_category':
		echo $Master->save_category();
		break;
	case 'delete_category':
		echo $Master->delete_category();
		break;
	case 'save_phase':
		echo $Master->save_phase();
		break;
	case 'delete_phase':
		echo $Master->delete_phase();
		break;
	case 'save_member':
		echo $Master->save_member();
		break;
	case 'delete_member':
		echo $Master->delete_member();
		break;
	case 'save_collection':
		echo $Master->save_collection();
		break;
	case 'delete_collection':
		echo $Master->delete_collection();
		break;
	case 'get_categories':  // Add the case for fetching categories
		echo $Master->get_categories();
		break;

	default:
		// echo $sysset->index();
		break;
}
