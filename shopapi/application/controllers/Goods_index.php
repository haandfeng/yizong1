<?php
class Goods_index extends MY_Controller{
	function index()
	{		
		$this->load->model('First_model');
		$aArr = $this->First_model->ads();
		$this->url = $this->config->item("base_url");
		$this->url = $this->config->base_url();	
		$ecshop = $this->config->item("ecs_shop");
		$this->db->select('goods_name,goods_id,shop_price,goods_thumb');
		$this->db->limit(4);
		$best = $this->db->get_where('goods',array('is_delete'=>0,'is_best'=>1));
		$result = $best->result_array();
		for($i=0;$i<count($result);$i++){
			$result[$i]['goods_thumb'] = $ecshop.$result[$i]['goods_thumb'];
		}
		
		$this->db->select('goods_name,goods_id,shop_price,goods_thumb');
		$this->db->limit(4);
		$newq = $this->db->get_where('goods',array('is_delete'=>0,'is_hot'=>1));
		$new = $newq->result_array();
		for($i=0;$i<count($result);$i++){
			$new[$i]['goods_thumb'] = $ecshop.$new[$i]['goods_thumb'];
		}
		
		$this->db->select('goods_name,goods_id,shop_price,goods_thumb');
		$this->db->limit(4);
		$hotq = $this->db->get_where('goods',array('is_delete'=>0,'is_new'=>1));
		$hot = $hotq->result_array();
		for($i=0;$i<count($result);$i++){
			$hot[$i]['goods_thumb'] = $ecshop.$hot[$i]['goods_thumb'];
		}

		$data = array(
				'title' => 'My Title',
				'heading' => 'My Heading',
				'message' => 'My Message',
				'url' => $this->url,
				'ads'=> $aArr,
				'best'=>$result,
				'hot'=>$hot,
				'new'=>$new
		);
	  	$this->load->view('goods_index',$data);
	}
	function classlist(){
		$type = $this->_values['type'];
		$filt='';
		if($type == 'new'){
			$filt.='and is_new = 1';
		}
		if($type == 'hot'){
			$filt.='and is_hot = 1';
		}
		if($type == 'best'){
			$filt.='and is_best = 1';
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
					a.goods_name LIKE ? and is_delete=0 and is_on_sale=1 '.$filt.'
				GROUP BY
					a.goods_id '.$str;	
		$price_range = $this->db->query($sql,$data);
		$arr = $price_range->result_array();
		if(empty($arr)){
			$this->_tojson('0', '暂没有商品',array());
		}else{
			$this->_tojson('1', '获取商品列表成功',$arr);
		}
	}
}