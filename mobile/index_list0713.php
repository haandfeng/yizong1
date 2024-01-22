<?php

/**
 * ECSHOP 列出所有分类及品牌
 
 * $Author: derek $
 * $Id: catalog.php 17217 2011-01-19 06:29:08Z derek $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

//异步调用

if ($_REQUEST['act'] == 'ajax') {
    include('includes/cls_json.php');
	$size =10;
	$last=empty($_POST['last'])?0:$_POST['last'];
	$size=empty($_POST['amount'])?0:$_POST['amount'];
	
    $json = new JSON; 
    $goodslist = get_list_goods('other',$_CFG['exchange_rate'],$last,$size);

    foreach ($goodslist as $val) {
        $smarty->assign('goods', $val);
        $res[]['info'] = $GLOBALS['smarty']->fetch('library/index_list_ajax.lbi');
    }

    die($json->encode($res));
}


/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot
 * @return  array
 */
function get_list_goods($type = '', $rate='',$last,$size)
{
        //初始化数据 
        $type_goods['other'] = array(); 
        $limit = " limit ".$last.",".$size;
        //取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
        $sql = 'SELECT cat.cat_name,g.goods_id, g.goods_name,g.click_count, g.goods_name_style, g.market_price,g.suppliers_id,ss.suppliers_desc ,g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.exclusive, ".
                "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img, RAND() AS rnd " .
                'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' "."LEFT JOIN ". $GLOBALS['ecs']->table('category') ."AS cat ON cat.cat_id = g.cat_id ".
                "LEFT JOIN ". $GLOBALS['ecs']->table('suppliers') ."AS ss ON ss.suppliers_id = g.suppliers_id ";

        $sql .= ' WHERE  g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0';
        //秒杀商品不显示
        $sql .= ' AND g.is_seckill = 0';
        $sql .= ' ORDER BY g.sort_order, g.second_order ASC, g.suppliers_id DESC, g.last_update DESC '.$limit;
	
        $result = $GLOBALS['db']->getAll($sql);
        foreach ($result AS $idx => $row)
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
            $goods[$idx]['brand_name']   = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
            $goods[$idx]['class_name']   = isset($row['cat_name']) ? $row['cat_name'] : '';
            $goods[$idx]['goods_style_name']   = add_style($row['goods_name'],$row['goods_name_style']);

            $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                               sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['short_style_name']   = add_style($goods[$idx]['short_name'],$row['goods_name_style']);
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price']   = price_format($row['shop_price']);
            $goods[$idx]['final_price'] = price_format(get_final_price($row['goods_id'], 1, false));
            $goods[$idx]['hkd_price'] = $goods[$idx]['final_price']*$rate;//兑换后港元价格
            $goods[$idx]['is_exclusive']  = is_exclusive($row['exclusive'],get_final_price($row['goods_id']));
            if(empty($row['goods_thumb'])){
                $row['goods_thumb'] = $GLOBALS['db']->getOne("SELECT thumb_url FROM ".$GLOBALS['ecs']->table('goods_gallery')." WHERE goods_id=".$row['goods_id']);
            }
            $goods[$idx]['thumb']        = get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img']    = get_pc_url().'/'. get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
	    $goods[$idx]['sell_count']   =selled_count($row['goods_id']);
	    $goods[$idx]['pinglun']   =get_evaluation_sum($row['goods_id']);
	    $goods[$idx]['count'] = selled_count($row['goods_id']);
	    $goods[$idx]['click_count'] = $row['click_count'];
        $goods[$idx]['suppliers_id'] = $row['suppliers_id'];
        $goods[$idx]['suppliers_desc'] = $row['suppliers_desc'];
            
                $type_goods['other'][] = $goods[$idx];
             
        }
    

    return $type_goods[$type];
}
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
	
file_put_contents("21log.txt", $sql,FILE_APPEND);
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