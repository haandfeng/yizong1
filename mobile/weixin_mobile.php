<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');

$acturl = $_REQUEST['_u'];

$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
$weixin = new core_lib_wechat($weixinconfig);
if($_GET['code']){
    $json = $weixin->getOauthAccessToken();
    if($json['openid']){
        $rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$json['openid']}'");
        if($rows)
        {
            if($rows['ecuid'] > 0)
            {
                $username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
                $GLOBALS['user']->set_session($username);
                $GLOBALS['user']->set_cookie($username,1);
                update_user_info();  //更新用户信息
                recalculate_price(); //重新计算购物车中的商品价格
                //分销中心取ID
                $uid = $GLOBALS['db']->getOne("SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
                $acturl=str_replace("{u}",$uid,$acturl);

                //判断绑手机
                $mobile_phone = $GLOBALS['db']->getOne("SELECT mobile_phone FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'"); 
				$mobile_phone = isset($mobile_phone) ? $mobile_phone: '';
                if(strlen($mobile_phone)<1){
                    $acturl=$GLOBALS['ecs']->url()."phoneset.php?_u=".$acturl;
					file_put_contents("log.txt", print_r('7-'.$acturl.'\r\n',true), FILE_APPEND);
                }
                header("Location:$acturl");exit;
            }
        }
        else
        {
            $createtime = gmtime();
            $createymd = date('Y-m-d',gmtime());
            $GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`) 
				value (0,'" . $json['openid'] . "','{$createtime}','{$createymd}',0)");
        }
        $info = $weixin->getOauthUserinfo($json['access_token'],$json['openid']);
        $info_user_id = 'weixin' . '_' . $info['openid'];
        if($info['nickname'])
        {
            $info['name'] = str_replace("'", "", $info['nickname']);
            if($GLOBALS['user']->check_user($info['name'])) // 重名处理
            {
                $info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
            }
        }
        else
        {
            $info['name'] = 'weixin_' . rand(10000, 99999);
        }

        $sql = 'SELECT user_name,password,aite_id FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\' OR aite_id=\'' . $info['openid'] . '\'';
        $user_info = $GLOBALS['db']->getRow($sql);
        if($user_info)
        {
            $info['name'] = $user_info['user_name'];
            if($user_info['aite_id'] == $info['openid'])
            {
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$user_info[aite_id]'";
                $GLOBALS['db']->query($sql);
                $tag = 2;
            }
        }
        else
        {
            $user_pass = $GLOBALS['user']->compile_password(array(
                'password' => $info['openid']
            ));
            $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time , is_validated,froms,headimg) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '1','mobile','$info[headimgurl]')";
            $GLOBALS['db']->query($sql);
            $tag = 1; //第一次注册标记
        }
        $GLOBALS['user']->set_session($info['name']);
        $GLOBALS['user']->set_cookie($info['name']);
        update_user_info();
        recalculate_price();
        //修改新注册的用户成为普通分销商
        //2018-04-24 余某人更新 start
        /**改动前：
        $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET is_fenxiao = 2 WHERE user_id = '" . $_SESSION['user_id'] . "'");
         */
//        if($GLOBALS['_CFG']['is_apply_distrib'] == 1){
//            $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET is_fenxiao = 1 status WHERE user_id = '" . $_SESSION['user_id'] . "'");
//        }
        //2018-04-24 余某人更新 end



        //微信和新生成会员绑定
        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_user') . " SET ecuid = '" . $_SESSION['user_id'] . "',nickname = '" . $info['nickname'] . "',`sex` = '" . $info['sex'] . "' WHERE fake_id = '" . $json['openid'] . "'");
        if($tag == 1) //第一次注册绑定上级分销商
        {
            $sql = "SELECT parent_id FROM " .
                $GLOBALS['ecs']->table('bind_record') .
                " WHERE wxid = '" . $json['openid'] . "'";
            $parent_id = $GLOBALS['db']->getOne($sql);
            created_invite_qrcode();
            if($parent_id)
            {
                //扫描分销商二维码，绑定上级分销商
                $GLOBALS['db']->query("UPDATE " .
                    $GLOBALS['ecs']->table('users') .
                    " SET parent_id = '$parent_id'" .
                    " WHERE user_id = '" . $_SESSION['user_id'] . "'");
                $GLOBALS['db']->query("DELETE FROM " .
                    $GLOBALS['ecs']->table('bind_record') .
                    " WHERE wxid = '" . $json['openid'] . "'");
            }
        }
    }

    $url = $GLOBALS['ecs']->url()."user.php";
    header("Location:$acturl");exit;
}

$url = $GLOBALS['ecs']->url()."weixin_mobile.php?_u=".$acturl;//$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']； //$GLOBALS['ecs']->url()."weixin_mobile.php";
$url = $weixin->getOauthRedirect($url,1,'snsapi_userinfo');
//echo $url;die();
header("Location:$url");exit;


/**
 * 生成邀请 公共方法二维码
 *
 * @param int $uid 会员ID
 * @param int $invite 用户填写的邀请码
 * @return string
 */
function created_invite_qrcode($invite)
{
    $invite_code_self = random(6); //每一个新注册的会员都会生成一串6位的邀请码
    $uid = $_SESSION['user_id'];
    /* 防止重复 */
    if($GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . " WHERE invite_code = '$invite_code_self'")){
        $invite_code_self = random(6); //每一个新注册的会员都会生成一串6位的邀请码
    }
    /* 生成邀请二维码 及 邀请码 */
    require(ROOT_PATH.'includes/phpqrcode.php');
    $errorCorrectionLevel = 'L';//容错级别
    $matrixPointSize = 6;//生成图片大小
    if(!file_exists(ROOT_PATH.'images/qrimage/'.date('Ym'))){
        mkdir(ROOT_PATH.'images/qrimage/'.'/'.date('Ym'), 0777);
    }
    $filename = ROOT_PATH.'images/qrimage/'.date('Ym').'/'.$uid.'.png';
    $file = date('Ym').'/'.$uid.'.png';
    $data = 'http://'.$_SERVER['HTTP_HOST'].'/mobile/register.php?id='.$uid.'|'.$invite_code_self;
    //生成二维码图片
    QRcode::png($data,$filename, $errorCorrectionLevel, $matrixPointSize, 2);

    //更新 注册会员的 邀请码 及 二维码 (填写邀请码之后 查询 邀请码所对应的 会员id)
    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `invite_code` = "' . $invite_code_self .'",`invite_qrcode` = "' . $file .'",`invite_parent` = "' . $invite . '" WHERE user_id = ' . $_SESSION['user_id'];
    $GLOBALS['db']->query($sql);
    //不需要申请 就可以成为 分销商
    $parent_id = '';
    $parent_id = $GLOBALS['db']->getOne("SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE is_fenxiao = 1 AND invite_code = '$invite' AND invite_code <> ''");
    if($GLOBALS['_CFG']['is_apply_distrib'] == 0) {
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `is_fenxiao` = 1,`status` = 1,`parent_id` = "' . $parent_id . '" WHERE user_id = ' . $uid;
    }else{
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `parent_id` = "' . $parent_id . '" WHERE user_id = ' . $uid;
    }
    $GLOBALS['db']->query($sql);
}

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