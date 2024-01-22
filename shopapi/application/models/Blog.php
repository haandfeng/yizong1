<?php
class Blog_model extends CI_Model{
	public function Blog() {		
		$sql = 'select * from '.$this->db->dbprefix.'ad_custom limit 3';
		$result = $this->db->query($sql);
		return $result->result_array();
	}
}