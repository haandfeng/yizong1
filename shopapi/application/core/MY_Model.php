<?php
class MY_Model extends CI_Model{
	public function __construct(){
		parent::__construct();
		//订单状态
	}
		protected function order_status($str){
			switch ($str){
				//	$uArr[$i]['order_status'].'-'.$uArr[$i]['pay_status'].'-'.$uArr[$i]['shipping_status'];
				case '0-0-0':
				case '1-0-0':
					return "1";//待付款
					break;
				case '2-0-0':
					return "2";//已取消
					break;
				case '1-2-0':
				case '1-2-3':
				case '5-2-5':
					return "3";//待发货
					break;
				case '5-2-1':
					return "4";//已发货
					break;
				case '5-2-2':
					return "5";//已完成
					break;
			}
		}
}