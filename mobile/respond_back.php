<?php

/**
 * ECSHOP 支付响应页面
 
 * $Author: liubo $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');
/* 支付方式代码 */
$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : 'weixin';
/*--start日志地址*/
$data="https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
date_default_timezone_set('PRC'); 
file_put_contents("paylog/".date('Y-m-d',time())."_back.txt",date('H:i:s',time())."\r\n".$data."\r\n", FILE_APPEND);

$data=file_get_contents('php://input');
//file_put_contents("test1.txt",$data."\n", FILE_APPEND);

/*--日志内容*/
file_put_contents("paylog/".date('Y-m-d',time())."_back.txt",date('H:i:s',time())." :\r\n".$data."\r\n", FILE_APPEND);
file_put_contents("paylog/".date('Y-m-d',time())."_back.txt","--------------------------------------------------------------------------------------\r\n", FILE_APPEND);
/*--end日志*/
 

?>