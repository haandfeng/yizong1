<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

//判断是否有邀请码且没有处于登录状态
if(@$_SESSION['register']['invite'] && $_SESSION['user_id'] == '')
{
    header("Location:register.php");
}
/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

if($_GET['act'] == 'bang')
{
    $smarty->display('bang.dwt');
    exit;
}
if($_GET['act'] == 'check')
{
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    //判定老账号
    $sql = 'select ec_salt,user_id from '.$ecs->table('users').' where user_name = \''.$_POST['name'].'\'';
    $res = $db->getAll($sql);

    $password = md5(md5($_POST['password']).$res[0]['ec_salt']);
    $sql = 'select user_id from '.$ecs->table('users').' where user_name = \''.$_POST['name'].'\' and password =\''.$password.'\'';
    $res = $db->getAll($sql);

    if($res[0]['user_id'] > 0)
    {
        header('Location: weixin_login.php?type=1&user_id='.$res[0]['user_id']);
    }else{
        header('Location: weixin_bang.php?act=bang');
    }

}

$smarty->display('weixin_bang.dwt');
?>