<?php
define('IN_ECS', true);
require('../includes/init.php');
require('../includes/lib_order.php');
include_once('../includes/lib_payment.php');
error_reporting(E_ALL ^ E_NOTICE);
$out_trade_no = intval($_GET['out_trade_no']);




//根据支付id获取订单id
$order_id = $GLOBALS['db']->getOne("SELECT order_id FROM ".$GLOBALS['ecs']->table('pay_log')." WHERE log_id = '$out_trade_no'");
//获取订单信息
$order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' OR parent_order_id = '$order_id' limit 1");
if($order)
{
}
else
{
	echo 1;exit; 
}
?>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <title>微信安全支付</title>
	<script type="text/javascript" src="https://www.quickact.net/mobile/js/jweixin-1.3.2.js"></script>
	<script> 
		var xcx=false;
		var ua = navigator.userAgent.toLowerCase();
		if(ua.match(/MicroMessenger/i)=="micromessenger") { 
			wx.miniProgram.getEnv((res)=>{
			   if (res.miniprogram) {
				   xcx=true; 
			   
			   } 
			})
		} 
		if(xcx)
		{
			document.location="weixinpay_xcx.php?out_trade_no=<?php echo $out_trade_no ?>";
		}
		else
		{
			document.location="weixinpayy.php?out_trade_no=<?php echo $out_trade_no ?>";
		}
	</script>
</head>
<body>
</html>