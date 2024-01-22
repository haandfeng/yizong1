<?php

/**
 * ECSHOP 支付响应页面
 
 * $Author: liubo $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_CTRL', true);
define('IN_ECS', true);
require_once(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_payment.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : 'weixin';
if(empty($pay_code))
{
	exit;
}
/* 判断是否启用 */
$sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
if ($db->getOne($sql) == 0)
{
	$result = false;
	$msg = $_LANG['pay_disabled'];
}
else
{
	$plugin_file = APP_ROOT_PATH.'includes/modules/payment/' . $pay_code . '.php';

	/* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
	if (file_exists($plugin_file))
	{
		/* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
		include_once($plugin_file);

		$payment = new $pay_code();
		$result  = $payment->respond();

	}
	else
	{
		$result = false;
		$msg = $_LANG['pay_not_exist'];
	}
}
if($result)
{
	echo 'success';
}
else
{
	echo $msg;
}