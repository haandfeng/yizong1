<?php
/**
  *修改密码
 */
define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');
//判断是否有邀请码且没有处于登录状态
if(@$_SESSION['register']['invite'] && $_SESSION['user_id'] == '')
{
    header("Location:register.php");
}
/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

$id = $_SESSION['user_id'];
$db = $GLOBALS['db'];
$smarty = $GLOBALS['smarty'];

if($_REQUEST['act'] == '')
{
    //判断用户是否有绑定手机号或者邮箱
   /* $sql = 'select mobile_phone,qq from '.$GLOBALS['ecs']->table('users').' where user_id = '.$id;
    $row = $db->getRow($sql);
    if($row['mobile_phone'] || $row['qq']){
        $smarty->assign('row', $row);
        $smarty->display('edit_password.dwt');
        exit;
    }
   */
    $smarty->display('dang_password.dwt');
    exit;
}


if($_REQUEST['act'] == 'edit')
{
    $sql = 'select ec_salt from '.$GLOBALS['ecs']->table('users').' where user_id = '.$id;
    $row = $db->getRow($sql);
    $ec_salt = $row['ec_salt'];

    $odl = $_POST['password'];
    $odl = md5(md5($odl).$ec_salt);
    $new = $_POST['password1'];
    $new = md5(md5($new).$ec_salt);

    $sql = 'select user_id from '.$GLOBALS['ecs']->table('users').' where user_id ='.$id.' and password = \''.$odl.'\'';
    $row = $db->getRow($sql);
    if($row['user_id'] <= 0)
    {
        show_message('你的原密码错误');
        exit;
    }
    $sql = 'update '.$GLOBALS['ecs']->table('users').'set password = \''.$new.'\' where user_id='.$id;
    $row = $db->getRow($sql);
    header('Location:user.php');
}


















