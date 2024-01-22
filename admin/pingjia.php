<?php

/**
 * ECSHOP 管理中心评价管理
 
 * $Author: man $
 * $Id: pingjia.php 2019-09-11 00:00:00 man $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

 /* 检查权限 */
 admin_priv('users_manage');

/* 模板赋值 */
$smarty->assign('ur_here', '评价管理'); // 当前导航
$smarty->assign('action_link', array('href' => 'pingjia.php', 'text' => '评价管理'));

if($_POST){
    $startymd = htmlspecialchars($_POST['startymd'], ENT_QUOTES);
    $endymd = htmlspecialchars($_POST['endymd'], ENT_QUOTES);
    $num = intval($_POST['num']) > 0 ? intval($_POST['num']) : 0;
    $mincomment_rank = intval($_POST['mincomment_rank']) > 0 ? intval($_POST['mincomment_rank']) : 0;
    $minserver = intval($_POST['minserver']) > 0 ? intval($_POST['minserver']) : 0;
    $minsend = intval($_POST['minsend']) > 0 ? intval($_POST['minsend']) : 0;
    $minshipping = intval($_POST['minshipping']) > 0 ? intval($_POST['minshipping']) : 0;
    if($num <= 0) 
    {
         sys_msg('赠送积分不能小于等于0!',0,$link);
    }
    if($mincomment_rank <= 0)
    {
        sys_msg('描述评分不能小于等于0',0,$link); 
    }
    if($minserver <= 0)
    {
        sys_msg('服务评分不能小于等于0',0,$link); 
    }
    if($minsend <= 0)
    {
        sys_msg('发货评分不能小于等于0',0,$link); 
    }
    if($minshipping <= 0)
    {
        sys_msg('物流评分不能小于等于0',0,$link); 
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
    $count = $GLOBALS['db']->getOne($sql);
    if($count > 0)
    {
        $ret = $db->query("UPDATE " . $GLOBALS['ecs']->table('pingjiaconf') . " SET 
        `startymd`='$startymd',
        `endymd`='$endymd',
        `num`='$num',
        `mincomment_rank`='$mincomment_rank',
        `minserver`='$minserver',
        `minsend`='$minsend',
        `minshipping`='$minshipping'
        WHERE `cid`=1" );
    }
    else
    {
         $ret = $db->query("INSERT INTO " . $GLOBALS['ecs']->table('pingjiaconf') . 
                "(`startymd`,`endymd`,`num`,`mincomment_rank`,`minserver`,`minsend`,`minshipping`) " . 
                "values('$startymd','$endymd','$num','$mincomment_rank','$minserver','$minsend','$minshipping')");
    }
    
    sys_msg ( '操作成功', 0, $link );
}else{
    $pingjia = $db->getRow("select * from " . $GLOBALS['ecs']->table('pingjiaconf') . " ORDER BY cid DESC LIMIT 1");
    /* 显示模板 */
    assign_query_info();
    $smarty->assign('pingjia',$pingjia);
    $smarty->display('pingjia.htm');
}