<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');

$_t = $_REQUEST['_t'];
$code = $_REQUEST['code'];
$aid = $_REQUEST['aid'];
$openid = $_REQUEST['openid'];
if($_t=='openid')
{ 
	$arr=getOpenid($code,$aid);
	//echo $arr['openid'];
	//echo '---';
	$crr=getCode($aid);
	//echo $crr['access_token'];
	$orr=getUnionid($arr['openid'],$crr['access_token']);
	//echo $orr['unionid'];
}
else if($_t=='unionid')
{
	$crr=getCode($aid); 
	$orr=getUnionid($openid,$crr['access_token']);
}
// 获取openid
function getOpenid($code,$aid){ 
	$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = ".$aid );
	$appid = $weixinconfig['appid'];
	$secret = $weixinconfig['appsecret'];

	$url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';    
		
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);     
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl); 
	return json_decode($res, true);  
}

// 获取code
function getCode($aid){  
	$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = ".$aid );
	$appid = $weixinconfig['appid'];
	$secret = $weixinconfig['appsecret'];
	$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret='.$secret;    
		
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);     
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl); 
	return json_decode($res, true);  
}

// 获取Unionid
function getUnionid($openid,$code){   
	$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$code.'&openid='.$openid.'&lang=zh_CN' ;    
		
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);     
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl);
	echo $res;
	return json_decode($res, true); 
}

/**
 * 取得随机数
 *
 * @param int $length 生成随机数的长度
 * @param int $numeric 是否只产生数字随机数 1是0否
 * @return string
 */
function random($length, $numeric = 0)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max  = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}


?>