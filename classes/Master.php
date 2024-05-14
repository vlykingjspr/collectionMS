<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_category(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		
		$check = $this->conn->query("SELECT * FROM `category_list` where `name` = '{$name}' and delete_flag = 0 ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exists.";
		}else{
			if(empty($id)){
				$sql = "INSERT INTO `category_list` set {$data} ";
			}else{
				$sql = "UPDATE `category_list` set {$data} where id = '{$id}' ";
			}
			$save = $this->conn->query($sql);
			if($save){
				$resp['status'] = 'success';
				if(empty($id))
				$resp['msg'] = " New Category successfully saved.";
				else
				$resp['msg'] = " Category successfully updated.";
			}else{
				$resp['status'] = 'failed';
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_category(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `category_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Category successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_phase(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		
		$check = $this->conn->query("SELECT * FROM `phase_list` where `name` = '{$name}' and delete_flag = 0 ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Phase already exists.";
		}else{
			if(empty($id)){
				$sql = "INSERT INTO `phase_list` set {$data} ";
			}else{
				$sql = "UPDATE `phase_list` set {$data} where id = '{$id}' ";
			}
			$save = $this->conn->query($sql);
			if($save){
				$resp['status'] = 'success';
				if(empty($id))
				$resp['msg'] = " New Phase successfully saved.";
				else
				$resp['msg'] = " Phase successfully updated.";
			}else{
				$resp['status'] = 'failed';
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_phase(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `phase_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Phase successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_member(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `member_list` where phase_id = '{$phase_id}' and `block` = '{$block}' and `lot` = '{$lot}' and delete_flag = 0 ".(!empty($id) ? " and id != '{$id}'" : ""))->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = ' Phase Block/Lot Already exists.';
		}else{
			if(empty($id)){
				$sql = "INSERT INTO `member_list` set {$data} ";
			}else{
				$sql = "UPDATE `member_list` set {$data} where id = '{$id}' ";
			}
			$save = $this->conn->query($sql);
			if($save){
				$eid = empty($id) ? $this->conn->insert_id : $id;
				$resp['eid'] = $eid;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " New Member successfully saved.";
				else
					$resp['msg'] = " Member successfully updated.";
			}else{
				$resp['status'] = 'failed';
				if(empty($id))
					$resp['msg'] = " Member has failed to save.";
				else
					$resp['msg'] = " Member has failed to update.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}

		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_member(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `member_list` set `delete_flag` = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Member successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_collection(){
		if(empty($_POST['id'])){
			$prefix = date("Ym-");
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `collection_list` where code = '{$prefix}{$code}' ")->num_rows;
				if($check > 0){
					$code = sprintf("%'.05d",ceil($code)+1);
				}else{
					break;
				}
			}
			$_POST['code'] = $prefix.$code;
		}
		extract($_POST);
		$data = "";
		$c_fields = ['code','member_id','total_amount','date_collected','collected_by'];
		foreach($_POST as $k =>$v){
			if(in_array($k,$c_fields)){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		
		if(empty($id)){
			$sql = "INSERT INTO `collection_list` set {$data} ";
		}else{
			$sql = "UPDATE `collection_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$cid = empty($id) ? $this->conn->insert_id : $id;
			$resp['cid'] = $cid;
			$data = "";
			foreach($category_id as $k =>$v){
				if(!empty($data)) $data .=",";
				$data .= "('{$cid}','{$v}','{$fee[$k]}')";
			}
			if(!empty($data)){
				$this->conn->query("DELETE FROM `collection_items` where collection_id = '{$cid}'");
				$sql2 = "INSERT INTO `collection_items` (`collection_id`,`category_id`,`fee`) VALUES {$data}";
				$save2 = $this->conn->query($sql2);
				if($sql2){
					$resp['status'] = 'success';
					if(empty($id))
						$resp['msg'] = " New Collection successfully saved.";
					else
						$resp['msg'] = " Collection successfully updated.";
				}else{
					$resp['status'] = 'failed';
					if(empty($id)){
						$this->conn->query("DELETE FROM `collection_list` where id = '{$qid}'");
						$resp['msg'] = " Collection has failed save.";
					}else{
						$resp['msg'] = " Collection has failed update.";
					}
					$resp['error'] = $this->conn->error;
				}
			}
			
		}else{
			$resp['status'] = 'failed';
			if(empty($id))
				$resp['msg'] = " Collection has failed to save.";
			else
				$resp['msg'] = " Collection has failed to update.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_collection(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `collection_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Collection successfully deleted.");
		}else{
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
	default:
		// echo $sysset->index();
		break;
}