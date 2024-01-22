<?php

/**
 * ECSHOP 首页文件
 
 * $Author: derek $
 * $Id: index.php 17217 2011-01-19 06:29:08Z derek $
*/
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH .'/jssdk.php');
echo '--------start----------';
echo $_SERVER['HTTP_USER_AGENT'];
echo '--------end----------';
echo strpos($_SERVER['HTTP_USER_AGENT'], 'Appcan')>0;
echo '------------------';
echo strpos($_SERVER['HTTP_USER_AGENT'], 'Windows')>0;
?>