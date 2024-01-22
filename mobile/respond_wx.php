<?php

/**
 * ECSHOP 支付响应页面

 * $Author: derek $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

$oid = $_GET['oid'];

$msg = '支付成功！';
//0$order_sn = $_GET['out_trade_no'];
//order_paid($order_sn);

assign_template();
$position = assign_ur_here();
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('helps',      get_shop_help());      // 网店帮助
if($oid!='')
{
	$shiming=getshiming($oid);
}
$smarty->assign('message',    $msg.$shiming);
$smarty->assign('shop_url',   $ecs->url());
//file_put_contents('20211210.txt',"respond_wx:".$_COOKIE['pintuan_flagnew']."\r\n" , FILE_APPEND);
if (empty($_COOKIE['pintuan_flagnew'])==false)
{  
	$pintuanid=$_COOKIE['pintuan_flagnew'];
	setcookie("pintuan_flagnew", "", time() - 3600);
}
$smarty->assign('pintuanid',   $pintuanid);
$smarty->display('respond.dwt');

	function getshiming($oid)
	{
		$msg='';
		$sql = "select sm_status from ecs_users where exists (select 1 from ecs_order_info where order_id='$oid' and ecs_order_info.user_id=ecs_users.user_id)";
		//file_put_contents('20210617.txt',date('Y-m-d H:i:s')."sql-".$sql."\r\n" , FILE_APPEND); 
		$status=$GLOBALS['db']->getOne($sql);
		if($status=='1')
		{
			$msg='';
		}
		else
		{
			$msg="<br/>提示：为了购买正常，需要补充<br/>实名信息姓名+身份证号码 <!--a href='/mobile/user_shengfen.php?act=shiming' style='color:red'>去填写</a-->";
			$msg=$msg."<form name='formIdentity' action='user_shengfen.php' method='post' enctype='multipart/form-data'>  <div class='ccontainer'>         <div id='user_bzzx_1'>	      <div class='brand-con radius'>               <div class='hot-list' style='border: #e6e6e6 solid 1px; color:#666; font-weight:normal'>实名认证</div>        <div class='hot-list'>		<span>真实姓名：</span><input type='text' name='real_name' value='' class='inputBg'>          </div>				<div class='hot-list'>		<span>身份证号：</span><input type='text' name='card' value='' class='inputBg'>		</div><div class='hot-list'>		 		</div>				<div class='f_foot'>		<input name='act' type='hidden' value='act_identity'>    <input type='hidden' name='redirect' value=''><style>    input[type='submit'], input[type='reset'], input[type='button'], button {    -webkit-appearance: none;}.add_gift_attr {    background: #E71F19;    text-align: center;    line-height: 100%;    height: 100%;    font-size: 16px;    color: #fff;    width: 100%;    font-family: 微软雅黑;    border: none;    border-radius: 0;}.f_foot {    background: #ff7171;    width: 100%;    height: 33px;    text-align: center;    margin-top: -1.5rem;}</style>		<input name='submit' type='submit' value='确认' border='0' class='add_gift_attr'>		</div>				      </div>		    </div></div></form>";
		}
		return $msg;
	}
?>