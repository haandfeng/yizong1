<?php

/**
 * ECSHOP 文章内容
 
 * $Author: liuhui $
 * $Id: article.php 17069 2010-03-26 05:28:01Z liuhui $
*/

if(!defined('IN_CTRL'))
{
	die('Hacking alert');
}
$cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
include('includes/cls_json.php');
$json   = new JSON;
$articlecat = article_cat_list($cat_id, 0, false,4);
$smarty->assign('articlecat', $articlecat);
app_display('article_cat.dwt');
?>