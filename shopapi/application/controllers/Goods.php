<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 
 * Enter description here ...
 * @author sssss
 *
 */
class Goods extends MY_Controller{
	//
	//商品列表--------------------------------------------------------------------
	public function goodslist(){
		$cat_id=$this->_values['cat_id'];
		// $cat_id=3;
		$this->load->model('Goods_model');
		$res=$this->Goods_model->goodslist($cat_id);
		if($res!=0){
			$this->_tojson('1','获取商品列表成功',$res);
		}else{
			$this->_tojson('0','获取商品列表失败');
		}

	}
	//收藏产品----------------------------------------------------------------------
	public function collect(){
		$goods_id=$this->_values['goods_id'];
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		// $goods_id=12;
		// $key='dd7d20cc6a2726e3f2bc1927464c2dea';
		$this->load->model('Goods_model');
		$res=$this->Goods_model->collect($goods_id,$userid);
		if($res!=0){
			$this->_tojson('1','关注成功');
		}else{
			$this->_tojson('0','关注失败');
		}
	}
	//收藏产品列表-------------------------------------------------------------------
	public function collectlist(){
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		// $key='dd7d20cc6a2726e3f2bc1927464c2dea';
		$this->load->model('Goods_model');
		$collectlist=$this->Goods_model->collectlist($userid);
		if($collectlist!=0){
			$this->_tojson('1','获取关注产品成功',$collectlist);
		}else{
			$this->_tojson('0','暂无关注产品',array());
		}
	}
	//取消收藏产品-----------------------------------------------------------------------
	public function qcollect(){
		$goods_id=$this->_values['goods_id'];
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
		}
		// $goods_id=13;
		// $key='dd7d20cc6a2726e3f2bc1927464c2dea';
		$this->load->model('Goods_model');
		$res=$this->Goods_model->qcollect($goods_id,$userid);
		if($res!=0){
			$this->_tojson('1','取消关注成功');
		}else{
			$this->_tojson('0','取消关注失败');
		}
	}
	//打开应用获取购物车商品数量---------------------------------------------------------------
	public function cartnum(){
		$uid=$this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('Goods_model');
		$res=$this->Goods_model->cartnum($uid);
		if($res!=0){
			$this->_tojson('1','获取购物车数量成功',$res);
		}else{
			$this->_tojson('0','获取购物车数量失败');
		}
	}
	//加入购物车-------------------------------------------------------------------------------
	public function addcart(){
	$goods_id=$this->_values['goods_id'];
	// $goods_id=14;
	$num=$this->_values['num'];
	// $num=1;
	$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
	// $userid=7;
	$attrvalue_id=$this->_values['attrvalue_id'];

	// var_dump($attrvalue_id);
	// $attrvalue_id=array(249,252);
	// $goods_id=32;
	// $num=2;
	// // $userid=6;
	$this->load->model('Goods_model');
	if($attrvalue_id==null){
		$res=$this->Goods_model->addcart($goods_id,$num,$userid,array(''));
	}else{
		$attrvalue_id=json_decode($attrvalue_id,TRUE);
		$res=$this->Goods_model->addcart($goods_id,$num,$userid,$attrvalue_id);
	}
		if($res==1){
			$this->_tojson('1','添加购物车成功');
		}elseif($res==2){
			$this->_tojson('0','商品库存不足');
		}else{
			$this->_tojson('0','添加购物车失败');
		}
	}
	//从购物车删除产品--------------------------------------------------------------------------------
	public function delcart(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$rec_id=$this->_values['rec_id'];
		$this->load->model('Goods_model');
		$res=$this->Goods_model->delcart($rec_id,$uid);
		if($res!=0){
			$this->_tojson('1','商品删除成功');
		}else{
			$this->_tojson('0','商品删除失败');
		}
	}
	//购物车修改数量--------------------------------------------------------------------------------------
	public function charnum(){
		$rec_id=$this->_values['rec_id'];
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$num=$this->_values['num'];
		// $rec_id=102;
		// $num=20;
		// $key='f137240557d17c52f43a1b479d0bee10';
		$this->load->model('Goods_model');
		$res=$this->Goods_model->altercart($rec_id,$num,$userid);
		if($res==1){
			$this->_tojson('1','修改数量成功');
		}elseif($res==2){
			$this->_tojson('2','商品库存不足');
		}else{
			$this->_tojson('0','修改数量失败');
		}
	}
	//购物车商品列表------------------------------------------------------------------------------------
	public function cartlist(){
		// $key=$this->_values['key'];
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		// $key='44310aba66937d1720d19f21b3c0a467';
		$this->load->model('Goods_model');
		$res=$this->Goods_model->cartlist($userid);
		if($res!=0){
			if(count($res)>0){
				$this->_tojson('1','获取购物车商品列表成功',$res);
			}else{
				$this->_tojson('1','购物车为空',array());
			}
		}else{
			$this->_tojson('0','获取购物车商品列表失败',array());
		}
	}
	//商品详情列表页--------------------------------------------------------------------------------------
	public function goodsinfo(){
		$goods_id=$this->_values['goods_id'];
		$attrvalue_id=$this->_values['attr_value_id'];
		$sesskey=$this->_values['key'];

		if($attrvalue_id==null){
			$attrvalue_id=array();
		}
		if($sesskey==null){
			$sesskey="";
		}
		// $goods_id=15;
		// $attrvalue_id=array(207);
		$this->load->model('Goods_model');
		$res=$this->Goods_model->goodsinfo($goods_id,$attrvalue_id,$sesskey);
		if($res!=0){
			$this->_tojson('1','获取商品详情成功',$res);
		}else{
			$this->_tojson('0','获取商品详情失败');
		}
	}
	//立即购买--------------------------------------------------------------------------------------------
	public function buy(){
		$goods_id=$this->_values['goods_id'];
		$key=$this->_values['key'];
		// $key='5ae4936fc04fc47a5005d975dccd7248';
		// $goods_id=3;
		$this->load->model('Goods_model');
		$res=$this->Goods_model->buy($goods_id,$key);
		if($res!=0){
			$this->_tojson('1','获取购买商品详情成功',$res);
		}else{
			$this->_tojson('0','获取购买商品详情失败');
		}
	}
	//获取省份城市地区---------------------------------------------------------------------------------------
	// public function region(){
	// 	$region_id=$this->_values['region_id'];
	// 	$key=$this->_values['key'];
	// 	// $key='c01477205a9ad468b5436b1b84ff6b29';
	// 	// $region_id=3;
	// 	if($region_id==null){
	// 		$region_id=1;
	// 	}
	// 	$this->load->model('Goods_model');
	// 	$res=$this->Goods_model->region($region_id,$key);
	// 	if($res!=0){
	// 		$this->_tojson('1','获取地区列表成功',$res);
	// 	}else{
	// 		$this->_tojson('0','获取地区列表失败');
	// 	}
	// }
	//添加/修改收货地址 -----------------------------------------------------------------------------------
	public function address(){
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$province=$this->_values['province'];
		$city=$this->_values['city'];
		$district=$this->_values['district'];
		$username=$this->_values['username'];
		$address_p=$this->_values['address_p'];
		$telnumber=$this->_values['telnumber'];
		$address_id=$this->_values['address_id'];
		// $userid="43";
		// $province=1;
		// $city=2;
		// $district=3;
		// $username='萨达速度';
		// $address_p='打打杀杀烦烦烦';
		// $telnumber="13013001300";
		// $address_id=2;
		$this->load->model('Goods_model');
		$res=$this->Goods_model->address($userid,$province,$city,$district,$username,$address_p,$telnumber,$address_id);
		if($res!=0){
			if($res==1){
				$this->_tojson('1','添加收货地址成功');
			}elseif($res==2){
				$this->_tojson('0','添加收货地址失败');
			}elseif($res==3){
				$this->_tojson('1','修改收货地址成功');
			}else{
				$this->_tojson('0','添加收货地址失败');
			}
		}else{
			$this->_tojson('0','请登录再操作');
		}
	}
	//设为默认地址-------------------------------------------------------------------------------------
	public function addrdefault(){
		$uid = $this->_getId();
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$address_id=$this->_values['address_id'];
		// $address_id=4;
		$this->load->model('Goods_model');
		$res=$this->Goods_model->addrdefault($uid,$address_id);
		if($res!=0){
			$this->_tojson('1','默认地址设置成功');
		}else{
			$this->_tojson('0','默认地址设置失败');
		}
	}
	//获取单个收货地址详情---------------------------------------------------------------------------------
	public function addrdetail(){
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$address_id=$this->_values['address_id'];
		// $key="9a82404ac7d846ce927062c9d40a4bf8";
		// $address_id=3;
		$this->load->model('Goods_model');
		$res=$this->Goods_model->addrdetail($address_id,$userid);
		if($res!=0){
			$this->_tojson('1','获取地址详情成功',$res);
		}else{
			$this->_tojson('0','获取地址详情失败');
		}
	}
	//收货地址列表-------------------------------------------------------------------------------------------
	public function addresslist(){
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		// $key="d028f0dfceea201ef92a4384c1d2f6c3";
		$this->load->model('Goods_model');
		$res=$this->Goods_model->addresslist($userid);
		if($res!=0){
			if(count($res)>0){
				$this->_tojson('1','获取收货地址成功',$res);
			}else{
				$this->_tojson('1','暂无收货地址',$res);
			}
		}else{
			$this->_tojson('0','获取收货地址失败',array());
		}
	}
	//删除收货地址-------------------------------------------------------------------------------------------
	public function deladdress(){
		$userid=$this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$address_id=$this->_values['address_id'];
		// $key='5ae4936fc04fc47a5005d975dccd7248';
		// $address_id=5;
		$this->load->model('Goods_model');
		$res=$this->Goods_model->deladdress($userid,$address_id);
		if($res!=0){
			$this->_tojson('1','删除收货地址成功');
		}else{
			$this->_tojson('0','删除收货地址失败');
		}
	}
	//获取红包---------------------------------------------------------------------------------------------
	// public function bonus(){
	// 	$key=$this->_values['key'];
	// 	// $key='a5498308b91eef3a4cddc6b5c70ef6d0';
	// 	$this->load->model('Goods_model');
	// 	$res=$this->Goods_model->bonus($key);
	// 	if($res!=0){
	// 		$this->_tojson('1','获取红包成功',$res);
	// 	}else{
	// 		$this->_tojson('0','获取红包失败',$res);
	// 	}
	// }
}
