<?php

define('IN_ECS', true);
require(dirname(__FILE__) . '/../includes/init.php');
include_once "wxBizDataCrypt.php";



	$appid = 'wxaabb86e36c9c7620';
	$secret = '9708e0af98b6e842788c8468cd4f3c1c';
	$aid=3;
	
	$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = ".$aid );
	$appid = $weixinconfig['appid'];
	$secret = $weixinconfig['appsecret'];
	 
	$code = $_GET['code']?$_GET['code']:0;

	$arrSess = getWxSession($appid,$secret,$code);
	$sessionKey = $arrSess['session_key'];

	$encryptedData= $_GET['encryptedData'];
	$iv = $_GET['iv'];

	$pc = new WXBizDataCrypt($appid, $sessionKey);
	$errCode = $pc->decryptData($encryptedData, $iv, $data );

	if ($errCode == 0) {
		echo $data;
	} else {
		echo $errCode;
	}
	//if ($errCode == 0) {
	//	$wxLogin_tag = 'wxLogin'.(time().rand(0000,50000));
	//	$loginKey =  md5(base64_encode(time()+rand(0000,50000)));
	//	session($wxLogin_tag,$loginKey);
	//	return showMsg(1,'Success',['loginKey'=>$wxLogin_tag,'userInfo'=>$data]);
	//} else {
	//	return showMsg(0,'Error',$errCode);
	//}




function getWxSession($appid,$secret,$code,$grant_type = 'authorization_code'){
	$url =
		'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid
		.'&secret='.$secret.'&js_code=' .$code
		.'&grant_type='.$grant_type;
		
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	$out_put = curl_exec($curl); 
	curl_close($curl);   
	$arrData = json_decode($out_put,TRUE);  
	return $arrData;
}