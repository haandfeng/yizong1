<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$user = $this->value['username'];
		$pwd  = $this->value['pwd'];
		//echo $user;
		//echo $pwd;
		$data[] = $user;
		$data[] = $pwd;
		$sql = "select `user_name`,`user_id`,`password` from ecs_users where user_name=? and password=?";
		$query = $this->db->query($sql,$data);
		//echo $this->db->last_query();
		$uArr = $query->result_array();
		if($query->num_rows()){
			$this->_tojson('200',"获取成功",$uArr);
		}else{			
			$this->_tojson("-200","该用户不存在");
		}
	}
	public function abc()
	{
		$sql = "select * from ecs_users";
		$query = $this->db->query($sql);
		//echo $this->db->last_query();
		$uArr = $query->result_array();
		$Arr = array('page' => $uArr);
		$this->_tojson('200',"获取成功",$Arr);
	}
}
