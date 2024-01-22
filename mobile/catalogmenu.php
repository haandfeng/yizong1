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
$left = empty($_GET['left'])?"":$_GET['left'];
$pid = empty($_GET['pid'])?"0":$_GET['pid'];
$ceid = empty($_GET['ceid'])?"":$_GET['ceid'];
$classid = empty($_REQUEST['classid'])?"0":$_REQUEST['classid'];
$catchildid = empty($_POST['catchildid'])?"0":$_POST['catchildid'];

//异步调用

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'ajax') {
    include('includes/cls_json.php');
    $last = $_POST['last'];
    $amount = $_POST['amount'];
    $json = new JSON;

    $goodslist = category_getgoods($ceid,$classid,$catchildid, $amount, $last);

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


if (!$smarty->is_cached('catalogmenu.dwt'))
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
	if(preg_match('/hfxltbapp/', $_SERVER['HTTP_USER_AGENT']))
	{ 
		$smarty->assign('hfxltbapp',1);
	}
	else
	{ 
		$smarty->assign('hfxltbapp',0);
	}
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置

    $smarty->assign('helps',      get_shop_help()); // 网店帮助
    $smarty->assign('cat_list',   $cat_list);       // 分类列表
    $smarty->assign('cat_list',   '');// 分类列表
	$catname='全部分类';
	if($pid==0) {
		$cat = get_categories();
		if($ceid=='')
		{
			$ceid=get_catetop();
		}
		$child = get_categorieschild($ceid);
	}
	else{
		$cat = get_categories($pid);
		$catname=getcatename($pid);
	}



    $smarty->assign('pid',      $pid); 
    $smarty->assign('catname',      $catname); 
    $smarty->assign('ceid',      $ceid); 
    $smarty->assign('categories',      $cat); // 分类树
    $smarty->assign('child',      $child); 
    $smarty->assign('brand_list', get_brands());    // 所以品牌赋值
    $smarty->assign('promotion_info', get_promotion_info());
}
//取出logo图片
$sql = 'select path from ecs_logo';
$res = $db->getAll($sql);
$smarty->assign('logo',$res[0]['path']);

$smarty->assign('categorylist',getcategorylist());
$smarty->assign('left',$left);
$smarty->assign('comefrom',$_GET['comefrom']);
$smarty->assign('classid',$classid);
$smarty->display('catalogmenu.dwt');

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

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_getgoods($cat_id,$classid,$catchildid, $size, $last) {
	$sql = 'SELECT value FROM `ecs_shop_config` WHERE id=1073';
	$ishide=$GLOBALS['db']->getOne($sql);
	$where1=' ';
	if($ishide=='1')
	{
		$where1=' and ishide=0';
	}
	if(strlen($classid)>0)
	{
		if($classid=='0')
		{}
		else{
			$where1=$where1." and  classid like '%".$classid."%' ";
		}
	}
    $limit = " limit ".$last.",".$size;
    $children = get_children($cat_id);
	if($catchildid>0)
	{
		$where1=$where1.' and (g.cat_id='.$catchildid.' or exists(select 1 from ecs_goods_cat cg where cg.goods_id=g.goods_id and cg.cat_id='.$catchildid.') ) ';//c.cat_id='.$catchildid.')
	}
	
    //$where = ' where is_on_sale=1 and is_alone_sale = 1 and is_delete = 0'.$where1.'  AND ('.$children.' OR ' . get_extension_goods($children) . ') ORDER BY sort_order,second_order asc '.$limit ;
    $where = ' where is_on_sale=1 and is_alone_sale = 1 and is_delete = 0'.$where1.'   and ( exists(select 1 from ecs_goods_cat cg where cg.goods_id=g.goods_id and c'.$children.')  or '.$children.' OR ' . get_extension_goods($children) . ') ORDER BY sort_order,second_order asc '.$limit ;

    /* 获得商品列表 */
    //$sql = 'select g.goods_id,goods_name,shop_price,market_price,goods_thumb from ecs_goods AS g  left join ecs_goods_cat c on c.goods_id=g.goods_id '.$where;
	$sql = 'select g.goods_id,goods_name,shop_price,market_price,goods_thumb from ecs_goods AS g  '.$where;
    file_put_contents("0127.txt","sql:".$sql." \r\n", FILE_APPEND);
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

function getcatename($p_id )
{   
		$sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$p_id'  ";
		return $GLOBALS['db']->getOne($sql);
  
}
function get_categories($p_id = 0)
{
    if ($p_id > 0)
    { 
        $parent_id = $p_id; 
    }
    else
    {
        $parent_id = 0;
    }

    /*
     判断当前分类中全是是否是底级分类，
     如果是取出底级分类上级分类，
     如果不是取当前分类及其下的子分类
    */
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$parent_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0)
    {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT cat_id,cat_name ,parent_id,is_show ' .
                'FROM ' . $GLOBALS['ecs']->table('category') .
                "WHERE parent_id = '$parent_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";

        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res AS $row)
        {
            if ($row['is_show'])
            {
                $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $cat_arr[$row['cat_id']]['pid'] = $row['parent_id'];
                $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

                if (isset($row['cat_id']) != NULL)
                {
                    $cat_arr[$row['cat_id']]['cat_id'] = get_child_tree($row['cat_id']);
                }
            }
        }
    }
    if(isset($cat_arr))
    {
        return $cat_arr;
    }
}
function get_catetop( )
{ 
		$topcat=0;
        $parent_id = 0; 
 
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$parent_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0)
    {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT cat_id,cat_name ,parent_id,is_show ' .
                'FROM ' . $GLOBALS['ecs']->table('category') .
                "WHERE parent_id = '$parent_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC LIMIT 0, 1";

        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res AS $row)
        {
            if ($row['is_show'])
            {
                $topcat   = $row['cat_id']; 
            }
        }
    }
    
        return $topcat;
   
}
function get_categorieschild($ceid)
{
    if ($ceid > 0)
    { 
        $parent_id = $ceid; 
		$sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$parent_id' AND is_show = 1 ";  
		if ($GLOBALS['db']->getOne($sql) )
		{
			/* 获取当前分类及其子分类 */
			$sql = 'SELECT cat_id,cat_name ,parent_id,is_show ' .
					'FROM ' . $GLOBALS['ecs']->table('category') .
					"WHERE parent_id = '$parent_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";

			$res = $GLOBALS['db']->getAll($sql);

			foreach ($res AS $row)
			{
				if ($row['is_show'])
				{
					$cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
					$cat_arr[$row['cat_id']]['name'] = $row['cat_name']; 
					$cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

					if (isset($row['cat_id']) != NULL)
					{
						$cat_arr[$row['cat_id']]['cat_id'] = get_child_tree($row['cat_id']);
					}
				}
			}
		}
	}
    if(isset($cat_arr))
    {
        return $cat_arr;
    }
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