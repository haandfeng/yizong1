<?php  defined('BASEPATH') OR exit('No direct script access allowed');
class Order_model extends MY_Model{
	public function lorder($uid,$order_id){
		//获取订单信息
	$this->db->select('consignee,money_paid,shipping_fee,order_id,address,add_time,order_status,shipping_status,pay_status,order_sn,country,province,city,district,district,address,consignee,mobile,shipping_name,pay_name,integral_money,bonus,order_amount');
		$query = $this->db->get_where('order_info',array('order_id'=>$order_id,'user_id'=>$uid));
		$uArr = $query->result_array();
		if(empty($uArr)){
			return 0;
		}



		$this->db->select('bonus,integral,integral_money');
		$bonus = $this->db->get_where('order_info',array('order_id'=>$order_id));
		$boArr = $bonus->result_array();
		$sql = 'SELECT
				b.goods_name,
				b.goods_price,
				c.goods_thumb,
				b.goods_number
			FROM
				'.$this->db->dbprefix.'order_info AS a
			LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
			LEFT JOIN '.$this->db->dbprefix.'goods AS c ON b.goods_id = c.goods_id
			WHERE
				a.order_id = ?';
		$result = $this->db->query($sql,$uArr[0]['order_id']);
		$nArr[] = $result->result_array();
		foreach ($nArr as $k=>$v){
			foreach ($v as $c =>$b){
				$cArr[] = $b;
			}
		}
		for($j=0;$j<count($cArr);$j++){
			$total += $cArr[$j]['goods_price']*$cArr[$j]['goods_number'];
		}
		//获取地址
		$sql = 'select region_name from ecs_region where region_id = '.$uArr['0']['country'].'
					union
					select region_name from ecs_region where region_id = '.$uArr['0']['province'].'
					union
					select region_name from ecs_region where region_id = '.$uArr['0']['city'].'
					union
					select region_name from ecs_region where region_id = '.$uArr['0']['district'];
		$query = $this->db->query($sql);
		$result = $query->result_array();

		foreach ($result as $k){
			foreach ($k as $v){
				$str.=' '.$v;
			}
		}
		$str = $result['0']['region_name'].$result['1']['region_name'].'省'.$result['2']['region_name'].'市'.$result['3']['region_name'].'(区/县)'.$uArr[0]['address'];

		//使用红包

		$order_status=$uArr[0]['order_status'].'-'.$uArr[0]['pay_status'].'-'.$uArr[0]['shipping_status'];
		$mArr[] = array('bonus'=>$boArr[0]['bonus'],'integral_money'=>$boArr[0]['integral_money'],'integral'=>$boArr[0]['integral'],'order_status'=>$this->order_status($order_status),'consignee'=>$uArr[0]['consignee'],'money_paid'=>$uArr[0]['money_paid'],'shipping_fee'=>$uArr[0]['shipping_fee'],'order_amount'=>$uArr[0]['order_amount'],'shipping_name'=>$uArr[0]['shipping_name'],'add_time'=>$uArr[0]['add_time'],'order_sn'=>$uArr[0]['order_sn'],'goods'=>$cArr,'total'=>$total,'mobile'=>$uArr[0]['mobile'],'address'=>$str,'pay_name'=>$uArr[0]['pay_name']);

		if(empty($nArr)){
			return 0;
		}else
			return $mArr;
	}
	/**
	 * 确认订单
	 */
	public function confirm($uid,$goods,$address_id){
		$goodsa = explode(',',$goods);
		for($i=0;$i<count($goodsa);$i++){
			$nArr = explode('-',$goodsa[$i]);
			$goods_id[] = $nArr[0];
			$number[] = $nArr[1];
		}

		for($i=0;$i<count($goods_id);$i++){
			$this->db->select('integral');
			$gnum = $this->db->get_where('goods',array('goods_id'=>$goods_id[$i]));
			$numArr[] = $gnum->result_array();
		}
		foreach ($numArr as $k =>$v){
			foreach ($v as $c=>$b){
				$str[] = $b;
			}
		}
		for($i=0;$i<count($str);$i++){
			$integrala += $str[$i]['integral'];
		}
		//获取商品   多个个商品时
		for($i=0,$j=0;$i<count($goods_id),$j<count($number);$i++,$j++){
			$sql = 'SELECT
			goods_name AS title,
			goods_thumb AS goods_img,
			shop_price AS price,
			is_shipping AS shipping
			FROM
			'.$this->db->dbprefix.'goods
			WHERE
			goods_id = ?';
			$query = $this->db->query($sql,$goods_id[$i]);
			$result = $query->result_array();
			$result[0]['shop_num']=$number[$j];
			$arr[] = $result;
		}
		foreach ($arr as $value){
			foreach ($value as $key){
				$gArr[] = $key;
			}
		}
		for($i=0;$i<count($gArr);$i++){
			$gArr[$i]['goods_img'] = $this->config->item('ecs_shop').$gArr[$i]['goods_img'];
		}
		//总价格
		for($i=0;$i<count($gArr);$i++){
			$price[] = $gArr[$i]['price']*$gArr[$i]['shop_num'];
		}
		//可以使用的红包
		if($bouns_id!=''){
			$this->db->select('bonus_id,bonus_type_id,bonus_sn');
			$bouns = $this->db->get_where('user_bonus',array('user_id'=>$uid));
			$bArr = $bouns->result_array();
			for($i=0;$i<count($bArr);$i++){
				$this->db->select('type_name,type_money');
				$ss = $this->db->get_where('bonus_type',array('type_id'=>$bArr[$i]['bonus_type_id']));
				$cArr = $ss->result_array();
				$cArr[0]['bonus_id'] = $bArr[$i]['bonus_id'];
				$cArr[0]['bonus_sn'] = $bArr[$i]['bonus_sn'];
				$dArr[] = $cArr;
			}
			foreach ($dArr as $key){
				foreach ($key as $v)
				$wArr[] = $v;
			}
		}
		/**
		 * 获取红包面值
		 * 总价格减去红包的面值等于需付款的价格
		 */

		$bouns_id = $this->_values['bonus_id'];
		if($bouns_id!=''){
			$this->db->select('bonus_type_id');
			$bounsa = $this->db->get_where('user_bonus',array('user_id'=>$bonus_id));
			$bArra = $bouns->result_array();

			$this->db->select('type_money');
			$ss = $this->db->get_where('bonus_type',array('type_id'=>$bArra[0]['bonus_type_id']));
			$cArr = $ss->result_array();
		}
		//可以使用的积分
		$this->db->select('pay_points');
		$queryi = $this->db->get_where('users',array('user_id'=>$uid));
		$resultui = $queryi->result_array();
		$total = (array_sum($price)-$cArr[0]['type_money'])>0?array_sum($price)-$cArr[0]['type_money']:0;
		$add = array('address'=>'','mobile'=>$mobile,'user_name'=>$user_name,'goods'=>$gArr,'total'=>$total,'bouns'=>$wArr,'integral'=>$this->integral($resultui[0]['pay_points']));

		//获取用户收货信息信息
		$this->db->select('address_id');
		$querya = $this->db->get_where('user_address',array('user_id'=>$uid));
		$aArr = $querya->result_array();
		if(empty($aArr)){
			return $add;
		}
		//获取用户收货地址
		if(empty($address_id)){
			$this->db->select('address_id');
			$query = $this->db->get_where('users',array('user_id'=>$uid));
			$uArr = $query->result_array();
			if(empty($uArr[0]['address_id'])){
				$this->db->select('address_id');
				$querya = $this->db->get_where('user_address',array('user_id'=>$uid));
				$aArr = $querya->result_array();
				$eArr = array(
						'address_id'=>$aArr[0]['address_id']
				);
				$this->db->where('user_id',$uid);
				$this->db->update('users',$eArr);
			}else{
				$this->db->select('country,province,city,district,address,consignee,mobile,address_id');
				$query = $this->db->get_where('user_address',array('address_id'=>$uArr[0]['address_id']));
				$uArr = $query->result_array();
				$sql = 'select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['country'].'
					union
					select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['province'].'
					union
					select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['city'].'
					union
					select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['district'];
				$query = $this->db->query($sql);
				$result = $query->result_array();
				foreach ($result as $k){
					foreach ($k as $v){
						$str.=' '.$v;
					}
				}
				$str = $result['0']['region_name'].$result['1']['region_name'].'省'.$result['2']['region_name'].'市'.$result['3']['region_name'].'(区/县)'.$uArr[0]['address'];
				$user_name = $uArr[0]['consignee'];
				$mobile = $uArr[0]['mobile'];
				$address_idaa = $uArr[0]['address_id'];
			}
		}else{
			$this->db->select('country,province,city,district,address,consignee,mobile');
			$query = $this->db->get_where('user_address',array('address_id'=>$address_id));
			$uArr = $query->result_array();
			$sql = 'select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['country'].'
					union
					select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['province'].'
					union
					select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['city'].'
					union
					select region_name from '.$this->db->dbprefix.'region where region_id = '.$uArr[0]['district'];
			$query = $this->db->query($sql);
			$result = $query->result_array();
			foreach ($result as $k){
				foreach ($k as $v){
					$str.=' '.$v;
				}
			}
			$str = $result[0]['region_name'].$result[1]['region_name'].'省'.$result[2]['region_name'].'市'.$result[3]['region_name'].'(区/县)'.$uArr[0]['address'];
			$user_name = $uArr[0]['consignee'];
			$mobile = $uArr[0]['mobile'];
		}
		$add = array('address'=>$str,'integrala'=>$integrala,'address_id'=>$address_idaa,'mobile'=>$mobile,'user_name'=>$user_name,'goods'=>$gArr,'total'=>$total,'bouns'=>$wArr,'integral'=>$this->integral($resultui[0]['pay_points']));
		if(empty($uArr) && empty($gArr)){
			return 0;
		}else
			return $add;

	}
	public function delivery(){
		$sql = 'SELECT DISTINCT(b.shipping_id),a.shipping_name,a.shipping_desc,b.configure from '.$this->db->dbprefix;
		$sql .= 'shipping as a INNER JOIN '.$this->db->dbprefix.'shipping_area as b on a.shipping_id=b.shipping_id';
		$query = $this->db->query($sql);
		$uArr = $query->result_array();
		for($i=0;$i<count($uArr);$i++){
			$uArr[$i]['configure'] = unserialize($uArr[$i]['configure']);
			for($j=0;$j<count($uArr[$i]['configure']);$i++){
				$sho = $uArr[$i]['configure'];
				$uArr[$i]['shipp_fee']=$sho[1]['value'];
			}
		}
		if(empty($uArr)){
			return 0;
		}else
			return $uArr;
	}
	//取消订单
	public function qorder($uid,$order_id){
		$data = array(
				'user_id' =>$uid,
				'order_id' => $order_id
		);
		$sql = 'update '.$this->db->dbprefix.'order_info set order_status = 2,shipping_status =0,pay_status=0 where user_id =? and order_id=?';
		$query = $this->db->query($sql,$data);
		if($this->db->affected_rows()){
			return 1;
		}else{
			return 0;
		}
	}
	//确认收获
	public function received($uid,$order_id){
		$data = array(
				'user_id' =>$uid,
				'order_id' => $order_id
		);
		$sql = 'update '.$this->db->dbprefix.'order_info set order_status = 5,shipping_status = 2,pay_status=2 where user_id =? and order_id=?';
		$query = $this->db->query($sql,$data);
		if($this->db->affected_rows()){
			return 1;
		}else{
			return 0;
		}
	}
	//申请退货
	public function back_goods($uid,$order_id){
		$data = array(
				'user_id' =>$uid,
				'order_id' => $order_id
		);
		$sql = 'update '.$this->db->dbprefix.'order_info set order_status = 4,shipping_status=0,pay_status=0 where user_id =? and order_id=?';
		$query = $this->db->query($sql,$data);
		if($this->db->affected_rows()){
			return 1;
		}else{
			return 0;
		}
	}
	//待付费
	public function obligation($uid){
		$this->db->select('order_id,order_status,shipping_status,pay_status,order_sn,goods_amount');
		// $query = $this->db->get_where('order_info',array('pay_status'=>0,'shipping_status'=>0,'order_status'=>0,'user_id'=>$uid));
		$query = $this->db->group_start()
							->where('order_status',0)
						  	->or_where('order_status',1)
						  ->group_end()
						  ->where(array('pay_status'=>0,'shipping_status'=>0,'user_id'=>$uid))
						  ->get('order_info');
		$uArr = $query->result_array();
		for($i=0;$i<count($uArr);$i++){
			$sql = 'SELECT
					b.goods_name,
					b.goods_price,
					c.goods_thumb,
					b.goods_number as goods_number
					FROM
					'.$this->db->dbprefix.'order_info AS a
					LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
					LEFT JOIN '.$this->db->dbprefix.'goods AS c ON b.goods_id = c.goods_id
					WHERE
					a.order_id = ? and a.user_id='.$uid.' and a.pay_status=0';
			$result = $this->db->query($sql,$uArr[$i]['order_id']);
			$nArr = $result->result_array();
			/* for($j=0;$j<count($nArr);$j++){
			$total += $nArr[$j]['goods_price'];
			}  */
			$mArr[] = array('order_sn'=>$uArr[$i]['order_sn'],'order_id'=>$uArr[$i]['order_id'],'goods'=>$nArr,'total'=>$uArr[$i]['goods_amount'],'order_status'=>$uArr[$i]['order_status'],'shipping_status'=>$uArr[$i]['shipping_status'],'pay_status'=>$uArr[$i]['pay_status']);
		}
		if(!empty($nArr)){
			return $mArr;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	//待发货
	public function send_goods($uid){
		// $qqq = 'select order_id,order_status,shipping_status,pay_status,goods_amount from '.$this->db->dbprefix.'order_info where user_id=? and order_status=1 and pay_status=2 and (shipping_status=3 or shipping_status=0 or shipping_status=5)';
		// $query = $this->db->query($qqq,$uid);
		$query=$this->db->select('order_id,order_status,shipping_status,pay_status,goods_amount')
					->group_start()
						->where(array('order_status'=>1,'pay_status'=>2,'shipping_status'=>0))
					->group_end()
					->where('user_id',$uid)
			        ->get('order_info');
		$uArr = $query->result_array();
// 		echo $this->db->last_query()."<br>";

		for($i=0;$i<count($uArr);$i++){
			$sql = 'SELECT
					b.goods_name,
					b.goods_price,
					c.goods_thumb,
					b.goods_number as goods_number
					FROM
					'.$this->db->dbprefix.'order_info AS a
					LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
					LEFT JOIN '.$this->db->dbprefix.'goods AS c ON b.goods_id = c.goods_id
					WHERE
					a.order_id = ? and a.user_id='.$uid.' and a.shipping_status=0';
			$result = $this->db->query($sql,$uArr[$i]['order_id']);
			$nArr = $result->result_array();
// 			echo $this->db->last_query()."<br>";
			for($j=0;$j<count($nArr);$j++){
			$total += $nArr[$j]['goods_price'];
			}
			$mArr[] = array('order_sn'=>$uArr[$i]['order_sn'],'order_id'=>$uArr[$i]['order_id'],'goods'=>$nArr,'total'=>$uArr[$i]['goods_amount'],'order_status'=>$uArr[$i]['order_status'],'shipping_status'=>$uArr[$i]['shipping_status'],'pay_status'=>$uArr[$i]['pay_status']);
		}
		if(!empty($nArr)){
			return $mArr;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	//待收货
	public function reciv_goods($uid){
		$this->db->select('order_id,order_status,shipping_status,pay_status,goods_amount');
		$query = $this->db->get_where('order_info',array('shipping_status'=>1,'pay_status'=>2,'order_status'=>5,'user_id'=>$uid));
		$uArr = $query->result_array();
		for($i=0;$i<count($uArr);$i++){
			$sql = 'SELECT
					b.goods_name,
					b.goods_price,
					c.goods_thumb,
					b.goods_number as goods_number
					FROM
					'.$this->db->dbprefix.'order_info AS a
					LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
					LEFT JOIN '.$this->db->dbprefix.'goods AS c ON b.goods_id = c.goods_id
					WHERE
					a.order_id = ? and a.user_id='.$uid.' and a.shipping_status=1';
			$result = $this->db->query($sql,$uArr[$i]['order_id']);
			$nArr = $result->result_array();
			for($j=0;$j<count($nArr);$j++){
			$total += $nArr[$j]['goods_price'];
			}
			$mArr[] = array('order_sn'=>$uArr[$i]['order_sn'],'order_id'=>$uArr[$i]['order_id'],'goods'=>$nArr,'total'=>$uArr[$i]['goods_amount'],'order_status'=>$uArr[$i]['order_status'],'shipping_status'=>$uArr[$i]['shipping_status'],'pay_status'=>$uArr[$i]['pay_status']);
		}
		if(!empty($nArr)){
			return $mArr;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	//已完成
	public function order_sucess($uid){
		$this->db->select('order_id,order_status,shipping_status,pay_status,order_sn,goods_amount');
		$query = $this->db->get_where('order_info',array('pay_status'=>2,'shipping_status'=>2,'order_status'=>5,'user_id'=>$uid));
		$uArr = $query->result_array();
		for($i=0;$i<count($uArr);$i++){
			$sql = 'SELECT
					b.goods_name,
					b.goods_price,
					c.goods_thumb,
					b.goods_number as goods_number
					FROM
					'.$this->db->dbprefix.'order_info AS a
					LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
					LEFT JOIN '.$this->db->dbprefix.'goods AS c ON b.goods_id = c.goods_id
					WHERE
					a.order_id = ? and a.user_id='.$uid.' and a.shipping_status=2';
			$result = $this->db->query($sql,$uArr[$i]['order_id']);
			$nArr = $result->result_array();
			for($j=0;$j<count($nArr);$j++){
			$total += $nArr[$j]['goods_price'];
			}
			$mArr[] = array('order_sn'=>$uArr[$i]['order_sn'],'order_id'=>$uArr[$i]['order_id'],'goods'=>$nArr,'total'=>$uArr[$i]['goods_amount'],'order_status'=>$uArr[$i]['order_status'],'shipping_status'=>$uArr[$i]['shipping_status'],'pay_status'=>$uArr[$i]['pay_status']);
		}
		if(!empty($nArr)){
			return $mArr;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	//申请退货
	public function reqest(){

	}
	protected function integral($num) {
		$this->db->select('value');
		$query = $this->db->get_where('shop_config',array('id'=>211));
		$result = $query->result_array();
		$int = $result[0]['value'];
		return $num*($int/100);
	}
}
