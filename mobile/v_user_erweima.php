<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if($_CFG['is_distrib'] == 0)
{
	show_message('没有开启微信分销服务！','返回首页','index.php'); 
}

$is_agree = is_agree($_SESSION['user_id']);
if($is_agree != 1)
{
	ecs_header("Location: v_user_agree_period.php");
	exit;
}


if(isset($_GET['user_id']) && intval($_GET['user_id']) > 0)
{
	$user_id = intval($_GET['user_id']);
}
else
{
	 ecs_header("Location:./\n");
	 exit;
}

if($_SESSION['user_id'] != $user_id && $user_id > 0)
{
	$weixinconfig = $GLOBALS['db']->getRow( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
	$weixin = new core_lib_wechat($weixinconfig);
	$openid = '';
	if($_GET['code'])
	{
		$json = $weixin->getOauthAccessToken();
		$openid = $json['openid'];
		if($openid)
		{
			$info = $weixin->getOauthUserinfo($json['access_token'],$openid);
			$nickname = $info['nickname'];
			$sex = intval($info['sex']);
			$country = $info['country'];
			$province = $info['province'];
			$city = $info['city'];
			$headimgurl = $info['headimgurl'];
			$createtime = gmtime();
			$createymd = date('Y-m-d');
			$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$openid}'");
			if($rows)
			{
				 $set = "`nickname`='{$nickname}',`sex`='$sex'," .
				 		"`country`='$country',`province`='$province'," . 
						"`city`='$city',`headimgurl`='$headimgurl'";
				 $sql = "UPDATE " . $GLOBALS['ecs']->table('weixin_user') . 
				 		" SET {$set} WHERE fake_id='" . $openid . "'";
				 $GLOBALS['db']->query($sql);
			}
			else
			{
				 $sql = "INSERT INTO " . 
				 		$GLOBALS['ecs']->table('weixin_user') . 
						" (`ecuid`,`fake_id`,`createtime`,`createymd`," .												
						"`isfollow`,`nickname`,`sex`,`country`,`province`,".
						"`city`,`headimgurl`) values " .
						"(0,'{$openid}','{$createtime}','{$createymd}',".
						"0,'{$nickname}','{$sex}','{$country}','{$province}'," . 
						"'{$city}','{$headimgurl}')";
				 $GLOBALS['db']->query($sql);
			}
			$user_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$openid}'");
			if($user_info['ecuid'] == 0)
			{
				 ecs_header("Location:register.php?u={$user_id}\n");
				 exit;
			}
		}
	}
	if(empty($openid) || $openid == '')
	{
		$url = $GLOBALS['ecs']->url()."/v_user_erweima.php?user_id=" . $user_id;
		$url = $weixin->getOauthRedirect($url,1,'snsapi_userinfo');
		// header("Location:$url");exit;
	}
}

//是否生成过二维码
if(is_erweima($_SESSION['user_id']) == 0)
{
	$config = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
	$weixin = new core_lib_wechat($config);
	$scene_id = $db->getOne("select id from " . $GLOBALS['ecs']->table('weixin_qcode') . " order by id desc");
	$scene_id = $scene_id ? $scene_id+1 : 1;
	$qcode = $weixin->getQRCode($scene_id,1,$_SESSION['user_id']);
	$GLOBALS['db']->query("insert into " . $GLOBALS['ecs']->table('weixin_qcode') . " (`id`,`type`,`content`,`qcode`) value ($scene_id,4,'" . $_SESSION['user_id'] . "','{$qcode['ticket']}')");
}

//是否有邀请码
$invite_code_self = $GLOBALS['db']->getOne("SELECT invite_code FROM ". $GLOBALS['ecs']->table('users') ." WHERE user_id = '{$user_id}'");
if(!$invite_code_self)
{
    $invite_code_self = random(6);
    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `invite_code` = "' . $invite_code_self .'" WHERE user_id = ' . $_SESSION['user_id'];
    $GLOBALS['db']->query($sql);
}

//生成注册二维码
$file = 'images/code/'.$_SESSION['user_id'].'.png';
if(!is_file($file))
{
    require(ROOT_PATH.'includes/phpqrcode.php');
    $data = 'http://'.$_SERVER['HTTP_HOST'].'/mobile/register.php?id='.$user_id.'|'.$invite_code_self;
    $filename = ROOT_PATH.'images/code/'.$_SESSION['user_id'].'.png';
    $errorCorrectionLevel = 'L';//容错级别
    $matrixPointSize = 5;//生成图片大小
    QRcode::png($data,$filename, $errorCorrectionLevel, $matrixPointSize, 2);
}
$smarty->assign('filename',$file);

//顶部分销
$isfenxiao_topuser = $GLOBALS['db']->getOne( "SELECT isfenxiao_topuser FROM " . $GLOBALS['ecs']->table('users') . " WHERE `user_id` = '$_SESSION[user_id]'" );
$smarty->assign('isfenxiao_topuser',$isfenxiao_topuser);

//print_r(get_user_info_by_user_id($user_id)); die();
if (!$smarty->is_cached('v_user_erweima.dwt', $cache_id))
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
    //zhouhui 如果是获取的uid 则读取它.
    if($user_id){
    	$smarty->assign('user_info',	   get_user_info_by_user_id($user_id));
    	$smarty->assign('erweima',get_erweima_by_user_id($user_id));
		$smarty->assign('user_id',$user_id);
    }else
    {
		$smarty->assign('user_info',	   get_user_info_by_user_id($_SESSION['user_id']));
		$smarty->assign('erweima',get_erweima_by_user_id($_SESSION['user_id']));
		$smarty->assign('user_id',$_SESSION['user_id']);
	}

    /* 页面中的动态内容 */
    assign_dynamic('v_user_erweima');
}

$smarty->display('v_user_erweima.dwt');

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