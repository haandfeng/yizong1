<?php
/**
 * ECSHOP 商品标签打印
 
 * $Author: liubo $
 * $Id: goods_print.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require('includes/lib_goods.php');

/*------------------------------------------------------ */
//-- 批量上传
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'default' || !$_REQUEST['act'])
{
    $smarty->assign('suppliers_list',     get_suppliers_list());
    $smarty->assign('fenxiao_list',       get_fenxiao_list());

    /* 参数赋值 */
    $smarty->assign('ur_here', $_LANG['goods_print']);

    //搜索商品
    $smarty->assign('cat_list', cat_list());
    $smarty->assign('brand_list',   get_brand_list());
    

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_print.htm');
}elseif($_REQUEST['act'] == 'info'){
    $suppliers_id = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);
    $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
    if($user_id > 0){
        $sql = "SELECT invite_code, user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id = '".$user_id."' AND is_fenxiao = '1'";
        $row = $GLOBALS['db']->getRow($sql);
        if($row){
            $invite_code = $row['invite_code'];
            $user_name = $row['user_name'];
        }else{
            sys_msg('该用户未注册分销');
        }
    }else{
        $user_name = '平台方';
    }
    $page_size = isset($_REQUEST['page_size']) ? trim($_REQUEST['page_size']) : '';
    $is_select = !empty($_POST['is_select'])  ? intval($_POST['is_select'])    : 0;
    $goods_ids = !empty($_POST['goods_ids'])    ? trim($_POST['goods_ids'])    : '';
    $select_goods_ids = explode(',', $goods_ids);

    $goods_list = get_goods_print_list($suppliers_id, $select_goods_ids, $is_select);

    $smarty->assign('invite_code', $invite_code);
    $smarty->assign('user_name', $user_name);
    $smarty->assign('user_id', $user_id);
    $smarty->assign('goods_list', $goods_list);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('print/goods_print_info'.$page_size.'.htm');
}elseif ($_REQUEST['act'] == 'new_get_goods_list'){
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;
    $filters = $json->decode($_REQUEST['JSON']);
    $filters->keyword = json_str_iconv($filters->keyword);
    $where = get_where_sql($filters); // 取得过滤条件
    $where .= " AND (goods_sn LIKE '%H%' OR goods_sn LIKE '%Y%' OR goods_sn LIKE '%W%')";

    /* 取得数据 */
    $sql = 'SELECT goods_id, goods_name, goods_sn, shop_price '.
           'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . $where . ' ORDER BY goods_sn ASC';
    $arr = $GLOBALS['db']->getAll($sql);

    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('goods_id' => $val['goods_id'],
                        'goods_name' => "[".$val['goods_sn']."]".$val['goods_name']
                      );
    }
    make_json_result($opt);
}

/**
 * 取得供货商列表
 * @return array    二维数组
 */
function get_suppliers_list()
{
    $sql = 'SELECT suppliers_id, suppliers_name 
            FROM ' . $GLOBALS['ecs']->table('suppliers') . '
            WHERE is_check = 1
            ORDER BY suppliers_name ASC';
    $res = $GLOBALS['db']->query($sql);

    $suppliers_list[0] = "所有商家";
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $suppliers_list[$row['suppliers_id']] = $row['suppliers_name'];
    }

    return $suppliers_list;
}

/**
 * 取得分销商列表
 * @return array    二维数组
 */
function get_fenxiao_list()
{
    
    $sql = "select user_id,user_name,real_name from ". $GLOBALS['ecs']->table('users') . " where isfenxiao_topuser = '1' AND status = '1' order by user_name desc";

    $res = $GLOBALS['db']->query($sql);

    $fenxiao_list[0] = "平台方";
    $fenxiao_list[11165] = "杨艳红";
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $fenxiao_list[$row['user_id']] = $row['user_name'];
    }
    
    return $fenxiao_list;
}

/**
 * 取得商品列表
 * @return array    二维数组
 */
function get_goods_print_list($suppliers_id, $goods_ids, $is_select)
{
    $i=0;
    if($is_select > 0){
        $where = "(";
        foreach ($goods_ids AS $val){
            if($i > 0){
                $where .= " OR ";
            }
            $where .= "g.goods_id = '$val'";
            $i++;
        }
        $where .= ")";
    }else{
        $where = "(g.goods_sn like 'H%' OR g.goods_sn like 'Y%' OR g.goods_sn like 'W%')";
    }
    if($suppliers_id > 0){
        $where .= " AND g.suppliers_id = '$suppliers_id' ";
    }
    
    $sql = "select g.goods_id, g.goods_sn, g.goods_name, g.shop_price, g.goods_thumb, g.guige, g.gongxiao, g.shuoming, ss.suppliers_desc, c.countryname from ". $GLOBALS['ecs']->table('goods') . " as g LEFT JOIN ". $GLOBALS['ecs']->table('suppliers') ." as ss ON g.suppliers_id = ss.suppliers_id LEFT JOIN " . $GLOBALS['ecs']->table('country') . " as c on g.countrycode = c.countrycode where ".$where." order by g.goods_sn ASC";

    $res = $GLOBALS['db']->getALL($sql);

    foreach ($res as $key => $val) {
        $res[$key]['img_path'] = '../'.get_image_path($val['goods_id'], $val['goods_thumb'], true);
    }

    return $res;
}


?>