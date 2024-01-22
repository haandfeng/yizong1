<?php

/**
 * ECSHOP 商品详情
 
 * $Author: liubo $
 * $Id: goods.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
/* 代码增加 by www.yshop100.com strat */
require(dirname(__FILE__) . '/includes/lib_comment.php'); 
/* 代码增加_start     */
if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'key')
{
	$key = $_REQUEST['key']; 
	$sql = "select count(id) from ecs_keyword_log where word='".$key."'";
	$res = $GLOBALS['db']->getOne($sql);
	if($res)
	{
		if(intval($res)>0)
		{
			echo 'false';
			exit;
		}
	}  
	echo 'true';
}

?>