<?php
define('IN_ECS', true);

/*$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
$fp = fopen('log.log',"a");
flock($fp, LOCK_EX) ;
fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".serialize($xml)."\n\n");
flock($fp, LOCK_UN);
fclose($fp);*/
require('../includes/init.php');
require('../includes/lib_order.php');
include_once('../includes/lib_payment.php');
$xml="<xml><appid><![CDATA[wxbeb1c2fef7b3ab15]]></appid>
<bank_type><![CDATA[CFT]]></bank_type>
<cash_fee><![CDATA[1]]></cash_fee>
<cash_fee_type><![CDATA[CNY]]></cash_fee_type>
<device_info><![CDATA[1000]]></device_info>
<fee_type><![CDATA[HKD]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[1493763372]]></mch_id>
<nonce_str><![CDATA[n0drrjtkjqw3rthyj0sbv1xi8cuftgid]]></nonce_str>
<openid><![CDATA[ogOyx01Pa_zwr1CrJUOZ9uepZYJM]]></openid>
<out_trade_no><![CDATA[2018020452978]]></out_trade_no>
<rate_value><![CDATA[80730000]]></rate_value>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[94D57A76347CDF87A67EC70478F3241E]]></sign>
<time_end><![CDATA[20180204150326]]></time_end>
<total_fee>2</total_fee>
<trade_type><![CDATA[APP]]></trade_type>
<transaction_id><![CDATA[4200000097201802046919859582]]></transaction_id>
</xml>"









