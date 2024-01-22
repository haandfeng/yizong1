<?php

/**
 * ECSHOP 门店统计
 
 * $Author: langlibin $
 * $Id: sellers_stats.php 17217 2015-10-26 11:06:08Z langlibin $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/statistic.php');
require_once(ROOT_PATH . '/includes/lib_goods.php');

$smarty->assign('lang', $_LANG);

// act操作项的初始化
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

// 时间参数
if (isset($_REQUEST['start_date']) && !empty($_REQUEST['end_date']))
{
    $start_date = local_strtotime($_REQUEST['start_date']);
    $end_date = local_strtotime($_REQUEST['end_date']);
    if ($start_date == $end_date)
    {
        $end_date = $start_date + 86400;
    }
}
else
{
    $today = local_strtotime(local_date('Y-m-d'));   //本地时间
    $start_date = $today - 86400 * 6;
    $end_date = $today + 86400;               //至明天零时
}

if ($_REQUEST['act'] == 'list')
{
    admin_priv('order_seller');

    // 查询条件
    $where = ' WHERE oi.add_time >=' . $start_date . ' AND oi.add_time <=' . $end_date;

    //供货商
    if (isset($_REQUEST['sel_suppliers']) && $_REQUEST['sel_suppliers'] > 0){
        $suppliers_id = trim($_REQUEST['sel_suppliers']);
        $where .=  " AND oi.suppliers_id = '".$suppliers_id."'";
        $smarty->assign('sel_suppliers', $suppliers_id);
    }

    $stores_list = $db->getAll('SELECT store_id, store_name FROM zs_stores WHERE 1=1 ORDER BY store_id DESC');
    $stores_list[] = array('store_id' => '0', 'store_name' => '平台方');
    //总下单数
    // $stores_list[] = array('store_id' => 'all', 'store_name' => '全平台');

    // 分类名称字符串
    $stores_name_arr = '';
    // 下单金额字符串
    $goods_price_arr = '';
    // 下单商品数字符串
    $goods_count_arr = '';
    // 下单量数字符串
    $orders_count_arr = '';

    foreach($stores_list as $value)
    {
        // 分类id
        $store_id = $value['store_id'];
        // 分类名称
        $stores_name = $value['store_name'];

        
        if($value['store_id'] != 'all'){
            $where2 =  ' AND oi.store_id = '. $store_id;
        }else{
            $where2 = '';
        }

        // 查询分类下下单金额
        $sql = 'SELECT IFNULL(SUM(og.goods_price), 0) goods_price FROM ' . $ecs->table('order_info') . ' oi, '
            . $ecs->table('order_goods') . ' og, ' . $ecs->table('goods') . ' g ' . $where
            . ' AND og.goods_id = g.goods_id AND og.order_id = oi.order_id AND oi.order_status = "5"' . $where2;
        // 取得下单金额
        $goods_price = $db->getOne($sql);
        $stores_name_arr .= "'" . $stores_name . "',";
        $goods_price_arr .= "'" . $goods_price . "',";

        // 查询分类下下单商品数
        $sql = 'SELECT SUM(og.goods_number) goods_count FROM ' . $ecs->table('order_info') . ' oi, '
            . $ecs->table('order_goods') . ' og, ' . $ecs->table('goods') . ' g ' . $where
            . ' AND og.goods_id = g.goods_id AND og.order_id = oi.order_id AND oi.order_status = "5"' . $where2;
        // 取得下单商品数
        $goods_count = $db->getOne($sql);
        $goods_count_arr .= "'" . $goods_count . "',";

        // 查询分类下下单量
        $sql = 'SELECT COUNT(*) goods_count FROM ' . $ecs->table('order_info') . ' oi, '
            . $ecs->table('order_goods') . ' og, ' . $ecs->table('goods') . ' g ' . $where
            . ' AND og.goods_id = g.goods_id AND og.order_id = oi.order_id AND oi.order_status = "5"' . $where2;
        // 取得下单量
        $orders_count = $db->getOne($sql);
        $orders_count_arr .= "'" . $orders_count . "',";
    }

    $smarty->assign('ur_here', '导购员统计');

    $smarty->assign('stores_name_arr', $stores_name_arr);
    $smarty->assign('goods_price_arr', $goods_price_arr);
    $smarty->assign('goods_count_arr', $goods_count_arr);
    $smarty->assign('orders_count_arr', $orders_count_arr);
    // 开始时间
    $smarty->assign('start_date', local_date($_CFG['date_format'], $start_date));
    // 终了时间
    $smarty->assign('end_date', local_date($_CFG['date_format'], $end_date));

    assign_query_info();
    $smarty->display('sellers_stats.htm');
}

?>