<?php
header('content-type:text/html;charset=utf-8');

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');


$act = $_REQUEST['ac'];
if($act == 'seek')
{
    //查看数据
    $js = seek();
    exit($js);
}else if($act == 'bang'){
    $js = bang();
    exit($js);
}else if($act == 'int'){
    $js = int();
    exit($js);
}else{
    $str = '您正在'.$act.'调接口';
    $array = array('error'=>$str);
    $js = json_encode($array);
    exit($js);
}

/*
 * 查看数据
 *  type,user_id
 * */
function seek(){

    $type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
    $user_id = empty($_REQUEST['user_id']) ? '' : $_REQUEST['user_id'];
    $info_user_id = $type . '_' .$user_id;

    $sql1 = "SELECT user_name,password,aite_id FROM ecs_users WHERE aite_id ='$info_user_id'";

    $res = $GLOBALS['db']->getAll($sql1);

    if($res)
    {
        //有账号信息返回信息
        return json_encode($res);
    }else{
        //没有账号信息
        $res = array('start'=>'0','mas'=>'账号没有注册');
        return json_encode($res);
    }
}
//绑定老账号
/*
 * type,user_id,user_name,password
 *
 * */
function bang(){

    $type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
    $user_id = empty($_REQUEST['user_id']) ? '' : $_REQUEST['user_id'];
    $info_user_id = $type . '_' .$user_id;
    $user_name =  empty($_REQUEST['user_name']) ? '' : $_REQUEST['user_name'];
    $password =  empty($_REQUEST['password']) ? '' : $_REQUEST['password'];
    $sql = "select ec_salt from ecs_users where user_name='$user_name'";
    $result1 = $GLOBALS['db']->getAll($sql);
    $password = md5(md5($password).$result1['ec_salt']);
    $sql1 = "UPDATE ecs_users SET `aite_id`='$info_user_id' WHERE user_name = '$user_name' and password='$password'";
    $sth1 = $GLOBALS['db']->query($sql1);
    if($sth1)
    {
        $result1 = array('start'=>'1','mas'=>'绑定成功');
        return json_encode($result1);
    }else{
        //没有账号信息
        $result1 = array('start'=>'0','mas'=>'账号密码不对');
        return json_encode($result1);
    }
}

//注册新的账号
/*
 *  type
 *
 * */
function int()
{
    $type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
    $user_id = empty($_REQUEST['user_id']) ? '' : $_REQUEST['user_id'];
    $info_user_id = $type . '_' .$user_id;

    $user_name =  empty($_REQUEST['user_name']) ? '' : $_REQUEST['user_name'];
    $password =  empty($_REQUEST['password']) ? '' : $_REQUEST['password'];

    $ec_salt = '';
    for($i=0;$i<4;$i++)
    {
        $ec_salt .= rand(0,9);
    }
    //密码加密
    $password = md5(md5($password).$ec_salt);

    $sql = "INSERT INTO ecs_users (`aite_id`,`user_name`,`password`,`ec_salt`) VALUES('$info_user_id','$user_name','$password','$ec_salt')";
    echo $sql;die();
    $res = $GLOBALS['db']->query($sql);
    if($res)
    {
        $array = array('start'=>'1','mas'=>'注册成功');
        return json_encode($array);
    }else{
        $array = array('start'=>'0','mas'=>'注册失败');
        return json_encode($array);
    }
}
?>