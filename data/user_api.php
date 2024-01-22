<?php
header('content-type:text/html;charset=utf-8');
$config = array(
    'db'    => array(
        'host'      => 'localhost',
        'user'      => 'yizhong',
        'pass'      => '#yizhong#20170704',
        'db'        => 'dbyizong',
        'dns'       => 'mysql:dbname=dbyizong;host=localhost;charset=utf8'
    )
);
try {
    $db = new PDO($config['db']['dns'], $config['db']['user'], $config['db']['pass']);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

$act = $_REQUEST['ac'];
if($act == 'seek')
{
    //查看数据
    $js = seek($db);
    exit($js);
}else if($act == 'bang'){
    $js = bang($db);
    exit($js);
}else if($act == 'int'){
    $js = int($db);
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
function seek($db){
    $type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
    $user_id = empty($_REQUEST['user_id']) ? '' : $_REQUEST['user_id'];
    $info_user_id = $type . '_' .$user_id;
    $sql1 = "SELECT user_name,password,aite_id FROM ecs_users WHERE aite_id = :condition1";
    $sql_data1 = Array(
        ":condition1" => $info_user_id
    );
    $sth1 = $db->prepare($sql1);
    $sth1->execute($sql_data1);
// 获取一条
    $result1 = $sth1->fetch(PDO::FETCH_ASSOC);

    if($result1)
    {
        //有账号信息返回信息
        return json_encode($result1);
    }else{
        //没有账号信息
        $result1 = array('start'=>'0','mas'=>'账号没有注册');
        return json_encode($result1);
    }
}
//绑定老账号
/*
 * type,user_id,user_name,password
 *
 * */
function bang($db){
    $type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
    $user_id = empty($_REQUEST['user_id']) ? '' : $_REQUEST['user_id'];
    $info_user_id = $type . '_' .$user_id;

    $user_name =  empty($_REQUEST['user_name']) ? '' : $_REQUEST['user_name'];
    $password =  empty($_REQUEST['password']) ? '' : $_REQUEST['password'];
    $sql = 'select ec_salt from ecs_users where user_name=:user_name';
    $data = Array(
        ':user_name'=>$user_name
    );
    $sth1 = $db->prepare($sql);
    $sth1->execute($data);
    $result1 = $sth1->fetch(PDO::FETCH_ASSOC);

    $password = md5(md5($password).$result1['ec_salt']);

    $sql1 = "UPDATE ecs_users SET `aite_id`=:aite_id WHERE user_name = :user_name and password=:password";
    $sql_data1 = Array(
        ":aite_id" => $info_user_id,
        ":user_name" => $user_name,
        ":password" => $password
    );
    $sth1 = $db->prepare($sql1);
    $sth1->execute($sql_data1);

    if($sth1->rowCount() >0)
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
function int($db)
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

    $sql = "INSERT INTO ecs_users (`aite_id`,`user_name`,`password`,`ec_salt`) VALUES(:aite_id,:user_name,:password,:ec_salt)";
    $data = Array(
        'aite_id'=>$info_user_id,
        'user_name'=>$user_name,
        'password'=>$password,
        'ec_salt'=>$ec_salt
    );
    $sth3 = $db->prepare($sql);
    $result3 = $sth3->execute($data);
    if($result3)
    {
        $array = array('start'=>'1','mas'=>'注册成功');
        return json_encode($array);
    }else{
        $array = array('start'=>'0','mas'=>'注册失败');
        return json_encode($array);
    }
}
?>