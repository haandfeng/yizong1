<?php

/**
 * ECSHOP 管理中心签到管理
 
 * $Author: man $
 * $Id: qiandao.php 2019-09-11 00:00:00 man $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

 /* 检查权限 */
 admin_priv('users_manage');

/* 模板赋值 */
$smarty->assign('ur_here', '签到管理'); // 当前导航
$smarty->assign('action_link', array('href' => 'qiandao.php', 'text' => '签到管理'));

if($_POST){
    $startymd = htmlspecialchars($_POST['startymd'], ENT_QUOTES);
    $endymd = htmlspecialchars($_POST['endymd'], ENT_QUOTES);
    $num = intval($_POST['num']) > 0 ? intval($_POST['num']) : 0;
    $bignum = intval($_POST['bignum']) > 0 ? intval($_POST['bignum']) : 0;
    $addnum = intval($_POST['addnum']) > 0 ? intval($_POST['addnum']) : 0;
    if($num <= 0) 
    {
         sys_msg('赠送积分不能小于等于0!',0,$link);
    }
    if($addnum <= 0)
    {
        sys_msg('累加积分不能小于等于0',0,$link); 
    }
    if($bignum <= 0)
    {
        sys_msg('最大积分不能小于等于0',0,$link); 
    }
    if($startymd == '' || $endymd == '')
    {
        sys_msg('开始时间或结束时间不能为空！',0,$link); 
    }
    else
    {
        if(local_strtotime($startymd) > local_strtotime($endymd))
        {
            sys_msg('开始时间不能大于结束时间！',0,$link); 
        } 
    }
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('signconf') . " WHERE cid = 1";
    $count = $GLOBALS['db']->getOne($sql);
    if($count > 0)
    {
        $ret = $db->query("UPDATE " . $GLOBALS['ecs']->table('signconf') . " SET 
        `startymd`='$startymd',
        `endymd`='$endymd',
        `num`='$num',
        `bignum`='$bignum',
        `addnum`='$addnum'
        WHERE `cid`=1" );
    }
    else
    {
         $ret = $db->query("INSERT INTO " . $GLOBALS['ecs']->table('signconf') . 
                "(`startymd`,`endymd`,`num`,`bignum`,`addnum`) " . 
                "values('$startymd','$endymd','$num','$bignum','$addnum')");
    }
    
    sys_msg ( '操作成功', 0, $link );
}else{
    $sign = $db->getRow("select * from " . $GLOBALS['ecs']->table('signconf') . " ORDER BY cid DESC LIMIT 1");
    /* 显示模板 */
    assign_query_info();
    $smarty->assign('sign',$sign);
    $smarty->display('qiandao.htm');
}