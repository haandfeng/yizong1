<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 
 * Enter description here ...
 * @author Administrator
 *
 */
class First extends MY_Controller{
	/**
	 * app首页应用
	 * @param sting type search 
	 * @param sting type fitrate
	 * @param sting type ads
	 * @param sting type best
	 * @param sting type hot
	 * @param sting type new
	 */
	public function index(){		
		$keyword = $this->_values['keyword'];
		echo $keyword;
        file_put_contents('testa.txt',$keyword);
		$type = $this->_values['type'];
		$classify_id = $this->_values['classify_id'];
		$goods_type= $this->_values['goods_type'];
		if($type == 'search'){
			$order = $this->_values['order'];
			$filtrate = $this->_values['filtrate'];	
			$class = $this->_values['c'];
			$cat_id = $this->_values['cat_id'];
			$page = $this->_values['page'];
			$this->load->model('First_model');
			$nArr = $this->First_model->search($keyword,$order,$filtrate,$class,$cat_id,$page);
// 			for($i=0;$i<count($nArr);$i++){
// 				$nArr[$i]['image'] = $this->config->item('ecs_shop').$nArr[$i]['image'];
// 			}
			if($nArr==0){
				$this->_tojson('0','获取失败',(object)array());				
			}else				
				$this->_tojson('1','获取成功',$nArr);
			
		}
		if($type=='fitrate'){
			//返回价格
			$this->load->model('First_model');
			$nArr = $this->First_model->fitrate($keyword,$goods_type,$classify_id);
			if($nArr==0){
			$this->_tojson('0','获取失败');				
			}else				
			$this->_tojson('1','获取成功',$nArr);	
		}
		if($type == 'ads'){
			$this->load->model('First_model');
			$aArr = $this->First_model->ads();
			if(empty($aArr)){
				$this->_tojson('0','获取失败');
			}else{
				$this->_tojson('1','获取成功',$aArr);
			}
			
		}
		if($type == 'best'){
			$this->load->model('First_model');
			$bArr = $this->First_model->goods($type);
			if(empty($bArr)){
				$this->_tojson('0','获取失败');
			}else{
				$this->_tojson('1','获取成功',$bArr);
			}
		}
		if($type == 'hot'){
			$this->load->model('First_model');
			$bArr = $this->First_model->goods($type);
			if(empty($bArr)){
				$this->_tojson('0','获取失败');
			}else{
				$this->_tojson('1','获取成功',$bArr);
			}
		}
		if($type == 'new'){
			$this->load->model('First_model');
			$bArr = $this->First_model->goods($type);
			if(empty($bArr)){
				$this->_tojson('0','获取失败');
			}else{
				$this->_tojson('1','获取成功',$bArr);
			}
		}
	}

    /**
     * app首页应用
     * @param sting type bast [默认]//推荐商品广告
     * @param sting type hot        //热销商品广告
     * @param sting type new        //新品推荐广告
     */
	public function appindexads(){
        $type = $this->_values['type'] ? $this->_values['type'] : 'hot';
        switch ($type){
            case 'hot':
                $ad_name = '';
                break;

            case 'new':
                $ad_name = '';
                break;

            default:
                $ad_name = '';
                break;
        }

        $this->load->model('First_model');
        $list_ad = $this->First_model->getads($ad_name, 5);

        if(empty($list_ad)){
            $this->_tojson('0','获取失败');
        }else{
            $this->_tojson('1','获取成功',$list_ad);
        }
    }
	
	public function appindex(){	
		$data = array();	
		
		$data['logo'] = $this->config->item('ecs_shop').'mobile/themesmobile/yshop100com_mobile/yzimages/images/logo_03.png';
						
		$this->load->model('First_model');
		$adlist = $this->First_model->getads('wap首页幻灯广告', 5);		
		$data['adlist'] = $adlist;
		
		$menulist = $this->First_model->getmenu();
		foreach($menulist as $key=>$val){
			$menulist[$key]['menu_img'] = $this->config->item('ecs_shop').'mobile/'.$val['menu_img'];			
			$menulist[$key]['fullmenu_url'] = $this->config->item('ecs_shop').'mobile/'.$val['menu_url'];
		} 
		$data['menulist'] = $menulist;	
		
		$articles = $this->First_model->articles(6);
		$data['articles'] = $articles;	
		
		$ad_left = $this->First_model->getads('首页导航下左边广告图', 1);	
		$ad_right1 = $this->First_model->getads('首页导航下右边上部分广告图', 1);	
		$ad_right2 = $this->First_model->getads('首页导航下右边下部分广告图', 1);	
		$data['ad_left'] = $ad_left;
		$data['ad_right1'] = $ad_right1;
		$data['ad_right2'] = $ad_right2;

        $data['hot_ads']    = $this->First_model->getads('热销商品广告位', 5);
        $data['new_ads']    = $this->First_model->getads('新品上市广告位', 5);
        $data['bast_ads']   = $this->First_model->getads('精品推荐广告位', 5);

		$best_goodslist = $this->First_model->goodsByPagesize('best');		
		$data['best_goods'] = $best_goodslist;	
		
		$hot_goodslist = $this->First_model->goodsByPagesize('hot');		
		$data['hot_goods'] = $hot_goodslist;			
		
		$new_goodslist = $this->First_model->goodsByPagesize('new');	
		$data['new_goods'] = $new_goodslist;	
		
		$this->_tojson('1','获取成功',$data);
	}
	
	/**
	 * 获取产品分类
	 * @param classify_id return
	 * @param classify_name return
	 * @param parent_id return parent id
	 * @param goods_img return image
	 */
	public function classify(){
		$type = $this->_values['type'];
		$prent_id = $this->_values['prent_id'];
		$this->load->model('First_model');
		$uArr = $this->First_model->classify($type,$prent_id);		
		if(empty($uArr)){
			$this->_tojson('0', '获取分类信息失败');
		}else{
			$this->_tojson('1', '获取成功',$uArr);
		}
	}
	
	/*获取分类树*/
	public function getCatTree(){
		$prent_id = $this->_values['prent_id'];		
		$this->load->model('First_model');
		$uArr = $this->First_model->getCatTree($prent_id);			
		if(empty($uArr)){
			$this->_tojson('0', '获取分类信息失败');
		}else{
			$this->_tojson('1', '获取成功',$uArr);
		}
	}
	
	/**
	 * 热门关键词
	 */
	public function keywords(){
		$this->db->select('value');
		$query = $this->db->get_where('shop_config',array('code'=>'search_keywords'));
		$result = $query->result_array();
		$arr = explode(',',$result[0]['value']);
		if(empty($result)){
			$this->_tojson(0, '获取失败',array());
		}else{
			$this->_tojson(1, '获取成功',$arr);			
		}
	}
	//版本更新
	public function version(){
		
	}
	//意见反馈
	public function message(){
		
	}
}
