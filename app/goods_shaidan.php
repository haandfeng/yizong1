<?php

/**
 * ECSHOP 生成验证码
 
 * $Author: liubo $
 * $Id: goods_comment.php 17217 2011-01-19 06:29:08Z liubo $
*/

if(!defined('IN_CTRL'))
{
	die('Hacking alert');
}

include_once(ROOT_PATH . '/includes/lib_comment.php');



if ($_REQUEST['act'] == 'view')
{
	$shaidan_id = intval($_REQUEST['id']);

	$cache_id = sprintf('%X', crc32($_REQUEST['id'] . 'goods_shaidan'));
	if (!$smarty->is_cached('goods_shaidan.dwt', $cache_id))
	{
		$shaidan = $db->getRow("SELECT * FROM ".$ecs->table('shaidan')." WHERE shaidan_id = '$shaidan_id'");
		$shaidan_imgs = $GLOBALS['db']->getAll("SELECT * FROM ".$GLOBALS['ecs']->table('shaidan_img')." WHERE shaidan_id = '$shaidan_id'");	
		$goods_id = $shaidan['goods_id'];

		$smarty->assign('shaidan',               $shaidan);
		$smarty->assign('shaidan_imgs',          $shaidan_imgs);
		
		/* 获得商品的信息 */
		$goods = get_goods_info_app($goods_id);
		if ($goods === false)
		{
			/* 如果没有找到任何记录则跳回到首页 */
			make_json_error('找不到晒单记录');
		}
		
        $catlist = array();
        foreach(get_parent_cats($goods['cat_id']) as $k=>$v)
        {
            $catlist[] = $v['cat_id'];
        }
        assign_template('c', $catlist);
		
        /* meta */
        $smarty->assign('keywords',           htmlspecialchars($goods['keywords']));
        $smarty->assign('description',        htmlspecialchars($goods['goods_brief']));
		
        $position = assign_ur_here($goods['cat_id'], $goods['goods_name']);
		$position['ur_here'] .= ' <code>&gt;</code> 晒单';
		
        /* current position */
        $smarty->assign('page_title',          $position['title']);                    // 页面标题
        $smarty->assign('ur_here',             $position['ur_here']);                  // 当前位置
		
        $smarty->assign('goods',              $goods);
        $smarty->assign('goods_id',           $goods['goods_id']);
		$smarty->assign('categories',       get_categories_tree());  // 分类树
		$smarty->assign('helps',            get_shop_help()); // 网店帮助
		$smarty->assign('page_title',   $position['title']);    // 页面标题
		$smarty->assign('ur_here',      $position['ur_here']);  // 当前位置
		
		assign_dynamic('goods');
	}
	app_display('goods_shaidan.dwt');
	$smarty->display('goods_shaidan_view.dwt');
}
?>