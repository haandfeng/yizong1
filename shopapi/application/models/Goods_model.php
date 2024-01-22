<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Goods_model extends CI_Model{
		//商品列表---------------------------------------------------------------
	public function goodslist($cat_id){

		$sql='SELECT `ecs_goods`.`goods_id` as `goods_id`, `ecs_goods`.`goods_name` as `title`, `shop_price` as `price`, `is_promote` as `is_promotion`,`goods_sn`, `goods_thumb` as `image`FROM `ecs_goods` WHERE `cat_id` = ? AND is_delete=0';
		$goodslist=$this->db->query($sql,$cat_id);

		if($goodslist->num_rows()>0){
			$goodslist=$goodslist->result_array();
			foreach ($goodslist as $key => $value) {
				$goodslist[$key]['image']=$this->config->item('ecs_shop').$value['image'];
				// $comment=$this->db->select('count(*) as comment_num,AVG(comment_rank) as good')->where(array('comment_type'=>0,'id_value'=>$value['goods_id'],'status'=>1,'parent_id'=>0))
				// ->get_compiled_select('comment');
				$sql='SELECT count(*) as comment_num, ROUND(IFNULL(AVG(comment_rank),0),0) as good FROM `ecs_comment` WHERE `comment_type` =0 AND `id_value` = ? AND `status` = 1 AND `parent_id` =0';
				$comment=$this->db->query($sql,$value['goods_id']);
				if($comment->num_rows()>0){
					$comment=$comment->row_array();
					$goodslist[$key]['comment_num']=$comment['comment_num'];
					$goodslist[$key]['good']=$comment['good'];
				}else{
					$goodslist[$key]['comment_num']='0';
					$goodslist[$key]['good']='0';
				}
				if($value['is_promotion']!=0){
					$act=$this->db->select('act_desc')->where(array('goods_id'=>$value['goods_id']))->get('goods_activity');
					if($act->num_rows()>0){
						$act=$act->row_array();
						$goodslist[$key]['pro_medth']=$act['act_desc'];
					}else{
						$goodslist[$key]['pro_medth']="";
					}
				}else{
					$goodslist[$key]['pro_medth']="";
				}
			}
			// var_dump($goodslist);
			return $goodslist;
			exit();
		}else{
			return 0;
			exit();
		}
		
		
	}


	//商品详情列表页------------------------------------------------------------------------
public function goodsinfo($goods_id,$attrvalue_id,$sesskey){
	$goods=$this->db->where(array('goods_id'=>$goods_id,'is_delete'=>0))->get('goods');
	if($goods->num_rows()>0){
		$sql = 'SELECT
						a.goods_name,
						a.goods_id,
						a.goods_sn,
						a.shop_price,
						a.promote_price AS coupon_price,
						a.goods_desc AS content,
						sum(b.goods_number) AS sales,
						a.is_real,
						a.is_shipping
					FROM
						ecs_goods AS a
					LEFT JOIN ecs_order_goods AS b ON a.goods_id = b.goods_id
					WHERE
						a.goods_id = ?';
		$goodsinfo = $this->db->query($sql,$goods_id);
		$goodsinfo=$goodsinfo->row_array();
		$goodsinfo['total_price']=$goodsinfo['shop_price'];
		$gallery=$this->db->select('img_url as album')
		->where('goods_id',$goods_id)
		->get('goods_gallery');
		if($gallery->num_rows()>0){
			$gallery=$gallery->result_array();
			foreach ($gallery as $key => $value) {
				$goodsinfo['album'][]=$this->config->item('ecs_shop').$value['album'];
			}
		}else{
			$goodsinfo['album'][]="";
		}
		//商品的可选属性和基本参数--
		$goodsall=$this->db->select('ecs_attribute.attr_id as attr_name_id,
											goods_attr_id as attr_value_id,
											attr_price,
											attr_name,
											attr_value,
											attr_type')
											->join('ecs_attribute','ecs_attribute.attr_id=ecs_goods_attr.attr_id')
											->where(array('goods_id'=>$goods_id))
											->get('goods_attr');
											if($goodsall->num_rows()>0){
												$goodsall=$goodsall->result_array();
												foreach ($goodsall as $key => $value) {
													if($value['attr_type']==1){
														$goodsattr[]=$value;//可选属型-----

													}elseif ($value['attr_type']==0) {
														$goodsparam[]=$value;//基本参数-----
													}
												}
												//可选属性组装数据------
												$arr=array();
												foreach ($goodsattr as $key => $value) {
													$arr[$key]['attr_id']=empty($value['attr_name_id'])?'':$value['attr_name_id'];
													$arr[$key]['attr_name']=empty($value['attr_name'])?'':$value['attr_name'];
													if($value['attr_price']==""){
														$value['attr_price']="0";
													}
													$arr[$key]['attr_value']=array(array('attr_value_id'=>empty($value['attr_value_id'])?'':$value['attr_value_id'],'attr_value_name'=>empty($value['attr_value'])?'':$value['attr_value'],'attr_value_price'=>empty($value['attr_price'])?0:$value['attr_price'],'default'=>'0'));
												}
												$good=array();
												$good[0]=$arr[0];

												foreach ($arr as $key => $value) {
													if($key>0){
														$good[]=$value;
														foreach ($value as $k => $v) {
															if($k=='attr_name'){
																foreach ($good as $kgood => $vgood) {
																	if($kgood<count($good)-1){
																		if($v==$vgood['attr_name']){
																			$good[$kgood]['attr_value'][]=$value['attr_value'][0];
																			array_pop($good);
																		}
																	}
																}
															}
														}
													}
												}
												foreach ($good as $key => $value) {
													$goodsinfo['attribute'][]=$value;
												}
												//基本参数组装数据--------
												$arr=array();
												foreach ($goodsparam as $key => $value) {
													$arr[$key]['attr_name_id']=empty($value['attr_name_id'])?'':$value['attr_name_id'];
													$arr[$key]['attr_name']=empty($value['attr_name'])?'':$value['attr_name'];
													$arr[$key]['attr_value_id']=empty($value['attr_value_id'])?'':$value['attr_value_id'];
													$arr[$key]['attr_value']=$value['attr_value'];
												}
												foreach ($arr as $key => $value) {
													$goodsinfo['param'][]=$value;
												}
											}else{
												$goodsinfo['attribute']=array();
												$goodsinfo['param']=array();
											}
											//商品评论--------------
											$commentarr=$this->db->select('comment_id,
											email as user_email,
											user_name,
											content as comment_content,
											comment_rank,
											add_time as comment_time
											')
											->where(array('comment_type'=>0,'id_value'=>$goods_id,'status'=>1,'parent_id'=>0))
											->get('comment');
											if($commentarr->num_rows()>0){
												$commentarr=$commentarr->result_array();
												$reply=$this->db->select('count(`parent_id`) as replys,parent_id')
												->where(array('comment_type'=>0,'id_value'=>$goods_id,'status'=>1))
												->where('parent_id>',0)
												->group_by('parent_id')
												->get('comment');
												// ->get_compiled_select('comment');
												// echo $reply;
												if($reply->num_rows()>0){
													$reply=$reply->result_array();
													$arr=array();
													foreach ($commentarr as $key => $value) {
														$l=iconv_substr($value['user_name'],-1,1,'utf-8');
														$f=iconv_substr($value['user_name'],0,1,'utf-8');
														$arr[$key]['user_name']=$f.'***'.$l;
														$arr[$key]['comment_id']=$value['comment_id'];
														$arr[$key]['comment_time']=$value['comment_time'];
														$arr[$key]['comment_rank']=$value['comment_rank'];
														$arr[$key]['comment_content']=$value['comment_content'];
														foreach ($reply as $k => $v) {
															if($value['comment_id']==$v['parent_id']){
																$arr[$key]['comment_replynum']=$v['replys'];
															}
														}
														if(empty($arr[$key]['comment_replynum'])){
															$arr[$key]['comment_replynum']='0';
														}
													}
												}else{
													$arr=array();
													foreach ($commentarr as $key => $value) {
														$l=iconv_substr($value['user_name'],-1,1,'utf-8');
														$f=iconv_substr($value['user_name'],0,1,'utf-8');
														$arr[$key]['user_name']=$f.'***'.$l;
														$arr[$key]['comment_id']=$value['comment_id'];
														$arr[$key]['comment_time']=$value['comment_time'];
														$arr[$key]['comment_rank']=$value['comment_rank'];
														$arr[$key]['comment_content']=$value['comment_content'];
														$arr[$key]['comment_replynum']='0';
													}
												}
													
												foreach ($arr as $key => $value) {
													$goodsinfo['comment'][]=$value;
												}
											}else{
												$goodsinfo['comment']=array();
											}
											//传值attrvalue_id,根据属性不同返回的总价钱不同
											if(empty($attrvalue_id)){
												// var_dump($goodsinfo['attribute']);
												if(!empty($goodsinfo['attribute'])){
													foreach ($goodsinfo['attribute'] as $key => $value) {
														$comparr=array();
														foreach ($value['attr_value'] as $k => $v) {
															$comparr[]=$v['attr_value_price'];
														}
														$scomparr=$comparr;
														sort($scomparr);
														$k=array_search($scomparr[0],$comparr);
														$goodsinfo['attribute'][$key]['attr_value'][$k]['default']='1';
														$goodsinfo['total_price']=(string)($goodsinfo['shop_price']+$goodsinfo['attribute'][$key]['attr_value'][$k]['attr_value_price']);
														if(!strpos($goodsinfo['total_price'],'.')){
															$goodsinfo['total_price'].=".00";
														}
													}
												}
											}else{
												foreach ($attrvalue_id as $key => $value) {
													$res=$this->db->select('attr_price')->where(array('goods_attr_id'=>$value))->get('goods_attr');
													if($res->num_rows()>0){
														$res=$res->row_array();
														$goodsinfo['total_price']=(string)($goodsinfo['shop_price']+$res['attr_price']);
														if(!strpos($goodsinfo['total_price'],'.')){
															$goodsinfo['total_price'].=".00";
														}
													}
												}
													
											}
											//如果用户登录 判断此商品是否被此用户关注-----------------------------------------------
												
											if($sesskey!=""){
												$query=$this->db->select('userid')->where('sesskey',$sesskey)->get('sessions');
												if($query->num_rows()>0){
													$session = $query->row_array();
													$userid=$session['userid'];
													$attention=$this->db->where(array('user_id'=>$userid,'goods_id'=>$goods_id))->get('collect_goods');
													if($attention->num_rows()>0){
														$attention=$attention->row_array();
														if($attention['is_attention']==1){
															$goodsinfo['is_attention']=1;
														}else{
															$goodsinfo['is_attention']=0;
														}
													}else{
														$goodsinfo['is_attention']=0;
													}
												}else{
													$goodsinfo['is_attention']=0;
												}
											}else{
												$goodsinfo['is_attention']=0;
											}
											return $goodsinfo;
	}else{
		return 0;
	}
}
	//关注商品-----------------------------------------------------------------------
	public function collect($goods_id,$userid){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$data=array('user_id'=>$userid,'goods_id'=>$goods_id,'add_time'=>time(),'is_attention'=>1);
			$res=$this->db->where(array('user_id'=>$userid,'goods_id'=>$goods_id))->get('collect_goods');
			if($res->num_rows() > 0){
				return 0;
			}else{
			$this->db->insert('collect_goods',$data);
					if($this->db->affected_rows()){
						return 1;
					}else{
						return 0;
					}
			}
		// }else{
		// 	return 0;
		// }
	}
	//收藏商品列表--------------------------------------------------------------------
	public function collectlist($userid){
		$sql = "select b.goods_id,b.goods_name,b.shop_price as goods_price,b.goods_thumb as goods_img
				from ".$this->db->dbprefix."collect_goods as a left join  ".$this->db->dbprefix."goods as b on a.goods_id=b.goods_id
				where user_id=? and is_attention =1";
		$query = $this->db->query($sql,$userid);
		if($query->num_rows()>0){
		$data=$query->result_array();
		// $data['goods_img']=$this->config->item('ecs_shop').$data['goods_img'];
		foreach ($data as $key => $value) {
			$data[$key]['goods_img']=$this->config->item('ecs_shop').$value['goods_img'];
		}
		return $data;
	   }else{
	   	return 0;
	   }
	}
	//取消收藏---------------------------------------------------------------------------
	public function qcollect($goods_id,$userid){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$res=$this->db->where(array('user_id'=>$userid,'goods_id'=>$goods_id))->get('collect_goods');
			if($res->num_rows()>0){
				$this->db->where(array('user_id'=>$userid,'goods_id'=>$goods_id))->delete('collect_goods');
				if($this->db->affected_rows()){
					return 1;
				}else{
					return 0;
				}
			}else{
				return 0;
			}
		// }else{
		// 	return 0;
		// }
	}
	//打开应用获取购物车的数量-------------------------------------------------------------
	public function cartnum($uid){
		$res=$this->db->select_sum('goods_number','number')->where('user_id',$uid)->get('cart');
		if($res->num_rows()>0){
			$cart=$res->row_array();
			if($cart['number']==null){
				return array('number'=>'0');
			}
			return $cart;
		}
	}
	//加入购物车---------------------------------------------------------------------------
	public function addcart($goods_id,$num,$userid,$attrvalue_id){
		$query=$this->db->select('sesskey')->where('userid',$userid)->get('sessions');
		if($query->num_rows()>0){
			$session = $query->row_array();
			
			$sessionid=$session['sesskey'];
			//判断库存的数量。。。。。。。
			$good=$this->db->select('goods_number')->where('goods_id',$goods_id)->get('goods');
			$good=$good->row_array();
			if($num<=$good['goods_number']){
				$res=$this->db->where(array('goods_id'=>$goods_id,'user_id'=>$userid))->get('cart');
				if($res->num_rows()>0){
					//存在购物车记录的属性
					$res=$res->result_array();
					$add=0;
					foreach ($res as $key => $value) {
						$valueid=explode(',',$value['goods_attr_id']);
						$compare1=array_diff($valueid,$attrvalue_id);
						$compare2=array_diff($attrvalue_id,$valueid);
						$compare1=empty($compare1);
						$compare2=empty($compare2);
						if($compare1&&$compare2){
							$goods_number=$num+$value['goods_number'];
							$this->db->where(array('goods_id'=>$goods_id,'user_id'=>$userid,'goods_attr_id'=>$value['goods_attr_id']))->update('cart',array('goods_number'=>$goods_number));
							$add=1;
						}
					}

					if($add==1){
						return 1;
					}else{//-------
						$query=$this->db->where(array('goods_id'=>$goods_id))->get('goods');
						if($query->num_rows()>0){
							$goodsinfo=$query->row_array();
							$data['user_id']=$userid;
							$data['session_id']=$sessionid;
							$data['goods_id']=$goods_id;
							$data['goods_sn']=$goodsinfo['goods_sn'];
							$data['goods_name']=$goodsinfo['goods_name'];
							$data['market_price']=$goodsinfo['market_price'];
							$data['goods_price']=$goodsinfo['shop_price'];
							$data['goods_number']=$num;
							$data['goods_attr']=$goodsinfo['extension_code'];
							$data['is_real']=$goodsinfo['is_real'];
							$data['extension_code']=$goodsinfo['extension_code'];
							$data['is_shipping']=$goodsinfo['is_shipping'];
							$attr_id_string=implode(',',$attrvalue_id);
							$data['goods_attr_id']=$attr_id_string;
							// var_dump($data['goods_attr_id']);
							$string="";
							foreach ($attrvalue_id as $key => $value) {
								$Rarr=$this->db->select('attr_id,attr_value,attr_price')->where(array('goods_id'=>$goods_id,'goods_attr_id'=>$value))->get('goods_attr');
								if($Rarr->num_rows()>0){
									$Rarr=$Rarr->row_array();

									$arr=$this->db->select('attr_name')->where(array('attr_id'=>$Rarr['attr_id']))->get('attribute');
									if($arr->num_rows()>0){
										$arr=$arr->row_array();
										if($Rarr['attr_price']==""||$Rarr['attr_price']==0){
												$Rarr['attr_price']=0;
												$string.=$arr["attr_name"].':'.$Rarr['attr_value']." "."\n";
												$data['goods_price']+=$Rarr['attr_price'];
											}else{
												$string.=$arr["attr_name"].':'.$Rarr['attr_value'].'['.$Rarr['attr_price'].']'." "."\n";
												$data['goods_price']+=$Rarr['attr_price'];
											}
									}
								}
							}
							$data['goods_attr']=$string;
							// var_dump($data);
							$result=$this->db->insert('cart',$data);
							if($this->db->affected_rows()){
								return 1;
							}else{
								return 0;
							}

						}else{
							return 0;
						}	

					}//----=
					
				}else{
					$query=$this->db->where(array('goods_id'=>$goods_id))->get('goods');
					if($query->num_rows()>0){
						$goodsinfo=$query->row_array();
						$data['user_id']=$userid;
						$data['session_id']=$sessionid;
						$data['goods_id']=$goods_id;
						$data['goods_sn']=$goodsinfo['goods_sn'];
						$data['goods_name']=$goodsinfo['goods_name'];
						$data['market_price']=$goodsinfo['market_price'];
						$data['goods_price']=$goodsinfo['shop_price'];
						$data['goods_number']=$num;
						$data['goods_attr']=$goodsinfo['extension_code'];
						$data['is_real']=$goodsinfo['is_real'];
						$data['extension_code']=$goodsinfo['extension_code'];
						$data['is_shipping']=$goodsinfo['is_shipping'];
						$attr_id_string=implode(',',$attrvalue_id);
						// var_dump($attr_id_string);
						$data['goods_attr_id']=$attr_id_string;
						// var_dump($data['goods_attr_id']);
						$string="";
						foreach ($attrvalue_id as $key => $value) {
							$Rarr=$this->db->select('attr_id,attr_value,attr_price')->where(array('goods_id'=>$goods_id,'goods_attr_id'=>$value))->get('goods_attr');
							if($Rarr->num_rows()>0){
								$Rarr=$Rarr->row_array();

								$arr=$this->db->select('attr_name')->where(array('attr_id'=>$Rarr['attr_id']))->get('attribute');
								if($arr->num_rows()>0){
									$arr=$arr->row_array();
									if($Rarr['attr_price']==""||$Rarr['attr_price']==0){
											$Rarr['attr_price']=0;
											$string.=$arr["attr_name"].':'.$Rarr['attr_value']." "."\n";
											$data['goods_price']+=$Rarr['attr_price'];
										}else{
											$string.=$arr["attr_name"].':'.$Rarr['attr_value'].'['.$Rarr['attr_price'].']'." "."\n";
											$data['goods_price']+=$Rarr['attr_price'];
										}
								}
							}
						}
						// $string=substr($string,0,-2);
						
						$data['goods_attr']=$string;
						// var_dump($data['goods_attr_id']);
						// var_dump($data);
						$result=$this->db->insert('cart',$data);
						if($this->db->affected_rows()){
							return 1;
						}else{
							return 0;
						}

					}else{
						return 0;
					}
				}
			}else{
				return 2;//库存不足
			}
		}else{
			return 0;
		}		
	}
	//购物车删除产品-------------------------------------------------------------------------------
	public function delcart($rec_id,$uid){
		$rec_ida = explode(',',$rec_id);
		for($i=0;$i<count($rec_ida);$i++){
			//$data= array('user_id'=>$uid,'rec_id'=>$rec_ida[$i]);
			$j=0;
			$sql = "DELETE FROM `ecs_cart` WHERE `user_id` = '".$uid."' AND `rec_id` = '".$rec_ida[$i]."'";
			$query = $this->db->query($sql);
			$j++;
		}
		if($j==0){
			return 0;
		}else{
			return 1;
		}
	}
	//购物车修改商品数量-------------------------------------------------------------------------------
	public function altercart($rec_id,$num,$userid){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			// //判断库存的数量。。。。。。。
			// $good=$this->db->select('goods_number')->where('goods_id',$goods_id)->get('goods');
			// $good=$good->row_array();
			// if($num<=$good['goods_number']){
			$res=$this->db->where(array('user_id'=>$userid,'rec_id'=>$rec_id))->get('cart');
			if($res->num_rows()>0){
				$res=$res->row_array();
				//判断库存的数量。。。。。。。
				$good=$this->db->select('goods_number')->where('goods_id',$res['goods_id'])->get('goods');
				$good=$good->row_array();
				if($num<=$good['goods_number']){
					$this->db->where(array('user_id'=>$userid,'rec_id'=>$rec_id))->update('cart',array('goods_number'=>$num));
					if($this->db->affected_rows()){
						return 1;
					}else{
						return 0;
					}
				}else{
					return 2;//库存不足
				}
			}else{
				return 0;
			}
	}
	//购物车商品列表----------------------------------------------------------------------------------------
	public function cartlist($userid){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$data=$this->db->select('rec_id,goods_id,goods_name,goods_price,goods_number as number,goods_attr_id')->where(array('user_id'=>$userid))->get('cart');
			if($data->num_rows()>0){
				$goodsinfo=$data->result_array();
				foreach ($goodsinfo as $key => $value) {
					if($value['goods_attr_id']==""){
						$goodsinfo[$key]['attrvalue_id']=array();
					}else{
						$goodsinfo[$key]['attrvalue_id']=explode(',',$value['goods_attr_id']);
						//拼接商品标题  标题里带有属性----------------------------------------------------
						foreach ($goodsinfo[$key]['attrvalue_id'] as $v) {
						$Rarr=$this->db->select('attr_id,attr_value')->where('goods_attr_id',$v)->get('goods_attr');
						if($Rarr->num_rows()>0){
							$Rarr=$Rarr->row_array();
							$goodsinfo[$key]['goods_name'].=" ".$Rarr['attr_value'];
							}
						}
					}



					$data=$this->db->where('goods_id',$value['goods_id'])->get('goods');
					if($data->num_rows()>0){
						$goods=$data->row_array();
						$goodsinfo[$key]['goods_img']='http://shopapi.99-k.com/ecshop/'.$goods['goods_thumb'];
					}else{
						$goodsinfo[$key]['goods_img']='';
					}	
					unset($goodsinfo[$key]['goods_attr_id']);
					// $goodsinfo['total']+=$value['goods_price']*$value['number'];	
				}
				return $goodsinfo;
			}else{
				$goodsinfo=$data->result_array();
				return $goodsinfo;//购物车为空
			}
	}
	//立刻购买----------------------------------------------------------------------------------------------------
	public function buy($goods_id,$key){
		$query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		if($query->num_rows()>0){
			$session = $query->row_array();
			$userid=$session['userid'];
			$buyinfo=$this->db->select('goods_id,
											goods_name,
											shop_price as goods_price,
											goods_thumb as goods_img'
											)
								->where('goods_id',$goods_id)
								->get('goods');
			if($buyinfo->num_rows()>0){
				$buyinfo=$buyinfo->result_array();
				$address=$this->db->where('user_id',$userid)->get('user_address');
				if($address->num_rows()>0){
				$address=$address->result_array();
				foreach ($address as $key => $value) {
					$country=$this->get_region($value['country']);
					$province=$this->get_region($value['province']);
					$city=$this->get_region($value['city']);
					$district=$this->get_region($value['district']);
					$address[$key]['country']=$country;
					$address[$key]['province']=$province;
					$address[$key]['city']=$city;
					$address[$key]['district']=$district;
				}
				$userinfo=$this->db->select('address_id')->where('user_id',$userid)->get('users');
				$userinfo=$userinfo->row_array();
				foreach ($address as $key => $value) {
					if($value['address_id']==$userinfo['address_id']){
					$buyinfo[0]['address']=$address[$key]['country'].$address[$key]['province'].$address[$key]['city'].$address[$key]['district'].$address[$key]['address'];	
					}
				}
				
			}else{
				$buyinfo[0]['address']="";
			}
			return $buyinfo;
			}
		}else{
			return 0;
		}
	}
	public function get_region($region_id){
		$region=$this->db->select('region_name')->where('region_id',$region_id)->get('region');
		$region=$region->row_array();
		return $region['region_name'];
	}
	//所在地区三次查询 省 市 地区------------------------------------------------------------------------
	// public function region($region_id,$key){
	// 	$query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
	// 	if($query->num_rows()>0){
	// 		$regionlist=$this->db->select('region_id,region_name')->where('parent_id',$region_id)->get('region');
	// 		if($regionlist->num_rows()>0){
	// 			$regionlist=$regionlist->result_array();
	// 			return $regionlist;
	// 		}else{
	// 			return 0;
	// 		}
	// 	}else{
	// 		return 0;
	// 	}

	// }
	//添加/修改收货地址 -----------------------------------------------------------------------------------
	public function address($userid,$province,$city,$district,$username,$address_p,$telnumber,$address_id){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$data['user_id']=$userid;
			$data['consignee']=$username;
			$data['country']=1;
			$data['province']=$province;
			$data['city']=$city;
			$data['district']=$district;
			$data['address']=$address_p;
			$data['mobile']=$telnumber;
			if($address_id==null){
				$this->db->insert('user_address',$data);
				if($this->db->affected_rows()){
					return 1;//添加成功
				}else{
					return 2;//添加失败
				}
			}else{
				$this->db->where(array('address_id'=>$address_id))->update('user_address',$data);
				if($this->db->affected_rows()){
					return 3;//修改成功
				}else{
					return 4;//修改失败
				}
			}
			
		// }else{
		// 	return 0;
		// }

	}
	//获取详细地址----------------------------------------------------------------------------------------
	public function addrdetail($address_id,$userid){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$address=$this->db->select('address_id,
								province,
								city,
								district,
								address,
								consignee as username,
								mobile as telnum')
					->where(array('address_id'=>$address_id,'user_id'=>$userid))
					->get('user_address');
			if($address->num_rows()>0){
				$address=$address->row_array();
				$province=$this->get_region($address['province']);
				$city=$this->get_region($address['city']);
				$district=$this->get_region($address['district']);
				$address['area']=$province.$city.$district;
				// unset($address['province']);
				// unset($address['city']);
				// unset($address['district']);
				return $address;
			}else{
				return 0;
			}
		// }else{
		// 	return 0;
		// }
	}
	//收货地址列表-------------------------------------------------------------------------------------------
	public function addresslist($userid){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$addresslist=$this->db->select('address_id,
											province,
											city,
											district,
											address,
											consignee as username,
											mobile as telnumber')
									->where(array('user_id'=>$userid))
									->get('user_address');
			if($addresslist->num_rows()>0){
				$addresslist=$addresslist->result_array();
				$userinfo=$this->db->select('address_id as defaultid')->where('user_id',$userid)->get('users');
				$userinfo=$userinfo->row_array();
				foreach ($addresslist as $key => $value) {
					$province=$this->get_region($value['province']);
					$city=$this->get_region($value['city']);
					$district=$this->get_region($value['district']);
					$addresslist[$key]['address']=$province.$city.$district.$addresslist[$key]['address'];
					unset($addresslist[$key]['province']);
					unset($addresslist[$key]['city']);
					unset($addresslist[$key]['district']);
					if($addresslist[$key]['address_id']==$userinfo['defaultid']){
						$addresslist[$key]['is_default']="1";
					}else{
						$addresslist[$key]['is_default']="0";
					}
				}
				return $addresslist;

			}else{
				$addresslist=$addresslist->result_array();
				return $addresslist;
			}
		// }else{
		// 	return 0;
		// }
	}
	//删除收货地址-------------------------------------------------------------------------------------
	public function deladdress($userid,$address_id){
		// $query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
		// if($query->num_rows()>0){
		// 	$session = $query->row_array();
		// 	$userid=$session['userid'];
			$address=$this->db->where(array('user_id'=>$userid,'address_id'=>$address_id))->get('user_address');
			if($address->num_rows()>0){
				$this->db->where(array('user_id'=>$userid,'address_id'=>$address_id))->delete('user_address');
				if($this->db->affected_rows()){
					$userinfo=$this->db->where(array('user_id'=>$userid,'address_id'=>$address_id))->get('users');
					if($userinfo->num_rows()>0){
						$this->db->where(array('user_id'=>$userid))->update('users',array('address_id'=>0));		
					}
					return 1;
				}else{
					return 0;
				}
			}else{
				return 0;
			}
		// }else{
		// 	return 0;
		// }
	}
	//设为默认地址------------------------------------------------------------------------------------------
	public function addrdefault($uid,$address_id){
		if($uid!=null){
			$data['address_id']=$address_id;
			$this->db->where('user_id',$uid)->update('users',$data);
			if($this->db->affected_rows()){
				return 1;
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}
	//获取红包-----------------------------------------------------------------------------------------------
	// public function bonus($key){
	// 	$query=$this->db->select('userid')->where('sesskey',$key)->get('sessions');
	// 	if($query->num_rows()>0){
	// 		$session = $query->row_array();
	// 		$userid=$session['userid'];
	// 		$bonus=$this->db->select('bonus_id,
	// 							bonus_type_id,
	// 							bonus_sn,
	// 							used_time,
	// 							use_start_date as start_time,
	// 							use_end_date as end_time')
	// 					->join('bonus_type','bonus_type_id=type_id')
	// 					->where('user_id',$userid)
	// 					->get('user_bonus');
	// 		if($bonus->num_rows()>0){
	// 			$bonus=$bonus->result_array();
	// 			return $bonus;
	// 		}else{
	// 			return 0;
	// 		}
	// 	}else{
	// 		return 0;
	// 	}
	// }
}