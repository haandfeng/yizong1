<?php

include_once "wxBizDataCrypt.php"; 
  
$appid = 'wxaabb86e36c9c7620';//'wxff6238560cf03f1a';
$appsecret = '13926206340ee9549f249196d3cc1faa';//'0d0c2b0cb9af5ce593ccf744669e5b80';
$grant_type = "authorization_code"; //授权（必填）

$code = $_REQUEST['code'];        //有效期5分钟 登录会话

$encryptedData=$_REQUEST['encryptedData'];
$iv = $_REQUEST['iv'];
$signature = $_REQUEST['signature'];
$rawData = $_REQUEST['rawData'];

// 拼接url
$url = "https://api.weixin.qq.com/sns/jscode2session?"."appid=".$appid."&secret=".$appsecret."&js_code=".$code."&grant_type=".$grant_type;

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);     
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl); 
	$res1= json_decode($res, true);  
 

$sessionKey = $res1['session_key']; //取出json里对应的值
$signature2 =  sha1(htmlspecialchars_decode($rawData).$sessionKey);
// 验证签名
if ($signature2 !== $signature){
	echo "验签失败";
	exit;
} 

$pc = new WXBizDataCrypt($appid, $sessionKey);
$errCode = $pc->decryptData($encryptedData, $iv, $data );

if ($errCode == 0) {
	echo $data;
	exit;
} else {
	echo $errCode;
	exit;
}

 