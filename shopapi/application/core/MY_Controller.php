<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH.'/libraries/PHPMailer/class.phpmailer.php';
class MY_Controller extends CI_Controller{
	protected $values = '';
	public function __construct(){
		parent::__construct();
		header('content-type:text/html;charset=utf-8');
		$this->_values = $this->values();

		//$this->userid = $this->_getId();
		//echo $this->config->item('from');
		error_reporting('E_ALL^E_NOTIC');
	}
	
	//用于接收值
	protected function values(){
		$rValue = $_REQUEST;
		return $rValue;
	}
	//json 输出格式
	protected function _tojson($code,$msg,$data=''){
		$_ret = array(
			'code' => $code,		
			'msg' => $msg,
			'data' => $data
		);
		echo json_encode($_ret);
		exit();
	}
	//验证API_TOKEN
	private function _check_token ($c_token){
		if (empty($c_token)){
			$this->_tojson('-120','API_TOKEN validation fails');
		}
		if (isset($c_token) && !empty($c_token)){
			$module = $this->_getR(1);
			$controller = $this->_getR(2);
			$time = date("Y-m-d",time());
			$key = $this->config->item('key');
			$api_token = md5($module.$controller.$time.$key);
			if (strtolower($c_token) != strtolower($api_token)){
				$this->_tojson('-100','API_TOKEN validation fails');	
			}
		}else {
			$this->_tojson('-100','API_TOKEN validation fails');	
		}
	}
	
	//用于获取URL中的值
	protected function _getR($num){
		return $this->uri->segment($num);
	}
	//验证用户是否登录
	protected function _login(){
		$key = $_REQUEST['key'];
		$sql = "select * from ecs_sessions where sesskey=?";
		$result = $this->db->query($sql,$key);
		//echo $this->db->last_query();
		if($result->num_rows())		
			$this->_tojson('200',"获取成功");
		else
			$this->_tojson("-200","未登录，请登录后操作");
	}
	//获取id
	protected function _getId(){
		$key = $_REQUEST['key'];
		if(empty($key)){
			$this->_tojson('-210', 'key为空', array());
		}
		$sql = 'select userid from ecs_sessions where sesskey = ?';
		$result = $this->db->query($sql,$key);
		$userid = $result->result_array();
		if(!empty($userid)){
			return $userid[0]['userid'];
		}else{			
			return 0;
		}
	}
	protected function sendEail($title,$address,$message){		
		
		$mail=new PHPMailer();
		// 设置PHPMailer使用SMTP服务器发送Email
		$mail->IsSMTP();
		
		// 设置邮件的字符编码，若不指定，则为'UTF-8'
		//$mail->CharSet='UTF-8';
		$mail->CharSet=$this->config->item('charset');
		
		// 添加收件人地址，可以多次使用来添加多个收件人
		$mail->AddAddress($address);
		// 设置邮件正文
		$mail->Body=$message;
		
		// 设置邮件头的From字段。
		//$mail->From='shilh123@sina.cn';
		$mail->From=$this->config->item('from');
		
		// 设置发件人名字
		//$mail->FromName='LilyRecruit';
		$mail->FromName=$this->config->item('name');
		// 设置邮件标题
		$mail->Subject=$title;
		
		// 设置SMTP服务器。
		//$mail->Host='smtp.sina.cn';
		$mail->Host=$this->config->item('host');		
		
		// 设置为"需要验证"
		//$mail->SMTPAuth=true;
		//$mail->SMTPAuth=$this->config->item('auth');
		$mail->SMTPAuth=true;
		
		// 设置用户名和密码。
		//$mail->Username='shilh123@sina.cn';
	//	$mail->Password='shilihui0107';
		$mail->Username=$this->config->item('senduser');
		$mail->Password=$this->config->item('passwd');		
		// 发送邮件。
		return($mail->Send());
	}
	/**
	 *
	 * @Title: order_paid
	 * @Description:
	 * @param array $log_id
	 * @return boolean
	 * @throws
	 */
	protected function order_paid($log_id){
	
		$j = 0;
		$log_id = explode(',', $log_id);
		for($k=0;$k<count($log_id);$k++){
			$pay_log = $this->db->get_where('pay_log',array('log_id'=>$log_id[$k]));
			$pay_log = $pay_log->result_array();
			if ($pay_log && $pay_log['is_paid'] == 0)
			{
				//修改订单状态
				$data = array(
						'is_paid' => 1
				);
				$this->db->where('log_id',$log_id[$k]);
				$paylog = $this->db->update('pay_log',$data);
				if($pay_log[0]['order_type']==0){
					//获取订单信息
					$order = $this->db->get_where('order_info',array('order_id'=>$pay_log[0]['order_id']));
					$order_info = $order->result_array();
					$order_status = array(
							'order_status'=>'',
							'confirm_time' => time(),
							'pay_status' => 2,
							'pay_time' => time(),
							'order_status' => 1
					);
					$this->db->where('order_id',$pay_log[0]['order_id']);
					$c = $this->db->update('order_info',$order_status);
						
					//更新库存
					$order_goods = $this->db->get_where('order_goods',array('order_id'=>$pay_log[0]['order_id']));
					$order_goods = $order_goods->result_array();
					for($i=0;$i<count($order_goods);$i++){
						$goods = $this->db->get_where('goods',array('goods_id'=>$order_goods[$i]['goods_id']));
						$goods = $goods->result_array();
						$goods_num = array('goods_number'=>$goods[0]['goods_number']-$order_goods[$i]['goods_number']);
						$this->db->where('goods_id',$order_goods[$i]['goods_id']);
						$this->db->update('goods',$goods_num);
						if(!$this->db->affected_rows()){
							$j++;
						}
					}
						
					//减积分
					$this->db->select('integral');
					$inter = $this->db->get_where('order_info',array('order_id'=>$pay_log[0]['order_id']));
					$intre = $inter->result_array();
					if(!$intre[0]['integral'] == 0){
							
						$this->db->select('pay_points');
						$points = $this->db->get_where('users',array('user_id'=>$order_info[0]['user_id']));
						$pores = $points->result_array();
							
						$rempoomt = $pores[0]['pay_points'] - $intre[0]['integral'];
						$pin = array(
								'pay_points' => $rempoomt
						);
						$this->db->where('user_id',$uid);
						$this->db->update('users',$pin);
						if(!$this->db->affected_rows()){
							$j++;
						}
						//处理红包
						$this->db->select('bonus_id,bonus');
						$bon = $this->db->get_where('order_info',array('order_id'=>$pay_log[0]['order_id']));
						$bon_id = $bon->result_array($bon);
						if($bon_id[0]['bonus_id'] != 0){
							$this->db->where('bonus_id',$bon_id[0]['bonus_id']);
							$this->db->update('user_bonus',array('used_time'=> time()));
	
							if(!$this->db->affected_rows()){
								$j++;
							}
						}
					}
				}
			}
		}
	
		//发送邮件
		// 					$title = "您购买的商品已经发货了";
		// 					$this->db->select('email');
		// 					$eee = $this->db->get_where('users',array('user_id'=>$uid));
		// 					$erea = $eee->result_array();
		// 					$address = $erea[0]['email'];
		// 					$message = "尊敬的客户您购买的商品".$goods_st."已经与今天".date('Y-m-d H:i:s',time())."购买成功";
		// 					$emai = @$this->sendEail($title, $address, $message);
		// 					if(!$emai){
		// 						$this->_tojson(6, '邮件发送失败');
		// 						exit;
		// 					}
		if($j==0){
			$return = true;
			return $return;
		}else{
			$return = false;
			return $return;
		}
	}
}
