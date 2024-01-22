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
	if ($order['order_amount'] > 0){
	    $pay_params = array();
	    //计算总价汇率
	    $is_exchange = $GLOBALS['db']->getOne("SELECT is_exchange FROM " . $GLOBALS['ecs']->table('suppliers') . " WHERE suppliers_id = '". $order['suppliers_id'] ."'");
	    if($is_exchange > 0 && $order['exchange_amount'] > 0){
	    	$order['order_amount'] = $order['exchange_amount'];
	    }
		//防止商户订单号重复
		$order['order_id'] = $out_trade_no.'-'.$order['order_amount']*100; 
		$payment = payment_info($order['pay_id']);
        $pay_code = $payment['pay_code'];

        //选择微信公众号换取openid
        require(dirname(__FILE__) . '/weixin/wechat.class.php');
        $weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `token` = 'xcx'" );
        if(!$weixinconfig){
        	echo 1;exit; 
        }
		file_put_contents('20210324.txt',"pay_code::".$pay_code."\r\n" , FILE_APPEND);
        switch ($pay_code){
            case 'weixin':
                $pay_config = unserialize($payment['pay_config']);
                foreach ($pay_config as $key=>$val){
                    $pay_params[$val['name']] = $val['value'];
                }
                break;

            case 'weixin_cn':
                $pay_config = unserialize($payment['pay_config']);
                foreach ($pay_config as $key=>$val){
                    $pay_params[$val['name']] = $val['value'];
                }
            break;
        }
        $pay_params['order_sn'] = $order['order_sn'];
        $pay_params['order_amount'] = $order['order_amount']; 
        $pay_params['notify_url'] = 'https://www.quickact.net/mobile/respond.php';
        $pay_params['openid'] = $GLOBALS['db']->getOne("SELECT xcx_id FROM ".$GLOBALS['ecs']->table('users')." u inner join ".$GLOBALS['ecs']->table('order_info')." o on u.user_id=o.user_id WHERE o.order_id = '$order_id'");
		$pay_params['appId'] = $weixinconfig['appid'];
        $pay_params['is_exchange_rate'] = $is_exchange_rate;
		include_once('./includes/modules/payment/' . $pay_code . '.php');
		
		$pay_obj = new $payment['pay_code'];
        $jsApiParameters = $pay_obj->jsApiPay($pay_params);
        //file_put_contents('20210324.txt',"jsApiParameters::".$jsApiParameters."\r\n" , FILE_APPEND);
		$arr=json_decode($jsApiParameters,true); 
		$purl='/pages/wxpay/index?orderId='.$order['order_sn'].'&timeStamp='.$arr['timeStamp'].'&nonceStr='.$arr['nonceStr'].'&'.$arr['package'].'&signType='.$arr['signType'].'&paySign='.$arr['paySign'];
		//file_put_contents('20191023.txt',"purl::".$purl."\r\n" , FILE_APPEND);
	}
	else
	{
		show_message('此订单已支付！'); 
	}
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
		function onBridgeReady(){
			WeixinJSBridge.invoke(
				'getBrandWCPayRequest',
                <?php echo $jsApiParameters; ?>,
				function(res){
                    // alert(res.err_code+res.err_desc+res.err_msg);
					if(res.err_msg == "get_brand_wcpay_request:ok" ) {
						window.location.href='respond_wx.php?act=ok';
					}     // 使用以上方式判断前端返回,微信团队郑重提示：res.err_msg将在用户支付成功后返回    ok，但并不保证它绝对可靠。
				}
			);
		}
		 
		var ua = navigator.userAgent.toLowerCase();
		if(ua.match(/MicroMessenger/i)=="micromessenger") { 
			wx.miniProgram.getEnv((res)=>{
			   if (res.miniprogram) {
				   xcx=true; 
				   wx.miniProgram.navigateTo({url:'<?php echo $purl ?>'});
			   
			   } 
			})
		} 
		 
	</script>
</head>
<body>
</html>