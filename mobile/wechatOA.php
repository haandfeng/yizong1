<?php
/*
AppId：Wechat official account appid
Secret：Wechat  official account appsecret
Redirect_uri：Payment directory
Code：Get the code for sub_openid
*/
$AppId = 'wx62c6a5493ee94f4c';
$Secret = '02705de706575a34b7f17935bda3ae22';
$Scope = 'snsapi_base';
$Redirect_uri = urlencode('http://www.easebutech.com/mobile/wechatOA.php');
$Code = $_GET['code'];



    error_reporting(E_ERROR | E_PARSE );
    date_default_timezone_set("Asia/Hong_Kong");
    $url = 'https://osqt.qfpay.com';
    $api_type = '/trade/v1/payment';
    $mchid = 'ZK7M1Zq4AxWJdS0bxQgAraGmgv'; //錢台提供的 mchid
    $app_code = '44E8F05873584C3BBDCDA4ACBF6A74BE'; //錢台提供的 App Code
    $app_key = 'F8E4CE08540C4A3F8901585FBCBAF6BA'; //錢台提供的 App Key
    $now_time = date("Y-m-d H:i:s"); //獲取當前時間
//// 拼裝 Post 資料 ////
    $fields_string = '';
    $fields = array(
        'mchid' => urlencode($mchid),
        'out_trade_no' => urlencode(1470020842103),
        'pay_type' => urlencode(800201),
        //'sub_openid' => $OpenId,
        'txamt' => urlencode(101),
        'txdtm' => $now_time,
    );
    ksort($fields); //字典排序 A-Z 升序台式
    foreach($fields as $key=>$value) {
        $fields_string .= $key.'='.$value.'&' ;
    }

    $fields_string = substr($fields_string , 0 , strlen($fields_string) - 1);
    $sign = '';
    $sign = strtoupper(md5($fields_string . $app_key));
    echo $fields_string;
    $header = array();
    $header[] = 'X-QF-APPCODE: ' . $app_code;
    $header[] = 'X-QF-SIGN: ' . $sign;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . $api_type);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    $output = curl_exec($ch);
    if(curl_exec($ch) === false)
    {
        echo 'Curl error: ' . curl_error($ch);
    }
    else
    {
    }
    curl_close($ch);
    header('Content-type:text/json');
    $final_data = json_decode($output, true);
    echo "<pre>";
    var_dump($final_data);
    echo "</pre>";
    exit();
?>