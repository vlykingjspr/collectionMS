<?php
require_once('../config.php');
class Users extends DBConnection
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
	public function save_users()
	{
		extract($_POST);
		$data = '';
		$chk = $this->conn->query("SELECT * FROM `users` where username ='{$username}' " . ($id > 0 ? " and id!= '{$id}' " : ""))->num_rows;
		if ($chk > 0) {
			return 3;
			exit;
		}
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'password'))) {
				if (!empty($data)) $data .= " , ";
				$data .= " {$k} = '{$v}' ";
			}
		}
		if (!empty($password)) {
			$password = md5($password);
			if (!empty($data)) $data .= " , ";
			$data .= " `password` = '{$password}' ";
		}

		if (isset($_FILES['img']) && $_FILES['img']['tmp_name'] != '') {
			$fname = 'uploads/' . strtotime(date('y-m-d H:i')) . '_' . $_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'], '../' . $fname);
			if ($move) {
				$data .= " , avatar = '{$fname}' ";
				if (isset($_SESSION['userdata']['avatar']) && is_file('../' . $_SESSION['userdata']['avatar']) && $_SESSION['userdata']['id'] == $id)
					unlink('../' . $_SESSION['userdata']['avatar']);
			}
		}
		if (empty($id)) {
			$qry = $this->conn->query("INSERT INTO users set {$data}");
			if ($qry) {
				$this->settings->set_flashdata('success', 'User Details successfully saved.');
				return 1;
			} else {
				return 2;
			}
		} else {
			$qry = $this->conn->query("UPDATE users set $data where id = {$id}");
			if ($qry) {
				$this->settings->set_flashdata('success', 'User Details successfully updated.');
				foreach ($_POST as $k => $v) {
					if ($k != 'id') {
						if (!empty($data)) $data .= " , ";
						$this->settings->set_userdata($k, $v);
					}
				}
				if (isset($fname) && isset($move))
					$this->settings->set_userdata('avatar', $fname);

				return 1;
			} else {
				return "UPDATE users set $data where id = {$id}";
			}
		}
	}
	public function delete_users()
	{
		extract($_POST);
		$avatar = $this->conn->query("SELECT avatar FROM users where id = '{$id}'")->fetch_array()['avatar'];
		$qry = $this->conn->query("DELETE FROM users where id = $id");
		if ($qry) {
			$this->settings->set_flashdata('success', 'User Details successfully deleted.');
			if (is_file(base_app . $avatar))
				unlink(base_app . $avatar);
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	// New method to get the logged-in user details
	public function get_logged_in_user()
	{
		$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
		if ($user_id > 0) {
			$qry = $this->conn->query("SELECT * FROM users WHERE id = '{$user_id}'");
			if ($qry->num_rows > 0) {
				return $qry->fetch_assoc();
			}
		}
		return null;
	}
}

$users = new users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
	case 'save':
		echo $users->save_users();
		break;
	case 'delete':
		echo $users->delete_users();
	default:
		// echo $sysset->index();
		break;
}
