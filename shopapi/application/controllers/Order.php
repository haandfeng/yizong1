<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Order extends MY_Controller{
	/**
	 * 创建订单
	 * @param $address_id
	 * @param $goods_id
	 * @param $expressage
	 * @param $redpacket
	 * @param $message
	 * 
	 */
	public function create(){
		$user = $this->_getId();
		if($user==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$goods = $this->_values['goods_id'];
		$uArr = explode(',',$goods);
		for($i=0;$i<count($uArr);$i++){
			$nArr = explode('-',$uArr[$i]);
			$goods_id[] = $nArr[0];
			$number[] = $nArr[1];
		}
		$address_id = $this->_values['address_id'];
		$amount = $this->_values['amount'];
		$shipping_fee = $this->_values['shipping_fee'];
		$expressage = $this->_values['expressage_id'];
		$redpacket = $this->_values['redpacket'];
		$goods_attr_id = $this->_values['goods_attr_id'];
		$money_paid = $this->_values['money_paid'];
		$message = $this->_values['message'];
		$bonus_id = empty($this->_values['bonus_id'])?'':$this->_values['bonus_id'];
		$integral = $this->_values['integral'];
		/*
		 * 检测商品库存
		 * 多个商品
		 *
		 * 一表商品
		*/
		for($i=0;$i<count($goods_id);$i++){
			$this->db->select('goods_number,integral');
			$gnum = $this->db->get_where('goods',array('goods_id'=>$goods_id[$i]));
			$numArr[] = $gnum->result_array();
		}
		foreach ($numArr as $k =>$v){
			foreach ($v as $c=>$b){
				$str[] = $b;
			}
		}
// 		for($i=0;$i<count($str);$i++){
// 			$integral += $str[$i]['integral'];
// 		}
		//配货
		$this->db->select('shipping_name');
		$querys = $this->db->get_where('shipping',array('shipping_id'=>$expressage));
		$querys = $querys->result_array();
		//红包
		if($bonus_id!=''){
			$bonus_money = 'select a.bonus_id,b.type_money from '.$this->db->dbprefix.'user_bonus as a left join '.$this->db->dbprefix.'bonus_type as b on a.bonus_type_id = b.type_id where a.bonus_id=?';
			$bonus_query = $this->db->query($bonus_money,$bonus_id);
			$bonus_result = $bonus_query->result_array();
		}
		//地址
		$sql = 'select * from '.$this->db->dbprefix.'users where user_id = ?';
		$query = $this->db->query($sql,$user);
		$result = $query->result_array();	
		
		$email = $result[0]['email'];
		$sql = 'select best_time,sign_building,consignee,email,country,province,city,district,address,tel,mobile,zipcode from '.$this->db->dbprefix.'user_address where address_id = ?';
		$query = $this->db->query($sql,$address_id);
		$address = $query->result_array();
		/* 获取一个 产品*/
		/* for($i=0;$i<count($goods_id);$i++){
			$sql = 'select a.goods_name,a.goods_sn,a.shop_price,a.is_real,a.goods_thumb from '.$this->db->dbprefix.'goods as a left join '.$this->db->dbprefix.'goods_attr as b on a.goods_id = b.goods_id  where a.goods_id = ? and a.is_delete=0';
			$query = $this->db->query($sql,$goods_id[$i]);
			$goods = $query->result_array();
		} */
		//判断属性
		
		$this->db->select('goods_id,goods_attr_id,attr_value,attr_id');
		$att = $this->db->get_where('goods_attr',array('goods_attr_id'=>$goods_attr_id));
		$attr = $att->result_array();
		$this->db->select('attr_name');
		$attr_n = $this->db->get_where('attribute',array('attr_id'=>$attr[0]['attr_id']));
		$attr_a = $attr_n->result_array();
		$attr_name = $attr_a[0]['attr_name'].':'.$attr[0]['attr_value'];
		
		
		

		//获取商品属性
		for($i=0;$i<count($goods_id);$i++){
			$this->db->select('goods_attr,goods_attr_id');
			$attr_id = $this->db->get_where('cart',array('goods_id'=>$goods_id[$i]));
			$att_arr = $attr_id->result_array();
		}
		//检查购物车是否有该商品
/* 		for($i=0;$i<count($goods_id);$i++){
			$this->db->select('goods_name');
			$queryca = $this->db->get_where('cart',array('goods_id'=>$goods_id[$i]));
			$cart = $queryca->result_array();
			if(empty($cart)){
				$this->_tojson('8', '购物车没有该商品');
			}
		} */
		//生成订单
		$data = array(
				'order_sn' => date('Ymdhis'.rand(1000,9999),time()),//订单号
				'user_id' => $user,
				'consignee' => $address[0]['consignee'],
				'country' => $address[0]['country'],
				'province' => $address[0]['province'],
				'city' => $address[0]['city'],
				'district' => $address[0]['district'],
				'address' => $address[0]['address'],
				'zipcode' => $address[0]['zipcode'],
				'tel' => $address[0]['tel'],
				'mobile' => $address[0]['mobile'],
				'email' => $address[0]['email'],
				'best_time' => $address[0]['best_time'],
				'sign_building' => $address[0]['sign_building'],
				'postscript' => $message, // 订单留言
				'shipping_id' => $expressage, //配送方式id
				'shipping_name' => $querys[0]['shipping_name'],
				'pay_id' => 4,
				'pay_name' => '支付宝',
				'goods_amount' => $amount,  //商品总全额
				'shipping_fee' => $shipping_fee, //配送费用
				'money_paid' => $money_paid, //实际付费
				'integral' => $this->integral($integral), //使用积分
				'integral_money' => $integral, //积分兑换金额
				'bonus' => empty($bonus_result[0]['type_money'])?"":$bonus_result[0]['type_money'],
				'bonus_id'=>$bonus_id,
				'add_time' => time()
		);
		$result = $this->db->insert('order_info',$data);
		$cccc = $this->db->insert_id();
		if(!$this->db->affected_rows()){
			$this->_tojson('2', '插入订单数据失败',array());
			exit();
		}
		//添加商品到订单
		/*
		* 只有一个商品
		*/
		
		for($i=0;$i<count($goods_id);$i++){
			$sql = 'select a.goods_name,a.goods_sn,a.shop_price,a.is_real,a.goods_thumb from '.$this->db->dbprefix.'goods as a left join '.$this->db->dbprefix.'goods_attr as b on a.goods_id = b.goods_id  where a.goods_id = ? and a.is_delete=0';
			$query = $this->db->query($sql,$goods_id[$i]);
			$goods = $query->result_array();
			$_datae = array(
					'order_id' => $cccc,
					'goods_id' => $goods_id[$i],
					'goods_name' => $goods[0]['goods_name'],
					'goods_sn' => $goods[0]['goods_sn'],
					'goods_number' => $number[$i],
					'goods_price' => $goods[0]['shop_price'],
					'goods_attr' => empty($att_arr['goods_attr'])?"\r\n":$att_arr['goods_attr'],
					'is_real' => $goods[0]['is_real'],
					'goods_attr_id' => empty($att_arr['goods_attr_id'])?"0":$att_arr['goods_attr_id'],
			);
			$result = $this->db->insert('order_goods',$_datae);
			if(!$this->db->affected_rows()){
				$this->_tojson('3', '更新order_goods失败',array());
				exit();
			}
		}
		//清空购物车
		$gouwu = $this->_values['type'];
		if($gouwu==0){
			for($i=0;$i<count($goods_id);$i++){
				$this->db->delete('cart',array('goods_id'=>$goods_id[$i],'user_id'=>$user));
				if(!$this->db->affected_rows()){
					$this->_tojson('7', '清空购物车失败',array());
				}
			}
		}
		$arr = array(
				'order_id' => $cccc,
				'order_amount' => empty($order_amount)?0:$order_amount,
				'order_type' => 0,
				'is_paid' => 0
		);
		$this->db->insert('pay_log',$arr);
		$pay_log = $this->db->insert_id();
		$cArr = array('money_paid' => $money_paid,'order_sn'=>$pay_log);
		$this->_tojson('1', '创建订单成功',$cArr);
	}
	protected function integral($num) {
		$this->db->select('value');
		$query = $this->db->get_where('shop_config',array('id'=>211));
		$result = $query->result_array();
		$int = $result[0]['value'];
		return $num/($int/100);
	}
	//查看订单
	public function lorder(){

		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$order_id = $this->_values['order_id'];
		$this->load->model('Order_model');
		$uArr = $this->Order_model->lorder($uid,$order_id);
		if($uArr){
			$this->_tojson('1', '获取订单详情成功',$uArr);
		}else{
		
			$this->_tojson('0', '获取订单详情失败',array());
		}
	}
	/**
	 * 确认订单
	 */
	public function confirm(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',(object)array());
			exit;
		}
		$address_id = $this->_values['address_id'];
 		$goods = $this->_values['goods_id'];
 		$this->load->model('Order_model');
		$uArr = $this->Order_model->confirm($uid,$goods,$address_id);
		//检查库存
		$goodsa = explode(',',$goods);
		for($i=0;$i<count($goodsa);$i++){
			$nArr = explode('-',$goodsa[$i]);
			$goods_id[] = $nArr[0];
			$number[] = $nArr[1];
		}

		for($i=0;$i<count($goods_id);$i++){
			$select = 'select goods_number,warn_number,goods_name from '.$this->db->dbprefix.'goods where goods_id = ?';
			$query = $this->db->query($select,$goods_id[$i]);
			$result = $query->result_array();
			if($result[0]['goods_number']<=$result[0]['warn_number']){					
				$this->_tojson('3',$result[0]['goods_name'].'商品数量不足',(object)array());
				exit;
			}
		}
		
		
		$this->db->select('address_id');
		$querya = $this->db->get_where('user_address',array('user_id'=>$uid));
		$aArr = $querya->result_array();
		if(empty($aArr)){			
			$this->_tojson('4','请输选择收获地址',$uArr);
		}
		$array = (object)array();
		switch ($uArr){
			case 0:
				$this->_tojson(0,'获取失败',$array);
				break;
			case 2:
				$this->_tojson('2','收货地址为空',$array);
				break;
			case 4:
				$this->_tojson('4','请输选择收获地址',$uArr);
				break;
			default:
				$this->_tojson('1','获取订单成功',$uArr);
				
		}
		
	}
	//获取配送信息
	public function delivery(){
		$this->load->model('Order_model');
		$uArr = $this->Order_model->delivery();
		if($uArr){
			$this->_tojson('1', '获取成功',$uArr);
		}else		
			$this->_tojson('0', '获取失败',array());
		
	}
	//红包
	public function bouns(){
		$user_id = $this->_getId();
		$money = $this->_values['money'];
		if($user_id==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		};
		$time = time();
		$sql ="SELECT
					a.bonus_id,
					a.bonus_type_id,
					a.user_id,
					b.type_name,
                    b.type_money,
                    b.use_start_date,
                    b.use_end_date,
					b.min_goods_amount
				FROM
					ecs_user_bonus AS a
				LEFT JOIN ecs_bonus_type AS b ON a.bonus_type_id = b.type_id
				WHERE
					a.user_id = '".$user_id."'
				AND '".$time."' < use_end_date
				AND '".$time."' > use_start_date
                AND min_goods_amount<'".$money."'
				AND a.order_id=0";
 		$bquery = $this->db->query($sql);
 		$uArr = $bquery->result_array();
 		if(empty($uArr)){
			$this->_tojson('0', '您暂时没有可用红包',array());
 		}
 		else{
 			$this->_tojson('1', '获取红包成功',$uArr);
 		}
		
	}
	//取消订单
	public function qorder(){
		$order_id = $this->_values['order_id'];
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('Order_model');
		$uArr = $this->Order_model->qorder($uid,$order_id);
		if($uArr){
			$this->_tojson('1', '取消订单成功');
		}else{

			$this->_tojson('0', '取消订单失败');
		}
		
	}
	//支付订单
	public function pay(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
		}
		$type = $this->_values['type'];
		$oid = $this->_values['order_id'];  // 订单号
		if($type == 1){
			//判断余额是否足够支付该订单
			$i = 0;
			$this->db->select('user_money');
			$mo = $this->db->get_where('users',array('user_id'=>$uid));
			$mresult = $mo->result_array();
			$user_money = $mresult[0]['user_money'];
			//获取订单应付金额
			$order_sn = explode(',', $oid);
			for($i=0;$i<count($order_sn);$i++){
				$this->db->select('money_paid');
				$pa = $this->db->get_where('order_info',array('order_id'=>$order_sn[$i]));
				$paid = $pa->result_array();
				$ordermoney = $paid[0]['money_paid'];
				$order_money += $ordermoney;
				$od = $order_money;
			}
			if($user_money<=$order_money){
				$this->_tojson('2', '余额不足以支付该订单');
				exit();
			}else{
				//支付后还剩的金钱
				$j = 0;
				$remoney = $user_money - $order_money;
				$arr = array(
						'user_money' => $remoney
				);
				$this->db->where('user_id',$uid);
				$aa = $this->db->update('users',$arr);
				if(!$this->db->affected_rows()){
					$j++;
				}
				$bool = $this->order_paid($oid);
				if(!$bool){
					$j++;
				}
				if($j==0){
					$this->_tojson(1, '购买成功');
				}else{

					$this->_tojson(0, '购买失败');
				}
			}
		}elseif($type == 4){
			$sql = 'select a.money_paid,b.goods_name from '.$this->db->dbprefix.'order_info as a left join '.$this->db->dbprefix.'order_goods as b on a.order_id = b.order_id where a.order_id = ?';
			$reulst = $this->db->query($sql,$oid);
			$uArr = $reulst->result_array();
			for($i=0;$i<count($uArr);$i++){
				$str .= $uArr[$i]['goods_name'];
			}
			
			// 		$alipay['return_url']       =   "\"m.alipay.com\"";
			
			$para['partner'] = "\"".$this->config->item('partner')."\"";
			$para['seller_id'] = "\"".$this->config->item('seller_id')."\"";
			$para['out_trade_no'] ="\"".$oid."\"";
			$para['subject'] = "\"商品支付\"";
			$para['body'] = "\"商品支付\"";
			$para['total_fee'] ="\"".$uArr[0]['money_paid']."\"";
			$para['total_fee'] ="\"0.01\"";
			$para['notify_url'] = "\"".$this->config->item('base_url')."index.php/Payment/recharback\"";
			$para['service']="\"mobile.securitypay.pay\"";
			$para['payment_type'] = "\"1\"";
			$para['_input_charset'] = "\"utf-8\"";
			$para['it_b_pay'] = "\"30m\"";
			$para['key'] = "\"".$this->config->item('key')."\"";
			
			//字符串编码
			$res_url = $this->createLinkstring($para);
			$path = APPPATH.'/libraries/Alipay/key/rsa_private_key.pem';
			$res_url1 = $this->rsaSign($res_url,$path);
// 			echo $res_url1;
			$partner = urlencode($res_url1);
			$partners = $res_url.'&sign='."\"$partner\"".'&sign_type='."\"RSA\"";
			$code = 1;
			$msg = "操作成功";
			$this->_tojson($code,$msg,$partners);
		}
		
	}
	
	protected function createLinkstring($para){
		$arg = '';
		while(list($key,$val) = each($para)){
			$arg.=$key."=".$val."&";
		}
		$arg = substr($arg,0,count($arg)-2);
		if(get_magic_quotes_gpc()){
			$arg = stripslashes($arg);
		}
		return $arg;
	}	
    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * return 签名结果
     */
    function rsaSign($data, $private_key_path){
        $priKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstringUrlencode($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".urlencode($val)."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }
	//待付款
	public function obligation(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('Order_model');
		$uArr = $this->Order_model->obligation($uid);
		if($uArr){
			$this->_tojson('1', '获取待付款列表成功',$uArr);
		}else{
	
			$this->_tojson('0', '暂时没有待付款产品',array());
		}		
	}
	//待发货
	public function send_goods(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		//$uid = 6;
	//	$order_id = $this->_values['order_id'];
		$this->load->model('Order_model');
		$uArr = $this->Order_model->send_goods($uid);
		if($uArr){
			$this->_tojson('1', '获取待发货列表成功',$uArr);
		}else{
	
			$this->_tojson('0', '暂时没有待发货产品',array());
		}		
	}
	//待收获
	public function reciv_goods(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
		}
		//$uid = 6;
	//	$order_id = $this->_values['order_id'];
		$this->load->model('Order_model');
		$uArr = $this->Order_model->reciv_goods($uid);
		if($uArr){
			$this->_tojson('1', '获取待收货列表成功',$uArr);
		}else{
	
			$this->_tojson('0', '暂时没有待收货产品',array());
		}		
	}
	//已完成
	public function order_sucess(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		//$uid = 6;
	//	$order_id = $this->_values['order_id'];
		$this->load->model('Order_model');
		$uArr = $this->Order_model->order_sucess($uid);
		if($uArr){
			$this->_tojson('1', '获取完成订单列表成功',$uArr);
		}else{
	
			$this->_tojson('0', '没有完成订单',array());
		}		
	}
	//确认收获
	public function received(){
		$order_id = $this->_values['order_id'];
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('Order_model');
		$uArr = $this->Order_model->received($uid,$order_id);
		if($uArr){
			$this->_tojson('1', '确认收货成功');
		}else{
	
			$this->_tojson('0', '确认收货失败');
		}
	}
	//退货
	public function back_goods(){
		$order_id = $this->_values['order_id'];
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('Order_model');
		$uArr = $this->Order_model->back_goods($uid,$order_id);
		if($uArr){
			$this->_tojson('1', '申请退货成功');
		}else{
		
			$this->_tojson('0', '申请退货失败');
		}
	}
	//放回购物车
	public function recart(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$session = $this->_values['key'];
		$order_id = $this->_values['order_id'];
		$sql = 'select * from '.$this->db->dbprefix.'order_info as a left join '.$this->db->dbprefix.'order_goods as b on a.order_id=b.order_id where a.order_id=?';
		$sql = 'SELECT
					a.user_id,
					b.goods_id,
					b.goods_name,
					b.goods_sn,
					b.goods_number,
					b.goods_price,
					b.goods_attr,
					b.is_real,
					b.goods_attr_id
				FROM
					'.$this->db->dbprefix.'order_info AS a
				LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
				WHERE
					a.order_id = ?';
		$query = $this->db->query($sql,$order_id);
		$result = $query->result_array();
		$j = 0;
		for($i=0;$i<count($result);$i++){
			$result[$i]['session_id'] = $session;
			$this->db->insert('cart',$result[$i]);
			$j++;
		}
		if(!$j){
			$this->_tojson(0, '放回购物车失败');
		}else{
			$this->_tojson(1, '放回购物车成功');
		}		
	}
	//获取国家名
	public function country(){
		$this->db->select('region_id,region_name');
		$query = $this->db->get_where('region',array('parent_id'=>0));
		//$query = $this->db->get('region');
		$this->_tojson('1', '获取成功',$query->result_array());
	}
	public function province(){
		$parent_id = $this->_values['region_id'];
		//$parent_id = 1;
		$this->db->select('region_id,region_name');
		$query = $this->db->get_where('region',array('parent_id'=>$parent_id));
		$uArr = $query->result_array();
		if(empty($uArr)){
			$this->_tojson('0', '获取失败',array());
		}else
		$this->_tojson('1', '获取成功',$query->result_array());
	}
	public function city(){		
		$parent_id = $this->_values['region_id'];
		//$parent_id = 4;
		$this->db->select('region_id,region_name');
		$query = $this->db->get_where('region',array('parent_id'=>$parent_id));
		$uArr = $query->result_array();
		if(empty($uArr)){
			$this->_tojson('0', '获取失败',array());
		}else
		$this->_tojson('1', '获取成功',$query->result_array());
	}
	public function area(){			
		$parent_id = $this->_values['region_id'];
		//$parent_id = 57;
		$this->db->select('region_id,region_name');
		$query = $this->db->get_where('region',array('parent_id'=>$parent_id));	
		$uArr = $query->result_array();
		if(empty($uArr)){
			$this->_tojson('0', '获取失败',array());
		}else
		$this->_tojson('1', '获取成功',$query->result_array());
	}
}
