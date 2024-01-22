<?php

/**
 * ECSHOP 文章分类
 
 * $Author: derek $
 * $Id: user_transaction_shiming.php 17217 2011-01-19 06:29:08Z derek $
*/


define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');
/* 未登录处理 */
if(empty($_SESSION['user_id']) )
{
	ecs_header("Location: user.php?act=login\n"); 
}


$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
$action = isset($_POST['act']) ? trim($_POST['act']) :$action;


$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_default";
}

call_user_func($function_name);

function action_shiming()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$step = isset($_REQUEST['step']) ? trim($_REQUEST['step']) : '1';

	$sql = "select mobile_phone2 as mobile_phone,status,real_name,bankcard,card from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
	$res_status = $db->query($sql);
	while($row_status =  $GLOBALS['db']->fetchRow($res_status))
	{
		$mobile_phone=$row_status['mobile_phone'];
		$real_name=$row_status['real_name'];
		$bankcard=$row_status['bankcard'];
		$card=$row_status['card'];
		$status=$row_status['status']; 
	}
	$real_name = isset($_REQUEST['unm']) ? trim($_REQUEST['unm']) : $real_name;
	$card = isset($_REQUEST['ucd']) ? trim($_REQUEST['ucd']) : $card;
	
	$smarty->assign('phone', $mobile_phone);
	$smarty->assign('real_name', $real_name);
	$smarty->assign('bankcard', $bankcard);
	$smarty->assign('card', $card);
	$smarty->assign('status', $status);
	$smarty->assign('username', $username);
	$smarty->assign('usercode', $usercode);
	
	$sql = "select store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
	$fuyuanurl = $GLOBALS['db']->getOne($sql);
	$smarty->assign('fuyuanurl', $fuyuanurl);
	
	if ($step=='1')
	{
	$smarty->display('user_agree_period1.dwt');
	}
	else if ($step=='2')
	{
	$smarty->display('user_agree_period2.dwt');
	}
	else if ($step=='3')
	{
	$smarty->display('user_agree_period.dwt');
	}

}
/**
 * 发送短信验证码
 */
function action_send_mobile_code()
{ 
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	
	require_once (ROOT_PATH . 'includes/lib_validate_record.php');
	
	$mobile_phone = trim($_POST['mobile']); 
	
	
	if(empty($mobile_phone))
	{
		exit("手机号不能为空");
		return;
	}
	else if(! is_mobile_phone($mobile_phone))
	{
		exit("手机号格式不正确");
		return;
	}
	else if(check_validate_record_exist($mobile_phone))
	{ 
		// 获取数据库中的验证记录
		$record = get_validate_record($mobile_phone);
		
		/**
		 * 检查是过了限制发送短信的时间
		 */
		$last_send_time = $record['last_send_time'];
		$expired_time   = $record['expired_time'];
		$create_time    = $record['create_time'];
		$count          = $record['count'];
		$count_ip       = $record['count_ip'];
		
		// 每天每个手机号最多发送的验证码数量
		$max_sms_count    = 3;
		$max_sms_count_ip = 5;

		// 发送最多验证码数量的限制时间，默认为24小时
		$max_sms_count_time = 60 * 60 * 24;
		

		if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count) {
			echo ("同一手机号24小时内只能发送3次验证码！");
			return;
		}

		if(time() - $create_time < $max_sms_count_time && $record['count_ip'] > $max_sms_count_ip) {
			echo ("同一个ip24小时内只能发送5次验证码！");
			return;
		}

		if((time() - $last_send_time) < 60)
		{
			echo ("每60秒内只能发送一次短信验证码，请稍候重试");
			return;
		}
		else if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count)
		{
			echo ("您发送验证码太过于频繁，请稍后重试！");
			return;
		}
		else
		{
			$count ++;
			$count_ip ++;
		}
	} 
	
	require_once (ROOT_PATH . 'includes/lib_passport.php');
	 
	// 设置为空
	$_SESSION[VT_MOBILE_VALIDATE] = array();
	
	require_once (ROOT_PATH . 'sms/sms.php');
	 
	// 生成6位短信验证码
	$mobile_code = rand_number(6); 
	// 短信内容
	$content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['shop_name'], $mobile_code, $GLOBALS['_CFG']['shop_name']);
 
	/* 发送激活验证邮件 */
	$result = sendSMS($mobile_phone, $content);
	if($result)
	{
		
		if(! isset($count))
		{
			$ext_info = array(
				"count" => 1,
				"count_ip" => 1
			);
		}
		else
		{
			$ext_info = array(
				"count" => $count,
				"count_ip" => $count_ip
			);
		}
		// 保存验证的手机号
		$_SESSION[VT_MOBILE_VALIDATE] = $mobile_phone;
		// 保存验证信息
		save_validate_record($mobile_phone, $mobile_code, VT_MOBILE_VALIDATE, time(), time() + 30 * 60, $ext_info);
		echo 'ok';
	}
	else
	{
		echo '短信验证码发送失败';
	}
}

/**
 * 随机生成指定长度的数字
 *
 * @param number $length        	
 * @return number
 */
function rand_number ($length = 6)
{
	if($length < 1)
	{
		$length = 6;
	}
	
	$min = 1;
	for($i = 0; $i < $length - 1; $i ++)
	{
		$min = $min * 10;
	}
	$max = $min * 10 - 1;
	
	return rand($min, $max);
}

function action_act_identity ()
{

	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	include_once (ROOT_PATH . '/includes/cls_image.php');
	$image = new cls_image($_CFG['bgcolor']);
	$real_name = $_POST['real_name'];
	$card = $_POST['card'];
	$bankcard = $_POST['bankcard'];
	$isagree = $_POST['isagree'];
	$isimg = $_POST['isimg'];
	$mobile_phone = $_POST['mobile_phone'];
	$mobile_code = $_POST['mobile_code'];
	$ispwd = $_POST['ispwd'];
	//$k1 = $_POST['k1'];
	
	$sql = "select store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
	$fuyuanurl = $GLOBALS['db']->getOne($sql);
	
	if($ispwd=="1")
	{
		$pwd = $_POST['pwd'];
		$card = $_POST['card'];
		$sig="pwd=".$pwd."|card=".$card."|iopjkg7867gh84g000f47";
		$sign=md5(md5($sig)); 
		$url=$fuyuanurl."index.php?a=save&m=mode_perioduser|input&d=flow&ajaxbool=true&rnd=673623&id=&card=".$card."&pwd=".$pwd."&sysmodeid=94&sysmodenum=perioduser&jk802=0&sign=".urlencode($sign);		
file_put_contents("log/fuyuanpwd_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n原：".$sig."\r\n生：".$sign, FILE_APPEND);			
			$curl = curl_init(); 
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$out_put = curl_exec($curl); 
			curl_close($curl);  
file_put_contents("log/fuyuanpwd_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
			$returnarray = json_decode($out_put,TRUE);
			if($returnarray["msg"]=='密码设置成功') {
				
				$sql = "update ecs_users_period set pwd='".$pwd."' where user_id=".$_SESSION['user_id'];
				$GLOBALS['db']->query($sql); 
				show_message('您申请实名认证审核通过,密码设置成功！', '返回购物车', '/mobile/flow.php');
			}
			else{
				show_message('您申请实名认证审核通过,'.$returnarray["msg"].'！', '返回购物车', '/mobile/flow.php');
			} 
	}
	else if($isimg=="1")
	{
		if(isset($_FILES['face_card']) && $_FILES['face_card']['tmp_name'] != '')
		{
			if($_FILES['face_card']['width'] > 800)
			{
				show_message('图片宽度不能超过800像素！');
			}
			if($_FILES['face_card']['height'] > 800)
			{
				show_message('图片高度不能超过800像素！');
			} 
			$face_card = $image->upload_image($_FILES['face_card']);
			if($face_card === false)
			{
				show_message($image->error_msg());
			}
		}
		if(isset($_FILES['back_card']) && $_FILES['back_card']['tmp_name'] != '')
		{
			if($_FILES['back_card']['width'] > 800)
			{
				show_message('图片宽度不能超过800像素！');
			}
			if($_FILES['back_card']['height'] > 800)
			{
				show_message('图片高度不能超过800像素！');
			}
			$back_card = $image->upload_image($_FILES['back_card']);
			if($back_card === false)
			{
				show_message($image->error_msg());
			}
		}

		$sql = "select face_card,back_card from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
		$rows = $GLOBALS['db']->getRow($sql);
		if($rows['face_card'] == '')
		{
			if($face_card == '')
			{
				show_message('请上传身份证正面照！');
			}
		}

		if($rows['back_card'] == '')
		{
			if($back_card == '')
			{
				show_message('请上传身份证背面照！');
			}
		} 
		if($face_card != ''&&$back_card != '')
		{
			$sql = "update " . $GLOBALS['ecs']->table('users') . " set face_card = 'mobile/$face_card' ,back_card = 'mobile/$back_card'  where user_id = '" . $_SESSION['user_id'] . "'";
			$result=$GLOBALS['db']->query($sql); 
			
			
		}  
		$sql = "select face_card,back_card from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
		$rows = $GLOBALS['db']->getRow($sql);
		$imgfront=https()."://".$_SERVER["SERVER_NAME"] ."/".$rows['face_card'];
		$imgback=https()."://".$_SERVER["SERVER_NAME"] ."/".$rows['back_card'];
		
		//身份证照自动审核 
		$sql = "select value,store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_shenfengimg'";
		$flagrow = $GLOBALS['db']->getRow($sql);
		if($flagrow['value']=='1')
		{ 
				$out_put=toimginfo($flagrow['store_dir'],$imgfront,0);
				$returnarray = json_decode($out_put,TRUE);
				if($returnarray['code']!='1')
				{
					show_message('您上传的身份证照（正面）出错了，'.$returnarray['msg'] );
				}
				else
				{
					$username=$returnarray['result']['name'];
					$usercode=$returnarray['result']['code'];
					$out_put=toimginfo($flagrow['store_dir'],$imgback,1);
					if($returnarray['code']!='1')
					{
						show_message('您上传的身份证照（背面）出错了，'.$returnarray['msg'] );
					}
					else if($username==""||$usercode=="")
					{
						show_message('您上传的身份证照不清晰');
					}
					else
					{
						header('Location: user_agree_period.php?act=shiming&step=3&unm='.$username.'&ucd='.$usercode);
						
					}
				} 
				
		}
		exit;
	}
	$sql = "select rank_points from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
	$rows = $GLOBALS['db']->getRow($sql);
	$pay_points=$rows['rank_points'];
	
	$sql = 'select amount from ecs_period_points where minpoints<='.$pay_points.' and maxpoints>'.$pay_points .' order by id desc limit 1';
	$period_point = $db->getOne($sql);
	if(!empty($period_point))
	{
		if(strpos($period_point,'M')==true)
		{
			$period_point=str_replace('M',$pay_points,$period_point);
			$pay_points=eval("return $period_point;");
		}
		else{
			$pay_points=$period_point;
		}
	}
	
	
	$sql = '';
	if($isagree=='1')//签分期协议
	{  
		if($mobile_phone=="")
		{
			show_message('请填写手机号');			
		} 
		if($real_name=="")
		{
			show_message('请填写姓名');			
		} 
		if($card=="")
		{
			show_message('请填写身份证号');			
		} 
		if($bankcard=="")
		{
			show_message('请填写银行卡号');			
		} 
		if($mobile_code=="")
		{
			show_message('请先手机验证');			
		} 
		$mobile_phone2 = $_SESSION[VT_MOBILE_VALIDATE];  
		
		if($mobile_phone!=$mobile_phone2)
		{
			show_message('请填写手机号错误');	
		} 
		require_once (ROOT_PATH . 'includes/lib_passport.php');
		$result = validate_mobile_code($mobile_phone, $mobile_code); 
		if($result == 1)
		{
			show_message($_LANG['msg_mobile_phone_blank'] );
		}
		else if($result == 2)
		{
			show_message($_LANG['msg_mobile_phone_format'] );
		}
		else if($result == 3)
		{
			show_message($_LANG['msg_mobile_phone_code_blank'] );
		}
		else if($result == 4)
		{
			show_message($_LANG['invalid_mobile_phone_code'] );
		}
		else if($result == 5)
		{
			show_message($_LANG['invalid_mobile_phone_code'] );
		}
		else if($result == 0)
		{
			$sql = "update " . $GLOBALS['ecs']->table('users') . " set real_name = '$real_name',bankcard='$bankcard',card='$card',mobile_phone2='$mobile_phone'  where user_id = '" . $_SESSION['user_id'] . "'";
			$result=$GLOBALS['db']->query($sql); 
			if($result>0)
			{
				$sql = 'select count(user_id)from ecs_users_period where user_id='.$_SESSION['user_id'];
				$result=$GLOBALS['db']->getOne($sql);
				if($result==0)
				{
					$day=date('d',time());
					if($day>28)$day=28;
					$sql = 'insert into ecs_users_period(user_id,totalamount,useamount,notuseamount,Expiredate,billdate_day,isagree,agreetime) ';
					$sql.= 'values('.$_SESSION['user_id'].','.$pay_points.',0,'.$pay_points.',DATE_ADD(now(), INTERVAL 1 YEAR),'.$day.',1,now())';
					$GLOBALS['db']->query($sql); 
				} 
				else
				{
					$day=date('d',time());
					if($day>28)$day=28;
					$sql = 'update ecs_users_period set totalamount='.$pay_points.',useamount=0,notuseamount='.$pay_points;
					$sql.=',Expiredate=DATE_ADD(now(), INTERVAL 1 YEAR),billdate_day='.$day.',isagree=1,agreetime=now() where user_id='.$_SESSION['user_id'];
					$GLOBALS['db']->query($sql); 
				}
			}
		  
			$jkmsg=""; 
			 //tofuyuan('');show_message('该协议！', '返回上一页', 'user_shengfen.php?act=shiming');//调试
			//判断自动审核
			
			$sql = "select value,store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_yinhang'";
			$flagrow = $GLOBALS['db']->getRow($sql);
			if($flagrow['value']=='1')
			{
					$host = "https://b4bankcard.market.alicloudapi.com";
					$path = "/bank4Check";
					$method = "GET";
					$appcode = $flagrow['store_dir'];
					$headers = array();
					array_push($headers, "Authorization:APPCODE " . $appcode);
					$querys = "accountNo=".$bankcard."&idCard=".$card."&mobile=".$mobile_phone."&name=".$real_name;
					$bodys = "";
					$url = $host . $path . "?" . $querys;
	file_put_contents("log/yinhang_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND);
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($curl, CURLOPT_FAILONERROR, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HEADER, false);
					//curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
					//状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
					if (1 == strpos("$".$host, "https://"))
					{
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
					}
					$out_put = curl_exec($curl);
	file_put_contents("log/yinhang_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
				
					$returnarray = json_decode($out_put,TRUE);
					$jkmsg=str_replace("'","''",$returnarray['msg']);
					if($returnarray['status']=='01')
					{
						if($returnarray['cardType']=='借记卡')
						{
								$sql = "update ecs_users_period set isflag=99,flagmsg='".$jkmsg."' where user_id=".$_SESSION['user_id'];
								$GLOBALS['db']->query($sql);   
							$farr=tofuyuan('');
							if($farr["flag"]==true)
							{
								$sql = "update ecs_users_period set isflag=100,flagmsg='".$jkmsg."' where user_id=".$_SESSION['user_id'];
								$GLOBALS['db']->query($sql);  
								//show_message('您申请实名认证审核通过！', '返回购物车', '/mobile/flow.php');
								
								$smarty = $GLOBALS['smarty'];
								$smarty->assign('u', $farr["dataid"]);
								$smarty->assign('card', $card);
								$smarty->display('user_agree_period5.dwt');
							}
							else
							{
								$sql = "update ecs_users_period set isflag=3,flagmsg='".$farr["msg"]."' where user_id=".$_SESSION['user_id'];
								$GLOBALS['db']->query($sql); 
								show_message('您申请实名认证失败，'.$farr["msg"].'！', '返回购物车', '/mobile/flow.php');
							}
						}
						else
						{
							$jkmsg="非借记卡";
							$sql = "update ecs_users_period set isflag=3,flagmsg='".$jkmsg."' where user_id=".$_SESSION['user_id'];
							$GLOBALS['db']->query($sql); 
							tofuyuan('');
							show_message('您申请实名认证审核不通过，'.$jkmsg.'！');
						} 
					}
					else if($returnarray['status']=='02'||$returnarray['status']=='207'||$returnarray['status']=='206'||$returnarray['status']=='205'||$returnarray['status']=='204'||$returnarray['status']=='203'||$returnarray['status']=='202')
					{
						$sql = "update ecs_users_period set isflag=3,flagmsg='".$jkmsg."' where user_id=".$_SESSION['user_id'];
						$GLOBALS['db']->query($sql); 
						tofuyuan('');
						show_message('您申请实名认证审核不通过，'.$jkmsg.'！');
					}
					else
					{
						tofuyuan($out_put);
						show_message('您已申请实名认证，请等待管理员的审核！', '返回上一页', '/mobile/');
					}
			}
			else{
				tofuyuan('');
				show_message('您已申请实名认证，请等待管理员的审核！', '返回上一页', '/mobile/');
			} 
		}
	}
	else
	{
		show_message('您未同意该协议！', '返回上一页', 'user_shengfen.php?act=shiming');
	}
}
function tofuyuan($out_put)
{ 
		$flag=false;
		$msg="";
		$dataid=0;
		$sql = "select store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
		$fuyuanurl = $GLOBALS['db']->getOne($sql);
		
		//返回福源 
		$sql = "select p.user_id,isflag,flagmsg,real_name,mobile_phone2 as mobile_phone,user_name,rank_points,bankcard,card,face_card,back_card,totalamount,useamount,notuseamount,expiredate from " . $GLOBALS['ecs']->table('users') . " a left join ecs_users_period p on p.user_id=a.user_id where p.user_id = '" . $_SESSION['user_id'] . "'";
		$rows = $GLOBALS['db']->getRow($sql); 
		if($rows["user_id"]!="")
		{ 
			if($rows["flagmsg"]=="")$rows["flagmsg"]=$out_put;
			$url=$fuyuanurl."index.php?a=save&m=mode_perioduser|input&d=flow&ajaxbool=true&rnd=673623&fromuser_id=".$rows["user_id"]."&id=";
			$url.="&createtime=".urlencode(date('Y-m-d H:i:s',time()))."&real_name=".$rows["real_name"];
			$url.="&mobile_phone=".$rows["mobile_phone"];
			$url.="&user_name=".$rows["user_name"];
			$url.="&pay_points=".$rows["rank_points"];
			$url.="&bankcard=".$rows["bankcard"];
			$url.="&card=".$rows["card"];
			$url.="&face_card=".urlencode(https()."://".$_SERVER["SERVER_NAME"] ."/".$rows["face_card"]);
			$url.="&back_card=".urlencode(https()."://".$_SERVER["SERVER_NAME"] ."/".$rows["back_card"]);
			$url.="&flagmsg=".urlencode($rows["flagmsg"]);
			$url.="&isflag=".$rows["isflag"];
			$url.="&totalamount=".$rows["totalamount"];
			$url.="&useamount=".$rows["useamount"];
			$url.="&notuseamount=".$rows["notuseamount"];
			$url.="&expiredate=".$rows["expiredate"];
			$url.="&sysmodeid=94&sysmodenum=perioduser&jk802=0"; 
			$url.="&sign=".md5(md5("real_name=".$rows["real_name"]."|mobile_phone=".$rows["mobile_phone"]."|bankcard=".$rows["bankcard"]."|card=".$rows["card"]."|expiredate=".$rows["expiredate"]."|iopjkg7867gh84g000f45")); 
			
file_put_contents("log/fuyuan_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND);			
			$curl = curl_init(); 
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$out_put = curl_exec($curl); 
			curl_close($curl);  
file_put_contents("log/fuyuan_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
			$returnarray = json_decode($out_put,TRUE);
			if($returnarray["success"]==true) {$flag=true;$dataid=$returnarray["data"];}
			else $msg=$returnarray["msg"];
		}
		return array("flag"=>$flag,"msg"=>$msg,"dataid"=>$dataid);
}
function toimginfo($appcode,$img,$type)
{
	if($type==0)$t="front";else $t="back";
	$host = "https://ocridcard.market.alicloudapi.com";
	$path = "/idimages";
	$method = "POST";
	$headers = array();
	array_push($headers, "Authorization:APPCODE " . $appcode);
	//根据API的要求，定义相对应的Content-Type
	array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
	$querys = "";
	$bodys = 'image='.$img.'&idCardSide='.$t; //图片 + 正反面参数 默认正面，背面请传back 
	$url = $host . $path;
file_put_contents("log/shenfengimg_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND); 
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FAILONERROR, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	//curl_setopt($curl, CURLOPT_HEADER, true);   如不输出json, 请打开这行代码，打印调试头部状态码。
	//状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
	if (1 == strpos("$".$host, "https://"))
	{
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	}
	curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
	$out_put = curl_exec($curl);
file_put_contents("log/shenfengimg_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
	return $out_put;
}
 function https()
 {
    if(
        (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') or
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
    )
	{return 'https';}
	else
	{
		return 'http';
	}
}  

?>