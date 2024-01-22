<?php

/**
 * ECSHOP 首页文件
 
 * $Author: derek $
 * $Id: index.php 17217 2011-01-19 06:29:08Z derek $
*/
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH .'/jssdk.php');
 
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
if (isset($_REQUEST['is_c']))
{
    $is_c = intval($_REQUEST['is_c']);
}
if($is_c == 1){

    header("Location:../index.php?is_c=1"); 
}
/*------------------------------------------------------ */
//-- Shopex系统地址转换
/*------------------------------------------------------ */
if (!empty($_GET['gOo']))
{
    if (!empty($_GET['gcat']))
    {
        /* 商品分类。*/
        $Loaction = 'category.php?id=' . $_GET['gcat'];
    }
    elseif (!empty($_GET['acat']))
    {
        /* 文章分类。*/
        $Loaction = 'article_cat.php?id=' . $_GET['acat'];
    }
    elseif (!empty($_GET['goodsid']))
    {
        /* 商品详情。*/
        $Loaction = 'goods.php?id=' . $_GET['goodsid'];
    }
    elseif (!empty($_GET['articleid']))
    {
        /* 文章详情。*/
        $Loaction = 'article.php?id=' . $_GET['articleid'];
    }

    if (!empty($Loaction))
    {
        ecs_header("Location: $Loaction\n");

        exit;
    }
}

//判断是否有ajax请求
$act = !empty($_GET['act']) ? $_GET['act'] : '';
if ($act == 'cat_rec')
{
    $rec_array = array(1 => 'best', 2 => 'new', 3 => 'hot');
    $rec_type = !empty($_REQUEST['rec_type']) ? intval($_REQUEST['rec_type']) : '1';
    $cat_id = !empty($_REQUEST['cid']) ? intval($_REQUEST['cid']) : '0';
    include_once('includes/cls_json.php');
    $json = new JSON;
    $result   = array('error' => 0, 'content' => '', 'type' => $rec_type, 'cat_id' => $cat_id);

    $children = get_children($cat_id);
    $smarty->assign($rec_array[$rec_type] . '_goods',      get_category_recommend_goods($rec_array[$rec_type], $children));    // 推荐商品
    $smarty->assign('cat_rec_sign', 1);
    $result['content'] = $smarty->fetch('library/recommend_' . $rec_array[$rec_type] . '.lbi');
    die($json->encode($result));
}
//file_put_contents('20200427.txt',date('Y-m-d H:i:s')."HTTP_USER_AGENT:".$_SERVER['HTTP_USER_AGENT']."\r\n" , FILE_APPEND); 
if(preg_match('/MicroMessenger/', $_SERVER['HTTP_USER_AGENT']))
{
	//file_put_contents('20200427.txt',date('Y-m-d H:i:s')."isappli:\r\n" , FILE_APPEND);
	$smarty->assign('isappli','');
}
else
{
	//file_put_contents('20200427.txt',date('Y-m-d H:i:s')."isappli:display:none;\r\n" , FILE_APPEND);
	$smarty->assign('isappli','display:none;');
}
if(preg_match('/hfxltbapp/', $_SERVER['HTTP_USER_AGENT']))
{ 
	$smarty->assign('hfxltbapp',1);
}
else
{ 
	$smarty->assign('hfxltbapp',0);
}
/*------------------------------------------------------ */
//-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内容
/*------------------------------------------------------ */
/* 缓存编号 */
//$cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-' . $_CFG['lang']));

//if (!$smarty->is_cached('index.dwt', $cache_id))
if(true)
{
	file_put_contents('20200427.txt',date('Y-m-d H:i:s')."is_cached\r\n" , FILE_APPEND);
    assign_template();
    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
    $smarty->assign('flash_theme',     $_CFG['flash_theme']);  // Flash轮播图片模板

    $smarty->assign('feed_url',        ($_CFG['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

    $smarty->assign('categories',      get_categories_tree()); // 分类树
    $smarty->assign('helps',           get_shop_help());       // 网店帮助
    $smarty->assign('top_goods',       get_top10());           // 销售排行

$smarty->assign('uid',      $_SESSION['user_id']); 
$shipscancode=$GLOBALS['db']->getOne("select shipscancode from " . $GLOBALS['ecs']->table('users') . " WHERE user_id='" . $_SESSION['user_id'] . "'");
$smarty->assign('shipscancode',      $shipscancode); 
    $smarty->assign('bs_goods',       get_recommend_goods('bs','',$_CFG['exchange_rate']));     // 保税专区
    $smarty->assign('best_goods',      get_recommend_goods('best','',$_CFG['exchange_rate']));    // 推荐商品
    $smarty->assign('new_goods',       get_recommend_goods('new','',$_CFG['exchange_rate']));     // 最新商品
    $smarty->assign('hot_goods',       get_recommend_goods('hot','',$_CFG['exchange_rate']));     // 热点文章
    $smarty->assign('promotion_goods', get_promote_goods()); // 特价商品
    $smarty->assign('brand_list',      get_brands());
    $smarty->assign('promotion_info',  get_promotion_info()); // 增加一个动态显示所有促销信息的标签栏

    // $smarty->assign('invoice_list',    index_get_invoice_query());  // 发货查询
    // $smarty->assign('new_articles',    index_get_new_articles());   // 最新文章
    // $smarty->assign('group_buy_goods', index_get_group_buy());      // 团购商品
    // $smarty->assign('auction_list',    index_get_auction());        // 拍卖活动
    // $smarty->assign('shop_notice',     $_CFG['shop_notice']);       // 商店公告
	//yyy添加start	
	$smarty->assign('wap_index_ad',get_wap_advlist('wap首页幻灯广告', 10));  //wap首页幻灯广告位
	//$smarty->assign('wap_index_icon',get_wap_advlist('wap端首页8个图标', 8));  //wap首页幻灯广告位
    //$smarty->assign('wap_index_img',get_wap_advlist('手机端首页精品推荐广告', 5));  //wap首页幻灯广告位
    // $smarty->assign('wap_index_ad_left',get_wap_advlist('首页导航下左边广告图', 1));
    // $smarty->assign('wap_index_ad_right1',get_wap_advlist('首页导航下右边上部分广告图', 1));
    // $smarty->assign('wap_index_ad_right2',get_wap_advlist('首页导航下右边下部分广告图', 1));
    $smarty->assign('wap_index_ad_top',get_wap_advlist('web首页幻灯片上方装饰', 1));
    $smarty->assign('wap_index_ad_bottom',get_wap_advlist('web首页幻灯片下方装饰', 1));
    $smarty->assign('wap_index_pop_ad',get_wap_advlist('首页气泡下广告位', 4));
    $smarty->assign('wap_index_redbag_ad',get_wap_advlist('首页红包广告位', 1));
    $smarty->assign('wap_menu_ads',get_wap_advlist('菜单上方推荐', 4));
    $smarty->assign('wap_new_ads',get_wap_advlist('新品上市推荐', 3));
    $smarty->assign('wap_hot_ads',get_wap_advlist('热销商品推荐', 10));
    $smarty->assign('wap_bs_ads',get_wap_advlist('保税专区推荐', 6));
    $smarty->assign('wap_zy_ads',get_wap_advlist('直邮专区推荐', 6));

    //对应楼层下广告位
    $smarty->assign('wap_new_adv',get_wap_advlist('新品上市广告位', 5));
    $smarty->assign('wap_hot_adv',get_wap_advlist('热销商品广告位', 1));
    $smarty->assign('wap_best_adv',get_wap_advlist('精品推荐广告位', 1));
    $smarty->assign('wap_bs_adv',get_wap_advlist('保税专区广告位', 1));
    $smarty->assign('wap_zy_adv',get_wap_advlist('直邮专区广告位', 1));
    $smarty->assign('wap_bsth_adv',get_wap_advlist('保税特惠广告位', 1));
    //print_r(get_wap_advlist('首页导航下左边广告图', 1));exit;
	$smarty->assign('menu_list',get_menu()); //首页导航栏
    //print_r(get_recommend_goods('new','',$_CFG['exchange_rate']));exit;
	
	//yyy添加end
    //广告位下标题
    $smarty->assign('wap_new_title',get_wap_advlist('新品上市标题', 1));
    $smarty->assign('wap_hot_title',get_wap_advlist('热销推荐标题', 1));
    $smarty->assign('wap_all_title',get_wap_advlist('所有商品标题', 1));
    $smarty->assign('wap_bs_title',get_wap_advlist('保税专区标题', 1));
    $smarty->assign('wap_bsth_title',get_wap_advlist('保税特惠标题', 1));
    $smarty->assign('wap_huodong_title',get_wap_advlist('最新活动标题', 1));
    $smarty->assign('wap_menu_title',get_wap_advlist('菜单推荐标题', 2));
	
    /* 首页主广告设置 */
    $smarty->assign('index_ad',     $_CFG['index_ad']);
    if ($_CFG['index_ad'] == 'cus')
    {
        $sql = 'SELECT ad_type, content, url FROM ' . $ecs->table("ad_custom") . ' WHERE ad_status = 1';
        $ad = $db->getRow($sql, true);
        $smarty->assign('ad', $ad);
    }

    /* links */
    $links = index_get_links();
    $smarty->assign('img_links',       $links['img']);
    $smarty->assign('txt_links',       $links['txt']);
    $smarty->assign('data_dir',        DATA_DIR);       // 数据目录
	
	
	/*jdy add 0816 添加首页幻灯插件*/	
$smarty->assign("flash",get_flash_xml());
$smarty->assign('flash_count',count(get_flash_xml()));


    /* 首页推荐分类 */
    $cat_recommend_res = $db->getAll("SELECT c.cat_id, c.cat_name, cr.recommend_type FROM " . $ecs->table("cat_recommend") . " AS cr INNER JOIN " . $ecs->table("category") . " AS c ON cr.cat_id=c.cat_id");
    if (!empty($cat_recommend_res))
    {
        $cat_rec_array = array();
        foreach($cat_recommend_res as $cat_recommend_data)
        {
            $cat_rec[$cat_recommend_data['recommend_type']][] = array('cat_id' => $cat_recommend_data['cat_id'], 'cat_name' => $cat_recommend_data['cat_name']);
        }
        $smarty->assign('cat_rec', $cat_rec);
    }

    /* 页面中的动态内容 */
    assign_dynamic('index');
}
//取出logo图片
$sql = 'select path from ecs_logo';
$res = $db->getAll($sql);
$smarty->assign('logo',$res[0]['path']);
if($_COOKIE['tishi'])
{
    $smarty->assign('tishi',0);
}else{
    setcookie('tishi',1);
    $smarty->assign('tishi',1);
}
$smarty->assign('categorylist',getcategorylist());
$smarty->display('index.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 调用发货单查询
 *
 * @access  private
 * @return  array
 */
function index_get_invoice_query()
{
    $sql = 'SELECT o.order_sn, o.invoice_no, s.shipping_code FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o' .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('shipping') . ' AS s ON s.shipping_id = o.shipping_id' .
            " WHERE invoice_no > '' AND shipping_status = " . SS_SHIPPED .
            ' ORDER BY shipping_time DESC LIMIT 10';
    $all = $GLOBALS['db']->getAll($sql);

    foreach ($all AS $key => $row)
    {
        $plugin = ROOT_PATH . 'includes/modules/shipping/' . $row['shipping_code'] . '.php';

        if (file_exists($plugin))
        {
            include_once($plugin);

            $shipping = new $row['shipping_code'];
            $all[$key]['invoice_no'] = $shipping->query((string)$row['invoice_no']);
        }
    }

    clearstatcache();

    return $all;
}

/**
 * 获得最新的文章列表。
 *
 * @access  private
 * @return  array
 */
function index_get_new_articles()
{
    $sql = 'SELECT a.article_id, a.title, ac.cat_name, a.add_time, a.file_url, a.open_type, ac.cat_id, ac.cat_name ' .
            ' FROM ' . $GLOBALS['ecs']->table('article') . ' AS a, ' .
                $GLOBALS['ecs']->table('article_cat') . ' AS ac' .
            ' WHERE a.is_open = 1 AND a.cat_id = ac.cat_id AND ac.cat_type = 1' .
            ' ORDER BY a.article_type DESC, a.add_time DESC LIMIT ' . $GLOBALS['_CFG']['article_number'];
    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    foreach ($res AS $idx => $row)
    {
        $arr[$idx]['id']          = $row['article_id'];
        $arr[$idx]['title']       = $row['title'];
        $arr[$idx]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
                                        sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];
        $arr[$idx]['cat_name']    = $row['cat_name'];
        $arr[$idx]['add_time']    = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);
        $arr[$idx]['url']         = $row['open_type'] != 1 ?
                                        build_uri('article', array('aid' => $row['article_id']), $row['title']) : trim($row['file_url']);
        $arr[$idx]['cat_url']     = build_uri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);
    }

    return $arr;
}

/**
 * 获得最新的团购活动
 *
 * @access  private
 * @return  array
 */
function index_get_group_buy()
{
	$sql = 'SELECT value FROM `ecs_shop_config` WHERE id=1073';
	$ishide=$GLOBALS['db']->getOne($sql);
	$where1=' ';
	if($ishide=='1')
	{
		$where1=' and ishide=0 ';
	}
    $time = gmtime();
    $limit = get_library_number('group_buy', 'index');
	
    $group_buy_list = array();
    if ($limit > 0)
    {
        $sql = 'SELECT gb.*,g.*,gb.act_id AS group_buy_id, gb.goods_id, gb.ext_info, gb.goods_name, g.goods_thumb, g.goods_img ' .
                'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS gb, ' .
                    $GLOBALS['ecs']->table('goods') . ' AS g ' .
                "WHERE gb.act_type = '" . GAT_GROUP_BUY . "' " .
                "AND g.goods_id = gb.goods_id " .
                "AND gb.start_time <= '" . $time . "' " .
                "AND gb.end_time >= '" . $time . "' " .
                "AND g.is_delete = 0 " .$where1.
                "ORDER BY gb.act_id DESC " .
                "LIMIT $limit" ;
				
        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            /* 如果缩略图为空，使用默认图片 */
            $row['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $row['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);

            /* 根据价格阶梯，计算最低价 */
            $ext_info = unserialize($row['ext_info']);
            $price_ladder = $ext_info['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder))
            {
                $row['last_price'] = price_format(0);
            }
            else
            {
                foreach ($price_ladder AS $amount_price)
                {
                    $price_ladder[$amount_price['amount']] = $amount_price['price'];
                }
            }
            ksort($price_ladder);
            $row['last_price'] = price_format(end($price_ladder));
            $row['url'] = build_uri('group_buy', array('gbid' => $row['group_buy_id']));
            $row['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                           sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $row['short_style_name']   = add_style($row['short_name'],'');
			
			$stat = group_buy_stat($row['act_id'], $row['deposit']);
			$row['valid_goods'] = $stat['valid_goods'];
            $group_buy_list[] = $row;
        }
    }

    return $group_buy_list;
}

/**
 * 取得拍卖活动列表
 * @return  array
 */
function index_get_auction()
{
	$sql = 'SELECT value FROM `ecs_shop_config` WHERE id=1073';
	$ishide=$GLOBALS['db']->getOne($sql);
	$where1=' ';
	if($ishide=='1')
	{
		$where1=' and ishide=0 ';
	}
    $now = gmtime();
    $limit = get_library_number('auction', 'index');
    $sql = "SELECT a.act_id, a.goods_id, a.goods_name, a.ext_info, g.goods_thumb ".
            "FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS a," .
                      $GLOBALS['ecs']->table('goods') . " AS g" .
            " WHERE a.goods_id = g.goods_id" .
            " AND a.act_type = '" . GAT_AUCTION . "'" .
            " AND a.is_finished = 0" .
            " AND a.start_time <= '$now'" .
            " AND a.end_time >= '$now'" .
            " AND g.is_delete = 0" .$where1.
            " ORDER BY a.start_time DESC" .
            " LIMIT $limit";
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $ext_info = unserialize($row['ext_info']);
        $arr = array_merge($row, $ext_info);
        $arr['formated_start_price'] = price_format($arr['start_price']);
        $arr['formated_end_price'] = price_format($arr['end_price']);
        $arr['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr['url'] = build_uri('auction', array('auid' => $arr['act_id']));
        $arr['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                           sub_str($arr['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $arr['goods_name'];
        $arr['short_style_name']   = add_style($arr['short_name'],'');
        $list[] = $arr;
    }

    return $list;
}

/**
 * 获得所有的友情链接
 *
 * @access  private
 * @return  array
 */
function index_get_links()
{
    $sql = 'SELECT link_logo, link_name, link_url FROM ' . $GLOBALS['ecs']->table('friend_link') . ' ORDER BY show_order';
    $res = $GLOBALS['db']->getAll($sql);

    $links['img'] = $links['txt'] = array();

    foreach ($res AS $row)
    {
        if (!empty($row['link_logo']))
        {
            $links['img'][] = array('name' => $row['link_name'],
                                    'url'  => $row['link_url'],
                                    'logo' => $row['link_logo']);
        }
        else
        {
            $links['txt'][] = array('name' => $row['link_name'],
                                    'url'  => $row['link_url']);
        }
    }

    return $links;
}

function get_flash_xml()
{
    $flashdb = array();
    if (file_exists(ROOT_PATH . DATA_DIR . '/flash_data.xml'))
    {

        // 兼容v2.7.0及以前版本
        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/flash_data.xml'), $t, PREG_SET_ORDER))
        {
            preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/flash_data.xml'), $t, PREG_SET_ORDER);
        }

        if (!empty($t))
        {
            foreach ($t as $key => $val)
            {
                $val[4] = isset($val[4]) ? $val[4] : 0;
                $flashdb[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
				
				//print_r($flashdb);
            }
        }
    }
    return $flashdb;
}

function get_wap_advlist( $position, $num )
{
		$arr = array( );
		$sql = "select ap.ad_width,ap.ad_height,ad.ad_id,ad.ad_name,ad.ad_code,ad.ad_link,ad.ad_id from ".$GLOBALS['ecs']->table( "ecsmart_ad_position" )." as ap left join ".$GLOBALS['ecs']->table( "ecsmart_ad" )." as ad on ad.position_id = ap.position_id where ap.position_name='".$position.( "' and UNIX_TIMESTAMP()>ad.start_time and UNIX_TIMESTAMP()<ad.end_time and ad.enabled=1 order by ad.order ASC, ad.ad_id DESC limit ".$num );
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
function getcategorylist()
{ 
	$sql = "select * from ".$GLOBALS['ecs']->table('category')." where parent_id=0  AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC" ;
	$list = $GLOBALS['db']->getAll($sql);
	$arr = array();
	foreach($list as $key => $rows)
	{
		$arr[$key]['id'] = $rows['cat_id'];
		$arr[$key]['name'] = $rows['cat_name']; 
	} 
	return $arr;
}

function get_is_computer(){
$is_computer=$_REQUEST['is_computer'];
return $is_computer;
}

function get_menu()
{
	$sql = "select * from ".$GLOBALS['ecs']->table('ecsmart_menu')." order by sort";
	$list = $GLOBALS['db']->getAll($sql);
	$arr = array();
	foreach($list as $key => $rows)
	{
		$arr[$key]['id'] = $rows['id'];
		$arr[$key]['menu_name'] = $rows['menu_name'];
		$arr[$key]['menu_img'] = $rows['menu_img'];
		$arr[$key]['menu_url'] = $rows['menu_url']; 
	} 
	return $arr;
}
function vislog()
{
	$url=$_SERVER["REQUEST_URI"];
	$ip=getip();
	$remark = $_SERVER['HTTP_USER_AGENT'];
	$user='游客';  
	if($_SESSION['user_id']){
		$user='用户'.$_SESSION['user_id'];  
	}	
	$sql="insert into ecs_ip_log(username,ip,createtime,url,remark)values('".$user."','".$ip."',now(),'".$url."','".$remark."')";
	$GLOBALS['db']->query($sql);
}
function getip() {

  static $ip = '';

  $ip = $_SERVER['REMOTE_ADDR'];

  if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {

    $ip = $_SERVER['HTTP_CDN_SRC_IP'];

  } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {

    $ip = $_SERVER['HTTP_CLIENT_IP'];

  } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {

    foreach ($matches[0] AS $xip) {

      if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {

        $ip = $xip;

        break;

      }

    }

  }

  return $ip;

}

?>