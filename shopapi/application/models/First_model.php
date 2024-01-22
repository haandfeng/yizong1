<?php defined('BASEPATH') OR exit('No direct script access allowed');
class First_model extends CI_Model{
	public function fitrate($keyword,$goods_type,$classify_id){
        
        file_put_contents('testb.txt',$keyword);
		//获取最高价格和最低价格
		$str = '';
		if($goods_type == 'best'){
			$str = ' and is_best=1';
		}
		if($goods_type == 'hot'){
			$str = ' and is_hot=1';
		}
		if($goods_type == 'new'){
			$str = ' and is_new=1';
		}
		if(!empty($keyword)){
			$str = " and goods_name like '%".$keyword."%'";
		}
		if(!empty($classify_id)){
			$str = " and cat_id='$classify_id'";
		}
		$sql = "select max(`shop_price`) as shop_price from ".$this->db->dbprefix."goods where is_delete=0 and is_on_sale=1 $str";
		$price_range = $this->db->query($sql);
		$max = $price_range->result_array();
		$sql = "select min(`shop_price`) as shop_price from ".$this->db->dbprefix."goods where is_delete=0 and is_on_sale=1 $str";
		$price_range = $this->db->query($sql);
		$min = $price_range->result_array();
		//获取分类
// 		$keyword = '手机a';
// 		$data[] = '%'.$keyword.'%';
// 		$sql = 'select * from '.$this->db->dbprefix.'category where cat_name like ?';
		$sql = 'select * from '.$this->db->dbprefix.'category';
// 		$classify = $this->db->query($sql,$data);
		$classify = $this->db->query($sql);
		//是否免运费
		$mArr = array(
			'price_range'=>$max[0]['shop_price'].'-'.$min[0]['shop_price'],
			'classify' => $classify->result_array(),
			'is_fare' => 1,
			'is_promotion' =>1
		);
		
		return $mArr;
	}
	public function search($keyword,$order,$filtrate,$class,$cat_id,$page){
		//计算总共有多少页内容
		$ccc = '';
		if($keyword != ''){
			$ccc.= "goods_name like '%".$keyword."%'";
		}
		if($class == 'new'){
			$ccc.= 'is_new=1';
		}
		if($class == 'hot'){
			$ccc.= 'is_hot=1';
		}
		if($class == 'best'){
			$ccc.= 'is_best=1';
		}
		if($cat_id != ''){
			$ccc.= 'cat_id='.$cat_id;
		}
		$page_size = 10;	//每页显示的条数
		$ssql = "select count(*) as count from ".$this->db->dbprefix."goods where $ccc";
		$querss = $this->db->query($ssql);
		$result = $querss->result_array();
		$count = $result[0]['count'];
		$page_count = intval($count/$page_size);	//总页面数
		$page_num = ($page-1)*10;
			
		$str = '';
		if($order == 1){
			//销量排序
			$str.='order by c.goods_number desc';
		}elseif($order == 2){
			//按照价格由低到高的排序
			$str.='order by a.shop_price asc';
		}elseif($order ==3){
			//按照价格由高到低
			$str.='order by a.shop_price desc';
			
		}else{
			//按照人气排序
			$str.='order by a.click_count desc';			
		}
		//筛选条件
	//	$filtrate = '{"is_promotion":"0","price_range":"0","is_fare":"1","classify":"1"}';
		$filtr = json_decode($filtrate,true);
		$is_promotion = $filtr['is_promotion'];
		$price_range = $filtr['price_range'];
		$is_fare = $filtr["is_fare"];
		$classify = $filtr['classify'];
		$price = explode('-',$price_range);
		$time = time();
		if(!empty($filtrate)){
			$filt ='';
			if(!empty($is_promotion)&&!empty($price_range)&&!empty($is_fare)&&!empty($classify)){
				if($is_promotion==1 && $is_fare ==1){
					$filt .="and is_shipping =1 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=1";
				}
				if($is_promotion==2 && $is_fare ==1){
					$filt .="and is_shipping =1 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=0";
				}
				if($is_promotion==1 && $is_fare ==2){
					$filt .="and is_shipping =0 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=1";
				}
				if($is_promotion==2 && $is_fare ==2){
					$filt .="and is_shipping =0 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=0";
				}
			}
			if(empty($is_promotion)&&!empty($price_range)&&!empty($is_fare)&&!empty($classify)){
				if($is_fare==1){
					$filt .="and is_shipping =1 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."'";						
				}
				if($is_fare==2){
					$filt .="and is_shipping =0 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."'";						
				}
			}
			if(!empty($is_promotion)&&empty($price_range)&&!empty($is_fare)&&!empty($classify)){
				if($is_promotion==1 && $is_fare ==1){
					$filt .="and is_shipping =1 and cat_id='".$classify."' and promote_price=1";
				}
				if($is_promotion==2 && $is_fare ==1){
					$filt .="and is_shipping =1 and cat_id='".$classify."' and promote_price=0";
				}
				if($is_promotion==1 && $is_fare ==2){
					$filt .="and is_shipping =0 and cat_id='".$classify."' and promote_price=0";
				}
				if($is_promotion==2 && $is_fare ==2){
					$filt .="and is_shipping =0 and cat_id='".$classify."' and promote_price=0";
				}
			}
			if(!empty($is_promotion)&&!empty($price_range)&&empty($is_fare)&&!empty($classify)){
				if($is_promotion==1){
					$filt .="and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=1";
				}
				if($is_promotion ==2){
					$filt .="and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=0";
				}
			}
			if(!empty($is_promotion)&&!empty($price_range)&&!empty($is_fare)&&empty($classify)){				
				if($is_promotion==1 && $is_fare ==1){
					$filt .="and is_shipping =1 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=1";
				}
				if($is_promotion==2 && $is_fare ==1){
					$filt .="and is_shipping =1 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=0";
				}
				if($is_promotion==1 && $is_fare ==2){
					$filt .="and is_shipping =0 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=1";
				}
				if($is_promotion==2 && $is_fare ==2){
					$filt .="and is_shipping =2 and shop_price between '".$price[0]."' and '".$price[1]."'and cat_id='".$classify."' and promote_price=0";
				}
			}
			if(empty($is_promotion)&&empty($price_range)&&!empty($is_fare)&&!empty($classify)){
				if($is_fare==1){
					$filt .="and is_shipping =1 and cat_id='".$classify."'";					
				}
				if($is_fare==2){
					$filt .="and is_shipping =0 and cat_id='".$classify."'";						
				}
			}
			if(empty($is_promotion)&&!empty($price_range)&&empty($is_fare)&&!empty($classify)){
				$filt .="and shop_price between '".$price[0]."' and '".$price[1]."' and cat_id='".$classify."'";
			}
			if(empty($is_promotion)&&!empty($price_range)&&!empty($is_fare)&&empty($classify)){
				if($is_fare==1){
				$filt .="and shop_price between '".$price[0]."' and '".$price[1]."' and is_shipping =1";
				}
				if($is_fare==2){
				$filt .="and shop_price between '".$price[0]."' and '".$price[1]."' and is_shipping =0";
				}
			}
			if(!empty($is_promotion)&&empty($price_range)&&empty($is_fare)&&!empty($classify)){
				if($is_promotion==1){
					$filt .="and promote_price=1 and cat_id='".$classify."'";
				}
				if($is_promotion==2){
					$filt .="and promote_price=0 and cat_id='".$classify."'";
				}
			}
			if(!empty($is_promotion)&&empty($price_range)&&!empty($is_fare)&&empty($classify)){
				if($is_promotion==1 && $is_fare ==1){
					$filt .="and promote_price=1 and is_shipping =1";
				}
				if($is_promotion==2 && $is_fare ==1){
					$filt .="and promote_price=0 and is_shipping =1";		
				}
				if($is_promotion==1 && $is_fare ==2){
					$filt .="and promote_price=1 and is_shipping =0";
				}
				if($is_promotion==2 && $is_fare ==2){
					$filt .="and promote_price=0 and is_shipping =0";	
				}
			}
			if(!empty($is_promotion)&&!empty($price_range)&&empty($is_fare)&&empty($classify)){
				if($is_promotion==1){
					$filt .="and promote_price=1  and shop_price between '".$price[0]."' and '".$price[1]."'";
				}
				if($is_promotion==2){
					$filt .="and promote_price=0  and shop_price between '".$price[0]."' and '".$price[1]."'";
				}
			}
			if(empty($is_promotion)&&empty($price_range)&&empty($is_fare)&&!empty($classify)){
				$filt .="and cat_id='".$classify."'";
			}
			if(empty($is_promotion)&&empty($price_range)&&!empty($is_fare)&&empty($classify)){
				if($is_promotion==1){
					$filt .="and is_shipping =1";
				}
				if($is_promotion==2){
					$filt .="and is_shipping =0";		
				}
			}
			if(!empty($is_promotion)&&empty($price_range)&&empty($is_fare)&&empty($classify)){
				if($is_promotion==1){
					$filt .="and promote_price=1";
				}
				if($is_promotion==2){
					$filt .="and promote_price=0";
				}
			}
			if(empty($is_promotion)&&!empty($price_range)&&empty($is_fare)&&empty($classify)){
				$filt .="and shop_price between '".$price[0]."' and '".$price[1]."'";
			}
		}
		if(!empty($class)){
			
			if($class == 'new'){
				$cl.='and is_new = 1';
			}
			if($class == 'hot'){
				$cl.='and is_hot = 1';
			}
			if($class == 'best'){
				$cl.='and is_best = 1';
			}
		}
		if(!empty($cat_id)){
			$datae = "and a.cat_id='".$cat_id."'";
		//	echo $datae;
		}
		if(!empty($keyword)){
			$datae = "and a.goods_name LIKE '%$keyword%'";
		}
			
		$sql = 'SELECT
				a.goods_thumb AS image,
				a.goods_id,
				a.goods_name AS title,
				a.shop_price AS price,
				a.is_promote AS is_promotion,
				a.promote_price AS pro_price,
				a.integral AS interge,
				b.comment_rank AS good,
				sum(c.goods_number) AS sell_num
				FROM
					ecs_goods AS a
				LEFT JOIN '.$this->db->dbprefix.'comment AS b ON a.goods_id = b.id_value
				LEFT JOIN '.$this->db->dbprefix.'order_goods AS c ON c.goods_id = a.goods_id
				WHERE
					is_delete=0 '.$datae.' and is_on_sale=1 '.$filt.$cl.'
				GROUP BY
					a.goods_id '.$str.' LIMIT '.$page_num.',10';	
		$price_range = $this->db->query($sql);
		$arr = $price_range->result_array();
//		echo '<hr>'.$this->db->last_query().'<hr>';
		for($i=0;$i<count($arr);$i++){
			$arr[$i]['good'] = empty($arr[$i]['good']) ? 0 :$arr[$i]['good'];
			$arr[$i]['goods_number'] = empty($arr[$i]['goods_number']) ? 0 :$arr[$i]['goods_number'];
			$arr[$i]['sell_num'] = empty($arr[$i]['sell_num']) ? 0 :$arr[$i]['sell_num'];
			$aArr[$i]['image'] = $this->config->item('ecs_shop').$nArr[$i]['image'];
		}
		$nArr = array('goods'=>$arr,'count'=>$count,'page_size'=>$page_size,'page'=>$page,'page_count'=>($page_count+1),'goods_type'=>$class);
		if(empty($arr)){
			return 0;
		}else return $nArr;
	}
	public function ads(){
		$sql = 'select * from '.$this->db->dbprefix.'ad_custom limit 3';
		$result = $this->db->query($sql);
		return $result->result_array();
	}
	
	/*广告获取*/
	public function getads($position, $num){
		$sql = "select ap.ad_width,ap.ad_height,ad.ad_id,ad.ad_name,ad.ad_code,ad.ad_link,ad.ad_id from ".$this->db->dbprefix."ecsmart_ad_position as ap left join ".$this->db->dbprefix."ecsmart_ad as ad on ad.position_id = ap.position_id where ap.position_name='".$position.( "' and UNIX_TIMESTAMP()>ad.start_time and UNIX_TIMESTAMP()<ad.end_time and ad.enabled=1 limit ".$num );
		$result = $this->db->query($sql);
		$list = $result->result_array();
		foreach($list as $key=>$val){
			$list[$key]['ad_code'] = $this->config->item('ecs_shop').'/mobile/data/afficheimg/'.$val['ad_code'];
		}
		return $list;
	}
	
	/*获取首页菜单*/
	public function getmenu(){
		$sql = "select * from ".$this->db->dbprefix."ecsmart_menu order by sort";
		$result = $this->db->query($sql);
		$list = $result->result_array();		
		return $list;
	}
	
	/*获得最新的文章列表*/
	public function articles($nums){
		$sql = 'SELECT a.article_id, a.title, ac.cat_name, a.add_time, a.file_url, a.open_type, ac.cat_id, ac.cat_name ' .
            ' FROM ' . $this->db->dbprefix . 'article AS a, ' .
                $this->db->dbprefix . 'article_cat AS ac' .
            ' WHERE a.is_open = 1 AND a.cat_id = ac.cat_id AND ac.cat_type = 1' .
            ' ORDER BY a.article_type DESC, a.add_time DESC LIMIT ' . $nums;
		$result = $this->db->query($sql);
		$list = $result->result_array();	
		foreach($list as $key=>$val){
			$list[$key]['url'] = $this->config->item('ecs_shop').'mobile/article.php?id='.$val['article_id'];
		}	
		return $list;
	}
	
	public function goods($tt){
		$str = '';
		if($tt == 'best'){
			$str.='is_best=1';
		}
		if($tt == 'hot'){
			$str.='is_hot=1';
		}
		if($tt == 'new'){
			$str.='is_new=1';
		}
		$sql = 'SELECT
				a.goods_thumb AS goods_img,
				a.goods_id,
				a.goods_name AS goods_name,
				a.shop_price,
				sum(c.goods_number) AS sell_num
				FROM
					'.$this->db->dbprefix.'goods AS a
				LEFT JOIN '.$this->db->dbprefix.'order_goods AS c ON a.goods_id =c.goods_id 
				where '.$str.' and is_delete=0 and is_on_sale=1
				GROUP BY c.goods_id';
		$result = $this->db->query($sql);
		return $result->result_array();
	}
	
	/*商品获取*/
	public function goodsByPagesize($tt,$pagesize=10){
		$config_sql = "select * from ".$this->db->dbprefix."shop_config where id=1064";
		$result = $this->db->query($config_sql);
		$config_info = $result->result_array();		
		$exchange_rate = floatval($config_info[0]['value']);		
			
		$str = '';
		if($tt == 'best'){
			$str.='is_best=1';
		}
		if($tt == 'hot'){
			$str.='is_hot=1';
		}
		if($tt == 'new'){
			$str.='is_new=1';
		}
		$sql = 'SELECT
				a.goods_thumb AS goods_img,
				a.goods_id,
				a.goods_name AS goods_name,
				a.shop_price,				
				cat.cat_name
				FROM '.$this->db->dbprefix.'goods AS a
				LEFT JOIN '.$this->db->dbprefix.'category AS cat ON cat.cat_id = a.cat_id 
				where '.$str.' and is_delete=0 and is_on_sale=1	limit 0,'.$pagesize;				
		$result = $this->db->query($sql);
		$list = $result->result_array();
		foreach($list as $key=>$val){
			$list[$key]['goods_img'] = $this->config->item('ecs_shop').$val['goods_img'];
			$list[$key]['hk_price'] = sprintf("%.2f", $exchange_rate*$val['shop_price']);
		}
		return $list;
	}
	
	public function classify($type,$prent_id){
		$str='';
		if($type == 0){
			$str.='parent_id = 0';
		}else{
			$str.='parent_id = ?';
		}
		
		$sql = 'select cat_id as classify_id,cat_name as classify_name from '.$this->db->dbprefix.'category where is_show=1 and '.$str;
		$result = $this->db->query($sql,$prent_id);
		$uArr = $result->result_array();
		if($type !=0){
			$this->db->select('cat_id');
			$ee = $this->db->get_where('category',array('parent_id'=>$prent_id));
			$ccArr = $ee->result_array();
			for($i=0;$i<count($ccArr);$i++){
				$sq = 'select goods_name,goods_thumb from '.$this->db->dbprefix.'goods where cat_id=? and (is_new=1 or is_hot=1 or is_best=1)';
				$query = $this->db->query($sq,$ccArr[$i]['cat_id']);
				$ccc[] = $query->result_array();
			}
		}
		foreach ($ccc as $k=>$v){
			foreach ($v as $c => $b){
				$cccc[] = $b;
			}
		}
		$nArr = array('classify'=>$uArr,'product'=>$cccc,'prent_id'=>$prent_id);
		return $nArr;
	}
	
	/*获取分类树*/
	public function getCatTree($cat_id = 0){
		$res = array();		
		if ($cat_id > 0){			
			/* 获取当前分类及其子分类 */
			$sql = 'SELECT cat_id,cat_name ,parent_id,is_show ' .
					'FROM ' . $this->db->dbprefix .
					"category WHERE parent_id = '$cat_id' AND is_show = 1 and is_virtual=0 ORDER BY sort_order ASC, cat_id ASC";
			$arr_q = $this->db->query($sql);
			$res = $arr_q->result_array();				
			foreach ($res as $key => $row)
			{
				if ($row['is_show'])
				{
					$res[$key]['url']  = $this->config->item('ecs_shop').'mobile/category.php?id='.$row['cat_id'];
					if (isset($row['cat_id']) != NULL)
					{
						$res[$key]['child'] = $this->get_child_tree($row['cat_id']);						
					}
				}							
			}							
		}
		return $res;		
	} 
	
	protected function get_child_tree($tree_id = 0){	
		$res = array();	
		$child_sql = 'SELECT cat_id, cat_name, parent_id, is_show ' .
					'FROM ' . $this->db->dbprefix .
					"category WHERE parent_id = '$tree_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
		$arr_q = $this->db->query($child_sql);
		$res = $arr_q->result_array();
		foreach ($res as $key=> $row)
		{
			if ($row['is_show'])
				$res[$key]['url']  = $this->config->item('ecs_shop').'category.php?id='.$row['cat_id']; 			   
			   	if (isset($row['cat_id']) != NULL)
			   	{
					$res[$key]['child'] = $this->get_child_tree($row['cat_id']);
			   	}
		}
		return $res;
	}
	
	protected function fare($num){
		switch ($num){
			case 0:
				return "免运费";
				break;
			case 1:
				return "不免运费";
				break;
		}
	}
}
