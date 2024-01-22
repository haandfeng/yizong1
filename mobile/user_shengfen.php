<?php

/**
 * ECSHOP 文章分类
 
 * $Author: derek $
 * $Id: user_transaction_shiming.php 17217 2011-01-19 06:29:08Z derek $
*/


define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

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


function action_shiming ()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$redirect = isset($_REQUEST['redirect']) ? trim($_REQUEST['redirect']) : '';

	$sql = "select mobile_phone,sm_status as status from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
	$res_status = $db->query($sql);  
	while($row_status =  $GLOBALS['db']->fetchRow($res_status))
	{
		$mobile_phone=$row_status['mobile_phone'];
		$status=$row_status['status']; 
	}
	if($status == 1){
		if($redirect == 'fenxiao'){
			ecs_header("Location: v_user_agree_period.php");
			exit;
		}
		show_message('您已实名认证！', '返回上一页', '/mobile/');
	}
	$position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
	$smarty->assign('phone', $mobile_phone);
	$smarty->assign('status', $status);
	$smarty->assign('redirect', $redirect);
	$smarty->display('user_transaction_shiming.dwt');

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
	$redirect = isset($_REQUEST['redirect']) ? trim($_REQUEST['redirect']) : '';

	include_once (ROOT_PATH . '/includes/cls_image.php');
	$image = new cls_image($_CFG['bgcolor']);
	$real_name = $_POST['real_name'];
	$card = $_POST['card'];
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
			// show_message('请上传身份证正面照！');
		}
	}

	if($rows['back_card'] == '')
	{
		if($back_card == '')
		{
			// show_message('请上传身份证背面照！');
		}
	}

	//$sql = "select count(user_id) from " . $GLOBALS['ecs']->table('users') . " where card = '$card'";
	//$cardcount = $GLOBALS['db']->getOne($sql);
	//if($cardcount>0)
	//{
	//	show_message('该身份证号码已存在，不能重复使用！');
	//}
	
	$sql = 'update ' . $GLOBALS['ecs']->table('users') . " set real_name = '$real_name',card='$card',sm_status = '2'";
	if($face_card != '')
	{
		$sql .= " ,face_card = 'mobile/$face_card'";
	}
	if($back_card != '')
	{
		$sql .= " ,back_card = 'mobile/$back_card'";
	}
	$sql .= " where user_id = '" . $_SESSION['user_id'] . "'";
	$num = $GLOBALS['db']->query($sql);
	$face_card="";
	$back_card="";
	if($num > 0)
	{
		//判断自动审核
		$sql = "select value,store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_shengfen'";
		$flagrow = $GLOBALS['db']->getRow($sql);
		if($flagrow['value']=='1')
		{
				$host = "https://idcert.market.alicloudapi.com";
				$path = "/idcard";
				$method = "GET";
				$appcode = $flagrow['store_dir'];
				$headers = array();
				array_push($headers, "Authorization:APPCODE " . $appcode);
				$querys = "idCard=".$card."&name=".$real_name;
				$bodys = "";
				$url = $host . $path . "?" . $querys;
file_put_contents("log/shiming_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND);
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
file_put_contents("log/shiming_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
			
				$returnarray = json_decode($out_put,TRUE);
				if($returnarray['status']=='01')
				{
					$sql = 'update ' . $GLOBALS['ecs']->table('users') . " set sm_status ='1',sm_time=now() where user_id = '" . $_SESSION['user_id'] . "'";
					$GLOBALS['db']->query($sql);
					if($redirect == 'fenxiao'){
						ecs_header("Location: v_user_agree_period.php");
						exit;
					}
					show_message('您申请实名认证审核通过！', '返回上一页', '/mobile/');
				}
				else if($returnarray['status']=='02'||$returnarray['status']=='205'||$returnarray['status']=='204'||$returnarray['status']=='203'||$returnarray['status']=='202')
				{
					$sql = 'update ' . $GLOBALS['ecs']->table('users') . " set sm_status ='3' where user_id = '" . $_SESSION['user_id'] . "'";
					$GLOBALS['db']->query($sql);
					show_message('您申请实名认证审核不通过，请检查填写是否正确！', '返回上一页', '/mobile/');
				}
				else
				{
					show_message('您已申请实名认证，请等待管理员的审核！', '返回上一页', '/mobile/');
				}
		}
		else{
		show_message('您已申请实名认证，请等待管理员的审核！', '返回上一页', '/mobile/');
		}
	}
	else
	{	if($redirect == 'fenxiao'){
			show_message('实名认证失败！', '返回上一页', 'user_shengfen.php?act=shiming&redirect=fenxiao');
			exit;
		}
		show_message('实名认证失败！', '返回上一页', 'user_shengfen.php?act=shiming');
	}
}

?>