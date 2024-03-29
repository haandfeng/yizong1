<?php

/**
 * ECSHOP 购物流程
 
 * $Author: derek $
 * $Id: flow.php 17218 2011-01-24 04:10:41Z derek $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_order.php');


/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');

//购物车打开修改商品属性页面
if(isset($_REQUEST['is_ajax']) && $_REQUEST['step']=='show_choose_attr'){
    include('includes/cls_json.php');
    $json   = new JSON;
    $rec_id = empty($_REQUEST['rec_id'])?0:  intval($_REQUEST['rec_id']);
    $smarty->assign('goods_cart',get_cart_goods_info($rec_id));
    $goods_id = $GLOBALS['db']->getOne("select goods_id from ". $GLOBALS['ecs']->table('cart') ." where rec_id = $rec_id");
    $smarty->assign('goods',get_goods_info($goods_id));
    $properties = get_goods_properties($goods_id);
    $smarty->assign('properties',          $properties['pro']);                              // 商品属性
    $smarty->assign('specification',       $properties['spe']);                              // 商品规格
    $output = $GLOBALS['smarty']->fetch('library/choose_attr.lbi');
    die($json->encode($output));
}

//购物车修改商品属性
if(isset($_REQUEST['is_ajax']) && $_REQUEST['step']=='edit_cart_goods'){
    include('includes/cls_json.php');
    $json   = new JSON;
    $rec_id = empty($_REQUEST['rec_id'])?0:  intval($_REQUEST['rec_id']);
    $attr_id   = isset($_REQUEST['attr']) ? $_REQUEST['attr']: "";
    $attr_id_array    = empty($_REQUEST['attr']) ?array(): explode(',', $_REQUEST['attr']);
    $goods_id = empty($_REQUEST['goods_id'])?0:intval($_REQUEST['goods_id']);
    $shop_price  = get_final_price($goods_id, 1, true, $attr_id);
    $cart_number = empty($_REQUEST['number'])?0: intval($_REQUEST['number']);
    $product_number = get_goods_attr_number($goods_id,$attr_id_array);
    $result = array();
    if($product_number == 0){
        $result['err'] = $_LANG['shortage'];
    }
    elseif($product_number>0 && $product_number<$cart_number){
        $result['err'] = sprintf($_LANG['shortage_max'], $product_number);
    }else{
    $goods_attr = "";
    $attr_price = "";
    foreach($attr_id_array as $key=>$value){
        $sql = "select ga.attr_id, ga.attr_value, ga.attr_price, at.attr_name from ". $GLOBALS['ecs']->table('goods_attr') ." as ga left join ". $GLOBALS['ecs']->table('attribute') ." as at on ga.attr_id = at.attr_id  where ga.goods_attr_id = $value";
        $res = $GLOBALS['db']->getRow($sql);
        $goods_attr .= $res['attr_name'].":".$res['attr_value'];
        $attr_price = empty($res['attr_price'])?0:$res['attr_price']+$attr_price;
    }
    //$goods_attr = $goods_attr.'['.$attr_price.']';
    $GLOBALS['db']->query("update ". $GLOBALS['ecs']->table('cart') ." set goods_attr = '$goods_attr', goods_attr_id = '$attr_id', goods_price = '$shop_price' where rec_id = $rec_id");
    $result['err'] = '0';
    }  
    die($json->encode($result));
}

/* 代码增加_start  BY  www.yshop100.com */
if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'selcart')
{
	include('includes/cls_json.php');
    $json   = new JSON;
    $res    = array('err_msg' => '', 'result' => '');
	if ($_GET['sel_goods'])
	{
        if($_GET['sel_goods']){
    		$id_ext = " AND rec_id in (". $_GET['sel_goods'] .") ";
        }
		$cart_goods = get_cart_goods($id_ext);
		$shopping_money = sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']);
		$market_price_desc= sprintf($_LANG['than_market_price'],
        $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate']);
		$res['result'] = $shopping_money;
		/*
		隐藏市场价格
		if ($_CFG['show_marketprice'])
		{
			$res['result'] .= "".$market_price_desc ;
		}
		*/
		//折扣活动
		$res['suppid'] = intval($_GET['suppid']);
		$res['your_discount'] = '';
		$discount = compute_discount($res['suppid']);
		if(is_array($discount)){
			$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
			$res['your_discount'] = sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount']));
		}
	}
	else
	{
		$res['result'] = '请选择要结算的商品！';
	}	
	die($json->encode($res));
}
/* 代码增加_end  BY  www.yshop100.com */


/* 代码增加_start  By www.yshop100.com  */
$_CFG['anonymous_buy']='0';
$smarty->assign('lang',             $_LANG);
if ($_REQUEST['act']=='EditAddress')
{
	include_once('includes/cls_json.php');
	include_once('includes/lib_transaction.php');
	$result = array('error' => 0, 'message' => '', 'content' => '');
    $json  = new JSON;

	$address_id = intval($_GET['address_id']);
	if ($address_id)
	{
		$sql="select * from ". $ecs->table('user_address') ." where address_id='$address_id' ";
		$address_info = $db->getRow($sql);
		if ($address_info)
		{
			$address_info['tel_array'] = explode("-", $address_info['tel']);	
		}
		$smarty->assign('address', $address_info);
		$province_list = get_regions(1, $address_info['country']);
        $city_list     = get_regions(2, $address_info['province']);
        $district_list = get_regions(3, $address_info['city']);        
        $smarty->assign('province_list', $province_list);
        $smarty->assign('city_list',     $city_list);
        $smarty->assign('district_list', $district_list);
	}
	else
	{
		$smarty->assign('province_list', get_regions(1, $_CFG['shop_country']));
	}
	$result['content'] =  $smarty->fetch("library/address_edit.lbi");	
	die($json->encode($result));
}

elseif ($_REQUEST['act']=='selAddress')
{
	include_once('includes/cls_json.php');
	$order = flow_order_info();
	$result = array('error' => 0, 'message' => '', 'content' => '');
    $json  = new JSON;
    $address_id = intval($_GET['address_id']);

	$sql = "update ". $GLOBALS['ecs']->table('users') ." set address_id='". $_REQUEST['address_id'] ."' where user_id='".$_SESSION['user_id']."' ";
	$db->query($sql);
	$sql = "SELECT * ".
                    " FROM " . $GLOBALS['ecs']->table('user_address') . 
                    " WHERE  address_id = '".$_REQUEST['address_id']."' ";
    $consignee = $GLOBALS['db']->getRow($sql);
    $_SESSION['flow_consignee'] = $consignee;

	$flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
    $shipping_list     = available_shipping_list($region);
	$cart_weight_price = cart_weight_price($flow_type);
    $insure_disabled   = true;
    $cod_disabled      = true;

    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
    $shipping_count = $db->getOne($sql);

    foreach ($shipping_list AS $key => $val)
    {
        $shipping_cfg = unserialize_config($val['configure']);
        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
            price_format($val['insure'], false) : $val['insure'];

        /* 当前的配送方式是否支持保价 */
        if ($val['shipping_id'] == $order['shipping_id'])
        {
            $insure_disabled = ($val['insure'] == 0);
            $cod_disabled    = ($val['support_cod'] == 0);
        }
    }

    $smarty->assign('shipping_list',   $shipping_list);
	
    $smarty->assign('insure_disabled', $insure_disabled);
    $smarty->assign('cod_disabled',    $cod_disabled);
	$result['content']     = $smarty->fetch('library/shipping_box.lbi');
	die($json->encode($result));
}

elseif ($_REQUEST['act']=='delAddress')
{
	include_once('includes/cls_json.php');
	include_once('includes/lib_transaction.php');
	$order = flow_order_info();
	$result = array('error' => 0, 'message' => '', 'content' => '', 'content2'=>'');
    $json  = new JSON;
	$address_id = intval($_GET['address_id']);
    drop_consignee($address_id);
	
	$consignee_list = get_consignee_list_yshop100();
	if($_SESSION['flow_consignee']['address_id']==$address_id)
	{
		if ($consignee_list)
		{
			$_SESSION['flow_consignee'] = $consignee_list[0];
			$region            = array($consignee_list[0]['country'], $consignee_list[0]['province'], $consignee_list[0]['city'], $consignee_list[0]['district']);
		}
		else
		{
			$_SESSION['flow_consignee']="";
			$region            = array(1, 0, 0, 0);
		}
		$shipping_bian=1;
	}
	else
	{
		if ($consignee_list)
		{
			$shipping_bian=0;
		}
	}
	$smarty->assign('consignee_list', $consignee_list);	
	$smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));
	$smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));
	$result['content'] =  $smarty->fetch("library/address_list.lbi");	

	if($shipping_bian)
	{    
	$flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
    $shipping_list     = available_shipping_list($region);
	$cart_weight_price = cart_weight_price($flow_type);
    $insure_disabled   = true;
    $cod_disabled      = true;

    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
    $shipping_count = $db->getOne($sql);

    foreach ($shipping_list AS $key => $val)
    {
        $shipping_cfg = unserialize_config($val['configure']);
        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
            price_format($val['insure'], false) : $val['insure'];

        /* 当前的配送方式是否支持保价 */
        if ($val['shipping_id'] == $order['shipping_id'])
        {
            $insure_disabled = ($val['insure'] == 0);
            $cod_disabled    = ($val['support_cod'] == 0);
        }
    }

    $smarty->assign('shipping_list',   $shipping_list);
	
    $smarty->assign('insure_disabled', $insure_disabled);
    $smarty->assign('cod_disabled',    $cod_disabled);
	$result['content2']     = $smarty->fetch('library/shipping_box.lbi');	
	}
    
	if ($consignee_list)
	{
		$result['have_consignee']     = '1';
	}
	else
	{
		$result['have_consignee']     = '0';
	}

	die($json->encode($result));
}

elseif ($_REQUEST['act']=='saveAddress')
{
	include_once('includes/cls_json.php');
	include_once('includes/lib_transaction.php');
    $json  = new JSON;
	/* 保存收货地址信息_start */
	$_POST['address']=strip_tags(urldecode($_POST['address']));
    $_POST['address'] = json_str_iconv($_POST['address']);
	$address_yshop100 = $json->decode($_POST['address']);
	$consignee = array(
            'address_id'    => empty($address_yshop100->address_id) ?  '0'  :   intval($address_yshop100->address_id),
            'consignee'     => empty($address_yshop100->consignee)  ? '' :   compile_str(trim($address_yshop100->consignee)),
            'country'       => empty($address_yshop100->country)    ? '' :   intval($address_yshop100->country),
            'province'      => empty($address_yshop100->province)   ? '' :   intval($address_yshop100->province),
            'city'          => empty($address_yshop100->city)       ? '' :   intval($address_yshop100->city),
            'district'      => empty($address_yshop100->district)   ? '' :   intval($address_yshop100->district),
            'email'         => empty($address_yshop100->email)      ? '' :   compile_str($address_yshop100->email),
            'address'       => empty($address_yshop100->address)    ? '' :   compile_str($address_yshop100->address),
            'zipcode'       => empty($address_yshop100->zipcode)    ? '' :   compile_str(make_semiangle(trim($address_yshop100->zipcode))),
            'tel'           => empty($address_yshop100->tel)        ? '' :   compile_str(make_semiangle(trim($address_yshop100->tel))),
            'mobile'        => empty($address_yshop100->mobile)     ? '' :   compile_str(make_semiangle(trim($address_yshop100->mobile))),
     );
     if ($_SESSION['user_id'] > 0)
     {
            /* 如果用户已经登录，则保存收货人信息 */
            $consignee['user_id'] = $_SESSION['user_id'];
            save_consignee($consignee, true);
    }
	/* 保存收货地址信息_end */

	$result = array('error' => 0, 'message' => '', 'content' => '', 'closediv'=>$address_yshop100->closediv);    
	$consignee_list = get_consignee_list_yshop100();
	$smarty->assign('consignee_list', $consignee_list);
	$result['content'] =  $smarty->fetch("library/address_list.lbi");

    if($consignee_list && count($consignee_list)==1)
	{	
		$_SESSION['flow_consignee'] = $consignee_list[0];
	}
	if ($address_yshop100->shipping_bian=='0' && $address_yshop100->address_id>0 && $address_yshop100->address_id==$_SESSION['flow_consignee']['address_id'])
	{
		$sql = "SELECT * ".
                    " FROM " . $GLOBALS['ecs']->table('user_address') . 
                    " WHERE  address_id = '".$address_yshop100->address_id."' ";
		$consignee = $GLOBALS['db']->getRow($sql);
		$_SESSION['flow_consignee'] = $consignee;
	}
	if ($address_yshop100->shipping_bian=='1' || ($address_yshop100->shipping_bian=='0' && $address_yshop100->address_id>0 && $address_yshop100->address_id==$_SESSION['flow_consignee']['address_id']))
	{
	$order = flow_order_info();
	$flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
    $shipping_list     = available_shipping_list($region);
	$cart_weight_price = cart_weight_price($flow_type);
    $insure_disabled   = true;
    $cod_disabled      = true;

    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
    $shipping_count = $db->getOne($sql);

    foreach ($shipping_list AS $key => $val)
    {
        $shipping_cfg = unserialize_config($val['configure']);
        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
            price_format($val['insure'], false) : $val['insure'];

        /* 当前的配送方式是否支持保价 */
        if ($val['shipping_id'] == $order['shipping_id'])
        {
            $insure_disabled = ($val['insure'] == 0);
            $cod_disabled    = ($val['support_cod'] == 0);
        }
    }

    $smarty->assign('shipping_list',   $shipping_list);
	
    $smarty->assign('insure_disabled', $insure_disabled);
    $smarty->assign('cod_disabled',    $cod_disabled);
	$result['content2']     = $smarty->fetch('library/shipping_box.lbi');	
	}

	die($json->encode($result));
}

function get_consignee_list_yshop100()
{
	$consignee = get_consignee($_SESSION['user_id']);
	$sql="SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
            " WHERE user_id = '". $_SESSION['user_id'] ."' order by address_id ";
	$consignee_list_yshop100 = $GLOBALS['db']->getAll($sql);
	foreach ($consignee_list_yshop100  as $cons_key => $cons_val)
	{
				$consignee_list_yshop100[$cons_key]['address_short_name'] = $cons_val['consignee']."<br>";
				$consignee_list_yshop100[$cons_key]['address_short_name'] .=  get_region_info($cons_val['province'])."-";
				$consignee_list_yshop100[$cons_key]['address_short_name'] .=  get_region_info($cons_val['city'])."-";
				$consignee_list_yshop100[$cons_key]['address_short_name'] .=  get_region_info($cons_val['district'])."&nbsp;";
				$consignee_list_yshop100[$cons_key]['address_short_name'] .=  sub_str($cons_val['address'],16);
				$consignee_list_yshop100[$cons_key]['address_short_name'] .=  $cons_val['zipcode'] ? (",".$cons_val['zipcode']) : "";
				$consignee_list_yshop100[$cons_key]['address_short_name'] .=  "<br>".($cons_val['tel'] != '--' ? $cons_val['tel'] : $cons_val['mobile']);
				if ($consignee['address_id'] == $cons_val['address_id'])
				{
					$consignee_list_yshop100[$cons_key]['def_addr'] =1;
					$have_def_addr=1;
				}
	}
	if ( count($consignee_list_yshop100) && !$have_def_addr){ $consignee_list_yshop100[0]['def_addr'] =1; }
	return 	$consignee_list_yshop100;	
}
/* 代码增加_end  By www.yshop100.com  */

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

if (!isset($_REQUEST['step']))
{
    $_REQUEST['step'] = "cart";
}

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

assign_template();
assign_dynamic('flow');
if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
$smarty->assign('iswei',      1);   //判断是否为微信
}
//$smarty->assign('iswei',      1);
$position = assign_ur_here(0, $_LANG['shopping_flow']);
$smarty->assign('page_title',       $position['title']);    // 页面标题
$smarty->assign('ur_here',          $position['ur_here']);  // 当前位置

$smarty->assign('categories',       get_categories_tree()); // 分类树
$smarty->assign('helps',            get_shop_help());       // 网店帮助
$smarty->assign('lang',             $_LANG);
$smarty->assign('show_marketprice', $_CFG['show_marketprice']);
$smarty->assign('data_dir',    DATA_DIR);       // 数据目录

/*------------------------------------------------------ */
//-- 添加商品到购物车
/*------------------------------------------------------ */
if ($_REQUEST['step'] == 'add_to_cart')
{
   include_once('includes/cls_json.php');
    $_POST['goods']=strip_tags(urldecode($_POST['goods']));
    $_POST['goods'] = json_str_iconv($_POST['goods']);

    if (!empty($_REQUEST['goods_id']) && empty($_POST['goods']))
    {
        if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0)
        {
            ecs_header("Location:./\n");
        }
        $goods_id = intval($_REQUEST['goods_id']);
        exit;
    }

    $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
    $json  = new JSON;

    if (empty($_POST['goods']))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }

    $goods = $json->decode($_POST['goods']);
    
    /* 判断是否为正在预售的商品 */
    if(!isset($goods->extCode) || $goods->extCode != 'pre_sale')
    {
    	$pre_sale_id = is_pre_sale_goods($goods->goods_id);
    	if($pre_sale_id != null)
    	{
    		/* 进入收货人页面 */
    		$uri = build_uri("pre_sale", array("pre_sale_id" => $pre_sale_id));
    		$result['error']  = 777;
    		$result['message'] = "请到预售商品区购买，自动跳转中";
    		$result['uri'] = 'pre_sale.php?id='.$pre_sale_id;
    		die($json->encode($result));
    	}
    }
    	//www.yshop100.com start add 2015-311-23 限购
	$time_xg_now=gmtime();
	$row_xg= $GLOBALS['db']->getRow("select is_buy,buymax, buymax_start_date, buymax_end_date from ". $GLOBALS['ecs']->table('goods') ." where goods_id='".$goods->goods_id."' " );
	if ( $row_xg['is_buy'] == 1 && $row_xg['buymax'] >0 && $row_xg['buymax_start_date'] < $time_xg_now  && $row_xg['buymax_end_date'] > $time_xg_now  )
	{
		if ($_SESSION['user_id'] == 0 )
		{
			$result['error']  = 999;
			$result['message'] = "此商品为限购商品，需要登录后才能继续购买！";
			die($json->encode($result));
		}
		else
		{
			$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";

			$num_cart_old_1=$GLOBALS['db']->getOne("select sum(goods_number) from ". $GLOBALS['ecs']->table('cart') ." where " . $sql_where . " and goods_id= " . $goods->goods_id );
			$num_cart_old_2=$GLOBALS['db']->getOne("select sum(og.goods_number) from ". $GLOBALS['ecs']->table('order_goods') ." AS og , ". $GLOBALS['ecs']->table('order_info') ." AS o where o.user_id='$_SESSION[user_id]' and  o.order_id = og.order_id and add_time > ". $row_xg['buymax_start_date'] ." and add_time < ". $row_xg['buymax_end_date'] ."  and og.goods_id = " . $goods->goods_id );
			$num_cart_old = $num_cart_old_1 + $num_cart_old_2 ;
			$num_total = $num_cart_old +  intval($goods->number);
			if ( $num_total > intval($row_xg['buymax']) )
			{
				$result['error']   = 888;
				$num_else=intval($row_xg['buymax'])-$num_cart_old_2;
				$result['message'] ="注意：\n\r此商品限购期间每人限购 ". $row_xg['buymax'] . " 件\n\r";
				if ($num_cart_old_2 > 0)
				{
					$result['message'] .="您在限购期间已经成功购买过". $num_cart_old_2 ." 件！\n\r";
				}
				if ($num_cart_old_1 > 0)
				{
					$result['message'] .="您的购物车中已经存在". $num_cart_old_1 ."件！\n\r";
				}
				$result['message'] .= "您只能买 ". $num_else ." 件";
				die($json->encode($result));
			}
		}

	}
	
	//www.yshop100.com end add 2015-11-23 限购
    
    
    /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
    if (empty($goods->spec) AND empty($goods->quick))
    {
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ".
            "g.goods_attr_id, g.attr_value, g.attr_price " .
        'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
        "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " .
        'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';

        $res = $GLOBALS['db']->getAll($sql);

        if (!empty($res))
        {
            $spe_arr = array();
            foreach ($res AS $row)
            {
                $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                $spe_arr[$row['attr_id']]['name']     = $row['attr_name'];
                $spe_arr[$row['attr_id']]['attr_id']     = $row['attr_id'];
                $spe_arr[$row['attr_id']]['values'][] = array(
                                                            'label'        => $row['attr_value'],
                                                            'price'        => $row['attr_price'],
                                                            'format_price' => price_format($row['attr_price'], false),
                                                            'id'           => $row['goods_attr_id']);
            }
            $i = 0;
            $spe_array = array();
            foreach ($spe_arr AS $row)
            {
                $spe_array[]=$row;
            }
            $result['error']   = ERR_NEED_SELECT_ATTR;
            $result['goods_id'] = $goods->goods_id;
            $result['parent'] = $goods->parent;
            $result['message'] = $spe_array;

            die($json->encode($result));
        }
    }

    /* 更新：如果是一步购物，先清空购物车 */
    if ($_COOKIE['one_step_buy'] == '1')
    {
        clear_cart();
    }

    /* 检查：商品数量是否合法 */
    if (!is_numeric($goods->number) || intval($goods->number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    /* 更新：购物车 */
    else
    {
        if(!empty($goods->spec))
        {
            foreach ($goods->spec as  $key=>$val )
            {
                $goods->spec[$key]=intval($val);
            }
        }

        // 更新：添加到购物车
		$recid=addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent);
        if ($recid>-1)
        {//file_put_contents('20191012.txt',"ok:".$recid."\r\n" , FILE_APPEND); 
            if ($_CFG['cart_confirm'] > 2)
            {
                $result['message'] = '';
            }
            else
            {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }

            $result['content'] = insert_cart_info();
			if($recid>1)
			{
				if(!empty($_REQUEST['luin']))
				{
					if($_REQUEST['luin']>0)
					{
						$GLOBALS['db']->query('update ecs_cart set user_id='.$_REQUEST['luin'].' where user_id=0 and rec_id='.$recid);
					}
				}
			}
            $result['one_step_buy'] = $_COOKIE['one_step_buy'];
        }
        else
        {
            $result['message']  = $err->last_message();
            $result['error']    = $err->error_no;
            $result['goods_id'] = stripslashes($goods->goods_id);
            if (is_array($goods->spec))
            {
                $result['product_spec'] = implode(',', $goods->spec);
            }
            else
            {
                $result['product_spec'] = $goods->spec;
            }
        }
    }
$rows = $GLOBALS['db']->getRow("select goods_brief,shop_price,goods_name,goods_thumb from ".$GLOBALS['ecs']->table('goods')." where goods_id=".$goods->goods_id);
$result['shop_price'] = price_format($rows['shop_price']);
$result['goods_name'] = $rows['goods_name'];
$result['goods_thumb'] = $rows['goods_thumb'];
$result['goods_brief'] = $rows['goods_brief'];
$result['goods_id'] = $goods->goods_id;
$sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount' .
' FROM ' . $GLOBALS['ecs']->table('cart') .
" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'";
$rowss = $GLOBALS['db']->GetRow($sql);
$result['goods_price'] = price_format($rowss['amount']);
$result['goods_number'] = $rowss['number'];
    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}


elseif ($_REQUEST['step'] == 'add_to_cart1')
{
    /* 立即购买先清空购物车 */
     clear_cart();
    include_once('includes/cls_json.php');
    $_POST['goods'] = json_str_iconv($_POST['goods']);
    if (!empty($_REQUEST['goods_id']) && empty($_POST['goods']))
    {
        if (!is_numeric($_REQUEST['goods_id']) || intval($_REQUEST['goods_id']) <= 0)
        {
            ecs_header("Location:./\n");
        }
        $goods_id = intval($_REQUEST['goods_id']);
        exit;
    }
    $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
    $json  = new JSON;
    if (empty($_POST['goods']))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }
    $goods = $json->decode($_POST['goods']);
    
    	//www.yshop100.com start add 2015-311-23 限购
	$time_xg_now=gmtime();
	$row_xg= $GLOBALS['db']->getRow("select is_buy,buymax, buymax_start_date, buymax_end_date from ". $GLOBALS['ecs']->table('goods') ." where goods_id='".$goods->goods_id."' " );
	if ( $row_xg['is_buy'] == 1 && $row_xg['buymax'] >0 && $row_xg['buymax_start_date'] < $time_xg_now  && $row_xg['buymax_end_date'] > $time_xg_now  )
	{
		if ($_SESSION['user_id'] == 0 )
		{
			$result['error']  = 999;
			$result['message'] = "此商品为限购商品，需要登录后才能继续购买！";
			die($json->encode($result));
		}
		else
		{
			$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";

			$num_cart_old_1=$GLOBALS['db']->getOne("select sum(goods_number) from ". $GLOBALS['ecs']->table('cart') ." where " . $sql_where . " and goods_id= " . $goods->goods_id );
			$num_cart_old_2=$GLOBALS['db']->getOne("select sum(og.goods_number) from ". $GLOBALS['ecs']->table('order_goods') ." AS og , ". $GLOBALS['ecs']->table('order_info') ." AS o where o.user_id='$_SESSION[user_id]' and  o.order_id = og.order_id and add_time > ". $row_xg['buymax_start_date'] ." and add_time < ". $row_xg['buymax_end_date'] ."  and og.goods_id = " . $goods->goods_id );
			$num_cart_old = $num_cart_old_1 + $num_cart_old_2 ;
			$num_total = $num_cart_old +  intval($goods->number);
			if ( $num_total > intval($row_xg['buymax']) )
			{
				$result['error']   = 888;
				$num_else=intval($row_xg['buymax'])-$num_cart_old_2;
				$result['message'] ="注意：\n\r此商品限购期间每人限购 ". $row_xg['buymax'] . " 件\n\r";
				if ($num_cart_old_2 > 0)
				{
					$result['message'] .="您在限购期间已经成功购买过". $num_cart_old_2 ." 件！\n\r";
				}
				if ($num_cart_old_1 > 0)
				{
					$result['message'] .="您的购物车中已经存在". $num_cart_old_1 ."件！\n\r";
				}
				$result['message'] .= "您只能买 ". $num_else ." 件";
				die($json->encode($result));
			}
		}

	}
	
	//www.yshop100.com end add 2015-11-23 限购
        
    /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
    if (empty($goods->spec) AND empty($goods->quick))
    {
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ".
            "g.goods_attr_id, g.attr_value, g.attr_price " .
        'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
        "WHERE a.attr_type != 0 AND g.goods_id = '" . $goods->goods_id . "' " .
        'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';
        $res = $GLOBALS['db']->getAll($sql);
        if (!empty($res))
        {
            $spe_arr = array();
            foreach ($res AS $row)
            {
                $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                $spe_arr[$row['attr_id']]['name']     = $row['attr_name'];
                $spe_arr[$row['attr_id']]['attr_id']     = $row['attr_id'];
                $spe_arr[$row['attr_id']]['values'][] = array(
                                                            'label'        => $row['attr_value'],
                                                            'price'        => $row['attr_price'],
                                                            'format_price' => price_format($row['attr_price'], false),
                                                            'id'           => $row['goods_attr_id']);
            }
            $i = 0;
            $spe_array = array();
            foreach ($spe_arr AS $row)
            {
                $spe_array[]=$row;
            }
            $result['error']   = ERR_NEED_SELECT_ATTR;
            $result['goods_id'] = $goods->goods_id;
            $result['parent'] = $goods->parent;
            $result['message'] = $spe_array;
            die($json->encode($result));
        }
    }
 
    /* 检查：商品数量是否合法 */
    if (!is_numeric($goods->number) || intval($goods->number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    /* 更新：购物车 */
    else
    {
        // 更新：添加到购物车
        if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent)>-1)
        {
            if ($_CFG['cart_confirm'] > 2)
            {
                $result['message'] = '';
            }
            else
            {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }
            $result['content'] = insert_cart_info();
            $result['one_step_buy'] = $_COOKIE['one_step_buy'];

        }
        else
        {
            $result['message']  = $err->last_message();
            $result['error']    = $err->error_no;
            $result['goods_id'] = stripslashes($goods->goods_id);
            if (is_array($goods->spec))
            {
                $result['product_spec'] = implode(',', $goods->spec);
            }
            else
            {
                $result['product_spec'] = $goods->spec;
            }
        }
    }
    $result['confirm_type'] =3;
    $rows = $GLOBALS['db']->getRow("select goods_brief,shop_price,goods_name,goods_thumb from ".$GLOBALS['ecs']->table('goods')." where goods_id=".$goods->goods_id);
$result['shop_price'] = price_format($rows['shop_price']);
$result['goods_name'] = $rows['goods_name'];
$result['goods_thumb'] = $rows['goods_thumb'];
$result['goods_brief'] = $rows['goods_brief'];
$result['goods_id'] = $goods->goods_id;
$sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount' .
' FROM ' . $GLOBALS['ecs']->table('cart') .
" WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'";
$rowss = $GLOBALS['db']->GetRow($sql);
$result['goods_price'] = price_format($rowss['amount']);
$result['goods_number'] = $rowss['number'];
    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'link_buy')
{
    $goods_id = intval($_GET['goods_id']);

    if (!cart_goods_exists($goods_id,array()))
    {
        addto_cart($goods_id);
    }
    ecs_header("Location:./flow.php\n");
    exit;
}
elseif ($_REQUEST['step'] == 'login')
{
    include_once('languages/'. $_CFG['lang']. '/user.php');

    /*
     * 用户登录注册
     */
    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        $smarty->assign('anonymous_buy', $_CFG['anonymous_buy']);

        /* 检查是否有赠品，如果有提示登录后重新选择赠品 */
	$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";		
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
                " WHERE $sql_where AND is_gift > 0";
        if ($db->getOne($sql) > 0)
        {
            $smarty->assign('need_rechoose_gift', 1);
        }

        /* 检查是否需要注册码 */
        $captcha = intval($_CFG['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
        {
            $smarty->assign('enabled_login_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }
        if ($captcha & CAPTCHA_REGISTER)
        {
            $smarty->assign('enabled_register_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }
    }
    else
    {
        include_once('includes/lib_passport.php');
        if (!empty($_POST['act']) && $_POST['act'] == 'signin')
        {
            $captcha = intval($_CFG['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
            {
                if (empty($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }

                /* 检查验证码 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                $validator->session_word = 'captcha_login';
                if (!$validator->check_word($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }
            }

            $_POST['password']=isset($_POST['password']) ? trim($_POST['password']) : '';
            if ($user->login($_POST['username'], $_POST['password'],isset($_POST['remember'])))
            {
                update_user_info();  //更新用户信息
                recalculate_price(); // 重新计算购物车中的商品价格

                /* 检查购物车中是否有商品 没有商品则跳转到首页 */
		$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
                $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') . " WHERE $sql_where ";
                if ($db->getOne($sql) > 0)
                {
                    ecs_header("Location: flow.php?step=checkout\n");
                }
                else
                {
                    ecs_header("Location:index.php\n");
                }

                exit;
            }
            else
            {
                $_SESSION['login_fail']++;
                show_message($_LANG['signin_failed'], '', 'flow.php?step=login');
            }
        }
        elseif (!empty($_POST['act']) && $_POST['act'] == 'signup')
        {
            if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
            {
                if (empty($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }

                /* 检查验证码 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                if (!$validator->check_word($_POST['captcha']))
                {
                    show_message($_LANG['invalid_captcha']);
                }
            }

            if (register(trim($_POST['username']), trim($_POST['password']), trim($_POST['email'])))
            {
                /* 用户注册成功 */
                ecs_header("Location: flow.php?step=checkout\n");
                exit;
            }
            else
            {
                $err->show();
            }
        }
        else
        {
            // TODO: 非法访问的处理
        }
    }
}

elseif ($_REQUEST['step'] == 'consignee')
{
	clear_all_files();
    /*------------------------------------------------------ */
    //-- 收货人信息
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /*
         * 收货人信息填写界面
         */

        if (isset($_REQUEST['direct_shopping']))
        {
            $_SESSION['direct_shopping'] = 1;
        }

        /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('shop_country',       $_CFG['shop_country']);
        $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

        /* 获得用户所有的收货人信息 */
        if ($_SESSION['user_id'] > 0)
        {
            $consignee_list = get_consignee_list($_SESSION['user_id']);

            if (count($consignee_list) < 10)
            {
                /* 如果用户收货人信息的总数小于 10 则增加一个新的收货人信息 */
                $consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '', 'type' => 'add');
            }
        }
        else
        {
            if (isset($_SESSION['flow_consignee'])){
                $consignee_list = array($_SESSION['flow_consignee']);
            }
            else
            {
                $consignee_list[] = array('country' => $_CFG['shop_country']);
            }
        }
        $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));
        $smarty->assign('consignee_list', $consignee_list);

        /* 取得每个收货地址的省市区列表 */
        $province_list = array();
        $city_list = array();
        $district_list = array();
        foreach ($consignee_list as $region_id => $consignee)
        {
            $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
            $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
            $consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;

            $province_list[$region_id] = get_regions(1, $consignee['country']);
            $city_list[$region_id]     = get_regions(2, $consignee['province']);
            $district_list[$region_id] = get_regions(3, $consignee['city']);
        }
        $smarty->assign('province_list', $province_list);
        $smarty->assign('city_list',     $city_list);
        $smarty->assign('district_list', $district_list);

        /* 返回收货人页面代码 */
        $smarty->assign('real_goods_count', exist_real_goods(0, $flow_type) ? 1 : 0);
    }
    else
    {
        /*
         * 保存收货人信息
         */
        $consignee = array(
            'address_id'    => empty($_POST['address_id']) ? 0  :   intval($_POST['address_id']),
            'consignee'     => empty($_POST['consignee'])  ? '' :   compile_str(trim($_POST['consignee'])),
            'country'       => empty($_POST['country'])    ? '' :   intval($_POST['country']),
            'province'      => empty($_POST['province'])   ? '' :   intval($_POST['province']),
            'city'          => empty($_POST['city'])       ? '' :   intval($_POST['city']),
            'district'      => empty($_POST['district'])   ? '' :   intval($_POST['district']),
            'email'         => empty($_POST['email'])      ? '' :   compile_str($_POST['email']),
            'address'       => empty($_POST['address'])    ? '' :   compile_str($_POST['address']),
            'zipcode'       => empty($_POST['zipcode'])    ? '' :   compile_str(make_semiangle(trim($_POST['zipcode']))),
            'tel'           => empty($_POST['tel'])        ? '' :   compile_str(make_semiangle(trim($_POST['tel']))),
            'mobile'        => empty($_POST['mobile'])     ? '' :   compile_str(make_semiangle(trim($_POST['mobile']))),
            'sign_building' => empty($_POST['sign_building']) ? '' :compile_str($_POST['sign_building']),
            'best_time'     => empty($_POST['best_time'])  ? '' :   compile_str($_POST['best_time']),
        );

        if ($_SESSION['user_id'] > 0)
        {
            include_once(ROOT_PATH . 'includes/lib_transaction.php');

            /* 如果用户已经登录，则保存收货人信息 */
            $consignee['user_id'] = $_SESSION['user_id'];

            save_consignee($consignee, true);
        }

        /* 保存到session */
        $_SESSION['flow_consignee'] = stripslashes_deep($consignee);

        ecs_header("Location: flow.php?step=checkout\n");
        exit;
    }
}
elseif ($_REQUEST['step'] == 'drop_consignee')
{
    /*------------------------------------------------------ */
    //-- 删除收货人信息
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }
    else
    {
        show_message($_LANG['not_fount_consignee']);
    }
}
elseif ($_REQUEST['step'] == 'checkout')
{
    
     /* 检查用户是否已经登录
     * 如果没有登录则跳转到登录和注册页面
     */
    if ($_SESSION['user_id'] == 0)
    {
        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
        //ecs_header("Location: user.php\n");
        ecs_header("Location: user.php?act=login&jump=jszx\n");
        exit;
    }
    
    /*------------------------------------------------------ */
    //-- 订单确认
    /*------------------------------------------------------ */

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 团购标志 */
    if ($flow_type == CART_GROUP_BUY_GOODS)
    {
        $smarty->assign('is_group_buy', 1);
    }
    /* 积分兑换商品 */
    elseif ($flow_type == CART_EXCHANGE_GOODS)
    {
        $smarty->assign('is_exchange_goods', 1);
    }
    else
    {
        //正常购物流程  清空其他购物流程情况
        $_SESSION['flow_order']['extension_code'] = '';
    }
    
    if($flow_type != CART_EXCHANGE_GOODS){
    	//非积分兑换形式的商品
    	/* 代码增加_start  By  www.yshop100.com */
		$sel_cartgoods_count = count($_REQUEST['sel_cartgoods']);
		$_SESSION['sel_cartgoods'] =  $sel_cartgoods_count>0 ? (implode(",", $_REQUEST['sel_cartgoods'])) : $_SESSION['sel_cartgoods'];
		/* 代码增加_end   By  www.yshop100.com */
		
		//验证购物车中提交过来的商品中参加的活动是否都正常start
		$_REQUEST['sel_goods'] = $_SESSION['sel_cartgoods'];
		$favourable_list = favourable_list($_SESSION['user_rank'],false);
		if($favourable_list){
			$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
			foreach($favourable_list as $fk=>$fv){
				if(!$fv['available']){
					$sql = "select count(rec_id) as num from ". $ecs->table('cart') .
					" WHERE $sql_where " .
	        		"AND is_gift = ".$fv['act_id'];
					if($db->getOne($sql) > 0){
						show_message('购物车中参加['.$fv['act_name'].']活动的商品未满足条件，请重新设置或者将其赠品删除', '', '', 'warning');
					}
				}
			}
			unset($sql_where);
		}
	
		//验证购物车中提交过来的商品中参加的活动是否都正常end
    }

    /* 检查购物车中是否有商品 */
	$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
	$sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
		" WHERE $sql_where " .
		" AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

    if ($db->getOne($sql) == 0)
    {
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }else{
            if($flow_type != CART_EXCHANGE_GOODS)
		{
		$time_xg_now=gmtime();
        if($_REQUEST['sel_goods']){
            $sql_plus = " and c.rec_id in (".$_REQUEST['sel_goods'].") ";
        }
		$sql="select c.goods_number,g.goods_id, g.goods_name,g.is_buy, g.buymax, g.buymax_start_date, g.buymax_end_date from ".$ecs->table('cart'). " AS c left join ".$ecs->table('goods'). " AS g on c.goods_id=g.goods_id where 1 ".$sql_plus;
		$goods_list = $db->getAll($sql);
		foreach($goods_list as $k => $v)
		{
			if($v['is_buy'] == 1 && $v['buymax'] >0 && $v['buymax_start_date'] < $time_xg_now  && $v['buymax_end_date'] > $time_xg_now )
			{
				$num_cart_old=$GLOBALS['db']->getOne("select sum(og.goods_number) from ". $GLOBALS['ecs']->table('order_goods') ." AS og , ". $GLOBALS['ecs']->table('order_info') ." AS o where o.user_id='$_SESSION[user_id]' and o.order_id = og.order_id and add_time > ". $v['buymax_start_date'] ." and add_time < ". $v['buymax_end_date'] ."  and og.goods_id = " . $v['goods_id'] ); 
				$num_total = $num_cart_old +  intval($v['goods_number']);
				if ( $num_total > intval($v['buymax']) )
				{
					$num_else=intval($v['buymax'])-$num_cart_old;
					$message .= "商品 <font color=#330099>【".$v['goods_name']."】</font> 限购期间每人限购 <font color=#330099>". $v['buymax'] . "</font> 件<br>";
					if ($num_cart_old)
					{
						$message .="您在限购期间已经成功购买过 <font color=#330099>$num_cart_old</font> 件！<br>";
					}
					$message .= "您最多只能再买 <font color=#330099>". $num_else ."</font> 件<br>";
				}
			} 
		}
		if($message != '')
		{
			show_message($message, $_LANG['back_to_cart'], 'flow.php',  'info',  false);
			exit; 
		}
            }
	}

    /*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
    {
        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
        ecs_header("Location: flow.php?step=login\n");
        exit;
    }

    $consignee = get_consignee($_SESSION['user_id']);

	if (empty($consignee))
	{
		$consignee['country']='1'; 
	}
    if(empty($consignee['address'])){
        $smarty->assign('no_consignee', true);
    }

	/*收货人显示省市区*/
	$sql = "SELECT concat( IFNULL(c.region_name, ' '),   
        IFNULL(p.region_name, ''),   
        IFNULL(t.region_name, ''),   
        IFNULL(d.region_name, '')) AS region " .  
        " FROM " . $ecs->table('region') . " c, " . $ecs->table('region') . " p, " . $ecs->table('region') . " t, " . $ecs->table('region') . " d  
        WHERE c.`region_id`='".$consignee['country']."'  
        AND p.`region_id`='".$consignee['province']."'  
        AND t.`region_id`='".$consignee['city']."'  
        AND d.`region_id`='".$consignee['district']."'";  
  
$consignee['region'] = $db->getOne($sql); 
    $smarty->assign('consignee', $consignee);

    include_once('includes/lib_transaction.php');
    if ($_SESSION['user_id'] > 0)
    {	
		$sql="SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
		" WHERE user_id = '". $_SESSION['user_id'] ."' order by address_id ";
		$consignee_list_yshop100 = $GLOBALS['db']->getAll($sql);
		foreach ($consignee_list_yshop100  as $cons_key => $cons_val)
		{
			$consignee_list_yshop100[$cons_key]['address_short_name'] = $cons_val['consignee']."<br>";
			$consignee_list_yshop100[$cons_key]['address_short_name'] .=  get_region_info($cons_val['province'])."-";
			$consignee_list_yshop100[$cons_key]['address_short_name'] .=  get_region_info($cons_val['city'])."-";
			$consignee_list_yshop100[$cons_key]['address_short_name'] .=  get_region_info($cons_val['district'])."&nbsp;";
			$consignee_list_yshop100[$cons_key]['address_short_name'] .=  sub_str($cons_val['address'],16);
			$consignee_list_yshop100[$cons_key]['address_short_name'] .=  $cons_val['zipcode'] ? (",".$cons_val['zipcode']) : "";
			$consignee_list_yshop100[$cons_key]['address_short_name'] .=  "<br>".$cons_val['tel'];
			if ($consignee['address_id'] == $cons_val['address_id'])
			{
				$consignee_list_yshop100[$cons_key]['def_addr'] =1;
				$have_def_addr=1;
			}
		}
		if ( count($consignee_list_yshop100) && !$have_def_addr){ $consignee_list_yshop100[0]['def_addr'] =1; }          
   }
   $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));
   $smarty->assign('consignee_list', $consignee_list_yshop100);
   $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计
    /*
     * 分供货商显示商品
     */

    // echo "<pre>";
    // print_r($cart_goods);
    // echo $cart_goods[0]['goods_thumb'];




    $cart_goods_new = array();
    if(count($cart_goods)>0){
        $i=0;
    	foreach($cart_goods as $key => $val){
            //不同供应商不能同时结算
            if($i == 0){
                $suppliers_id = $val['suppliers_id'];
            }elseif($val['suppliers_id'] != $suppliers_id){
                show_message('订单供应商不唯一');
            }
            $i++;
            //end
    		$cart_goods_new[$val['supplier_id']]['goodlist'][] = $val;
                $cart_goods_new[$val['supplier_id']]['shipping_html'] = insert_get_shop_shipping(array('suppid'=>$val['supplier_id'],'consignee'=>$consignee,'flow_type'=>$flow_type,'suppliers_id'=>$val['suppliers_id']));
    	}
    }

    /* 检查收货人信息是否完整 */
    if (!check_consignee_info($consignee, $flow_type) && $suppliers_id == '2')
    {
        /* 如果不完整则转向到收货人信息填写界面 */
        ecs_header("Location: flow.php?step=consignee\n");
        exit;
    }else{
        $_SESSION['flow_consignee'] = $consignee;
    }

    //实名验证
    $sql = "select status from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
    $rows = $GLOBALS['db']->getRow($sql);
    if($rows['status'] != '1' && $suppliers_id == '2')
    {
        ecs_header("Location: user_shengfen.php?act=shiming\n");
        exit;
    }

    if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS)
    {
	    foreach($cart_goods_new as $k => $v){
			$discount = compute_discount($k);
			if(is_array($discount)){
				$cart_goods_new[$k]['zhekou']['discount'] = $discount['discount'];
				$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
				$cart_goods_new[$k]['zhekou']['your_discount'] = sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount']));
			}
	    }
    }


    /* 对是否允许修改购物车赋值 */
    if ($flow_type != CART_GENERAL_GOODS || $_COOKIE['one_step_buy'] == '1')
    {
        $smarty->assign('allow_edit_cart', 0);
    }
    else
    {
        $smarty->assign('allow_edit_cart', 1);
    }

    /*
     * 取得购物流程设置
     */
    $smarty->assign('config', $_CFG);
    /*
     * 取得订单信息
     */
    $order = flow_order_info();
    $smarty->assign('order', $order);

    /* 计算折扣 */
    if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS)
    {
        $discount = compute_discount();
        $smarty->assign('discount', $discount['discount']);
        $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
        $smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));
    }

    /*
     * 计算订单的费用
     */
	$shopping = intval($_CFG['shopping']);
	$d_discount = intval($_CFG['discount']);
	$total = order_fee($order, $cart_goods, $consignee);

    if($total['goods_price'] <= 0 && $total['exchange_integral'] == 0){
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }

    $smarty->assign('total', $total);
    $smarty->assign('shopping_money', sprintf($_LANG['shopping_money'], $total['formated_goods_price']));
    $smarty->assign('market_price_desc', sprintf($_LANG['than_market_price'], $total['formated_market_price'], $total['formated_saving'], $total['save_rate']));

    /* 取得配送列表 */
//    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
//    $shipping_list     = available_shipping_list($region);
//    $cart_weight_price = cart_weight_price($flow_type);
//    $insure_disabled   = true;
//    $cod_disabled      = true;
//
//    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
//    //$sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
//    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE $sql_where AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
//    $shipping_count = $db->getOne($sql);
//
//    foreach ($shipping_list AS $key => $val)
//    {
//        $shipping_cfg = unserialize_config($val['configure']);
//        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
//        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);
//
//        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
//        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
//        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
//        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
//            price_format($val['insure'], false) : $val['insure'];
//
//        /* 当前的配送方式是否支持保价 */
//        if ($val['shipping_id'] == $order['shipping_id'])
//        {
//            $insure_disabled = ($val['insure'] == 0);
//            $cod_disabled    = ($val['support_cod'] == 0);
//        }
//    }
//
//    $smarty->assign('shipping_list',   $shipping_list);
//    $smarty->assign('insure_disabled', $insure_disabled);
//    $smarty->assign('cod_disabled',    $cod_disabled);

    /* 取得支付列表 */
    if ($order['shipping_id'] == 0)
    {
        $cod        = true;
        $cod_fee    = 0;
    }
    else
    {
        $shipping = shipping_info($order['shipping_id']);
        $cod = $shipping['support_cod'];

        if ($cod)
        {
            /* 如果是团购，且保证金大于0，不能使用货到付款 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $group_buy_id = $_SESSION['extension_id'];
                if ($group_buy_id <= 0)
                {
                    show_message('error group_buy_id');
                }
                $group_buy = group_buy_info($group_buy_id);
                if (empty($group_buy))
                {
                    show_message('group buy not exists: ' . $group_buy_id);
                }

                if ($group_buy['deposit'] > 0)
                {
                    $cod = false;
                    $cod_fee = 0;

                    /* 赋值保证金 */
                    $smarty->assign('gb_deposit', $group_buy['deposit']);
                }
            }

            if ($cod)
            {
                $shipping_area_info = shipping_area_info($order['shipping_id'], $region);
                $cod_fee            = $shipping_area_info['pay_fee'];
            }
        }
        else
        {
            $cod_fee = 0;
        }
    }
    $sql = 'SELECT * FROM `ecs_period_config` where id>0';
    $period_list = $db->getAll($sql); 
    
    $sql = 'SELECT minorderamount FROM `ecs_period_config` where id=0';
    $minorderamount = $db->getOne($sql);
    
    $sql = 'SELECT maxorderamount FROM `ecs_period_config` where id=0';
    $maxorderamount = $db->getOne($sql);
    
    $sql = 'SELECT * FROM `ecs_users_period` where user_id='.$_SESSION['user_id'];
    $period_user = $db->getRow($sql);
    
    $sql = "select value from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
    $isksf = $db->getOne($sql);
    
    $sql = "select open_quickpay from " . $GLOBALS['ecs']->table('users') . " where user_id=".$_SESSION['user_id'];
    $open_quickpay = $db->getOne($sql);
        
    
    $sql = 'SELECT min(minpoints) FROM `ecs_period_points`  ';
    $minpoints = $db->getOne($sql);

    // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
    $payment_list = available_payment_list(1, $cod_fee, false, $suppliers_id);
    	$pay_balance_id=0;//当前配置于的余额支付的递增id
    if(isset($payment_list))
    {
        foreach ($payment_list as $key => $payment)
        {
            if ($payment['is_cod'] == '1')
            {
                $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
            }
            /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
            if ($payment['pay_code'] == 'yeepayszx' && $total['amount'] > 300)
            {
                unset($payment_list[$key]);
            }
            /* 如果有余额支付 */
            if ($payment['pay_code'] == 'balance')
            {
		$pay_balance_id = $payment['pay_id'];
                /* 如果未登录，不显示 */
                if ($_SESSION['user_id'] == 0)
                {
                    unset($payment_list[$key]);
                }
                else
                {
                    if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id'])
                    {
                        $smarty->assign('disable_surplus', 1);
                    }
                }
            }
        }
    }

    /*echo '<pre>';
    print_R($payment_list);
    echo '</pre>';
    die();*/

    $smarty->assign('pay_balance_id', $pay_balance_id);
    $smarty->assign('payment_list', $payment_list);
    $smarty->assign('period_list', $period_list);
    $smarty->assign('minorderamount', $minorderamount);
    $smarty->assign('maxorderamount', $maxorderamount);
    $smarty->assign('period_user', $period_user); 
    $smarty->assign('isksf', $isksf); 
    $smarty->assign('open_quickpay', $open_quickpay); 
    $smarty->assign('iszhigou', $suppliers_id); 

    /* 取得包装与贺卡 */
    if ($total['real_goods_count'] > 0)
    {
        /* 只有有实体商品,才要判断包装和贺卡 */
        if (!isset($_CFG['use_package']) || $_CFG['use_package'] == '1')
        {
            /* 如果使用包装，取得包装列表及用户选择的包装 */
            $smarty->assign('pack_list', pack_list());
        }

        /* 如果使用贺卡，取得贺卡列表及用户选择的贺卡 */
        if (!isset($_CFG['use_card']) || $_CFG['use_card'] == '1')
        {
            $smarty->assign('card_list', card_list());
        }
    }

    $user_info = user_info($_SESSION['user_id']);
    $smarty->assign('pay_points', $user_info['pay_points']); 
    $smarty->assign('rank_points', $user_info['rank_points']); 
    $smarty->assign('minpoints', $minpoints); 

    /* 如果使用余额，取得用户余额 */
    if ((!isset($_CFG['use_surplus']) || $_CFG['use_surplus'] == '1')
        && $_SESSION['user_id'] > 0
        && $user_info['user_money'] > 0
        && $pay_balance_id > 0)
    {
        // 能使用余额
        $smarty->assign('allow_use_surplus', 1);
        $smarty->assign('your_surplus', $user_info['user_money']);
    }

    /* 如果使用积分，取得用户可用积分及本订单最多可以使用的积分 */
    if ((!isset($_CFG['use_integral']) || $_CFG['use_integral'] == '1')
        && $_SESSION['user_id'] > 0
        && $user_info['pay_points'] > 0
        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
    {
        // 能使用积分
        $keyong = flow_available_points();// 可用积分
        foreach($keyong as $k=>$v){
        	$cart_goods_new[$k]['jifen'] = $v;
        }
        
        $smarty->assign('allow_use_integral', 1);
        //$smarty->assign('order_max_integral', $keyong);  
        $smarty->assign('your_integral',      $user_info['pay_points']); // 用户积分
    }

    /* 如果使用红包，取得用户可以使用的红包及用户选择的红包 */
    if ((!isset($_CFG['use_bonus']) || $_CFG['use_bonus'] == '1')
        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != 
CART_EXCHANGE_GOODS))
    {
        // 取得用户可用红包
		$user_bonus = user_bonus($_SESSION['user_id'], $total['goods_price_supplier']);//把参数由总金额改为每个店铺的订单金额
		//$user_bonus = user_bonus($_SESSION['user_id'], $total['goods_price']);

        if (!empty($user_bonus))
        {
            foreach ($user_bonus AS $key => $val)
            {
                foreach($val as $k => $v)
				{
					$user_bonus[$key][$k]
['bonus_money_formated'] = price_format($v['type_money'], false);			
	
					
					$cart_goods_new[$key]['redbag'] = 
$user_bonus[$key];
				
				}
            }
            //file_put_contents('./goodslist.txt',var_export($cart_goods_new,true)); 
 
			foreach($user_bonus as $key=>$val){
				foreach($val as $k=>$v){
					$res[$k]=$v;
				}
			}
			$smarty->assign('bonus_list', $res);
        }
        // 能使用红包
        $smarty->assign('allow_use_bonus', 1);
    }
    $smarty->assign('goods_list', $cart_goods_new);


    /* 如果使用缺货处理，取得缺货处理列表 */
    if (!isset($_CFG['use_how_oos']) || $_CFG['use_how_oos'] == '1')
    {
        if (is_array($GLOBALS['_LANG']['oos']) && !empty($GLOBALS['_LANG']['oos']))
        {
            $smarty->assign('how_oos_list', $GLOBALS['_LANG']['oos']);
        }
    }

    /* 如果能开发票，取得发票内容列表 */
    if ((!isset($_CFG['can_invoice']) || $_CFG['can_invoice'] == '1')
        && isset($_CFG['invoice_content'])
        && trim($_CFG['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS)
    {

        $inv_content_list = explode("\n", str_replace("\r", '', $_CFG['invoice_content']));
        $smarty->assign('inv_content_list', $inv_content_list);
        $inv_type_list = array();
        foreach ($_CFG['invoice_type']['type'] as $key => $type)
        {

            if (!empty($type)&&$_CFG['invoice_type']['enable'][$key]=='1')
            {
                $inv_type_list[$type] = ($key < 2 ? $_LANG[$type] : $_CFG['invoice_type']['type'][$key]) . ' [' . floatval($_CFG['invoice_type']['rate'][$key]) . '%]';
            }
        }
        $smarty->assign('inv_type_list', $inv_type_list);
	$smarty->assign('province_list', get_regions(1, $_CFG['shop_country']));
    }
	if ($_CFG['time_shouhuo'])
	{
		$bjtimes=$_CFG['time_shouhuo']*3600;
	}
	else
	{
		$bjtimes=10*3600;
	}
	$week_list = array();
	$week_list[0]['name'] = date('m-d', time());
	$week_list[0]['week'] = "今天";
	$week_list[1]['name'] = date('m-d', strtotime('+1 days'));
	$week_list[1]['week'] = getWeek(strtotime('+1 days'));
	$week_list[2]['name'] = date('m-d', strtotime('+2 days'));
	$week_list[2]['week']  = getWeek(strtotime('+2 days'));
	$week_list[3]['name'] = date('m-d', strtotime('+3 days'));
	$week_list[3]['week'] = getWeek(strtotime('+3 days'));
	$week_list[4]['name'] = date('m-d', strtotime('+4 days'));
	$week_list[4]['week']  =getWeek(strtotime('+4 days'));
	$week_list[5]['name'] = date('m-d', strtotime('+5 days'));
	$week_list[5]['week'] = getWeek(strtotime('+5 days'));
	$week_list[6]['name'] = date('m-d', strtotime('+6 days'));
	$week_list[6]['week'] = getWeek(strtotime('+6 days'));
	foreach ($week_list as $wkey => $week)
	{
		$chatimes_11 = strtotime(date('Y')."-". $week['name'] ." 09:00") - time();
		$chatimes_12 = strtotime(date('Y')."-".$week['name']." 15:00") - time();
		if ($chatimes_11 > $bjtimes || $chatimes_12 > $bjtimes)
		{
			$week_list[$wkey]['time1'] = '1';
		}
		else
		{
			$week_list[$wkey]['time1'] = '0';
		}
		$chatimes_21 = strtotime(date('Y')."-".$week['name']." 15:00") - time();
		$chatimes_22 = strtotime(date('Y')."-".$week['name']." 19:00") - time();
		if ($chatimes_21 > $bjtimes || $chatimes_22 > $bjtimes)
		{
			$week_list[$wkey]['time2'] = '1';
		}
		else
		{
			$week_list[$wkey]['time2'] = '0';
		}
		$chatimes_31 = strtotime(date('Y')."-".$week['name']." 19:00") - time();
		$chatimes_32 = strtotime(date('Y')."-".$week['name']." 22:00") - time();
		if ($chatimes_31 > $bjtimes || $chatimes_32 > $bjtimes)
		{
			$week_list[$wkey]['time3'] = '1';
		}
		else
		{
			$week_list[$wkey]['time3'] = '0';
		}
	}
	$smarty->assign('week_list', $week_list);

        	//判断是否开启余额支付
	$sql = 'SELECT `is_surplus_open`'.
            'FROM `ecs_users`'.
            'WHERE `user_id` = \''.$_SESSION['user_id'].'\''.
            'LIMIT 1';
    $is_surplus_open = $GLOBALS['db']->getOne($sql);
	$smarty->assign('is_surplus_open', $is_surplus_open);
        
    /* 保存 session */
    $_SESSION['flow_order'] = $order;
}
elseif ($_REQUEST['step'] == 'select_pickinfo')
{
	include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

	$pid = (isset($_REQUEST['pid'])) ? intval($_REQUEST['pid']) : 0;
    $suppid = (isset($_REQUEST['sid'])) ? intval($_REQUEST['sid']) : 0;

	//$consignee = get_consignee($_SESSION['user_id']);
	$info = get_pickup_one_info($pid);

	$pickinfo = get_pickup_info($info['city_id'],$suppid);
	$retinfo = array();

	foreach($pickinfo as $key=>$val){
		$retinfo[$val['district_id']]['name'] = $val['region_name'];
		$retinfo[$val['district_id']]['info'][] = $val;
	}
	$smarty->assign('district', intval($info['district_id']));
	$smarty->assign('selectid', $pid);
	$smarty->assign('suppid', $suppid);
	$smarty->assign('pinfo', $retinfo);
	$result['content']     = $smarty->fetch('library/pickup.lbi',true);
	echo $json->encode($result);
    exit;
}elseif ($_REQUEST['step'] == 'save_point')
{
	include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);
	$pid = (isset($_REQUEST['pid'])) ? intval($_REQUEST['pid']) : 0;
    $suppid = (isset($_REQUEST['sid'])) ? intval($_REQUEST['sid']) : 0;
	$info = get_pickup_one_info($pid);
	$result['suppid']      = $suppid;
	$result['picktxt'] = "<input type='hidden' id='point".$suppid."' name='pickup_point[".$suppid."]' value='".$info['id']."'><span class='ziti'>自提点：</span><span>".$info['shop_name']."</span><a href='javascript:void(0);' onclick='show(\"pop\",".$suppid.")' class='revise'>修改</a>";
	echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_shipping')
{
    
    /*------------------------------------------------------ */
    //-- 改变配送方式
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();
	$recid = (isset($_REQUEST['recid'])) ? intval($_REQUEST['recid']) : 0;
        $suppid = (isset($_REQUEST['suppid'])) ? intval($_REQUEST['suppid']) : 0;
        if($recid){
        	$order['shipping_pay'][$suppid] = $recid;
        }
        $_SESSION['flow_order'] = $order;
//        $order['shipping_id'] = intval($_REQUEST['shipping']);
//        $regions = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
//        $shipping_info = shipping_area_info($order['shipping_id'], $regions);

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);
        /* 取得可以得到的积分和红包 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
		$smarty->assign('total_bonus',    price_format(get_total_bonus($total['goods_price_supplier']), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['cod_fee']     = $shipping_info['pay_fee'];
        if (strpos($result['cod_fee'], '%') === false)
        {
            $result['cod_fee'] = price_format($result['cod_fee'], false);
        }
		$result['shipping_name'] = $shipping_info['shipping_name'] ;
        $result['need_insure'] = ($shipping_info['insure'] > 0 && !empty($order['need_insure'])) ? 1 : 0;
        $result['content']     = $smarty->fetch('library/order_total.lbi');
//	$result['supplier_shipping']     = $smarty->fetch('library/order_supplier_shipping.lbi');
//	$result['pickup_content']     =  '';
//	if(intval($_REQUEST['pickup']) > 0){
//		$sql = 'select * from ' . $ecs->table('pickup_point') .
//			' where city_id=' . $consignee['city'];
//		$pickup_point_list = $db->getAll($sql);
//			$smarty->assign('pickup_point_list',      $pickup_point_list);
//			$result['pickup_content']     = $smarty->fetch('library/pickup.lbi');
//	}
		$result['suppid']      = $suppid;
		$result['picktxt']     = '';
		if(is_pups($recid)){
			// if(isset($consignee['city']) && intval($consignee['city'])>0){
				$pickinfo = get_pickup_info(intval($consignee['city']),$suppid);
				if($pickinfo){
					$result['picktxt'] = "<input type='hidden' id='point".$suppid."' name='pickup_point[".$suppid."]' value='0'><a href='javascript:void(0);' onclick='show(\"pop\",".$suppid.")' class='pickup_point_btn'>请选择自提门店</a>";
				}
				foreach($pickinfo as $pkey=>$pval){
					if($consignee['district'] == $pval['district_id']){
						$result['picktxt'] = "<input type='hidden' id='point".$suppid."' name='pickup_point[".$suppid."]' value='".$pval['id']."'><span class='ziti'>自提点：</span><span>".$pval['shop_name']."</span><a href='javascript:void(0);' onclick='show(\"pop\",".$suppid.")' class='revise'>修改</a>";
					}
				}
			// }
		}
    }
    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_insure')
{
    /*------------------------------------------------------ */
    //-- 选定/取消配送的保价
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['need_insure'] = intval($_REQUEST['insure']);

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和红包 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_payment')
{
    /*------------------------------------------------------ */
    //-- 改变支付方式
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['pay_id'] = intval($_REQUEST['payment']);
        $payment_info = payment_info($order['pay_id']);
        $result['pay_code'] = $payment_info['pay_code'];
	$order['pay_code'] = $payment_info['pay_code'];
        $result['pay_name'] = $payment_info['pay_name'];
				
        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);
	$smarty->assign('real_goods_count', $total['real_goods_count']);

        /* 取得可以得到的积分和红包 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}

/* 原余额支付方法
elseif ($_REQUEST['step'] == 'check_surplus_open')
{
    $pay_id = $_SESSION['flow_order']['pay_id'];
    $sql_paycode = "SELECT pay_code FROM " . $ecs->table('ecsmart_payment') . " WHERE pay_id = " . $pay_id;
    $pay_code = $db->getOne($sql_paycode);
    $surplus = $_SESSION['flow_order']['surplus'];
    if($pay_code == 'balance'||$surplus > 0){
        $sql = 'SELECT `is_surplus_open`'.
            'FROM `ecs_users`'.
            'WHERE `user_id` = \''.$_SESSION['user_id'].'\''.
            'LIMIT 1';
        $is_surplus_open = $GLOBALS['db']->getOne($sql);
        echo $is_surplus_open;
    }
    else
    {
        echo '0';
    }
    exit;
}
*/

/*余额额支付密码_添加_START_www.yshop100.com*/
elseif ($_REQUEST['step'] == 'check_surplus_open')
{
    $pay_code = $_SESSION['flow_order']['pay_code'];
    $surplus = $_SESSION['flow_order']['surplus'];
    if($pay_code == 'balance'||$surplus > 0){
        $sql = 'SELECT `is_surplus_open`'.
            'FROM `ecs_users`'.
            'WHERE `user_id` = \''.$_SESSION['user_id'].'\''.
            'LIMIT 1';
        $is_surplus_open = $GLOBALS['db']->getOne($sql);
        echo $is_surplus_open;
    }
    else
    {
        echo '0';
    }
    exit;
}

elseif ($_REQUEST['step'] == 'verify_surplus_password')
{
    $sql = 'SELECT COUNT( * )'.
            'FROM `ecs_users`'.
            'WHERE `user_id` = \''.$_SESSION['user_id'].'\''.
            'AND `surplus_password` = \''.md5($_GET['surplus_password']).'\'';
    $count = $GLOBALS['db']->getOne($sql);
    echo $count;
    exit;
}

elseif ($_REQUEST['step'] == 'verify_quick_password')
{
    $sql = 'SELECT pwd FROM `ecs_users_period` WHERE `user_id` = \''.$_SESSION['user_id'].'\''.
            'AND `pwd` = \''.md5(md5($_GET['pwd'])).'\'';
    $count = $GLOBALS['db']->getOne($sql);
    echo $count;
    exit;
}

elseif ($_REQUEST['step'] == 'select_pack')
{
    /*------------------------------------------------------ */
    //-- 改变商品包装
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['pack_id'] = intval($_REQUEST['pack']);

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和红包 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $total['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['pack_fee_formated'] = $total['pack_fee_formated'];
        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'select_card')
{
    /*------------------------------------------------------ */
    //-- 改变贺卡
    /*------------------------------------------------------ */

    include_once('includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '', 'content' => '', 'need_insure' => 0);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        $order['card_id'] = intval($_REQUEST['card']);

        /* 保存 session */
        $_SESSION['flow_order'] = $order;

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 取得可以得到的积分和红包 */
        $smarty->assign('total_integral', cart_amount(false, $flow_type) - $order['bonus'] - $total['integral_money']);
        $smarty->assign('total_bonus',    price_format(get_total_bonus(), false));

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }
        $result['card_fee_formated'] = $total['card_fee_formated']; 
        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    echo $json->encode($result);
    exit;
}
elseif ($_REQUEST['step'] == 'change_surplus')
{    /*------------------------------------------------------ */
    //-- 改变余额
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');

    $surplus   = floatval($_GET['surplus']);
    //$result['suppid'] = $suppid		= intval($_GET['suppid']);
    $user_info = user_info($_SESSION['user_id']);
    
    /* 取得订单信息 */
    $order = flow_order_info();
    //$surplus_info = (isset($order['surplus_info'])) ? $order['surplus_info'] : array();
	//$surplus_info[$suppid] = $surplus;

    //if ($user_info['user_money'] + $user_info['credit_line'] < array_sum($surplus_info))
	if ($user_info['user_money'] + $user_info['credit_line'] < $surplus)
    {
        $result['error'] = $_LANG['surplus_not_enough'];
    }
    else
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 获得收货人信息 */
        $consignee = get_consignee($_SESSION['user_id']);

        /* 对商品信息赋值 */
        $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计
        
        if (empty($cart_goods))
        {
            $result['error'] = $_LANG['no_goods_in_cart'];
        }
        else
        {
            
		    
		    //$order['surplus_info'] = $surplus_info;
            $order['surplus'] = $surplus;//array_sum($surplus_info);//$surplus;

            /* 计算订单的费用 */
            $total = order_fee($order, $cart_goods, $consignee);
			
            $smarty->assign('total', $total);

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $smarty->assign('is_group_buy', 1);
            }

			if($total['amount'] <= 0 && $surplus){
				$result['surplus'] = $total['surplus'];
				$result['show'] = true;
			}else{
				$result['surplus'] = $surplus;
				$result['show'] = false;
			}

            $result['content'] = $smarty->fetch('library/order_total.lbi');
        }
    }

    $json = new JSON();
    die($json->encode($result));
}

elseif ($_REQUEST['step'] == 'change_integral')
{
    /*------------------------------------------------------ */
    //-- 改变积分
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');

    $points    = floatval($_GET['points']);
    $result['suppid'] = $suppid		= intval($_GET['suppid']);
    $user_info = user_info($_SESSION['user_id']);

    /* 取得订单信息 */
    $order = flow_order_info();
    
    $integral_info = (isset($order['integral_info'])) ? $order['integral_info'] : array();
    $integral_info[$suppid] = $points;
    
    $order['integral_info'] = $integral_info;

    $flow_points = flow_available_points();  // 该订单允许使用的积分
    $user_points = $user_info['pay_points']; // 用户的积分总数
    
    //所有订单的总积分
    $points_all = array_sum($integral_info);

    if ($points_all > $user_points)
    {
        $result['error'] = $_LANG['integral_not_enough'];
    }
    elseif ($points > $flow_points[$suppid])
    {
        $result['error'] = sprintf($_LANG['integral_too_much'], $flow_points[$suppid]);
    }
    else
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        $order['integral'] = $points_all;

        /* 获得收货人信息 */
        $consignee = get_consignee($_SESSION['user_id']);

        /* 对商品信息赋值 */
        $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

        if (empty($cart_goods))
        {
            $result['error'] = $_LANG['no_goods_in_cart'];
        }
        else
        {
			$result['integralnum'] = $order['integral'];
            /* 计算订单的费用 */
            $total = order_fee($order, $cart_goods, $consignee);
            $smarty->assign('total',  $total);
            $smarty->assign('config', $_CFG);

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS)
            {
                $smarty->assign('is_group_buy', 1);
            }

            $result['content'] = $smarty->fetch('library/order_total.lbi');
            $result['error'] = '';
        }
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_bonus')
{
    /*------------------------------------------------------ */
    //-- 改变红包
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

	$result['suppid'] = $suppid = intval($_GET['suppid']);

        /* 取得订单信息 */
        $order = flow_order_info();

        $bonus = bonus_info(intval($_GET['bonus']));

        if ((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id'] && $bonus['supplier_id'] == $suppid) || intval($_GET['bonus']) == 0)
        {
		$bonus_info = (isset($order['bonus_id_info'])) ? $order['bonus_id_info'] : array();
		if(intval($_GET['bonus']) == 0){
			unset($bonus_info[$suppid]);
		}else{
			$bonus_info[$suppid] = $_GET['bonus'];
		}
		$order['bonus_id_info'] = $bonus_info = array_filter($bonus_info);
		$order['bonus_id'] = implode(',',$bonus_info);

		$bonus_sn_info = (isset($order['bonus_sn_info'])) ? $order['bonus_sn_info'] : array();
		unset($bonus_sn_info[$suppid]);
		$order['bonus_sn_info'] = $bonus_sn_info = array_filter($bonus_sn_info);
		$order['bonus_sn'] = implode(',',$bonus_sn_info);
        }
        else
        {
            $order['bonus_id'] = 0;
            $result['error'] = $_LANG['invalid_bonus'];
        }

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }
        $result['type_money'] = $total['bonus_formated'];
        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }

    $json = new JSON();
    die($json->encode($result));
}
elseif ($_REQUEST['step'] == 'change_needinv')
{
    /*------------------------------------------------------ */
    //-- 改变发票的设置
    /*------------------------------------------------------ */
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');
    $json = new JSON();
    $_GET['inv_type'] = !empty($_GET['inv_type']) ? json_str_iconv(urldecode($_GET['inv_type'])) : '';
    $_GET['invPayee'] = !empty($_GET['invPayee']) ? json_str_iconv(urldecode($_GET['invPayee'])) : '';
    $_GET['inv_content'] = !empty($_GET['inv_content']) ? json_str_iconv(urldecode($_GET['inv_content'])) : '';

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
        die($json->encode($result));
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();

        if (isset($_GET['need_inv']) && intval($_GET['need_inv']) == 1)
        {
            $order['need_inv']    = 1;
            $order['inv_type']    = trim(stripslashes($_GET['inv_type']));
            $order['inv_payee']   = trim(stripslashes($_GET['inv_payee']));
            $order['inv_content'] = trim(stripslashes($_GET['inv_content']));
        }
        else
        {
            $order['need_inv']    = 0;
            $order['inv_type']    = '';
            $order['inv_payee']   = '';
            $order['inv_content'] = '';
        }

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);
        $smarty->assign('total', $total);

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        die($smarty->fetch('library/order_total.lbi'));
    }
}
elseif ($_REQUEST['step'] == 'change_oos')
{
    /*------------------------------------------------------ */
    //-- 改变缺货处理时的方式
    /*------------------------------------------------------ */

    /* 取得订单信息 */
    $order = flow_order_info();

    $order['how_oos'] = intval($_GET['oos']);

    /* 保存 session */
    $_SESSION['flow_order'] = $order;
}
elseif ($_REQUEST['step'] == 'check_surplus')
{
    /*------------------------------------------------------ */
    //-- 检查用户输入的余额
    /*------------------------------------------------------ */
    $surplus   = floatval($_GET['surplus']);
    $user_info = user_info($_SESSION['user_id']);

    if (($user_info['user_money'] + $user_info['credit_line'] < $surplus))
    {
        die($_LANG['surplus_not_enough']);
    }

    exit;
}
elseif ($_REQUEST['step'] == 'check_integral')
{
    /*------------------------------------------------------ */
    //-- 检查用户输入的余额
    /*------------------------------------------------------ */
    $points      = floatval($_GET['integral']);
    $user_info   = user_info($_SESSION['user_id']);
    $flow_points = flow_available_points();  // 该订单允许使用的积分
    $user_points = $user_info['pay_points']; // 用户的积分总数

    if ($points > $user_points)
    {
        die($_LANG['integral_not_enough']);
    }

    if ($points > $flow_points)
    {
        die(sprintf($_LANG['integral_too_much'], $flow_points));
    }

    exit;
}
/*------------------------------------------------------ */
//-- 完成所有订单操作，提交到数据库
/*------------------------------------------------------ */
elseif ($_REQUEST['step'] == 'done')
{	
    include_once('includes/lib_clips.php');
    include_once('includes/lib_payment.php');

	/* 代码增加_start  By www.yshop100.com */
	$id_ext ="";
	if ($_SESSION['sel_cartgoods'])
	{
		$id_ext = " AND rec_id in (". $_SESSION['sel_cartgoods'] .") ";
	}
	/* 代码增加_end  By www.yshop100.com */

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
    
    /* 代码增加_end  By www.yshop100.com */
	$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
	$sql_where .= $id_ext;


    /* 检查购物车中是否有商品 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') .
        " WHERE $sql_where " .
        "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
    /* 代码增加_end  By www.yshop100.com */
    if ($db->getOne($sql) == 0)
    {
        show_message($_LANG['no_goods_in_cart'], '', '', 'warning');
    }
	$sql = "SELECT * FROM ".$ecs->table('cart')."WHERE $sql_where AND parent_id = 0 AND is_gift > 0 AND rec_type = '$flow_type'";
	$res = $db->getAll($sql);
	foreach($res as $key=>$value)
	{
		$goodsid = $value['goods_id'];
		$sql = "SELECT goods_number FROM ".$ecs->table('goods')."WHERE goods_id = $goodsid";
		$rec = $db->getOne($sql);
		if($value['goods_number'] > $rec)
		{
			show_message("赠品  ".$value['goods_name']."  已经赠送完!");
		}
	}
	
    /* 检查商品库存 */
    /* 如果使用库存，且下订单时减库存，则减少库存 */
    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
    {
        $cart_goods_stock = get_cart_goods($id_ext);
        $_cart_goods_stock = array();
		foreach ($cart_goods_stock['goods_list'] as $values)
		{
			foreach ($values['goods_list'] as $value)
			{
				$_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
			}
		}
        flow_cart_stock($_cart_goods_stock);
		$order_log_goods_info = $cart_goods_stock;
        unset($cart_goods_stock, $_cart_goods_stock);
    }

    /*
     * 检查用户是否已经登录
     * 如果用户已经登录了则检查是否有默认的收货地址
     * 如果没有登录则跳转到登录和注册页面
     */
    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
    {
        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
        ecs_header("Location: flow.php?step=login\n");
        exit;
    }

    $consignee = get_consignee($_SESSION['user_id']);

	/* 订单中的商品 */
    $cart_goods = cart_goods($flow_type);
    
	$cart_goods_new = array();
    if(count($cart_goods)>0){
    	foreach($cart_goods as $key => $val){
    		$cart_goods_new[$val['supplier_id']]['goodlist'][$val['rec_id']] = $val;
    		$cart_goods_new[$val['supplier_id']]['referer'] = $val['seller'];
    	}
    }
    //echo "<pre>";
    //print_r($cart_goods);
    //print_r($cart_goods_new);

    if (empty($cart_goods))
    {
        show_message($_LANG['no_goods_in_cart'], $_LANG['back_home'], './', 'warning');
    }

    /* 检查商品总额是否达到最低限购金额 */
    if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS) < $_CFG['min_goods_amount'])
    {
        show_message(sprintf($_LANG['goods_amount_not_enough'], price_format($_CFG['min_goods_amount'], false)));
    }

	//获取余额支付的id
	$sql = 'SELECT pay_id ' .
            ' FROM ' . $GLOBALS['ecs']->table('payment') .
            ' WHERE enabled = 1 and pay_code="balance"';
	$pay_balance_id = $GLOBALS['db']->getOne($sql);


    /*
    $orderinfo = flow_order_info();
    
    echo "<pre>";
    print_r($orderinfo);
    print_r($_POST);
    exit;*/

	$_POST['how_oos'] = isset($_POST['how_oos']) ? intval($_POST['how_oos']) : 0;
	$_POST['card_message'] = isset($_POST['card_message']) ? compile_str($_POST['card_message']) : '';
	$_POST['inv_type'] = !empty($_POST['inv_type']) ? compile_str($_POST['inv_type']) : '';
	$_POST['inv_payee'] = isset($_POST['inv_payee']) ? compile_str($_POST['inv_payee']) : '';
	$_POST['inv_content'] = isset($_POST['inv_content']) ? compile_str($_POST['inv_content']) : '';
	$_POST['postscript'] = isset($_POST['postscript']) ? compile_str($_POST['postscript']) : '';
	
	$order_integral = isset($_POST['integral']) ? $_POST['integral'] : array();
	$order_bonus_id = isset($_POST['bonus']) ? $_POST['bonus'] : array();
	$order_bonus_sn = isset($_POST['bonus_sn']) ? $_POST['bonus_sn'] : array();
	$order_surplus = isset($_POST['surplus']) ? $_POST['surplus'] : 0;
    
    //此订单拆分订单后的订单信息
    $order_info = array();
    //组装拆分的子订单数组信息start
    foreach ($cart_goods_new as $ckey=>$cval){
    	
    	$cart_goods = $cval['goodlist'];

	    
	    $order = array(
	        //'shipping_id'     => intval($_POST['shipping']),
            'quickpay_period'          => isset($_POST['quickpay_list']) ? intval($_POST['quickpay_list']) : 0,
	        'pay_id'          => intval($_POST['payment']),
	        'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,
	        'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,
	        'card_message'    => trim($_POST['card_message']),
	        'surplus'         => $order_surplus,//isset($order_surplus[$ckey]) ? floatval($order_surplus[$ckey]) : 0.00,
	        'integral'        => isset($order_integral[$ckey]) ? intval($order_integral[$ckey]) : 0,
	        'bonus_id'        => isset($order_bonus_id[$ckey]) ? intval($order_bonus_id[$ckey]) : 0,
	        'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
	        /*增值税发票_删除_START_www.yshop100.com*/
        	//'inv_type'        => $_POST['inv_type'],
        	//'inv_payee'       => trim($_POST['inv_payee']),
        	//'inv_content'     => $_POST['inv_content'],
			/*增值税发票_删除_END_www.yshop100.com*/
	        'postscript'      => trim($_POST['postscript']),
	        'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
	        'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
	        'user_id'         => $_SESSION['user_id'],
	        'add_time'        => gmtime(),
	        'order_status'    => OS_UNCONFIRMED,
	        'shipping_status' => SS_UNSHIPPED,
	        'pay_status'      => PS_UNPAYED,
	        'agency_id'       => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'])),
 	
            'supplier_id'	  => $ckey

	        );
		$order['defaultbank'] = $_POST['www_yshop100_com_bank'] ? trim($_POST['www_yshop100_com_bank']) : "";
	    
        /* 检查收货人信息是否完整 */
        $_shipping = shipping_info($_POST['pay_ship'][$ckey]);
        if($_shipping['shipping_code'] != 'pups' && !check_consignee_info($consignee, $flow_type)){
            /* 如果不完整则转向到收货人信息填写界面 */
            ecs_header("Location: flow.php?step=consignee\n");
            exit;
        }elseif($_shipping['shipping_code'] == 'pups' && !$_POST['pickup_point'][0]){
            /* 自提订单没有选门店 */
            show_message("请选择自提门店");
        }

		/*增值税发票_添加_START_www.yshop100.com*/
    	/*发票信息*/
		if($_REQUEST['inv_type'] == 'normal_invoice')
		{
			$inv_arr = array('inv_type','inv_payee_type','inv_payee','inv_content');
			if(isset($_REQUEST['inv_payee_type']) && $_REQUEST['inv_payee_type'] == 'individual')
			{
				 $order['inv_payee'] = '个人';
			}
		}
		elseif($_REQUEST['inv_type'] == 'vat_invoice')
		{
			$inv_arr = array('inv_type','inv_content','vat_inv_company_name',
				'vat_inv_taxpayer_id','vat_inv_registration_address','vat_inv_registration_phone',
				'vat_inv_deposit_bank','vat_inv_bank_account','inv_consignee_name',
				'inv_consignee_phone','inv_consignee_province','inv_consignee_city',
				'inv_consignee_district','inv_consignee_address');
		}
		foreach($inv_arr as $key)
		{
			$value = !empty($_REQUEST[$key])?trim($_REQUEST[$key]):'';;
			if(!empty($value))
			{
				$order[$key] = $value;
			}
		}

		/*增值税发票_添加_END_www.yshop100.com*/
		
	    /* 扩展信息 */
	    if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS)
	    {
	        $order['extension_code'] = $_SESSION['extension_code'];
	        $order['extension_id'] = $_SESSION['extension_id'];
	    }
	    else
	    {
	        $order['extension_code'] = '';
	        $order['extension_id'] = 0;
	    }

		/*检查配送方式是否选择*/
            // 如果是虚拟商品不需要选择配送方式
        if( $_SESSION['extension_code'] != 'virtual_good'){
	    if(!isset($_POST['pay_ship'][$ckey])){
	    	show_message('请选择各个商家的配送方式！');
	    }else{
	    	$shipid = $db->getOne("select shipping_id from ".$ecs->table('shipping')." where shipping_id=".$_POST['pay_ship'][$ckey]." and supplier_id=".$ckey);
	    	if($shipid){
	    		$order['shipping_id'] = intval($shipid);
	    	}else{
	    		show_message('配送方式存在不可用，请重新选择！');
	    	}
	    }
        }
	
	    /* 检查积分余额是否合法 */
	    $user_id = $_SESSION['user_id'];
	    if ($user_id > 0)
	    {
	        $user_info = user_info($user_id);
	
	        $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
	        if ($order['surplus'] < 0)
	        {
	            $order['surplus'] = 0;
	        }
	
	        // 查询用户有多少积分
	        $flow_points = flow_available_points();  // 该订单允许使用的积分
	        $user_points = $user_info['pay_points']; // 用户的积分总数
	
	        $order['integral'] = min($order['integral'], $user_points, $flow_points[$ckey]);
	        if ($order['integral'] < 0)
	        {
	            $order['integral'] = 0;
	        }
	    }
	    else
	    {
	        $order['surplus']  = 0;
	        $order['integral'] = 0;
	    }
	
	    /* 检查红包是否存在 */
	    if ($order['bonus_id'] > 0)
	    {
	        $bonus = bonus_info($order['bonus_id']);
	        //|| $bonus['min_goods_amount'] > cart_amount_new(array_keys($cart_goods),true, $flow_type)
	
	        if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 )
	        {
	            $order['bonus_id'] = 0;
	        }else{
	        	
	        }
	    }
	    elseif (isset($_POST['bonus_sn'][$ckey]))
	    {	
	        $bonus_sn = intval($_POST['bonus_sn'][$ckey]);
	        $bonus = bonus_info(0, $bonus_sn);
			$now = gmtime();
	        //|| $bonus['min_goods_amount'] > cart_amount_new(array_keys($cart_goods),true, $flow_type)
	        if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0  || $now > $bonus['use_end_date'])
	        {
	        }
	        else
	        {
	            if ($user_id > 0)
	            {
	                $sql = "UPDATE " . $ecs->table('user_bonus') . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
					$db->query($sql);
	            }
	            $order['bonus_id'] = '';//$bonus['bonus_id'];
			    //$order['bonus_id'] = $bonus['bonus_id'];
	            $order['bonus_sn'] = $bonus_sn;
	        }
	    }
	    
		/* 判断是不是实体商品 */
	    foreach ($cart_goods AS $val)
	    {

	        /* 统计实体商品的个数 */
	        if ($val['is_real'])
	        {
	            $is_real_good=1;
	        }
	    }
	    if(isset($is_real_good))
	    {
	        $sql="SELECT shipping_id FROM " . $ecs->table('shipping') . " WHERE shipping_id=".$order['shipping_id'] ." AND enabled =1"; 
	        if(!$db->getOne($sql))
	        {
	           show_message($_LANG['flow_no_shipping']);
	        }
	    }
	
	    /* 收货人信息 */
	    foreach ($consignee as $key => $value)
	    {
	        $order[$key] = addslashes($value);
	    }
        //自提订单且没有收货人信息，记录手机号码
        if(!$order['mobile'] && $_POST['mobile']){
            $order['mobile'] = $_POST['mobile'];
        }
	
		/* 代码增加_start  By  www.yshop100.com */
		$order['best_time'] = isset($_POST['best_time']) ? trim($_POST['best_time']) : '';
		/* 代码增加_end  By  www.yshop100.com */

		//配送方式的钱算到里面
	    $order['shipping_pay'][$ckey] = $_POST['pay_ship'][$ckey];

        //判断供应商是否有汇率
        $cart_goods_first = current($cart_goods);
        $order['suppliers_id'] = $cart_goods_first['suppliers_id'];
        $is_exchange = $GLOBALS['db']->getOne("SELECT is_exchange FROM " . $ecs->table('suppliers') . " WHERE suppliers_id = '". $order['suppliers_id'] ."'");
	   
	    /* 订单中的总额 */
	    $total = order_fee($order, $cart_goods, $consignee);

		unset($order['shipping_pay'][$ckey]);// 去掉这条信息以免影响下订单操作

		$order['exchange_rate'] = $GLOBALS['_CFG']['exchange_rate'];
	    $order['bonus']        = $total['bonus'];
        $order['exchange_bonus'] = $order['bonus_exchange'] = $total['bonus_exchange'];
        $order['goods_amount'] = $total['goods_price'];
        $order['goods_amount_exchange'] = $total['goods_price_exchange'];
        $order['discount']     = $total['discount'];
        $order['exchange_discount'] = $total['discount_exchange'];
        $order['surplus']      = $total['surplus'];
		if($order['surplus'] > 0){
			//前台的总余额减去每一个平台方或入驻商家使用的余额,结果做为下一个商家使用的余额
			$order_surplus = $order_surplus - $order['surplus'];
		}
		if($total['amount'] <= 0){
			//余额全部支付，让支付方式修改为余额支付
			$order['pay_id'] = $pay_balance_id;//余额支付方式的id
		}
	    $order['tax']          = $total['tax'];
        $order['exchange_tax'] = $order['tax_exchange'] = $total['tax_exchange'];
	
	    // 购物车中的商品能享受红包支付的总额
	    $discount_amout = compute_discount_amount($ckey);

	    // 红包和积分最多能支付的金额为商品总额
	    $temp_amout = $order['goods_amount'] - $discount_amout;
        $temp_amout_exchange = $order['goods_amount_exchange'] - $discount_amout_exchange;

	    if ($temp_amout <= 0)
	    {
	        $order['bonus_id'] = 0;
	    }
	
	    /* 配送方式 */
	    if ($order['shipping_id'] > 0)
	    {
	        $shipping = shipping_info($order['shipping_id']);
	        $order['shipping_name'] = addslashes($shipping['shipping_name']);
			//如果是门店自提，订单需要做特殊标识
	        if($shipping['shipping_code'] == 'pups'){
	        	$order['is_pickup'] = $order['shipping_id'];
	        }
	    }
	    $order['shipping_fee'] = $total['shipping_fee'];
        $order['exchange_shipping_fee'] = $order['shipping_fee_exchange'] = $total['shipping_fee_exchange'];
        $order['insure_fee']   = $total['shipping_insure'];
        $order['insure_fee_exchange']   = $total['shipping_insure_exchange'];
	
	    /* 支付方式 */
	    if ($order['pay_id'] > 0)
	    {
	        $payment = payment_info($order['pay_id']);
	        $order['pay_name'] = addslashes($payment['pay_name']);
	    }else{
	    	show_message('支付方式必须选择一项!');
	    }
	    $order['pay_fee'] = $total['pay_fee'];
        $order['pay_fee_exchange'] = $total['pay_fee_exchange'];
        $order['cod_fee'] = $total['cod_fee'];
        $order['cod_fee_exchange'] = $total['cod_fee_exchange'];
	
	    /* 商品包装 */
	    if ($order['pack_id'] > 0)
	    {
	        $pack               = pack_info($order['pack_id']);
	        $order['pack_name'] = addslashes($pack['pack_name']);
	    }
	    $order['pack_fee'] = $total['pack_fee'];
        $order['pack_fee_exchange'] = $total['pack_fee_exchange'];
	
	    /* 祝福贺卡 */
	    if ($order['card_id'] > 0)
	    {
	        $card               = card_info($order['card_id']);
	        $order['card_name'] = addslashes($card['card_name']);
	    }
	    $order['card_fee']      = $total['card_fee'];
        $order['card_fee_exchange']      = $total['card_fee_exchange'];
	
	    $order['order_amount']  = number_format($total['amount'], 2, '.', '');
        $order['exchange_amount'] = $order['order_amount_exchange'] = number_format($total['amount_exchange'], 2, '.', '');
	
		/*增值税发票_添加_START_www.yshop100.com*/
    	/*发票金额*/
    	$order['inv_money'] =  $order['order_amount'] ;
    	/*增值税发票_添加_END_www.yshop100.com*/
		
    	/* 如果全部使用余额支付，检查余额是否足够 */
	    if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0)
		//if ($order['order_amount'] > 0)
	    {
	        if($order['surplus'] >0) //余额支付里如果输入了一个金额
	        {
	            $order['order_amount'] = $order['order_amount'] + $order['surplus'];
                $order['order_amount_exchange'] = $order['order_amount_exchange'] + $order['surplus_exchange'];
                $order['surplus'] = 0;
                $order['surplus_exchange'] = 0;
	        }
	        if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
	        {
	            show_message($_LANG['balance_not_enough']);
	        }
	        else
	        {
	            $order['surplus'] = $order['order_amount'];
				//是否开启余额变动给客户发短信-用户消费
				if($_CFG['sms_user_money_change'] == 1)
				{
					$sql = "SELECT user_money,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
					$users = $GLOBALS['db']->getRow($sql); 
					$content = sprintf($_CFG['sms_use_balance_reduce_tpl'],date("Y-m-d H:i:s",gmtime()),$order['order_amount'],$users['user_money'],$_CFG['sms_sign']);
					if($users['mobile_phone'])
					{
						require_once (ROOT_PATH . 'sms/sms.php');
						sendSMS($users['mobile_phone'],$content);
					}
				}
	            $order['order_amount'] = 0;
	        }
	    }
	
	    /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
	    if ($order['order_amount'] <= 0)
	    {
	        $order['order_status'] = OS_CONFIRMED;
	        $order['confirm_time'] = gmtime();
	        $order['pay_status']   = PS_PAYED;
	        $order['pay_time']     = gmtime();
			$order['order_amount'] = 0;
	       //$order['order_amount'] = $order['surplus'];//把支付的金额存进order_amount这个中
	    }
	
	    $order['integral_money']   = $total['integral_money'];
        $order['integral_money_exchange']   = $total['integral_money_exchange'];
	    $order['integral']         = $total['integral'];
	
	    if ($order['extension_code'] == 'exchange_goods')
	    {
	        $order['integral_money']   = 0;
            $order['integral_money_exchange']   = 0;
	        $order['integral']         = $total['exchange_integral'];
	    }
	
	    $order['from_ad']          = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
	    //$order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : '';
	    $order['referer']          = $cval['referer'];
	
	    /* 记录扩展信息 */
	    if ($flow_type != CART_GENERAL_GOODS)
	    {
	        $order['extension_code'] = $_SESSION['extension_code'];
	        $order['extension_id'] = $_SESSION['extension_id'];
	    }
	
	    $affiliate = unserialize($_CFG['affiliate']);
	    if(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1)
	    {
	        //推荐订单分成
	        $parent_id = get_affiliate();
	        if($user_id == $parent_id)
	        {
	            $parent_id = 0;
	        }
	    }
	    elseif(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0)
	    {
	        //推荐注册分成
	        $parent_id = 0;
	    }
	    else
	    {
	        //分成功能关闭
	        $parent_id = 0;
	    }
	    $order['parent_id'] = $parent_id;
		
	    	/* 代码增加_start     */
		/*  自提功能
			获取订单确认页选择的自提点
		*/
		$pickup_point = isset($_POST['pickup_point'][$ckey]) ? $_POST['pickup_point'][$ckey] : 0;
		if($pickup_point > 0)
			$order['is_pickup'] = 1;
		else
			$order['is_pickup'] = 0;
		$order['pickup_point'] = $pickup_point;
		/* 代码增加_end     */
		//$order['order_sn'] = get_order_sn();
		//file_put_contents('./inserttt'.$order['order_sn'].'.txt',var_export($order,true));
		
		if(count($order)>0){
			$order_info[$ckey] = $order;
		}
		unset($order);
    }
    //组装拆分的子订单数组信息end
    
    
    
    //判断是否拆分为多个订单,多个订单就生成父订单id号
    $del_patent_id = 0;
    if(count($order_info)>1){
    	$error_no = 0;
	    do
	    {
	        $save['order_sn'] = get_order_sn(); //获取新订单号
	        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $save, 'INSERT');
            //添加订单log日志
            //$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info_log'), $save, 'INSERT');
	        $error_no = $GLOBALS['db']->errno();

//	        if ($error_no > 0 && $error_no != 1062)
//	        {
//	            die($GLOBALS['db']->errorMsg());
//	        }
	    }
	    while ($error_no == 1062); //如果是订单号重复则重新提交数据
	    $del_patent_id = $parent_order_id = $db->insert_id();
    }else{
    	$parent_order_id = 0;
    }
    
    $all_order_amount = 0;//记录订单所需支付的总金额
    //用来展示用的数组数据
    $split_order = array();
    $split_order['sub_order_count'] = count($order_info);
    //生成订单
	//$payment_www_com['www_yshop100_com_alipay_bank'] = $_POST['www_yshop100_com_bank'] ? trim($_POST['www_yshop100_com_bank']) : "www_yshop100_com";

    foreach($order_info as $ok=>$order){
    	
    	$cart_goods = $cart_goods_new[$ok]['goodlist']; 
    	
        if($cart_goods){
        	$id_ext_new = " AND rec_id in (". implode(',',array_keys($cart_goods)) .") ";
        }
    	
    	//获取佣金id
    	$order['rebate_id'] = 0;//get_order_rebate($ok);
    	
    	//下单来源
		$order['froms'] = WEB_FROM;
    	
    	$order['parent_order_id'] = $parent_order_id;
	     /* 插入订单表 */
	    $error_no = 0;
	    do
	    {
	        $order['order_sn'] = get_order_sn(); //获取新订单号
			
	        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'INSERT');
	        //$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info_log'), $order, 'INSERT');

	        $error_no = $GLOBALS['db']->errno();

	        if ($error_no > 0 && $error_no != 1062)
	        {
	            die($GLOBALS['db']->errorMsg());
	        }
	    }
	    while ($error_no == 1062); //如果是订单号重复则重新提交数据
	
	    $new_order_id = $db->insert_id();
	    $order['order_id'] = $new_order_id;
	    
	    $parent_order_id = ($parent_order_id>0) ? $parent_order_id : $new_order_id;
		
		#订单提交成功记录提交的信息
		$order_log_data = ['goods_info' => $order_log_goods_info,'order_info'=>['order_id'=>$new_order_id,'order_amount'=>$order['goods_amount'],'money_paid' => $order['money_paid'],'bonus'=>$order['bonus'],'buyer'=>$order['consignee']]];
		RecordsFile( ROOT_PATH.'data/logs/','order-logs-'.date('Y-m-d').'.txt',$order_log_data);

	    /* 插入订单商品 下面这个SQL有修改   注意末尾那个字段 */
	    /* 代码增加_start  By www.yshop100.com */
	    $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
	                "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, cost_price, ".
	                "goods_price,split_money, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, package_attr_id) ".
	            " SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, cost_price, ".
	                "goods_price,split_money, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id, package_attr_id ".
	            " FROM " .$ecs->table('cart') .
	            " WHERE $sql_where AND rec_type = '$flow_type' $id_ext_new ";
	    /* 代码增加_end  By www.yshop100.com */
	    $ordergoodsresult=$db->query($sql);
		if($ordergoodsresult<1)//商品表没保存成功清主表
		{
			$sql = "delete from ". $GLOBALS['ecs']->table('order_info') ." where user_id='".$_SESSION['user_id']."' and order_id='$new_order_id'";
			$db->query($sql);
			show_message('系统繁忙请稍候再试！', $_LANG['back_home'], './', 'warning'); 
		}

        //单个商品折算汇率价格
        $sql = "SELECT rec_id, goods_price FROM " .$ecs->table('order_goods'). " WHERE order_id = '". $new_order_id ."'";
        $rows = $GLOBALS['db']->getAll($sql);
        foreach ($rows as $row) {
            if($row['goods_price'] > 0){
                $exchange_price  = number_format($row['goods_price']*$GLOBALS['_CFG']['exchange_rate'], 2, '.', '');
                $sql = "UPDATE ". $ecs->table('order_goods') ." SET exchange_price = '". $exchange_price ."' WHERE rec_id = '". $row['rec_id'] ."'";
                $db->query($sql);
            }
        }
		
	    /* 修改拍卖活动状态 */
	    if ($order['extension_code']=='auction')
	    {
	        $sql = "UPDATE ". $ecs->table('goods_activity') ." SET is_finished='2' WHERE act_id=".$order['extension_id'];
	        $db->query($sql);
	    }
	
	    /* 处理余额、积分、红包 */
	    if ($order['user_id'] > 0 && $order['surplus'] > 0)
	    {
	        log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($_LANG['pay_order'], $order['order_sn']));
			//是否开启余额变动给客户发短信-用户消费
			if($_CFG['sms_user_money_change'] == 1)
			{
				$sql = "SELECT user_money,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
				$users = $GLOBALS['db']->getRow($sql); 
				$content = sprintf($_CFG['sms_use_balance_reduce_tpl'],date("Y-m-d H:i:s",gmtime()),$order['surplus'],$users['user_money'],$_CFG['sms_sign']);
				if($users['mobile_phone'])
				{
					require_once (ROOT_PATH . 'sms/sms.php');
					sendSMS($users['mobile_phone'],$content);
				}
			}
	    }

	    if ($order['user_id'] > 0 && $order['integral'] > 0)
	    {
	        log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($_LANG['pay_order'], $order['order_sn']));
	    }
	
		
	    if ($order['bonus_id'] > 0 && $temp_amout > 0 )
	    {
	        use_bonus($order['bonus_id'], $new_order_id);
	    }
		if($order['bonus_id'] == ''&&$bonus['bonus_id']<>'')
		{
	        $order['bonus_id'] = $bonus['bonus_id'];
			use_bonus($order['bonus_id'], $new_order_id);
	    }
	    
	    $split_order['suborder_list'][$ok]['order_sn'] = $order['order_sn'];
		$split_order['suborder_list'][$ok]['pay_name'] = $order['pay_name'];
		$split_order['suborder_list'][$ok]['shipping_name'] = $order['shipping_name'];
	    //$split_order['suborder_list'][$ok]['order_amount_formated'] = price_format($order['order_amount']);
		//if($order['order_amount'] <=0 && $payment['pay_code'] == 'balance'){//余额全额支付
		if($order['order_amount'] <=0){//余额全额支付
			$split_order['suborder_list'][$ok]['order_amount_formated'] = price_format($order['surplus'],false);
		}else{
			$split_order['suborder_list'][$ok]['order_amount_formated'] = price_format($order['order_amount'],false);
		}
	    
	
	    /* 如果使用库存，且下订单时减库存，则减少库存 */
	    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
	    {
                   
	        change_order_goods_storage($order['order_id'], true, SDT_PLACE);
                
	    }
	//$GLOBALS['db'] -> query("unlock tables");
	    /* 给商家发邮件 */
	    /* 增加是否给客服发送邮件选项 */
	    if ($_CFG['send_service_email'] && $_CFG['service_email'] != '')
	    {
	        $tpl = get_mail_template('remind_of_new_order');
	        $smarty->assign('order', $order);
	        $smarty->assign('goods_list', $cart_goods);
	        $smarty->assign('shop_name', $_CFG['shop_name']);
	        $smarty->assign('send_date', date($_CFG['time_format']));
	        $content = $smarty->fetch('str:' . $tpl['template_content']);
	        send_mail($_CFG['shop_name'], $_CFG['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
	    }
            /* 处理虚拟团购商品 */
	    /* 如果订单金额为0 处理虚拟卡 */
  
	    if ($order['order_amount'] <= 0)
	    {
			//在这段之后加上
			//扣库存
			if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PAY){
				  change_order_goods_storage($order['order_id'], true, SDT_PAY);
			}
	    	/* 代码增加_start  By www.yshop100.com */
	    	$sql = "SELECT goods_id, goods_name,extension_code, goods_attr_id, goods_number AS num FROM ".
	               $GLOBALS['ecs']->table('cart') .
	                " WHERE is_real = 0 ".
	                " AND $sql_where AND rec_type = '$flow_type'";
	        /* 代码增加_end  By www.yshop100.com */
	        $res = $GLOBALS['db']->getAll($sql);
	
	        $virtual_goods = array();
                $virtual_goods_num = 0;
	        foreach ($res AS $row)
	        {   
                    /* 代码增加_start  By www.yshop100.com _sunlizhi*/
//                    if($row['extension_code'] == 'virtual_good'){
//                        $virtual_goods_num = $virtual_goods_num+1;
//                    }
                    $sqla = "select valid_date,supplier_id from ".$GLOBALS['ecs']->table('goods') ." where goods_id=".$row['goods_id'];
                    $goods_info = $GLOBALS['db']->getRow($sqla);
                    $valid_date = $goods_info['valid_date'];
                    $supplier_id = $goods_info['supplier_id'];
                    /* 代码增加_end  By www.yshop100.com _sunlizhi*/
	            $virtual_goods[$row['extension_code']][] = array('goods_id' => $row['goods_id'], 'goods_attr_id'=>$row['goods_attr_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num'],'valid_date'=>$valid_date,'supplier_id'=>$supplier_id,'mobile_phone'=>$_REQUEST['mobile_phone']);
	        }

	        if ($virtual_goods AND $flow_type != CART_GROUP_BUY_GOODS)
	        {
     
 
	            /* 虚拟卡发货 */
	            if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
	            {         
                        //card_send_sms($virtual_goods);
                        foreach($virtual_goods['virtual_good'] as $key=>$val){
                        if($val['supplier_id']){
                            $supplier_name = $GLOBALS['db']->getOne("select supplier_name from ".$GLOBALS['ecs']->table('supplier')." where supplier_id = $val[supplier_id]");
                        }else{
                             $supplier_name = '网站自营';
                        }
                        $card = $GLOBALS['db']->getAll("select card_sn from ".$GLOBALS['ecs']->table('virtual_goods_card')." where order_sn='".$order['order_sn']."'");
                        require_once (ROOT_PATH . 'sms/sms.php');
                        foreach($card as $k=>$v){  
                            $card_sn .= $v['card_sn'].", ";
                            }     
                            $content = sprintf($_LANG['mobile_virtual_template'], $supplier_name, $val['goods_name'], $card_sn,local_date('Y-m-d',$val['valid_date']));
                            $result = sendSMS($_REQUEST['mobile_phone'],$content);  
                        }
	                /* 如果没有实体商品，修改发货状态，送积分和红包 */
	                $sql = "SELECT COUNT(*)" .
	                        " FROM " . $ecs->table('order_goods') .
	                        " WHERE order_id = '$order[order_id]' " .
	                        " AND is_real = 1";
	                if ($db->getOne($sql) <= 0)
	                {


	                    /* 修改订单状态 */
	                    update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));
	                    /* 如果订单用户不为空，计算积分，并发给用户；发红包 */

                       
	                    if ($order['user_id'] > 0)
	                    {
	                        /* 取得用户信息 */
	                        $user = user_info($order['user_id']);
	
	                        /* 计算并发放积分 */
	                        $integral = integral_to_give($order);
	                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($_LANG['order_gift_integral'], $order['order_sn']));


	                        /* 发放红包 */
							
	                        send_order_bonus($order['order_id']);
	                    }
	                }
	            }
	        }
	
	    }

	    //为每一个订单生成一个支付日志记录
	    $order['log_id'] = insert_pay_log($order['order_id'], $order['order_amount'], PAY_ORDER);
    	$all_order_amount += $order['order_amount'];
    	user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //推送feed到uc
    }

    /* 清空购物车 */
    clear_cart($flow_type,$id_ext);
    /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
    clear_all_files();
    
    //删除父订单记录
    if($del_patent_id > 0){
    	$sql="delete from ".$GLOBALS['ecs']->table('order_info')." where order_id='$del_patent_id' ";
		$GLOBALS['db']->query($sql);
    }
    

	/* 代码增加_start  By  www.yshop100.com */
	//$split_order = split_order($new_order_id);
	$smarty->assign('split_order',      $split_order);
	/* 如果需要，发短信 */
	if(count($split_order['suborder_list']) > 0){
		foreach($split_order['suborder_list'] as $key => $val){
			$supplier_ids[$key] = $val['order_sn'];
		}
	}

	//$supplier_ids = array_keys();
	require_once (ROOT_PATH . 'sms/sms.php');
	send_sms($supplier_ids,$_CFG['sms_order_placed_tpl'],1);

	$order['order_amount'] = $all_order_amount; //替换为总金额去支付

	/* 取得支付信息，生成支付代码 */
	if ($split_order['sub_order_count'] >1)
	{
		//如果一次下单有多个订单要支付，生成一个父订单的日志
		$order['order_sn'] = $parent_order_id; 
		/* 插入支付日志 */

    	$order['log_id'] = insert_pay_log($order['order_sn'], $order['order_amount'], PAY_ORDER);
	}else{
		/* 插入支付日志 */
    	//$order['log_id'] = insert_pay_log($order['order_id'], $order['order_amount'], PAY_ORDER);
	}

	if ($order['order_amount'] > 0)
    {
        $payment = payment_info($order['pay_id']);

        include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');

        $pay_obj    = new $payment['pay_code'];

        $pay_online = array('online'=>1);//$pay_obj->get_code($order, unserialize_config($payment['pay_config']));

		/* 代码修改_start    */
		$payment_www_com=unserialize_config($payment['pay_config']);
		if ($order['pay_id'] == 1 || $order['pay_id'] == 9 || $order['pay_id'] == 10)
        {
        	$payment_www_com['payment_inst'] = $order['pay_id'] == 9 ? 'ALIPAYCN' : 'ALIPAYHK';
        }

		if ($payment['pay_code']=='alipay_bank')
		{
			$payment_www_com['www_yshop100_com_alipay_bank'] = $_POST['www_yshop100_com_bank'] ? trim($_POST['www_yshop100_com_bank']) : "www_yshop100_com";
			
			$pay_online = $pay_obj->get_code($order, $payment_www_com);
		}else if($payment['pay_code']=='alipay' || $payment['pay_code']=='alipay_cn' || $payment['pay_code']=='alipay_hk'){
			RecordsFile( ROOT_PATH.'data/logs/','test-config-'.date('Y-m-d').'.txt',$payment_www_com);
            $pay_online = $pay_obj->get_code($order, $payment_www_com);
        }
        else if($payment['pay_code']=='quickpay')
        {
            $pay_online = $pay_obj->get_code($order, $payment_www_com);
            if($pay_online!="")
            {
                show_message('支付失败，'.$pay_online, '重新输入', '/mobile/flow.php');
            }
        }
        
		/* 代码修改_end    */

        $order['pay_desc'] = $payment['pay_desc'];
		RecordsFile( ROOT_PATH.'data/logs/','test-'.date('Y-m-d').'.txt',['botton' => $pay_online]);
        $smarty->assign('pay_online', $pay_online);
    }
//    print_r($pay_online);

	if(!empty($order['shipping_name']))
    {
        $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
    }

    /* 订单信息 */
    $smarty->assign('order',      $order);
    //$smarty->assign('total',      $total);
    //$smarty->assign('goods_list', $cart_goods);
    //$smarty->assign('order_submit_back', sprintf($_LANG['order_submit_back'], $_LANG['back_home'], $_LANG['goto_user_center'])); // 返回提示

    if($order['user_id']) pushUserMsg($order['user_id']);
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : "/mobile/";
    $autoUrl = str_replace($_SERVER['REQUEST_URI'],"",$GLOBALS['ecs']->url());
    @file_get_contents($autoUrl."/weixin/auto_do.php?type=1&is_one_user=".$order['user_id']);
	
    unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
    unset($_SESSION['flow_order']);
    unset($_SESSION['direct_shopping']);
    
 
}

/*------------------------------------------------------ */
//-- 更新购物车
/*------------------------------------------------------ */

elseif ($_REQUEST['step'] == 'update_cart')
{
    if (isset($_POST['goods_number']) && is_array($_POST['goods_number']))
    {
        flow_update_cart($_POST['goods_number']);
    }

   // show_message($_LANG['update_cart_notice'], $_LANG['back_to_cart'], 'flow.php');
	ecs_header("Location: flow.php\n");
    exit;
}

/*------------------------------------------------------ */
//-- 删除购物车中的商品
/*------------------------------------------------------ */

elseif ($_REQUEST['step'] == 'drop_goods')
{
    $rec_id = intval($_GET['id']);
    flow_drop_cart_goods($rec_id);

    ecs_header("Location: flow.php\n");
    exit;
}

/* 把优惠活动加入购物车 */
elseif ($_REQUEST['step'] == 'add_favourable')
{
    /* 取得优惠活动信息 */
    $act_id = intval($_POST['act_id']);
    $favourable = favourable_info($act_id);
    if (empty($favourable))
    {
        show_message($_LANG['favourable_not_exist']);
    }

    /* 判断用户能否享受该优惠 */
    if (!favourable_available($favourable))
    {
        show_message($_LANG['favourable_not_available']);
    }

    /* 检查购物车中是否已有该优惠 */
    $cart_favourable = cart_favourable();
    if (favourable_used($favourable, $cart_favourable))
    {
        show_message($_LANG['favourable_used']);
    }

    /* 赠品（特惠品）优惠 */
    if ($favourable['act_type'] == FAT_GOODS)
    {
        /* 检查是否选择了赠品 */
        if (empty($_POST['gift']))
        {
            show_message($_LANG['pls_select_gift']);
        }

        /* 检查是否已在购物车 */
        $sql = "SELECT goods_name" .
                " FROM " . $ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "'" .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
                " AND is_gift = '$act_id'" .
                " AND goods_id " . db_create_in($_POST['gift']);
        $gift_name = $db->getCol($sql);
        if (!empty($gift_name))
        {
            show_message(sprintf($_LANG['gift_in_cart'], join(',', $gift_name)));
        }

        /* 检查数量是否超过上限 */
        $count = isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0;
        if ($favourable['act_type_ext'] > 0 && $count + count($_POST['gift']) > $favourable['act_type_ext'])
        {
            show_message($_LANG['gift_count_exceed']);
        }

        /* 添加赠品到购物车 */
        foreach ($favourable['gift'] as $gift)
        {
            if (in_array($gift['id'], $_POST['gift']))
            {
                add_gift_to_cart($act_id, $gift['id'], $gift['price']);
            }
        }
    }
    elseif ($favourable['act_type'] == FAT_DISCOUNT)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], cart_favourable_amount($favourable) * (100 - $favourable['act_type_ext']) / 100);
    }
    elseif ($favourable['act_type'] == FAT_PRICE)
    {
        add_favourable_to_cart($act_id, $favourable['act_name'], $favourable['act_type_ext']);
    }

    /* 刷新购物车 */
    ecs_header("Location: flow.php\n");
    exit;
}
elseif ($_REQUEST['step'] == 'clear')
{
	$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";

    $sql = "DELETE FROM " . $ecs->table('cart') . " WHERE $sql_where";
    $db->query($sql);

    ecs_header("Location:./\n");
}
elseif ($_REQUEST['step'] == 'drop_to_collect')
{
    if ($_SESSION['user_id'] > 0)
    {
        $rec_id = intval($_GET['id']);
        $goods_id = $db->getOne("SELECT  goods_id FROM " .$ecs->table('cart'). " WHERE rec_id = '$rec_id' AND session_id = '" . SESS_ID . "' ");
        $count = $db->getOne("SELECT goods_id FROM " . $ecs->table('collect_goods') . " WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id'");
        if (empty($count))
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";
            $db->query($sql);
        }
        flow_drop_cart_goods($rec_id);
    }
    ecs_header("Location: flow.php\n");
    exit;
}

/* 验证红包序列号 */
elseif ($_REQUEST['step'] == 'validate_bonus')
{
    include_once('includes/cls_json.php');
    $result = array('error' => '', 'content' => '');

	$result['suppid'] = $suppid = intval($_GET['suppid']);

	$bonus_sn = intval($_REQUEST['bonus_sn']);
    if (is_numeric($bonus_sn) && $bonus_sn>0)
    {
        $bonus = bonus_info(0, $bonus_sn, $suppid);
    }
    else
    {
        $bonus = array();
    }

//    if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0)
//    {
//        die($_LANG['bonus_sn_error']);
//    }
//    if ($bonus['min_goods_amount'] > cart_amount())
//    {
//        die(sprintf($_LANG['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false)));
//    }
//    die(sprintf($_LANG['bonus_is_ok'], price_format($bonus['type_money'], false)));
    $bonus_kill = price_format($bonus['type_money'], false);

    /* 取得购物类型 */
    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

    /* 获得收货人信息 */
    $consignee = get_consignee($_SESSION['user_id']);

    /* 对商品信息赋值 */
    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计

    if (empty($cart_goods))
    {
        $result['error'] = $_LANG['no_goods_in_cart'];
    }
    else
    {
        /* 取得购物流程设置 */
        $smarty->assign('config', $_CFG);

        /* 取得订单信息 */
        $order = flow_order_info();
		$bonus_info = (isset($order['bonus_sn_info'])) ? $order['bonus_sn_info'] : array();
		$bonus_id_info = (isset($order['bonus_id_info'])) ? $order['bonus_id_info'] : array();
		


        if (((!empty($bonus) && $bonus['user_id'] == $_SESSION['user_id']) || ($bonus['type_money'] > 0 && empty($bonus['user_id']))) && $bonus['supplier_id'] == $suppid && $bonus['order_id'] <= 0)
        {
            //$order['bonus_kill'] = $bonus['type_money'];
            $now = gmtime();
            if ($now > $bonus['use_end_date'])
            {
		$order['bonus_sn'] = '';
		//$order['bonus_sn'] = implode(',',$bonus_info);//$bonus_sn;
                $result['error']=$_LANG['bonus_use_expire'];
            }
            else
            {
                $order['bonus_id'] = $bonus['bonus_id'];
                $order['bonus_sn'] = $bonus_sn;
            }
        }
        else
        {
            //$order['bonus_kill'] = 0;
            $order['bonus_id'] = '';
            $result['error'] = $_LANG['invalid_bonus'];
        }

        /* 计算订单的费用 */
        $total = order_fee($order, $cart_goods, $consignee);

        if($total['goods_price']<$bonus['min_goods_amount'])
        {
         $order['bonus_id'] = '';
         /* 重新计算订单 */
         $total = order_fee($order, $cart_goods, $consignee);
         $result['error'] = sprintf($_LANG['bonus_min_amount_error'], price_format($bonus['min_goods_amount'], false));
        }
		$result['bonusnum'] = $order['bonus_sn'];
        $smarty->assign('total', $total);

        /* 团购标志 */
        if ($flow_type == CART_GROUP_BUY_GOODS)
        {
            $smarty->assign('is_group_buy', 1);
        }

        $result['content'] = $smarty->fetch('library/order_total.lbi');
    }
    $json = new JSON();
    die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 添加礼包到购物车
/*------------------------------------------------------ */
elseif ($_REQUEST['step'] == 'add_package_to_cart')
{
    include_once('includes/cls_json.php');
    $package_id = $_POST['packageId'];
    $number = $_POST['number'];

    $result = array('error' => 0, 'message' => '', 'content' => '', 'package_id' => '');
    $json  = new JSON;

    if (empty($package_id))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }

    $package = $json->decode($_POST['package_info']);

    /* 如果是一步购物，先清空购物车 */
    if ($_COOKIE['one_step_buy'] == '1')
    {
        clear_cart();
    }

    /* 商品数量是否合法 */
    if (!is_numeric($number) || intval($number) <= 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['invalid_number'];
    }
    else
    {
        /* 添加到购物车 */
        if (add_package_to_cart($package_id, $number))
        {
            if ($_CFG['cart_confirm'] > 2)
            {
                $result['message'] = '';
            }
            else
            {
                $result['message'] = $_CFG['cart_confirm'] == 1 ? $_LANG['addto_cart_success_1'] : $_LANG['addto_cart_success_2'];
            }

            $result['content'] = insert_cart_info();
            $result['one_step_buy'] = $_COOKIE['one_step_buy'];
        }
        else
        {
            $result['message']    = $err->last_message();
            $result['error']      = $err->error_no;
            $result['package_id'] = stripslashes($package_id);
        }
    }
    $result['confirm_type'] = !empty($_CFG['cart_confirm']) ? $_CFG['cart_confirm'] : 2;
    die($json->encode($result));
}
else
{
    /* 标记购物流程为普通商品 */
    $_SESSION['flow_type'] = CART_GENERAL_GOODS;

    /* 如果是一步购物，跳到结算中心 */
	//if ($_COOKIE['one_step_buy'] == '1')
    //{
	//	$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
	//	$sql = "SELECT rec_id FROM " . $ecs->table('cart') . 
	//		" WHERE $sql_where AND rec_type = '" . $flow_type . "'";
	//	$_SESSION['sel_cartgoods'] = $db->getOne($sql);
     //   ecs_header("Location: flow.php?step=checkout\n");
    //    exit;
    //}else{
		unset($_SESSION['sel_cartgoods']);//购物车页面中的商品初始化为全选，所以清除这个保存被选中的商品变量
    //}

    /* 取得商品列表，计算合计 */
    $cart_goods = get_cart_goods();
    $smarty->assign('goods_list', $cart_goods['goods_list']);
    $smarty->assign('total', $cart_goods['total']);

    //购物车的描述的格式化
    $smarty->assign('shopping_money',         sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']));
    $smarty->assign('market_price_desc',      sprintf($_LANG['than_market_price'],
        $cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate']));

    // 显示收藏夹内的商品
    if ($_SESSION['user_id'] > 0)
    {
        require_once(ROOT_PATH . 'includes/lib_clips.php');
        $collection_goods = get_collection_goods($_SESSION['user_id']);
        $smarty->assign('collection_goods', $collection_goods);
    }

    /* 取得优惠活动 */
    $favourable_list = favourable_list($_SESSION['user_rank']);
    //usort($favourable_list, 'cmp_favourable');
	if($favourable_list){
		$new_fav = array();
		foreach($favourable_list as $key => $val){
			if(isset($cart_goods['goods_list'][$val['supplier_id']]) && $val['available']){
				$cart_goods['goods_list'][$val['supplier_id']]['favourable'][] = $val;
			}
		}
	}

	foreach($cart_goods['goods_list'] as $k=>$v){
		$discount = compute_discount($k);
		if(is_array($discount)){
			$cart_goods['goods_list'][$k]['discount']['discount'] = $discount['discount'];
			$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
			$cart_goods['goods_list'][$k]['discount']['your_discount'] = sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount']));
		}
	}
	
	$smarty->assign('goods_list', $cart_goods['goods_list']);
    $smarty->assign('total', $cart_goods['total']);
    
    //选择优惠活动中的赠品时所要执行的部分_start
	if(isset($_REQUEST['is_ajax']) && intval($_REQUEST['is_ajax']) > 0){
		include('includes/cls_json.php');
		$json   = new JSON;
		$res    = array('err_msg' => '', 'result' => '');
		if (isset($_REQUEST['suppid']))
		{
			$smarty->assign('favourable_list', $cart_goods['goods_list'][intval($_REQUEST['suppid'])]['favourable']);
			$res['result'] =  $smarty->fetch("library/favourable.lbi");
		}
		else
		{
				$res['result'] = '请选择要结算的商品！';
		}	

		die($json->encode($res));
	}
    //选择优惠活动中的赠品时所要执行的部分_end
    /* 计算折扣 */
   // $discount = compute_discount();
    //$smarty->assign('discount', $discount['discount']);
    //$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
    //$smarty->assign('your_discount', sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount'])));

    /* 增加是否在购物车里显示商品图 */
    $smarty->assign('show_goods_thumb', $GLOBALS['_CFG']['show_goods_in_cart']);

    /* 增加是否在购物车里显示商品属性 */
    $smarty->assign('show_goods_attribute', $GLOBALS['_CFG']['show_attr_in_cart']);

    /* 购物车中商品配件列表 */
    //取得购物车中基本件ID
    /* 如果用户已经登陆使用user_id进行判断，如果未登陆则使用 session_id*/
    $sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
    $sql = "SELECT goods_id " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE " .$sql_where .
            "AND rec_type = '" . CART_GENERAL_GOODS . "' " .
            "AND is_gift = 0 " .
            "AND extension_code <> 'package_buy' " .
            "AND parent_id = 0 ";
    $parent_list = $GLOBALS['db']->getCol($sql);

    $fittings_list = get_goods_fittings($parent_list);

    $smarty->assign('fittings_list', $fittings_list);
}

if($_REQUEST['step']=='update_group_cart')
{
	include_once('includes/cls_json.php');
	$result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
	$json = new JSON;
	$rec_id = intval($_GET['rec_id']);
	$number = intval($_GET['number']);
	$goods_id = intval($_GET['goods_id']);
	$is_package = intval($_GET['is_package']);
	$result['suppid'] = intval($_GET['suppid']);
	$result['rec_id'] = $rec_id;
	$result['number']=$number;

	if ($is_package == 0)
	{
		$time_xg_now=gmtime();
		$row_xg= $GLOBALS['db']->getRow("select is_buy,buymax, buymax_start_date, buymax_end_date from ". $GLOBALS['ecs']->table('goods') ." where goods_id='".$goods_id."' " );
		if ( $row_xg['is_buy'] == 1 && $row_xg['buymax'] >0 && $row_xg['buymax_start_date'] < $time_xg_now  && $row_xg['buymax_end_date'] > $time_xg_now  )
		{
			if ($_SESSION['user_id'] == 0 )
			{
				$result['error']  = 999;
				$result['message'] = "此商品为限购商品，需要登录后才能继续购买！";
				die($json->encode($result));
			}
			else
			{
				$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";

				$num_cart_old_1=$GLOBALS['db']->getOne("select sum(goods_number) from ". $GLOBALS['ecs']->table('cart') ." where " . $sql_where . " and goods_id= " . $goods_id );
				$num_cart_old_2=$GLOBALS['db']->getOne("select sum(og.goods_number) from ". $GLOBALS['ecs']->table('order_goods') ." AS og , ". $GLOBALS['ecs']->table('order_info') ." AS o where o.user_id='$_SESSION[user_id]' and  o.order_id = og.order_id and add_time > ". $row_xg['buymax_start_date'] ." and add_time < ". $row_xg['buymax_end_date'] ."  and og.goods_id = " . $goods_id );
				$num_cart_old = $num_cart_old_1 + $num_cart_old_2 ;
				$num_total = $num_cart_old_2 +  intval($number);
				if ( $num_total > intval($row_xg['buymax']) )
				{
					$result['error']   = 888;
					$num_else=intval($row_xg['buymax'])-$num_cart_old_2;
					$result['message'] ="注意：\n\r此商品限购期间每人限购 ". $row_xg['buymax'] . " 件\n\r";
					if ($num_cart_old_2 > 0)
					{
						$result['message'] .="您在限购期间已经成功购买过". $num_cart_old_2 ." 件！\n\r";
					}
					if ($num_cart_old_1 > 0)
					{
						$result['message'] .="您的购物车中已经存在". $num_cart_old_1 ."件！\n\r";
					}
					$result['message'] .= "您只能买 ". $num_else ." 件";
					$result['number']	= $num_else;
					die($json->encode($result));
				}
			}
		}
	}

	if ($GLOBALS['_CFG']['use_storage'] == 1)
	{
		$sql_where = $_SESSION['user_id']>0 ? " user_id='". $_SESSION['user_id'] ."' " : " session_id = '" . SESS_ID . "' AND user_id=0 ";
		if ($is_package == 0)
		{
			$pg_ids = $GLOBALS['db']->getAll("select goods_id, goods_number from " . $GLOBALS['ecs']->table('cart') . " where extension_code = 'package_buy' and " . $sql_where);
			$pg_num = 0;
			foreach($pg_ids as $pg_id)
			{
				$pg_num += $GLOBALS['db']->getOne("select goods_number from " .$GLOBALS['ecs']->table('package_goods') . " where package_id =  " . $pg_id['goods_id'] . " and goods_id = " . $goods_id);
			}
			$goods_number = $GLOBALS['db']->getOne("select goods_number from ".$GLOBALS['ecs']->table('goods')." where goods_id='$goods_id'");
			$number2 = $number + $pg_num;
			if($number2>$goods_number) //////// jx      库存判断 
			{
				$result['error'] = '1';
				$result['content'] ='对不起,您选择的数量超出库存您最多可购买'.$goods_number."件";
				$result['content'] ="对不起,此单品超出库存,您最多可购买".$goods_number."件";
				if ($pg_num > 0)
				{
					$result['content'] .= ",礼包中已包含此单品 " . $pg_num . "件";
				}
				$result['number']=$GLOBALS['db']->getOne("select goods_number from ".$GLOBALS['ecs']->table('cart')." where rec_id = '$rec_id'");
				die($json->encode($result));
			}
			//添加判断商品有属性的时候的库存   jx    
			$goods_attr_id = $GLOBALS['db']->getOne("SELECT goods_attr_id FROM ".$GLOBALS['ecs']->table('cart')."WHERE rec_id = '$rec_id'");
			if($goods_attr_id)
			{
				$str = explode(',',$goods_attr_id);//把字符串转换成数组
				$goods_attr = implode('|',$str);// 把数组转换成以‘|’的字符传
				$attr_number = $GLOBALS['db']->getOne("select product_number from ".$GLOBALS['ecs']->table('products')." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
				if($number>$attr_number)
				{
					$result['error'] = '1';
					$result['content'] ='对不起,您选择的数量超出库存您最多可购买'.$attr_number."件";
					$result['number']=$GLOBALS['db']->getOne("select product_number from ".$GLOBALS['ecs']->table('products')." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
					die($json->encode($result));
				}
			}
		}
		else
		{
			$goods_infos = $GLOBALS['db']->getAll("select pg.goods_id, pg.goods_number, g.goods_name from " . $GLOBALS['ecs']->table('package_goods') . " as pg left join " . $GLOBALS['ecs']->table('goods') . " as g on pg.goods_id = g.goods_id where package_id='$goods_id'");
			$is_null_g = 0;
			foreach($goods_infos as $goods_info)
			{
				$one_num = $GLOBALS['db']->getOne("SELECT SUM(goods_number) FROM " . $GLOBALS['ecs']->table('cart') . " WHERE goods_id = '$goods_info[goods_id]' and " . $sql_where);
				$number2 = $number * $goods_info['goods_number'] + $one_num;
				$goods_number = $GLOBALS['db']->getOne("select goods_number from ".$GLOBALS['ecs']->table('goods')." where goods_id='$goods_info[goods_id]'");

				if($number2>$goods_number) //////// jx      库存判断 
				{
					$result['error'] = '1';
					$result['content'] ="对不起,礼包中单品[" . $goods_info['goods_name'] . "]超出库存,您最多可购买".$goods_number."件";
					if ($one_num > 0)
					{
						$result['content'] .= ",已添加了单品 " . $one_num . "件";
					}
					$result['number']=$GLOBALS['db']->getOne("select goods_number from ".$GLOBALS['ecs']->table('cart')." where rec_id = '$rec_id'");
					die($json->encode($result));
				}
				//添加判断商品有属性的时候的库存   jx    
				$goods_attr_id = $GLOBALS['db']->getOne("SELECT goods_attr_id FROM ".$GLOBALS['ecs']->table('cart')."WHERE rec_id = '$rec_id'");
				if($goods_attr_id)
				{
					$str = explode(',',$goods_attr_id);//把字符串转换成数组
					$goods_attr = implode('|',$str);// 把数组转换成以‘|’的字符传
					$attr_number = $GLOBALS['db']->getOne("select product_number from ".$GLOBALS['ecs']->table('products')." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
					if($number>$attr_number)
					{
						$result['error'] = '1';
						$result['content'] ='对不起,您选择的数量超出库存您最多可购买'.$attr_number."件";
						$result['number']=$GLOBALS['db']->getOne("select product_number from ".$GLOBALS['ecs']->table('products')." where goods_id='$goods_id' AND goods_attr ='$goods_attr'");
						die($json->encode($result));
					}
				}
			}
		}		
	}
	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_number = '$number' WHERE rec_id = $rec_id";
	$GLOBALS['db']->query($sql);

	

	//折扣活动
	$result['your_discount'] = '';
	$discount = compute_discount($result['suppid']);
	if(is_array($discount)){
		$favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
		$result['your_discount'] = sprintf($_LANG['your_discount'], $favour_name, price_format($discount['discount']));
	}
	if ($is_package == 0)
	{
		//如果有优惠价格，获得商品最终价格
		$goods_attr_array = explode('|',$goods_attr);
		$shop_price  = get_final_price($goods_id, $number, true, $goods_attr_array);
		$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET goods_price = '$shop_price' WHERE rec_id = $rec_id";
		$GLOBALS['db']->query($sql);
	}
	else
	{
		$sql_sp = "SELECT goods_price FROM " . $GLOBALS['ecs']->table('cart') . " WHERE rec_id= " . $rec_id;
		$shop_price = $GLOBALS['db']->getOne($sql_sp);
	}
	
	$subtotal = $shop_price * $number;
	$result['goods_price'] = price_format($shop_price, false);
		
	//$subtotal = $GLOBALS['db']->getONE("select goods_price * goods_number AS subtotal from ".$GLOBALS['ecs']->table('cart')." where rec_id = $rec_id");
	$result['subtotal'] = price_format($subtotal, false);
	//$result['cart_amount_desc'] = sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']);
	/* 取得商品列表，计算合计 */
    if($_GET['sel_goods']){
        $id_ext = " AND rec_id in (". $_GET['sel_goods'] .") ";
    }
	$cart_goods = get_cart_goods($id_ext);
	//$cart_goods = get_cart_goods();
	
	$result['cart_amount_desc'] = $cart_goods['total']['goods_price'];
	$shopping_money = sprintf($_LANG['shopping_money'], $cart_goods['total']['goods_price']);
	$result['market_amount_desc'] = $shopping_money;
	if ($_CFG['show_marketprice'])
	{
		$market_price_desc= sprintf($_LANG['than_market_price'],$cart_goods['total']['market_price'], $cart_goods['total']['saving'], $cart_goods['total']['save_rate']);
		$result['market_amount_desc'].= "，".$market_price_desc ;
	}


	die($json->encode($result));
}

if ($_REQUEST['step']=='cart')
{
	$smarty->assign('template_dir', $GLOBALS['_CFG']['template']);
	 $hotgoods_list = cart_goods_recommend('is_hot');
     $smarty->assign('hotgoods_list', $hotgoods_list);
	 $bestgoods_list = cart_goods_recommend('is_best');
     $smarty->assign('bestgoods_list', $bestgoods_list);
}
function cart_goods_recommend($rtype)
{
	$sql_hot = "select goods_id, goods_name, goods_thumb, shop_price, market_price from " . $GLOBALS['ecs']->table('goods'). " where ". $rtype ."=1 order by  goods_id desc limit 0,10 ";
	 $res_hot=$GLOBALS['db']->query($sql_hot);
	 $hotgoods_list = array();
	 while ($row_hot = $GLOBALS['db']->fetchRow($res_hot))
	 {
		$row_hot['goods_name'] = sub_str($row_hot['goods_name'], 20) ;
        $row_hot['url']        = build_uri('goods', array('gid' => $row_hot['goods_id']), $row_hot['goods_name']);
        $row_hot['goods_thumb'] = get_pc_url().'/'.get_image_path($row_hot['goods_id'], $row_hot['goods_thumb'], true);
        $row_hot['shop_price']    =   price_format($row_hot['shop_price']);
		$row_hot['market_price']  =  price_format($row_hot['market_price']);
		$hotgoods_list[]=$row_hot;
	 }
	 return $hotgoods_list;
}
$smarty->assign('currency_format', $_CFG['currency_format']);
$smarty->assign('integral_scale',  $_CFG['integral_scale']);
$smarty->assign('step',            $_REQUEST['step']);
$smarty->assign('uid',      $_SESSION['user_id']); 
assign_dynamic('shopping_flow');

if ($_REQUEST['step']=='cart' || $_REQUEST['step']=='checkout')
{
	//echo "<pre>";
	$smarty->assign('template_dir', $GLOBALS['_CFG']['template']);
	//$smarty->display('flow-test.dwt');
	$smarty->display('flow.dwt');
}
else
{
	//$smarty->display('flow-test.dwt');
	$smarty->display('flow.dwt');
}

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得用户的可用积分
 *
 * @access  private
 * @return  integral
 */
function flow_available_points()
{
	$sql_where = $_SESSION['user_id']>0 ? "c.user_id='". $_SESSION['user_id'] ."' " : "c.session_id = '" . SESS_ID . "' AND c.user_id=0 ";
    $sql = "SELECT SUM(g.integral * c.goods_number) as integral,g.supplier_id ".
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE $sql_where AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' GROUP BY g.supplier_id";
   /* 代码修改_end  By  www.yshop100.com */
    $info = $GLOBALS['db']->getAll($sql);
    $ret = array();
    foreach($info as $key => $val){
    	$ret[$val['supplier_id']] = integral_of_value(intval($val['integral']));
    }
    return $ret;
}

/**
 * 更新购物车中的商品数量
 *
 * @access  public
 * @param   array   $arr
 * @return  void
 */
function flow_update_cart($arr)
{
    /* 处理 */
    foreach ($arr AS $key => $val)
    {
        $val = intval(make_semiangle($val));
        if ($val <= 0 || !is_numeric($key))
        {
            continue;
        }

        //查询：
        $sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
               " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
        $goods = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT g.goods_name, g.goods_number ".
                "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
                    $GLOBALS['ecs']->table('cart'). " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);

        //查询：系统启用了库存，检查输入的商品数量是否有效
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
        {
            if ($row['goods_number'] < $val)
            {
                show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                $row['goods_number'], $row['goods_number']));
                exit;
            }
            /* 是货品 */
            $goods['product_id'] = trim($goods['product_id']);
            if (!empty($goods['product_id']))
            {
                $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";

                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val)
                {
                    show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    $product_number['product_number'], $product_number['product_number']));
                    exit;
                }
            }
        }
        elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
        {
            if (judge_package_stock($goods['goods_id'], $val))
            {
                show_message($GLOBALS['_LANG']['package_stock_insufficiency']);
                exit;
            }
        }

        /* 查询：检查该项是否为基本件 以及是否存在配件 */
        /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
		$sql_where = $_SESSION['user_id']>0 ? "a.user_id='". $_SESSION['user_id'] ."' " : "a.session_id = '" . SESS_ID . "' AND a.user_id=0 ";
        $sql = "SELECT b.goods_number, b.rec_id
                FROM " .$GLOBALS['ecs']->table('cart') . " a, " .$GLOBALS['ecs']->table('cart') . " b
                WHERE   $sql_where AND a.rec_id = '$key'
               
                AND a.extension_code <> 'package_buy'
                AND b.parent_id = a.goods_id
                AND $sql_where";

        $offers_accessories_res = $GLOBALS['db']->query($sql);

        $sql_where1 = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
        //订货数量大于0
        if ($val > 0)
        {
            /* 判断是否为超出数量的优惠价格的配件 删除*/
            $row_num = 1;
            while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
            {
                if ($row_num > $val)
                {
                    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                            " WHERE session_id = '" . SESS_ID . "' " .
                            "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                    $GLOBALS['db']->query($sql);
                }

                $row_num ++;
            }

            /* 处理超值礼包 */
            if ($goods['extension_code'] == 'package_buy')
            {
                //更新购物车中的商品数量
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$val' WHERE rec_id='$key' AND $sql_where1";
            }
            /* 处理普通商品或非优惠的配件 */
            else
            {
                $attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
                $goods_price = get_final_price($goods['goods_id'], $val, true, $attr_id);

                //更新购物车中的商品数量
                $sql = "UPDATE " .$GLOBALS['ecs']->table('cart').
                        " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND $sql_where1";
            }
        }
        //订货数量等于0
        else
        {
            /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
            while ($offers_accessories_row = $GLOBALS['db']->fetchRow($offers_accessories_res))
            {
                $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                        " WHERE $sql_where1 " .
                        "AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
                $GLOBALS['db']->query($sql);
            }

            $sql = "DELETE FROM " .$GLOBALS['ecs']->table('cart').
                " WHERE rec_id='$key' AND $sql_where1";
        }

        $GLOBALS['db']->query($sql);
    }

    /* 删除所有赠品 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') . " WHERE $sql_where1 AND is_gift <> 0";
    $GLOBALS['db']->query($sql);
}

/**
 * 检查订单中商品库存
 *
 * @access  public
 * @param   array   $arr
 *
 * @return  void
 */
function flow_cart_stock($arr)
{
    foreach ($arr AS $key => $val)
    {
        $val = intval(make_semiangle($val));
        if ($val <= 0 || !is_numeric($key))
        {
            continue;
        }

        $sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
        $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
               " WHERE rec_id='$key' AND $sql_where";
        $goods = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
                "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
                    $GLOBALS['ecs']->table('cart'). " AS c ".
                "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);

        //系统启用了库存，检查输入的商品数量是否有效
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy')
        {
            if ($row['goods_number'] < $val)
            {
                show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                $row['goods_number'], $row['goods_number']));
                exit;
            }

            /* 是货品 */
            $row['product_id'] = trim($row['product_id']);
            if (!empty($row['product_id']))
            {
                $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val)
                {
                    show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    $row['goods_number'], $row['goods_number']));
                    exit;
                }
            }
        }
        elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy')
        {
            if (judge_package_stock($goods['goods_id'], $val))
            {
                show_message($GLOBALS['_LANG']['package_stock_insufficiency']);
                exit;
            }
        }
    }

}

/**
 * 删除购物车中的商品
 *
 * @access  public
 * @param   integer $id
 * @return  void
 */
function flow_drop_cart_goods($id)
{
    /* 取得商品id */
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('cart'). " WHERE rec_id = '$id'";
    $row = $GLOBALS['db']->getRow($sql);
    if ($row)
    {
		$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";//添加 www.yshop100.com
        //如果是超值礼包
        if ($row['extension_code'] == 'package_buy')
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE $sql_where " .
                    "AND rec_id = '$id' LIMIT 1";
        }

        //如果是普通商品，同时删除所有赠品及其配件
        elseif ($row['parent_id'] == 0 && $row['is_gift'] == 0)
        {
            /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
            $sql = "SELECT c.rec_id
                    FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('group_goods') . " AS gg, " . $GLOBALS['ecs']->table('goods'). " AS g
                    WHERE gg.parent_id = '" . $row['goods_id'] . "'
                    AND c.goods_id = gg.goods_id
                    AND c.parent_id = '" . $row['goods_id'] . "'
                    AND c.extension_code <> 'package_buy'
                    AND gg.goods_id = g.goods_id
                    AND g.is_alone_sale = 0";
            $res = $GLOBALS['db']->query($sql);
            $_del_str = $id . ',';
            while ($id_alone_sale_goods = $GLOBALS['db']->fetchRow($res))
            {
                $_del_str .= $id_alone_sale_goods['rec_id'] . ',';
            }
            $_del_str = trim($_del_str, ',');

            if($_del_str){
                $sql_plus = "rec_id IN ($_del_str) OR ";
            }
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE $sql_where " .
                    "AND (".$sql_plus." parent_id = '$row[goods_id]' OR is_gift <> 0)";
        }

        //如果不是普通商品，只删除该商品即可
        else
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
                    " WHERE $sql_where " .
                    "AND rec_id = '$id' LIMIT 1";
        }

        $GLOBALS['db']->query($sql);
    }

    flow_clear_cart_alone();
}

/**
 * 删除购物车中不能单独销售的商品
 *
 * @access  public
 * @return  void
 */
function flow_clear_cart_alone()
{
    /* 查询：购物车中所有不可以单独销售的配件 */
	$sql_where = $_SESSION['user_id']>0 ? "c.user_id='". $_SESSION['user_id'] ."' " : "c.session_id = '" . SESS_ID . "' AND c.user_id=0 ";//添加 www.yshop100.com

    $sql = "SELECT c.rec_id, gg.parent_id
            FROM " . $GLOBALS['ecs']->table('cart') . " AS c
                LEFT JOIN " . $GLOBALS['ecs']->table('group_goods') . " AS gg ON c.goods_id = gg.goods_id
                LEFT JOIN" . $GLOBALS['ecs']->table('goods') . " AS g ON c.goods_id = g.goods_id
            WHERE $sql_where 
            AND c.extension_code <> 'package_buy'
            AND gg.parent_id > 0
            AND g.is_alone_sale = 0";
    $res = $GLOBALS['db']->query($sql);
    $rec_id = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $rec_id[$row['rec_id']][] = $row['parent_id'];
    }

    if (empty($rec_id))
    {
        return;
    }
$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";//添加 www.yshop100.com

    /* 查询：购物车中所有商品 */
    $sql = "SELECT DISTINCT goods_id
            FROM " . $GLOBALS['ecs']->table('cart') . "
            WHERE $sql_where 
            AND extension_code <> 'package_buy'";
    $res = $GLOBALS['db']->query($sql);
    $cart_good = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $cart_good[] = $row['goods_id'];
    }

    if (empty($cart_good))
    {
        return;
    }

    /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
    $del_rec_id = '';
    foreach ($rec_id as $key => $value)
    {
        foreach ($value as $v)
        {
            if (in_array($v, $cart_good))
            {
                continue 2;
            }
        }

        $del_rec_id = $key . ',';
    }
    $del_rec_id = trim($del_rec_id, ',');

    if ($del_rec_id == '')
    {
        return;
    }

    /* 删除 */
    if($del_rec_id){
        $sql_plus =" AND rec_id IN ($del_rec_id) ";
    }
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ."
            WHERE $sql_where 
            " .$sql_plus;
    $GLOBALS['db']->query($sql);
}

/**
 * 比较优惠活动的函数，用于排序（把可用的排在前面）
 * @param   array   $a      优惠活动a
 * @param   array   $b      优惠活动b
 * @return  int     相等返回0，小于返回-1，大于返回1
 */
function cmp_favourable($a, $b)
{
    if ($a['available'] == $b['available'])
    {
        if ($a['sort_order'] == $b['sort_order'])
        {
            return 0;
        }
        else
        {
            return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
        }
    }
    else
    {
        return $a['available'] ? -1 : 1;
    }
}

/**
 * 修改购物车中商品数量，删除商品，取消选择时判断是否在此商品参加的优惠活动已经存在购物车，如果有就删除掉
 */
function del_cart_favourable_goods(){
	$favlist = favourable_list($_SESSION['user_rank']);
	
	return $favlist;
}

/**
 * 取得某用户等级当前时间可以享受的优惠活动
 * @param   int     $user_rank      用户等级id，0表示非会员  
 * @param   int     $is_have        是否判断已经选择赠品
 * @return  array
 */
function favourable_list($user_rank,$is_have=true)
{
    /* 购物车中已有的优惠活动及数量 */
    $used_list = cart_favourable();

    /* 当前用户可享受的优惠活动 */
    $favourable_list = array();
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();
	if(isset($_REQUEST['suppid'])){
    	$tj = " AND supplier_id=".$_REQUEST['suppid'];
    }else{
    	$tj = '';
    }
    $sql = "SELECT * " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND start_time <= '$now' AND end_time >= '$now'" .$tj.
            " AND act_type = '" . FAT_GOODS . "'" .
            " ORDER BY sort_order";
    $res = $GLOBALS['db']->query($sql);
    while ($favourable = $GLOBALS['db']->fetchRow($res))
    {
        $favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
        $favourable['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
        $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
        $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
        $favourable['gift']       = unserialize($favourable['gift']);
		$_REQUEST['suppid'] = $favourable['supplier_id'] = $favourable['supplier_id'];

        foreach ($favourable['gift'] as $key => $value)
        {
            $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);
            
            $goods_thumb = $GLOBALS['db']->getOne("SELECT `goods_thumb` FROM " . $GLOBALS['ecs']->table('goods') . " WHERE `goods_id`='{$value['id']}'");
            $favourable['gift'][$key]['goods_thumb'] = get_pc_url().'/'.get_image_path($value['id'], $goods_thumb, true);
        	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND goods_id = ".$value['id'];
            $is_sale = $GLOBALS['db']->getOne($sql);
            if(!$is_sale)
            {
                unset($favourable['gift'][$key]);
            }
        }

        $favourable['act_range_desc'] = act_range_desc($favourable);
        $favourable['act_type_desc'] = sprintf($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']], $favourable['act_type_ext']);

        /* 是否能享受 */
        $favourable['available'] = favourable_available($favourable);
        if ($favourable['available'] && $is_have)
        {
            /* 是否尚未享受 */
            $favourable['available'] = !favourable_used($favourable, $used_list);
        }

        $favourable_list[] = $favourable;
    }

    return $favourable_list;
}

/**
 * 根据购物车判断是否可以享受某优惠活动
 * @param   array   $favourable     优惠活动信息
 * @return  bool
 */
function favourable_available($favourable)
{
    /* 会员等级是否符合 */
    $user_rank = $_SESSION['user_rank'];
    if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank . ',') === false)
    {
        return false;
    }

    /* 优惠范围内的商品总额 */
    $amount = cart_favourable_amount($favourable);

    /* 金额上限为0表示没有上限 */
    return $amount >= $favourable['min_amount'] &&
        ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
}

/**
 * 取得优惠范围描述
 * @param   array   $favourable     优惠活动
 * @return  string
 */
function act_range_desc($favourable)
{
    if ($favourable['act_range'] == FAR_BRAND)
    {
        $sql = "SELECT brand_name FROM " . $GLOBALS['ecs']->table('brand') .
                " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
        return join(',', $GLOBALS['db']->getCol($sql));
    }
    elseif ($favourable['act_range'] == FAR_CATEGORY)
    {
        $sql = "SELECT cat_name FROM " . $GLOBALS['ecs']->table('category') .
                " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
        return join(',', $GLOBALS['db']->getCol($sql));
    }
    elseif ($favourable['act_range'] == FAR_GOODS)
    {
        $sql = "SELECT goods_name FROM " . $GLOBALS['ecs']->table('goods') .
                " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
        return join(',', $GLOBALS['db']->getCol($sql));
    }
    else
    {
        return '';
    }
}

/**
 * 取得购物车中已有的优惠活动及数量
 * @return  array
 */
function cart_favourable()
{
	$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";//添加 www.yshop100.com
    $list = array();
    $sql = "SELECT is_gift, COUNT(*) AS num " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE $sql_where " .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " AND is_gift > 0" .
            " GROUP BY is_gift";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $list[$row['is_gift']] = $row['num'];
    }

    return $list;
}

/**
 * 购物车中是否已经有某优惠
 * @param   array   $favourable     优惠活动
 * @param   array   $cart_favourable购物车中已有的优惠活动及数量
 */
function favourable_used($favourable, $cart_favourable)
{
    if ($favourable['act_type'] == FAT_GOODS)
    {
        return isset($cart_favourable[$favourable['act_id']]) &&
            $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
            $favourable['act_type_ext'] > 0;
    }
    else
    {
        return isset($cart_favourable[$favourable['act_id']]);
    }
}

/**
 * 添加优惠活动（赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   int     $id         赠品id
 * @param   float   $price      赠品价格
 */
function add_gift_to_cart($act_id, $id, $price)
{
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
            "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, ".
                "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " .
            "FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id = '$id'";
    $GLOBALS['db']->query($sql);
}

/**
 * 添加优惠活动（非赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   string  $act_name   优惠活动name
 * @param   float   $amount     优惠金额
 */
function add_favourable_to_cart($act_id, $act_name, $amount)
{
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . "(" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) ".
            "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, ".
                "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
    $GLOBALS['db']->query($sql);
}

/**
 * 取得购物车中某优惠活动范围内的总金额
 * @param   array   $favourable     优惠活动
 * @return  float
 */
function cart_favourable_amount($favourable)
{
	$sql_where = $_SESSION['user_id']>0 ? "c.user_id='". $_SESSION['user_id'] ."' " : "c.session_id = '" . SESS_ID . "' AND c.user_id=0 ";//添加 www.yshop100.com
    /* 查询优惠范围内商品总额的sql */
    $sql = "SELECT SUM(c.goods_price * c.goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE c.goods_id = g.goods_id " .
            "AND $sql_where " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
    		"AND g.supplier_id=".$favourable['supplier_id']." ".
            "AND c.is_gift = 0 " .
            "AND c.goods_id > 0 ";

    /* 根据优惠范围修正sql */
    if ($favourable['act_range'] == FAR_ALL)
    {
        // sql do not change
    }
    elseif ($favourable['act_range'] == FAR_CATEGORY)
    {
        /* 取得优惠范围分类的所有下级分类 */
        $id_list = array();
        $cat_list = explode(',', $favourable['act_range_ext']);
        foreach ($cat_list as $id)
        {
            $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
        }

        $sql .= "AND g.cat_id " . db_create_in($id_list);
    }
    elseif ($favourable['act_range'] == FAR_BRAND)
    {
        $id_list = explode(',', $favourable['act_range_ext']);

        $sql .= "AND g.brand_id " . db_create_in($id_list);
    }
    else
    {
        $id_list = explode(',', $favourable['act_range_ext']);

        $sql .= "AND g.goods_id " . db_create_in($id_list);
    }
    
    $sql .= (isset($_REQUEST['sel_goods']) && !empty($_REQUEST['sel_goods'])) ? " AND c.rec_id in (". $_REQUEST['sel_goods'] .") " : "";
    
    //计算某个店铺的商品总额
	if(isset($_REQUEST['suppid'])){
		$sql .= " AND g.supplier_id=".intval($_REQUEST['suppid']);
	}
	//echo $sql;

    /* 优惠范围内的商品总额 */
    return $GLOBALS['db']->getOne($sql);
}
function get_hot_cat_goods($type = '', $num = 20)
{
	
	$sql = 'SELECT g.goods_id,g.cat_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
	"IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
	'g.promote_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
	"FROM " . $GLOBALS['ecs']->table('goods') . ' AS g '.
	"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
	"ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
	'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND  g.is_delete = 0' ;

	switch ($type)
	{
		case 'best':
			$sql .= ' AND is_best = 1';
			break;
		case 'new':
			$sql .= ' AND is_new = 1';
			break;
		case 'hot':
			$sql .= ' AND is_hot = 1';
			break;
		case 'promote':
			$time = gmtime();
			$sql .= " AND is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time'";
			break;
	}

	$sql.=' ORDER BY g.sort_order, g.goods_id DESC';



	if ($num > 0)
	{
		$sql .= ' LIMIT ' . $num;
	}

	//echo $sql;

	$res = $GLOBALS['db']->getAll($sql);

	$goods = array();
	foreach ($res AS $idx => $row)
	{
		if ($row['promote_price'] > 0)
		{
			$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
			$goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
		}
		else
		{
			$goods[$idx]['promote_price'] = '';
		}

	
		$goods[$idx]['id']           = $row['goods_id'];
		$goods[$idx]['name']         = $row['goods_name'];
		$goods[$idx]['brief']        = $row['goods_brief'];
		$goods[$idx]['market_price'] = price_format($row['market_price']);
		$goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
		sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
		$goods[$idx]['shop_price']   = price_format($row['shop_price']);
		$goods[$idx]['thumb']        = empty($row['goods_thumb']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
		$goods[$idx]['goods_img']    = empty($row['goods_img']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
		$goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);


	}

	return $goods;
}
?>
