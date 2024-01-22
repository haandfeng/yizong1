<?php

/**
 * ECSHOP 订单管理
 
 * $Author: yehuaixiao $
 * $Id: order.php 17219 2011-01-27 10:49:19Z yehuaixiao $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
  
if ($_REQUEST['act'] == 'fxj0801')
{
    $minorderamount = $_REQUEST['minorderamount'];
    $maxorderamount = $_REQUEST['maxorderamount'];
	
    $monthrate1 = $_REQUEST['monthrate1'];
    $dayrate1 = $_REQUEST['dayrate1'];
    $isshow1 = $_REQUEST['isshow1'];
	
    $monthrate2 = $_REQUEST['monthrate2'];
    $dayrate2 = $_REQUEST['dayrate2'];
    $isshow2 = $_REQUEST['isshow2'];
	
    $monthrate3 = $_REQUEST['monthrate3'];
    $dayrate3 = $_REQUEST['dayrate3'];
    $isshow3 = $_REQUEST['isshow3'];
	
    $monthrate4 = $_REQUEST['monthrate4'];
    $dayrate4 = $_REQUEST['dayrate4'];
    $isshow4 = $_REQUEST['isshow4'];
 

    $a=$GLOBALS['db']->query("update ecs_period_config set minorderamount=".$minorderamount.",maxorderamount=".$maxorderamount." where id=0");
    $b=$GLOBALS['db']->query("update ecs_period_config set monthrate=".$monthrate1.",dayrate=".$dayrate1.",isshow=".$isshow1." where id=1");
    $c=$GLOBALS['db']->query("update ecs_period_config set monthrate=".$monthrate2.",dayrate=".$dayrate2.",isshow=".$isshow2." where id=2");
    $d=$GLOBALS['db']->query("update ecs_period_config set monthrate=".$monthrate3.",dayrate=".$dayrate3.",isshow=".$isshow3." where id=3");
    $e=$GLOBALS['db']->query("update ecs_period_config set monthrate=".$monthrate4.",dayrate=".$dayrate4.",isshow=".$isshow4." where id=4");
    if($a+$b+$c+$d+$e==5)
	{echo "ok";}
	else{echo "error";}
	
}
 
if ($_REQUEST['act'] == 'fxj0805')
{ 
    $min0 = $_REQUEST['min0'];
    $min1 = $_REQUEST['min1'];
    $min2 = $_REQUEST['min2'];
    $min3 = $_REQUEST['min3'];
    $min4 = $_REQUEST['min4'];
    $min5 = $_REQUEST['min5'];
    $min6 = $_REQUEST['min6'];
    $min7 = $_REQUEST['min7'];
    $min8 = $_REQUEST['min8'];
    $min9 = $_REQUEST['min9'];
	
    $max0 = $_REQUEST['max0'];
    $max1 = $_REQUEST['max1'];
    $max2 = $_REQUEST['max2'];
    $max3 = $_REQUEST['max3'];
    $max4 = $_REQUEST['max4'];
    $max5 = $_REQUEST['max5'];
    $max6 = $_REQUEST['max6'];
    $max7 = $_REQUEST['max7'];
    $max8 = $_REQUEST['max8'];
    $max9 = $_REQUEST['max9'];
	
    $mf0 = $_REQUEST['mf0'];
    $mf1 = $_REQUEST['mf1'];
    $mf2 = $_REQUEST['mf2'];
    $mf3 = $_REQUEST['mf3'];
    $mf4 = $_REQUEST['mf4'];
    $mf5 = $_REQUEST['mf5'];
    $mf6 = $_REQUEST['mf6'];
    $mf7 = $_REQUEST['mf7'];
    $mf8 = $_REQUEST['mf8'];
    $mf9 = $_REQUEST['mf9'];

    $op = $_REQUEST['op'];
	
	$a=0;
	$b0=0;
	$b1=0;
	$b2=0;
	$b3=0;
	$b4=0;
	$b5=0;
	$b6=0;
	$b7=0;
	$b8=0;
	$b9=0;
    $a=$GLOBALS['db']->query("delete from ecs_period_points ");
	if($a>0)
	{
		if(!empty($min0)) $b0=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min0.",".$max0.",'".$mf0."',now())");
		if(!empty($min1)) $b1=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min1.",".$max1.",'".$mf1."',now())");
		if(!empty($min2)) $b2=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min2.",".$max2.",'".$mf2."',now())");
		if(!empty($min3)) $b3=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min3.",".$max3.",'".$mf3."',now())");
		if(!empty($min4)) $b4=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min4.",".$max4.",'".$mf4."',now())");
		if(!empty($min5)) $b5=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min5.",".$max5.",'".$mf5."',now())");
		if(!empty($min6)) $b6=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min6.",".$max6.",'".$mf6."',now())");
		if(!empty($min7)) $b7=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min7.",".$max7.",'".$mf7."',now())");
		if(!empty($min8)) $b8=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min8.",".$max8.",'".$mf8."',now())");
		if(!empty($min9)) $b9=$GLOBALS['db']->query("insert into ecs_period_points(`minpoints`, `maxpoints`, `amount`, `updatetime`)values(".$min9.",".$max9.",'".$mf9."',now())");
		if($op!="") $GLOBALS['db']->query("UPDATE `dbyizong`.`ecs_shop_config` SET `value` = '".$op."' WHERE code='userinfo_fuyuan'");
    }
	if($b0+$b1+$b2+$b3+$b4+$b5+$b6+$b7+$b8+$b9>0)
	{echo "ok";}
	else{echo "error";}
	
}

if ($_REQUEST['act'] == 'fxj0814')
{ 
    $totalamount = $_REQUEST['t'];
    $useamount = $_REQUEST['u'];
    $notuseamount = $_REQUEST['n'];
    $returnamount = $_REQUEST['r'];
    $returnid = $_REQUEST['ri'];
    $userid = $_REQUEST['i']; 
    $notuseamount_qc = $notuseamount-$returnamount;

	$a=0;
    $a=$GLOBALS['db']->query("update  ecs_users_period set  `totalamount`=".$totalamount.", `useamount`=".$useamount.", `notuseamount`=".$notuseamount." where user_id=".$userid);
	if($a>0)
	{
		$a=$GLOBALS['db']->query("insert into ecs_period_log(return_id, `notuseamount_qc`, `amount`, `notuseamount_qm`, `createtime`) values(".$returnid.",".$notuseamount_qc.",".$returnamount.",".$notuseamount.",now())");
		echo "ok";
    } 
	else{echo "error";}
	
}
if ($_REQUEST['act'] == 'fxj0828')
{ 
    $over_time = $_REQUEST['t'];
    $isflag = $_REQUEST['yu']; 
	if($isflag='-1')
	{
		$flagmsg='有逾期';
		$isoverdue='1';
	}
    $userid = $_REQUEST['i'];  

	$a=0;
    $a=$GLOBALS['db']->query("update ecs_users_period set isflag=".$isflag.",flagmsg='".$flagmsg."',isoverdue=".$isoverdue.",over_time='".$over_time."' WHERE  user_id=".$userid);
	if($a>0)
	{ 
		echo "ok";
    } 
	else{echo "error";}
	
}
?>