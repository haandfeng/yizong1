<?php

/**
 * ECSHOP 列出所有分类及品牌
 
 * $Author: derek $
 * $Id: catalog.php 17217 2011-01-19 06:29:08Z derek $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
$ceid = empty($_GET['ceid'])?"":$_GET['ceid'];

//异步调用

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'ajax') {
    include('includes/cls_json.php');
    $last = $_POST['last'];
    $amount = $_POST['amount'];
    $json = new JSON;

    $goodslist = category_getgoods($ceid, $amount, $last);

    foreach ($goodslist as $val) {
        $smarty->assign('goods', $val);
        $res[]['info'] = $GLOBALS['smarty']->fetch('library/catalog_list_ajax.lbi');
    }
     /*
      if(count($goodslist)>0){
      $smarty->assign('goods_list', $goodslist);
      $res[]['info']  = $GLOBALS['smarty']->fetch('library/goods_list_ajax.lbi');
      } */
    die($json->encode($res));
}


if (!$smarty->is_cached('catalog.dwt'))
{
    /* 取出所有分类 */
    $cat_list = cat_list(0, 0, false);
    foreach ($cat_list AS $key=>$val)
    {
        if ($val['is_show'] == 0)
        {
            unset($cat_list[$key]);
        }
    }
    
    assign_template();
    assign_dynamic('catalog');
    $position = assign_ur_here(0, $_LANG['catalog']);
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置

    $smarty->assign('helps',      get_shop_help()); // 网店帮助
    $smarty->assign('cat_list',   $cat_list);       // 分类列表
    $smarty->assign('cat_list',   '');// 分类列表
    $cat = get_categories_tree();



    $smarty->assign('ceid',      $ceid); 
    $smarty->assign('categories',      $cat); // 分类树
    $smarty->assign('brand_list', get_brands());    // 所以品牌赋值
    $smarty->assign('promotion_info', get_promotion_info());
}

$smarty->assign('comefrom',$_GET['comefrom']);
$smarty->display('catalog.dwt');

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_getgoods($cat_id, $size, $last) {
    $limit = " limit ".$last.",".$size;
    $children = get_children($cat_id);
    $where = ' where is_on_sale=1 and is_alone_sale = 1 and is_delete = 0  AND ('.$children.' OR ' . get_extension_goods($children) . ') ORDER BY goods_id DESC '.$limit ;

    /* 获得商品列表 */
    $sql = 'select goods_id,goods_name,shop_price,market_price,goods_thumb from ecs_goods AS g  '.$where;
    
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        
        $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
        $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
        $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
        $arr[$row['goods_id']]['goods_thumb'] =  get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_thumb'], true);
    }
    return $arr;
}
/**
 * 计算指定分类的商品数量
 *
 * @access public
 * @param   integer     $cat_id
 *
 * @return void
 */
function calculate_goods_num($cat_list, $cat_id)
{
    $goods_num = 0;

    foreach ($cat_list AS $cat)
    {
        if ($cat['parent_id'] == $cat_id && !empty($cat['goods_num']))
        {
            $goods_num += $cat['goods_num'];
        }
    }

    return $goods_num;
}

/*分类调用id广告*/
function get_advlist( $position, $num )
{
        $arr = array( );
        $sql = "select ap.ad_width,ap.ad_height,ad.ad_id,ad.ad_name,ad.ad_code,ad.ad_link,ad.ad_id from ".$GLOBALS['ecs']->table( "ecsmart_ad_position" )." as ap left join ".$GLOBALS['ecs']->table( "ecsmart_ad" )." as ad on ad.position_id = ap.position_id where ap.position_name='".$position.( "' and UNIX_TIMESTAMP()>ad.start_time and UNIX_TIMESTAMP()<ad.end_time and ad.enabled=1 limit ".$num );
        $res = $GLOBALS['db']->getAll( $sql );
        foreach ( $res as $idx => $row )
        {
                $arr[$row['ad_id']]['name'] = $row['ad_name'];
                $arr[$row['ad_id']]['url'] = "affiche.php?ad_id=".$row['ad_id']."&uri=".$row['ad_link'];
                $arr[$row['ad_id']]['image'] = "data/afficheimg/".$row['ad_code'];
                $arr[$row['ad_id']]['content'] = "<a href='".$arr[$row['ad_id']]['url']."' target='_blank'><img src='data/afficheimg/".$row['ad_code']."' width='".$row['ad_width']."' height='".$row['ad_height']."' /></a>";
                $arr[$row['ad_id']]['ad_code'] = $row['ad_code'];
        }
        return $arr;
}
?>