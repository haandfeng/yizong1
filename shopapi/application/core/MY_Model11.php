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
					return "1";
					break;
				case '2-0-0':
					return "2";
					break;
				case '1-0-0':
					return "3";
					break;
				case '1-2-0':
					return "4";
					break;
				case '1-2-3':
					return "5";
					break;
				case '5-2-2':
					return "6";
					break;
				case '4-0-0':
					return "7";
					break;
				default:
					return "8";
					break;
			}
		}
}