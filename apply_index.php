<?php

/**
 * ECSHOP 专题前台
 
 * @author:     webboy <laupeng@163.com>
 * @version:    v2.1
 * ---------------------------------------------
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if (!$smarty->is_cached($templates, $cache_id))
{ 
	
	
    /* 模板赋值 */
    assign_template();
    $position = assign_ur_here(0, $GLOBALS['_LANG']['apply_index']);
    $smarty->assign('page_title',       $position['title']);       // 页面标题
    $smarty->assign('ur_here',          $position['ur_here'] . '> ' . $topic['title']);     // 当前位置
    $smarty->assign('helps',            get_shop_help()); // 网店帮助
    $smarty->assign('all',   	$cats['all']);
    $smarty->assign('tuijian',       $tuijian);
    
    $smarty->assign('logopath',		'/'.DATA_DIR.'/supplier/logo/');
    $smarty->assign('shops_list',   $shop_list['shops']);
    $smarty->assign('filter',       $shop_list['filter']);
    $smarty->assign('record_count', $shop_list['record_count']);
    $smarty->assign('page_count',   $shop_list['page_count']);
    
    $page = (isset($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
    
    $start_array = range(1,$page);
    $end_array   = range($page,$shop_list['page_count']);
    if($page-5>0){
    	$smarty->assign('start',$page-3);
    	$start_array = range($page,$page-2);
    }
    if($shop_list['page_count'] - $page > 5){
    	$smarty->assign('end',$page+3);
    	$end_array   = range($page,$page+2);
    }
    $page_array  = array_merge($start_array,$end_array);
    sort($page_array);
    $smarty->assign('page_array',	array_unique($page_array));
}
if ($action == 'store_joinin')
{
    
	
}
vislog();
//判断 弹框登陆 验证码是否显示
$captcha = intval($_CFG['captcha']);
if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
{
    $GLOBALS['smarty']->assign('enabled_captcha', 1);
    $GLOBALS['smarty']->assign('rand', mt_rand());
}
//取出logo图片
$sql = 'select path from ecs_logo';
$res = $db->getAll($sql);
//
if($_COOKIE['tishi'])
{
    $smarty->assign('tishi',0);
}else{
    setcookie('tishi',1);
    $smarty->assign('tishi',1);
}
$smarty->assign('logo',$res[0]['path']);
 $smarty->display('apply_index.dwt');

function vislog()
{
	$url=$_SERVER["REQUEST_URI"];
	$ip=getip();
	$remark = $_SERVER['HTTP_USER_AGENT'];
	$user='游客';  
	if($_SESSION['user_id']){
		$user='用户'.$_SESSION['user_id'];  
	}	
	$sql="insert into ecs_ip_log(username,ip,createtime,url,remark)values('".str_replace("'","''",$user)."','".str_replace("'","''",$ip)."',now(),'".str_replace("'","''",$url)."','".str_replace("'","''",$remark)."')";
	$GLOBALS['db']->query($sql);
}
function getip() {

  static $ip = '';

  $ip = $_SERVER['REMOTE_ADDR'];

  if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {

    $ip = $_SERVER['HTTP_CDN_SRC_IP'];

  } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {

    $ip = $_SERVER['HTTP_CLIENT_IP'];

  } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {

    foreach ($matches[0] AS $xip) {

      if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {

        $ip = $xip;

        break;

      }

    }

  }

  return $ip;

}
?>