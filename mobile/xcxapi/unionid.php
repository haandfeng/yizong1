<?php

define('IN_ECS', true);
require(dirname(__FILE__) . '/../includes/init.php');

	$aid=1;
	$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = ".$aid );
	$appid = $weixinconfig['appid'];
	$secret = $weixinconfig['appsecret'];
	
	 
	$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);     
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);
	$res = curl_exec($curl);
	curl_close($curl); 
	$res1= json_decode($res, true);  
	$access_token=$res1['access_token']; 
	$rows = $GLOBALS['db']->getAll ( "SELECT user_id,aite_id,wx_unionid FROM " . $GLOBALS['ecs']->table('users') . " WHERE aite_id is not null and wx_unionid is null and aite_id<>'' order by user_id desc limit 25 " );
	foreach ($rows as $val) { 
		$wid = $val['aite_id'];
		$user_id = $val['user_id'];
		if(strpos($wid,'weixin_')>-1)
		{ 
			$wid =str_replace('weixin_','',$wid);
			 echo $wid;
			$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$wid."&lang=zh_CN";
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 500);     
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_URL, $url);
			$res = curl_exec($curl);
			curl_close($curl); 
			 echo '==========='; 
			$res1= json_decode($res, true);  
			$unionid=$res1['unionid'];
			$f='';
			if($unionid<>'')
			{
				$f= $unionid; 
			}
			else if($res1['errcode']<>'')
			{//
				$f= 'err:'.$res1['errcode']; 
			}
			else if($res1['subscribe']==0)
			{
				$f= 'err:nosub'; 
			}
			else 
			{
				$f= 'err'; 
			}
		}
		else
		{
			$f= 'err:nowid'; 
		}
		
		$GLOBALS['db']->getRow ( "update " . $GLOBALS['ecs']->table('users') . " set wx_unionid='".$f."' WHERE user_id= ".$user_id );
		
	}
	 