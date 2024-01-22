<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Index_model extends CI_Model{
	public function fitrate(){
		//获取最高价格和最低价格
		$sql = 'select `shop_price` from ecs_goods';
		$price_range = $this->db->query($sql);
		return $price_range->result_array();
	}
}