<?php

/**
 * ECSHOP 入驻商列表
 
 * $Author: liubo $
 * $Id: ruzhu.php 17217 2020-08-05 06:29:08Z liubo $
*/


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}


/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

assign_template();
$position = assign_ur_here();
$smarty->assign('page_title',      $position['title']);    // 页面标题

$sql = "SELECT s.supplier_id, s.supplier_name, s.add_time, sr.rank_name FROM " . $GLOBALS['ecs']->table('supplier') . " AS s LEFT JOIN " . $GLOBALS['ecs']->table('supplier_rank') . " AS sr ON s.rank_id = sr.rank_id WHERE s.status = '1'";
$supplier_list = $GLOBALS['db']->getAll($sql);
foreach ($supplier_list as $key => $val)
{
    $supplier_list[$key]['add_time'] = local_date("Y年m月d日", $supplier_list[$key]['add_time']);
    $supplier_list[$key]['goods_number'] = get_supplier_goods_number($supplier_list[$key]['supplier_id']);
}
$smarty->assign('supplier_list', $supplier_list);
$smarty->assign('xlt_goods', get_supplier_goods_number(0));


$sql = 'select path from ecs_logo';
$res = $db->getAll($sql);
$smarty->assign('logo',$res[0]['path']);

$smarty->display('ruzhu.dwt', $cache_id);

function get_supplier_goods_number($supplier_id = 0){
	if($supplier_id > 0){
		$sql = "SELECT COUNT(sgc.goods_id) FROM " . $GLOBALS['ecs']->table('supplier_goods_cat') . " AS sgc LEFT JOIN  " . $GLOBALS['ecs']->table('goods') . " AS g ON sgc.goods_id = g.goods_id WHERE g.supplier_id = '".$supplier_id."' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 and ispchide=0 and g.goods_id < 1529 ";
	}else{
		$sql = "SELECT COUNT(goods_id) FROM " . $GLOBALS['ecs']->table('goods') . " WHERE supplier_id = '".$supplier_id."' AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 and ispchide=0 and goods_id < 1529 ";
	}
	$row = $GLOBALS['db']->getOne($sql);

	return $row;
}

?>