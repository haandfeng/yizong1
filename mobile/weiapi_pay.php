<?php
define('IN_ECS', true);
require('../includes/init.php');
require('../includes/lib_order.php');
include_once('../includes/lib_payment.php');
error_reporting(E_ALL ^ E_NOTICE);
$out_trade_no = intval($_GET['out_trade_no']);

//根据支付id获取订单id
$order_id = $GLOBALS['db']->getOne("SELECT order_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE log_id = '$out_trade_no'");
//获取订单信息
$order = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id' OR parent_order_id = '$order_id' limit 1");

if ($order) {
    if ($order['order_amount'] > 0) {
        //防止商户订单号重复
        $order['order_id'] = $out_trade_no . '-' . $order['order_amount'] * 100;
        $payment = payment_info($order['pay_id']);
        echo <<<ETO
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
             <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
             <meta http-equiv="X-UA-Compatible" content="ie=edge">
             <title>Document</title>
</head>
<body>
        <script>
            var uc = navigator.userAgent;
            var isAndroid = uc.indexOf('Android') > -1 || uc.indexOf('Adr') > -1; //android终端
            var isiOS = !!uc.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端        
     
            H5ToAppToWXPay({$order['order_amount']},'{$order['order_sn']}')                                
            /**
             * JS调用APP微信支付
             * @param price 商品价格
             * @param pay_sn 微信支付单号
             * @constructor
             */
            function H5ToAppToWXPay(price,pay_sn) {
                if(isAndroid){
                    window.AndroidWebView.AppToWXPay(price ,pay_sn);
                } else if (isiOS) {
                    AppToWXPay(price ,pay_sn);
                }
            }
        </script>
</body>
</html>       
ETO;
        die();
    } else {
        show_message('此订单已支付！');
    }
} else {
    echo 1;
    exit;
}
/*$new = new Wxpay();
$res = $new->getPrePayOrder('text', $order['order_sn'], $order['inv_money']*100);*/
//调用成功后
/*if ($res['return_code'] == 'SUCCESS') {
    $array['sign'] = $res['sign'];
    if ($res['result_code'] == 'SUCCESS') {
        $array['prepay_id'] = $res['prepay_id'];
        $res['sign'] = $new->res_sign($res);
        $res['wxkey'] = 'forEaseSummitEaseButechHkShFjiLI';
        exit(json_encode(array('code' => 200, 'datas' => $res)));
    }
}*/
?>