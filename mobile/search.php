<?php

/**
 * ECSHOP 搜索程序
 
 * $Author: derek $
 * $Id: search.php 17217 2011-01-19 06:29:08Z derek $
*/

define('IN_ECS', true);

if (!function_exists("htmlspecialchars_decode"))
{
    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
    {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
}

if (empty($_GET['encode']))
{
    $string = array_merge($_GET, $_POST);
    if (get_magic_quotes_gpc())
    {
        require(dirname(__FILE__) . '/includes/lib_base.php');
        //require(dirname(__FILE__) . '/includes/lib_common.php');

        $string = stripslashes_deep($string);
    }
    $string['search_encode_time'] = time();
    $string = str_replace('+', '%2b', base64_encode(serialize($string)));

    header("Location: search.php?encode=$string\n");

    exit;
}
else
{
    $string = base64_decode(trim($_GET['encode']));
    if ($string !== false)
    {
        $string = unserialize($string);
        if ($string !== false)
        {
            /* 用户在重定向的情况下当作一次访问 */
            if (!empty($string['search_encode_time']))
            {
                if (time() > $string['search_encode_time'] + 2)
                {
                    define('INGORE_VISIT_STATS', true);
                }
            }
            else
            {
                define('INGORE_VISIT_STATS', true);
            }
        }
        else
        {
            $string = array();
        }
    }
    else
    {
        $string = array();
    }
}

require(dirname(__FILE__) . '/includes/init.php');

$_REQUEST = array_merge($_REQUEST, addslashes_deep($string));

$_REQUEST['act'] = !empty($_REQUEST['act']) ? trim($_REQUEST['act']) : '';

if (!empty($_REQUEST['search_type']) && $_REQUEST['search_type'] == 'stores')
{
	header("Location:stores.php?keywords=" . $_REQUEST['keywords']);
}
/**
 * 删除搜索记录
 */
if($_REQUEST['is_ajax'] && $_REQUEST['act']=='del_session_search'){
   $name = $_REQUEST['name'];
   include_once('includes/cls_json.php');
   $json = new JSON; 
   unset($_SESSION[$name]);
   die($json->encode(1));
   exit;
}

/**
 * 搜索记录
 */
if($_REQUEST['is_ajax'] && $_REQUEST['act']=='get_session_search_goods'){
   include_once('includes/cls_json.php');
   $json = new JSON; 
    $res = '';
    if(isset($_SESSION['search_goods'])){
        foreach($_SESSION['search_goods'] as $val){
          $res .=  "<li><a href=javascript:search_key('".$val."')>".$val."</a></li>";
        }
    }
   die($json->encode($res));
}
/*------------------------------------------------------ */
//-- 高级搜索
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'advanced_search')
{
    $goods_type = !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
    $attributes = get_seachable_attributes($goods_type);
    $smarty->assign('goods_type_selected', $goods_type);
    $smarty->assign('goods_type_list',     $attributes['cate']);
    $smarty->assign('goods_attributes',    $attributes['attr']);

    assign_template();
    assign_dynamic('search');
    $position = assign_ur_here(0, $_LANG['advanced_search']);
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

    $smarty->assign('categories', get_categories_tree()); // 分类树
    $smarty->assign('helps',      get_shop_help());       // 网店帮助
    $smarty->assign('top_goods',  get_top10());           // 销售排行
    $smarty->assign('promotion_info', get_promotion_info());
    $smarty->assign('cat_list',   cat_list(0, 0, true, 2, false));
    $smarty->assign('brand_list', get_brand_list());
    $smarty->assign('action',     'form');
    $smarty->assign('use_storage', $_CFG['use_storage']);

	if(preg_match('/hfxltbapp/', $_SERVER['HTTP_USER_AGENT']))
	{ 
		$smarty->assign('hfxltbapp',1);
	}
	else
	{ 
		$smarty->assign('hfxltbapp',0);
	}
    $smarty->display('search.dwt');

    exit;
}
/*------------------------------------------------------ */
//-- 异步搜索结果
/*------------------------------------------------------ */
elseif($_REQUEST['is_ajax'] && $_REQUEST['act']=='goods_list')
{
    $_REQUEST['keywords']   = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))     : '';
    $_REQUEST['brand']      = !empty($_REQUEST['brand'])      ? intval($_REQUEST['brand'])      : 0;
    $_REQUEST['category']   = !empty($_REQUEST['category'])   ? intval($_REQUEST['category'])   : 0;
    $_REQUEST['min_price']  = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
    $_REQUEST['max_price']  = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
    $_REQUEST['goods_type'] = !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
    $_REQUEST['sc_ds']      = !empty($_REQUEST['sc_ds']) ? intval($_REQUEST['sc_ds']) : 0;
    $_REQUEST['outstock']   = !empty($_REQUEST['outstock']) ? 1 : 0;

    $size =10;
    $last=empty($_POST['last'])?0:$_POST['last'];
    // $size=empty($_POST['amount'])?0:$_POST['amount'];
    $limit = " limit ".$last.",".$size;
        /* 把搜索结果保存在session中 */
    if(!in_array($_REQUEST['keywords'],$_SESSION['search_goods'])&& !empty($_REQUEST['keywords'])){
        $_SESSION['search_goods'][]  = $_REQUEST['keywords'];
    }

    $sql = 'SELECT value FROM `ecs_shop_config` WHERE id=1073';
    $ishide=$GLOBALS['db']->getOne($sql);
    $where1=' ';
    if($ishide=='1')
    {
        $where1=' and ishide=0 ';
    }
   

    /* 初始化搜索条件 */
    $keywords  = '';
    $tag_where = '';
    if (!empty($_REQUEST['keywords']))
    {
        $arr = array();
        if (stristr($_REQUEST['keywords'], ' AND ') !== false)
        {
            /* 检查关键字中是否有AND，如果存在就是并 */
            $arr        = explode('AND', $_REQUEST['keywords']);
            $operator   = " AND ";
        }
        elseif (stristr($_REQUEST['keywords'], ' OR ') !== false)
        {
            /* 检查关键字中是否有OR，如果存在就是或 */
            $arr        = explode('OR', $_REQUEST['keywords']);
            $operator   = " OR ";
        }
        elseif (stristr($_REQUEST['keywords'], ' + ') !== false)
        {
            /* 检查关键字中是否有加号，如果存在就是或 */
            $arr        = explode('+', $_REQUEST['keywords']);
            $operator   = " OR ";
        }
        else
        {
            /* 检查关键字中是否有空格，如果存在就是并 */
            $arr        = explode(' ', $_REQUEST['keywords']);
            $operator   = " AND ";
        }

        $keywords = 'AND (';
        $goods_ids = array();
        foreach ($arr AS $key => $val)
        {
            if ($key > 0 && $key < count($arr) && count($arr) > 1)
            {
                $keywords .= $operator;
            }
            $val        = mysql_like_quote(trim($val));
            $sc_dsad    = $_REQUEST['sc_ds'] ? " OR goods_desc LIKE '%$val%'" : '';
            $keywords  .= "(g.goods_name LIKE '%$val%' OR g.goods_sn LIKE '%$val%' OR g.keywords LIKE '%$val%' $sc_dsad)";

            $sql = 'SELECT DISTINCT goods_id FROM ' . $GLOBALS['ecs']->table('tag') . " WHERE tag_words LIKE '%$val%' ";
            $res = $db->query($sql);
            while ($row = $db->FetchRow($res))
            {
                $goods_ids[] = $row['goods_id'];
            }

            $db->autoReplace($GLOBALS['ecs']->table('keywords'), array('date' => local_date('Y-m-d'),
                'searchengine' => 'ecshop', 'keyword' => addslashes(str_replace('%', '', $val)), 'count' => 1), array('count' => 1));
        }
        $keywords .= ')';

        $goods_ids = array_unique($goods_ids);
        $tag_where = implode(',', $goods_ids);
        if (!empty($tag_where))
        {
            $tag_where = 'OR g.goods_id ' . db_create_in($tag_where);
        }
    }

    $category   = !empty($_REQUEST['category']) ? intval($_REQUEST['category'])        : 0;
    $categories = ($category > 0)               ? ' AND ' . get_children($category)    : '';
    $brand      = $_REQUEST['brand']            ? " AND brand_id = '$_REQUEST[brand]'" : '';
    $outstock   = !empty($_REQUEST['outstock']) ? " AND g.goods_number > 0 "           : '';

    $min_price  = $_REQUEST['min_price'] != 0                               ? " AND g.shop_price >= '$_REQUEST[min_price]'" : '';
    $max_price  = $_REQUEST['max_price'] != 0 || $_REQUEST['min_price'] < 0 ? " AND g.shop_price <= '$_REQUEST[max_price]'" : '';

    /* 排序、显示方式以及类型 */
    $default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'final_price' : 'last_update');

    $sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'final_price', 'last_update', 'salenum'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;   /* 代码增加_start  By  www.yshop100.com */
    
    $order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method;
    $display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_SESSION['display_search']) ? $_SESSION['display_search'] : $default_display_type);

    $_SESSION['display_search'] = $display;

    $intromode = '';    //方式，用于决定搜索结果页标题图片

    if (!empty($_REQUEST['intro']))
    {
        switch ($_REQUEST['intro'])
        {
            case 'best':
                $intro   = ' AND g.is_best = 1';
                $intromode = 'best';
                $ur_here = $_LANG['best_goods'];
                break;
            case 'new':
                $intro   = ' AND g.is_new = 1';
                $intromode ='new';
                $ur_here = $_LANG['new_goods'];
                break;
            case 'hot':
                $intro   = ' AND g.is_hot = 1';
                $intromode = 'hot';
                $ur_here = $_LANG['hot_goods'];
                break;
            case 'promotion':
                $time    = gmtime();
                $intro   = " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
                $intromode = 'promotion';
                $ur_here = $_LANG['promotion_goods'];
                break;
            default:
                $intro   = '';
        }
    }
    else
    {
        $intro = '';
    }

    if (empty($ur_here))
    {
        $ur_here = $_LANG['search_goods'];
    }

    /*------------------------------------------------------ */
    //-- 属性检索
    /*------------------------------------------------------ */
    $attr_in  = '';
    $attr_num = 0;
    $attr_url = '';
    $attr_arg = array();

    if (!empty($_REQUEST['attr']))
    {
        $sql = "SELECT goods_id, COUNT(*) AS num FROM " . $GLOBALS['ecs']->table("goods_attr") . " WHERE 0 ";
        foreach ($_REQUEST['attr'] AS $key => $val)
        {
            if (is_not_null($val) && is_numeric($key))
            {
                $attr_num++;
                $sql .= " OR (1 ";

                if (is_array($val))
                {
                    $sql .= " AND attr_id = '$key'";

                    if (!empty($val['from']))
                    {
                        $sql .= is_numeric($val['from']) ? " AND attr_value >= " . floatval($val['from'])  : " AND attr_value >= '$val[from]'";
                        $attr_arg["attr[$key][from]"] = $val['from'];
                        $attr_url .= "&amp;attr[$key][from]=$val[from]";
                    }

                    if (!empty($val['to']))
                    {
                        $sql .= is_numeric($val['to']) ? " AND attr_value <= " . floatval($val['to']) : " AND attr_value <= '$val[to]'";
                        $attr_arg["attr[$key][to]"] = $val['to'];
                        $attr_url .= "&amp;attr[$key][to]=$val[to]";
                    }
                }
                else
                {
                    /* 处理选购中心过来的链接 */
                    $sql .= isset($_REQUEST['pickout']) ? " AND attr_id = '$key' AND attr_value = '" . $val . "' " : " AND attr_id = '$key' AND attr_value LIKE '%" . mysql_like_quote($val) . "%' ";
                    $attr_url .= "&amp;attr[$key]=$val";
                    $attr_arg["attr[$key]"] = $val;
                }

                $sql .= ')';
            }
        }

        /* 如果检索条件都是无效的，就不用检索 */
        if ($attr_num > 0)
        {
            $sql .= " GROUP BY goods_id HAVING num = '$attr_num'";

            $row = $db->getCol($sql);
            if (count($row))
            {
                $attr_in = " AND " . db_create_in($row, 'g.goods_id');
            }
            else
            {
                $attr_in = " AND 0 ";
            }
        }
    }
    elseif (isset($_REQUEST['pickout']))
    {
        /* 从选购中心进入的链接 */
        $sql = "SELECT DISTINCT(goods_id) FROM " . $GLOBALS['ecs']->table('goods_attr');
        $col = $db->getCol($sql);
        //如果商店没有设置商品属性,那么此检索条件是无效的
        if (!empty($col))
        {
            $attr_in = " AND " . db_create_in($col, 'g.goods_id');
        }
    }

    /* 查询商品 */
    $sql = "SELECT g.goods_id, g.goods_name, g.market_price,g.click_count, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ".
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.exclusive,  ".
                "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type ".
            "FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0 AND g.is_seckill = 0 $attr_in ".$where1.
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .
            "ORDER BY $sort ".$limit;
            
    if ($sort=='salenum')
    {
         $sql = "SELECT SUM(o.goods_number) as salenum, g.goods_id, g.goods_name, g.market_price,g.click_count, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ".
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.exclusive, ".
                "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type ".
            "FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('order_goods') .  " as o ON o.goods_id = g.goods_id " . 
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0  AND g.is_seckill = 0 $attr_in ".$where1.
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .  " group by g.goods_id " .
            "ORDER BY $sort ".$limit;
    }
    
      if($sort=="final_price"){
            /* 获得商品列表 */
 $shop_price_sql =  "if(g.promote_start_date < ".gmtime()."  and g.promote_end_date >  ".gmtime()."  and g.promote_price > 0 and g.promote_price < IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]'), promote_price, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]'))";
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                " IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                " IF(g.exclusive > 0 and g.exclusive < $shop_price_sql , g.exclusive, $shop_price_sql ) as final_price, g.promote_price, g.goods_type, g.exclusive, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0 AND g.is_seckill = 0 $attr_in ".$where1.
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .
            "ORDER BY cast($sort as DECIMAL( 10,2)) ".$limit;
        }

    $res = $GLOBALS['db']->query($sql);
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }
        $final_price  = get_final_price($row['goods_id'], 1, false);
        /* 处理商品水印图片 */
        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote_small";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new_small";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best_small";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']      = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
        }
        $arr[$row['goods_id']]['type']          = $row['goods_type'];
        $arr[$row['goods_id']]['market_price']  = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
        $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_brief']   = $row['goods_brief'];
    $arr[$row['goods_id']]['click_count'] = $row['click_count'];
        $arr[$row['goods_id']]['goods_thumb']   = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']     = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
    $arr[$row['goods_id']]['wap_count']     = selled_wap_count($row['goods_id']);
        $arr[$row['goods_id']]['is_exclusive']  = is_exclusive($row['exclusive'],$final_price);
        $arr[$row['goods_id']]['final_price'] = price_format($final_price);
    }

    if($display == 'grid')
    {
        if(count($arr) % 2 != 0)
        {
            $arr[] = array();
        }
    }

    $goods_list = $arr;


    $smarty->assign('goods_list', $arr);
    $smarty->assign('category',   $category);
    $smarty->assign('keywords',   htmlspecialchars(stripslashes($_REQUEST['keywords'])));
    $smarty->assign('search_keywords',   stripslashes(htmlspecialchars_decode($_REQUEST['keywords'])));
    $smarty->assign('brand',      $_REQUEST['brand']);
    $smarty->assign('min_price',  $min_price);
    $smarty->assign('max_price',  $max_price);
    $smarty->assign('outstock',  $_REQUEST['outstock']);

    /* 分页 */
    $url_format = "search.php?category=$category&amp;keywords=" . urlencode(stripslashes($_REQUEST['keywords'])) . "&amp;brand=" . $_REQUEST['brand']."&amp;action=".$action."&amp;goods_type=" . $_REQUEST['goods_type'] . "&amp;sc_ds=" . $_REQUEST['sc_ds'];
    if (!empty($intromode))
    {
        $url_format .= "&amp;intro=" . $intromode;
    }
    if (isset($_REQUEST['pickout']))
    {
        $url_format .= '&amp;pickout=1';
    }
    $url_format .= "&amp;min_price=" . $_REQUEST['min_price'] ."&amp;max_price=" . $_REQUEST['max_price'] . "&amp;sort=$sort";

    $url_format .= "$attr_url&amp;order=$order&amp;page=";

    $pager['search'] = array(
        'keywords'   => stripslashes(urlencode($_REQUEST['keywords'])),
        'category'   => $category,
        'brand'      => $_REQUEST['brand'],
        'sort'       => $sort,
        'order'      => $order,
        'min_price'  => $_REQUEST['min_price'],
        'max_price'  => $_REQUEST['max_price'],
        'action'     => $action,
        'intro'      => empty($intromode) ? '' : trim($intromode),
        'goods_type' => $_REQUEST['goods_type'],
        'sc_ds'      => $_REQUEST['sc_ds'],
        'outstock'   => $_REQUEST['outstock']
    );
    $pager['search'] = array_merge($pager['search'], $attr_arg);

    $pager = get_pager('search.php', $pager['search'], $count, $page, $size);
    $pager['display'] = $display;

    $smarty->assign('url_format', $url_format);

    assign_dynamic('search');
    $position = assign_ur_here(0, $ur_here . ($_REQUEST['keywords'] ? '_' . $_REQUEST['keywords'] : ''));
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
    $smarty->assign('intromode',      $intromode);
    $smarty->assign('categories', get_categories_tree()); // 分类树
    $smarty->assign('helps',       get_shop_help());      // 网店帮助
    $smarty->assign('top_goods',  get_top10());           // 销售排行
    $smarty->assign('promotion_info', get_promotion_info());
	if(preg_match('/hfxltbapp/', $_SERVER['HTTP_USER_AGENT']))
	{ 
		$smarty->assign('hfxltbapp',1);
	}
	else
	{ 
		$smarty->assign('hfxltbapp',0);
	}
    $smarty->display('search.dwt');
}
/*------------------------------------------------------ */
//-- 搜索结果
/*------------------------------------------------------ */
else
{
    $_REQUEST['keywords']   = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))     : '';
    $_REQUEST['brand']      = !empty($_REQUEST['brand'])      ? intval($_REQUEST['brand'])      : 0;
    $_REQUEST['category']   = !empty($_REQUEST['category'])   ? intval($_REQUEST['category'])   : 0;
    $_REQUEST['min_price']  = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
    $_REQUEST['max_price']  = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
    $_REQUEST['goods_type'] = !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
    $_REQUEST['sc_ds']      = !empty($_REQUEST['sc_ds']) ? intval($_REQUEST['sc_ds']) : 0;
    $_REQUEST['outstock']   = !empty($_REQUEST['outstock']) ? 1 : 0;
    $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
    $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
        /* 把搜索结果保存在session中 */
    if(!in_array($_REQUEST['keywords'],$_SESSION['search_goods'])&& !empty($_REQUEST['keywords'])){
        $_SESSION['search_goods'][]  = $_REQUEST['keywords'];
    }

	$sql = 'SELECT value FROM `ecs_shop_config` WHERE id=1073';
	$ishide=$GLOBALS['db']->getOne($sql);
	$where1=' ';
	if($ishide=='1')
	{
		$where1=' and ishide=0 ';
	}
    if(isset($_REQUEST['type']) && $_REQUEST['type'] == '1'){
        
    	//店铺搜索
    	//模糊搜出来的店铺id
    	$sql = "SELECT DISTINCT supplier_id
				FROM ".$GLOBALS['ecs']->table('supplier_shop_config')." AS ssc
				WHERE (
				code = 'shop_name'
				AND value LIKE '%".$_REQUEST['keywords']."%'
				)
				OR (
				code = 'shop_keywords'
				AND value LIKE '%".$_REQUEST['keywords']."%'
				)";
	    $suppids = $db->getAll($sql);
	    $count = count($suppids);
	    $start = ($page-1)*$size;
	   
	    $splice = array_slice($suppids,$start,$size);
	    
	    $suppliers = array();
	    if(is_array($splice)){
	    	foreach($splice as $key => $val){
	    		$suppliers[] = $val['supplier_id'];
	    	}
	    }
	    
	
	    $max_page = ($count> 0) ? ceil($count / $size) : 1;
	    if ($page > $max_page)
	    {
	        $page = $max_page;
    	}
    	$arr = array();
    	if(!empty($suppliers)){
	    	$sql = "select supplier_id,code,value from ".$GLOBALS['ecs']->table('supplier_shop_config')." where supplier_id in(".implode(',',$suppliers).")";
	    	$res = $info = $db->query($sql);
	    	
	    	while ($row = $db->FetchRow($res)){
	    		if(!isset($arr[$row['supplier_id']])){
	    			//获取店铺三个最新商品
	    			$gsql = "select goods_id, goods_name,shop_price, goods_thumb, goods_img from ".$GLOBALS['ecs']->table('goods')." where supplier_id=".$row['supplier_id']." AND is_delete = 0 AND is_on_sale = 1 AND is_alone_sale = 1 AND is_virtual = 0 ".$where1." order by goods_id desc";
	    			$glist = $db->getAll($gsql);
	    			$arr[$row['supplier_id']]['goodlist'] = $glist;
	    			foreach($glist as $k=>$v){
	    				$arr[$row['supplier_id']]['goodlist'][$k]['goods_thumb']   = get_pc_url().'/'.get_image_path($v['goods_id'], $v['goods_thumb'], true);
	        			$arr[$row['supplier_id']]['goodlist'][$k]['goods_img']     = get_pc_url().'/'.get_image_path($v['goods_id'], $v['goods_img']);
	    				$arr[$row['supplier_id']]['goodlist'][$k]['url']           = build_uri('goods', array('gid' => $v['goods_id']), $v['goods_name']);
                                        $arr[$row['supplier_id']]['goodlist'][$k]['shop_price']  =  $v['shop_price'];
                                        
                                }
	    		}
	    		$arr[$row['supplier_id']][$row['code']] = $row['value'];
	    		$arr[$row['supplier_id']]['supplier_id'] = $row['supplier_id'];
                        $arr[$row['supplier_id']]['shop_province_name'] = get_region_name($arr[$row['supplier_id']]['shop_province']);
                        $arr[$row['supplier_id']]['shop_city_name'] = get_region_name($arr[$row['supplier_id']]['shop_city']);
                        $arr[$row['supplier_id']]['goods_number'] = count($arr[$row['supplier_id']]['goodlist']);
                     
	    	}
                   //获取店铺评分
                foreach($arr as $k=>$v){
                                        //代码增加 
                    $sql1 = "SELECT AVG(comment_rank) FROM " . $GLOBALS['ecs']->table('comment') . " c" . " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " o"." ON o.order_id = c.order_id"." WHERE c.status > 0 AND  o.supplier_id = " .$v['supplier_id'];
                    $avg_comment = $GLOBALS['db']->getOne($sql1);
                    $avg_comment = number_format(round($avg_comment),1);			
                    $sql2 = "SELECT AVG(server), AVG(shipping) FROM " . $GLOBALS['ecs']->table('shop_grade') . " s" . " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " o"." ON o.order_id = s.order_id"." WHERE s.is_comment > 0 AND  s.server >0 AND o.supplier_id = " .$v['supplier_id'];
                    $row = $GLOBALS['db']->getRow($sql2);

                    $avg_server = number_format(round($row['AVG(server)']),1);
                    $avg_shipping = number_format(round($row['AVG(shipping)']),1);
                    $haoping = round((($avg_comment+$avg_server+$avg_shipping)/3)/5,2)*100;
                    //代码增加 
                    $arr[$k]['c_rank'] = $avg_comment;
                    $arr[$k]['serv_rank'] = $avg_server;
                    $arr[$k]['shipp_rank'] = $avg_shipping;
                }
    	}
        
        
    	$pager['search'] = array(
	        'keywords'   => stripslashes(urlencode($_REQUEST['keywords'])),
	        'sort'       => $_REQUEST['sort'],
	        'intro'      => empty($intromode) ? '' : trim($intromode),
	        'goods_type' => $_REQUEST['goods_type'],
	        'sc_ds'      => $_REQUEST['sc_ds'],
	        'outstock'   => $_REQUEST['outstock'],
	    );

 	$pager = get_pager('search.php', $pager['search'], $count, $page, $size);
        $display = 'shop_list';
        $smarty->assign('display',$display);
 	$smarty->assign('pager',$pager);
 	$smarty->assign('shop_list',$arr);
 	assign_template();
    	assign_dynamic('search');
	if(preg_match('/hfxltbapp/', $_SERVER['HTTP_USER_AGENT']))
	{ 
		$smarty->assign('hfxltbapp',1);
	}
	else
	{ 
		$smarty->assign('hfxltbapp',0);
	}
   	$smarty->display('search.dwt');
    	exit;
    }

    $action = '';
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'form')
    {
        /* 要显示高级搜索栏 */
        $adv_value['keywords']  = htmlspecialchars(stripcslashes($_REQUEST['keywords']));
        $adv_value['brand']     = $_REQUEST['brand'];
        $adv_value['min_price'] = $_REQUEST['min_price'];
        $adv_value['max_price'] = $_REQUEST['max_price'];
        $adv_value['category']  = $_REQUEST['category'];

        $attributes = get_seachable_attributes($_REQUEST['goods_type']);

        /* 将提交数据重新赋值 */
        foreach ($attributes['attr'] AS $key => $val)
        {
            if (!empty($_REQUEST['attr'][$val['id']]))
            {
                if ($val['type'] == 2)
                {
                    $attributes['attr'][$key]['value']['from'] = !empty($_REQUEST['attr'][$val['id']]['from']) ? htmlspecialchars(stripcslashes(trim($_REQUEST['attr'][$val['id']]['from']))) : '';
                    $attributes['attr'][$key]['value']['to']   = !empty($_REQUEST['attr'][$val['id']]['to'])   ? htmlspecialchars(stripcslashes(trim($_REQUEST['attr'][$val['id']]['to'])))   : '';
                }
                else
                {
                    $attributes['attr'][$key]['value'] = !empty($_REQUEST['attr'][$val['id']]) ? htmlspecialchars(stripcslashes(trim($_REQUEST['attr'][$val['id']]))) : '';
                }
            }
        }
        if ($_REQUEST['sc_ds'])
        {
            $smarty->assign('scck',            'checked');
        }
        $smarty->assign('adv_val',             $adv_value);
        $smarty->assign('goods_type_list',     $attributes['cate']);
        $smarty->assign('goods_attributes',    $attributes['attr']);
        $smarty->assign('goods_type_selected', $_REQUEST['goods_type']);
        $smarty->assign('cat_list',            cat_list(0, $adv_value['category'], true, 2, false));
        $smarty->assign('brand_list',          get_brand_list());
        $smarty->assign('action',              'form');
        $smarty->assign('use_storage',          $_CFG['use_storage']);

        $action = 'form';
    }

    /* 初始化搜索条件 */
    $keywords  = '';
    $tag_where = '';
    if (!empty($_REQUEST['keywords']))
    {
        $arr = array();
        if (stristr($_REQUEST['keywords'], ' AND ') !== false)
        {
            /* 检查关键字中是否有AND，如果存在就是并 */
            $arr        = explode('AND', $_REQUEST['keywords']);
            $operator   = " AND ";
        }
        elseif (stristr($_REQUEST['keywords'], ' OR ') !== false)
        {
            /* 检查关键字中是否有OR，如果存在就是或 */
            $arr        = explode('OR', $_REQUEST['keywords']);
            $operator   = " OR ";
        }
        elseif (stristr($_REQUEST['keywords'], ' + ') !== false)
        {
            /* 检查关键字中是否有加号，如果存在就是或 */
            $arr        = explode('+', $_REQUEST['keywords']);
            $operator   = " OR ";
        }
        else
        {
            /* 检查关键字中是否有空格，如果存在就是并 */
            $arr        = explode(' ', $_REQUEST['keywords']);
            $operator   = " AND ";
        }

        $keywords = 'AND (';
        $goods_ids = array();
        foreach ($arr AS $key => $val)
        {
            if ($key > 0 && $key < count($arr) && count($arr) > 1)
            {
                $keywords .= $operator;
            }
            $val        = mysql_like_quote(trim($val));
            $sc_dsad    = $_REQUEST['sc_ds'] ? " OR goods_desc LIKE '%$val%'" : '';
            $keywords  .= "(g.goods_name LIKE '%$val%' OR g.goods_sn LIKE '%$val%' OR g.keywords LIKE '%$val%' $sc_dsad)";

            $sql = 'SELECT DISTINCT goods_id FROM ' . $GLOBALS['ecs']->table('tag') . " WHERE tag_words LIKE '%$val%' ";
            $res = $db->query($sql);
            while ($row = $db->FetchRow($res))
            {
                $goods_ids[] = $row['goods_id'];
            }

            $db->autoReplace($GLOBALS['ecs']->table('keywords'), array('date' => local_date('Y-m-d'),
                'searchengine' => 'ecshop', 'keyword' => addslashes(str_replace('%', '', $val)), 'count' => 1), array('count' => 1));
        }
        $keywords .= ')';

        $goods_ids = array_unique($goods_ids);
        $tag_where = implode(',', $goods_ids);
        if (!empty($tag_where))
        {
            $tag_where = 'OR g.goods_id ' . db_create_in($tag_where);
        }
    }

    $category   = !empty($_REQUEST['category']) ? intval($_REQUEST['category'])        : 0;
    $categories = ($category > 0)               ? ' AND ' . get_children($category)    : '';
    $brand      = $_REQUEST['brand']            ? " AND brand_id = '$_REQUEST[brand]'" : '';
    $outstock   = !empty($_REQUEST['outstock']) ? " AND g.goods_number > 0 "           : '';

    $min_price  = $_REQUEST['min_price'] != 0                               ? " AND g.shop_price >= '$_REQUEST[min_price]'" : '';
    $max_price  = $_REQUEST['max_price'] != 0 || $_REQUEST['min_price'] < 0 ? " AND g.shop_price <= '$_REQUEST[max_price]'" : '';

    /* 排序、显示方式以及类型 */
    $default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'final_price' : 'last_update');

	$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'final_price', 'last_update', 'salenum'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;   /* 代码增加_start  By  www.yshop100.com */
	
    $order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method;
    $display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_SESSION['display_search']) ? $_SESSION['display_search'] : $default_display_type);

    $_SESSION['display_search'] = $display;

    $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
    $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

    $intromode = '';    //方式，用于决定搜索结果页标题图片

    if (!empty($_REQUEST['intro']))
    {
        switch ($_REQUEST['intro'])
        {
            case 'best':
                $intro   = ' AND g.is_best = 1';
                $intromode = 'best';
                $ur_here = $_LANG['best_goods'];
                break;
            case 'new':
                $intro   = ' AND g.is_new = 1';
                $intromode ='new';
                $ur_here = $_LANG['new_goods'];
                break;
            case 'hot':
                $intro   = ' AND g.is_hot = 1';
                $intromode = 'hot';
                $ur_here = $_LANG['hot_goods'];
                break;
            case 'promotion':
                $time    = gmtime();
                $intro   = " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
                $intromode = 'promotion';
                $ur_here = $_LANG['promotion_goods'];
                break;
            default:
                $intro   = '';
        }
    }
    else
    {
        $intro = '';
    }

    if (empty($ur_here))
    {
        $ur_here = $_LANG['search_goods'];
    }

    /*------------------------------------------------------ */
    //-- 属性检索
    /*------------------------------------------------------ */
    $attr_in  = '';
    $attr_num = 0;
    $attr_url = '';
    $attr_arg = array();

    if (!empty($_REQUEST['attr']))
    {
        $sql = "SELECT goods_id, COUNT(*) AS num FROM " . $GLOBALS['ecs']->table("goods_attr") . " WHERE 0 ";
        foreach ($_REQUEST['attr'] AS $key => $val)
        {
            if (is_not_null($val) && is_numeric($key))
            {
                $attr_num++;
                $sql .= " OR (1 ";

                if (is_array($val))
                {
                    $sql .= " AND attr_id = '$key'";

                    if (!empty($val['from']))
                    {
                        $sql .= is_numeric($val['from']) ? " AND attr_value >= " . floatval($val['from'])  : " AND attr_value >= '$val[from]'";
                        $attr_arg["attr[$key][from]"] = $val['from'];
                        $attr_url .= "&amp;attr[$key][from]=$val[from]";
                    }

                    if (!empty($val['to']))
                    {
                        $sql .= is_numeric($val['to']) ? " AND attr_value <= " . floatval($val['to']) : " AND attr_value <= '$val[to]'";
                        $attr_arg["attr[$key][to]"] = $val['to'];
                        $attr_url .= "&amp;attr[$key][to]=$val[to]";
                    }
                }
                else
                {
                    /* 处理选购中心过来的链接 */
                    $sql .= isset($_REQUEST['pickout']) ? " AND attr_id = '$key' AND attr_value = '" . $val . "' " : " AND attr_id = '$key' AND attr_value LIKE '%" . mysql_like_quote($val) . "%' ";
                    $attr_url .= "&amp;attr[$key]=$val";
                    $attr_arg["attr[$key]"] = $val;
                }

                $sql .= ')';
            }
        }

        /* 如果检索条件都是无效的，就不用检索 */
        if ($attr_num > 0)
        {
            $sql .= " GROUP BY goods_id HAVING num = '$attr_num'";

            $row = $db->getCol($sql);
            if (count($row))
            {
                $attr_in = " AND " . db_create_in($row, 'g.goods_id');
            }
            else
            {
                $attr_in = " AND 0 ";
            }
        }
    }
    elseif (isset($_REQUEST['pickout']))
    {
        /* 从选购中心进入的链接 */
        $sql = "SELECT DISTINCT(goods_id) FROM " . $GLOBALS['ecs']->table('goods_attr');
        $col = $db->getCol($sql);
        //如果商店没有设置商品属性,那么此检索条件是无效的
        if (!empty($col))
        {
            $attr_in = " AND " . db_create_in($col, 'g.goods_id');
        }
    }

    /* 获得符合条件的商品总数 */
    $sql   = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
        "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_virtual = 0 AND g.is_seckill = 0 $attr_in ".$where1.
        "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock ." ) ".$tag_where." )";
    $count = $db->getOne($sql);

    $max_page = ($count> 0) ? ceil($count / $size) : 1;
    if ($page > $max_page)
    {
        $page = $max_page;
    }

    /* 查询商品 */
    $sql = "SELECT g.goods_id, g.goods_name, g.market_price,g.click_count, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ".
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.exclusive,  ".
                "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type, ss.is_exchange ".
            "FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('suppliers') . " AS ss ON ss.suppliers_id = g.suppliers_id ".
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0 AND g.is_seckill = 0 $attr_in ".$where1.
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .
            "ORDER BY $sort $order";
			
	if ($sort=='salenum')
	{
		 $sql = "SELECT SUM(o.goods_number) as salenum, g.goods_id, g.goods_name, g.market_price,g.click_count, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ".
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.exclusive, ".
                "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type, ss.is_exchange ".
            "FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
			"LEFT JOIN " . $GLOBALS['ecs']->table('order_goods') .  " as o ON o.goods_id = g.goods_id " . 
            "LEFT JOIN " . $GLOBALS['ecs']->table('suppliers') . " AS ss ON ss.suppliers_id = g.suppliers_id ".
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0  AND g.is_seckill = 0 $attr_in ".$where1.
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .  " group by g.goods_id " .
            "ORDER BY $sort $order";
	}
	
      if($sort=="final_price"){
            /* 获得商品列表 */
 $shop_price_sql =  "if(g.promote_start_date < ".gmtime()."  and g.promote_end_date >  ".gmtime()."  and g.promote_price > 0 and g.promote_price < IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]'), promote_price, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]'))";
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                " IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                " IF(g.exclusive > 0 and g.exclusive < $shop_price_sql , g.exclusive, $shop_price_sql ) as final_price, g.promote_price, g.goods_type, g.exclusive, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img, ss.is_exchange ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('suppliers') . " AS ss ON ss.suppliers_id = g.suppliers_id ".
            "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  AND g.is_virtual = 0 AND g.is_seckill = 0 $attr_in ".$where1.
                "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .
            "ORDER BY cast($sort as DECIMAL( 10,2)) $order";
        }
        
    $res = $db->SelectLimit($sql, $size, ($page - 1) * $size);

    $arr = array();
    while ($row = $db->FetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }
        $final_price  = get_final_price($row['goods_id'], 1, false);
        /* 处理商品水印图片 */
        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote_small";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new_small";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best_small";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']      = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
        }
        $arr[$row['goods_id']]['type']          = $row['goods_type'];
        $arr[$row['goods_id']]['market_price']  = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
        $arr[$row['goods_id']]['shop_exchange_price']    = price_exchange_format($row['shop_price']);
        $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['promote_exchange_price'] = ($promote_price > 0) ? price_exchange_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_brief']   = $row['goods_brief'];
	$arr[$row['goods_id']]['click_count'] = $row['click_count'];
        $arr[$row['goods_id']]['goods_thumb']   = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']     = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
	$arr[$row['goods_id']]['wap_count']     = selled_wap_count($row['goods_id']);
        $arr[$row['goods_id']]['is_exclusive']  = is_exclusive($row['exclusive'],$final_price);
        $arr[$row['goods_id']]['final_price'] = price_format($final_price);
        $arr[$row['goods_id']]['final_exchange_price'] = price_exchange_format($final_price);
        $arr[$row['goods_id']]['is_exchange'] = $row['is_exchange'];
    }

    if($display == 'grid')
    {
        if(count($arr) % 2 != 0)
        {
            $arr[] = array();
        }
    }
    $smarty->assign('goods_list', $arr);
    $smarty->assign('category',   $category);
    $smarty->assign('keywords',   htmlspecialchars(stripslashes($_REQUEST['keywords'])));
    $smarty->assign('search_keywords',   stripslashes(htmlspecialchars_decode($_REQUEST['keywords'])));
    $smarty->assign('brand',      $_REQUEST['brand']);
    $smarty->assign('min_price',  $min_price);
    $smarty->assign('max_price',  $max_price);
    $smarty->assign('outstock',  $_REQUEST['outstock']);

    /* 分页 */
    $url_format = "search.php?category=$category&amp;keywords=" . urlencode(stripslashes($_REQUEST['keywords'])) . "&amp;brand=" . $_REQUEST['brand']."&amp;action=".$action."&amp;goods_type=" . $_REQUEST['goods_type'] . "&amp;sc_ds=" . $_REQUEST['sc_ds'];
    if (!empty($intromode))
    {
        $url_format .= "&amp;intro=" . $intromode;
    }
    if (isset($_REQUEST['pickout']))
    {
        $url_format .= '&amp;pickout=1';
    }
    $url_format .= "&amp;min_price=" . $_REQUEST['min_price'] ."&amp;max_price=" . $_REQUEST['max_price'] . "&amp;sort=$sort";

    $url_format .= "$attr_url&amp;order=$order&amp;page=";

    $pager['search'] = array(
        'keywords'   => stripslashes(urlencode($_REQUEST['keywords'])),
        'category'   => $category,
        'brand'      => $_REQUEST['brand'],
        'sort'       => $sort,
        'order'      => $order,
        'min_price'  => $_REQUEST['min_price'],
        'max_price'  => $_REQUEST['max_price'],
        'action'     => $action,
        'intro'      => empty($intromode) ? '' : trim($intromode),
        'goods_type' => $_REQUEST['goods_type'],
        'sc_ds'      => $_REQUEST['sc_ds'],
        'outstock'   => $_REQUEST['outstock']
    );
    $pager['search'] = array_merge($pager['search'], $attr_arg);

    $pager = get_pager('search.php', $pager['search'], $count, $page, $size);
    $pager['display'] = $display;

    $smarty->assign('url_format', $url_format);
    $smarty->assign('pager', $pager);

    assign_template();
    assign_dynamic('search');
    $position = assign_ur_here(0, $ur_here . ($_REQUEST['keywords'] ? '_' . $_REQUEST['keywords'] : ''));
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
    $smarty->assign('intromode',      $intromode);
    $smarty->assign('categories', get_categories_tree()); // 分类树
    $smarty->assign('helps',       get_shop_help());      // 网店帮助
    $smarty->assign('top_goods',  get_top10());           // 销售排行
    $smarty->assign('promotion_info', get_promotion_info());
	if(preg_match('/hfxltbapp/', $_SERVER['HTTP_USER_AGENT']))
	{ 
		$smarty->assign('hfxltbapp',1);
	}
	else
	{ 
		$smarty->assign('hfxltbapp',0);
	}
    $smarty->display('search.dwt');
}

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */
/**
 *
 *
 * @access public
 * @param
 *
 * @return void
 */
function is_not_null($value)
{
    if (is_array($value))
    {
        return (!empty($value['from'])) || (!empty($value['to']));
    }
    else
    {
        return !empty($value);
    }
}

/**
 * 获得可以检索的属性
 *
 * @access  public
 * @params  integer $cat_id
 * @return  void
 */
function get_seachable_attributes($cat_id = 0)
{
    $attributes = array(
        'cate' => array(),
        'attr' => array()
    );

    /* 获得可用的商品类型 */
    $sql = "SELECT t.cat_id, cat_name FROM " .$GLOBALS['ecs']->table('goods_type'). " AS t, ".
           $GLOBALS['ecs']->table('attribute') ." AS a".
           " WHERE t.cat_id = a.cat_id AND t.enabled = 1 AND a.attr_index > 0 ";
    $cat = $GLOBALS['db']->getAll($sql);

    /* 获取可以检索的属性 */
    if (!empty($cat))
    {
        foreach ($cat AS $val)
        {
            $attributes['cate'][$val['cat_id']] = $val['cat_name'];
        }
        $where = $cat_id > 0 ? ' AND a.cat_id = ' . $cat_id : " AND a.cat_id = " . $cat[0]['cat_id'];

        $sql = 'SELECT attr_id, attr_name, attr_input_type, attr_type, attr_values, attr_index, sort_order ' .
               ' FROM ' . $GLOBALS['ecs']->table('attribute') . ' AS a ' .
               ' WHERE a.attr_index > 0 ' .$where.
               ' ORDER BY cat_id, sort_order ASC';
        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->FetchRow($res))
        {
            if ($row['attr_index'] == 1 && $row['attr_input_type'] == 1)
            {
                $row['attr_values'] = str_replace("\r", '', $row['attr_values']);
                $options = explode("\n", $row['attr_values']);

                $attr_value = array();
                foreach ($options AS $opt)
                {
                    $attr_value[$opt] = $opt;
                }
                $attributes['attr'][] = array(
                    'id'      => $row['attr_id'],
                    'attr'    => $row['attr_name'],
                    'options' => $attr_value,
                    'type'    => 3
                );
            }
            else
            {
                $attributes['attr'][] = array(
                    'id'   => $row['attr_id'],
                    'attr' => $row['attr_name'],
                    'type' => $row['attr_index']
                );
            }
        }
    }

    return $attributes;
}

/**
 * 根据区域id 获得区域名称
 * @param int $region_id 区域id
 * @return $region_name 区域名称
 */
function get_region_name($region_id){
    $region_id = empty($region_id)?0:intval($region_id);
    $sql = "select region_name from ".$GLOBALS['ecs']->table('region')." where region_id = $region_id";
    $region_name = $GLOBALS['db'] -> getOne($sql);
    return $region_name;
}


?>