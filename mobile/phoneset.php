<?php

/**
 * ECSHOP 注册

 * $Author: liubo $
 * $Id: register.php 17217 2015-08-07 06:29:08Z niqingyang $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');

/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

//ini_set('display_errors',1);            //错误信息
//ini_set('display_startup_errors',1);    //php启动错误信息
//error_reporting(-1);
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$back_act = '';

//判断用户是否登录
/*if($_SESSION['user_id']){
    header("Location:user.php");
}*/

if($_REQUEST['goods_id'] && $_SESSION['user_id']){

    if($_REQUEST['goods_id']){
        $_SESSION['register']['goods_id'] = $_REQUEST['goods_id'];
    }
    if($_REQUEST['id'] ){
        list($register_uid, $register_invite) = explode ('|',$_REQUEST['id']);
        $_SESSION['register']['parent_id'] = $register_uid;
        $_SESSION['register']['invite'] = $register_invite;
    }

    header("Location:goods.php?id=".$_REQUEST['goods_id']);
}

/* 如果是显示页面，对页面进行相应赋值 */
if(true)
{
    assign_template();
    $position = assign_ur_here(0, $_LANG['user_center']);
    $smarty->assign('page_title', $position['title']); // 页面标题
    $smarty->assign('ur_here', $position['ur_here']);
    $sql = "SELECT value FROM " . $ecs->table('shop_config') . " WHERE id = 419";
    $row = $db->getRow($sql);
    $car_off = $row['value'];
    $smarty->assign('car_off', $car_off);
    /* 是否显示积分兑换 */
    if(! empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
    {
        $smarty->assign('show_transform_points', 1);
    }
    $smarty->assign('helps', get_shop_help()); // 网店帮助
    $smarty->assign('data_dir', DATA_DIR); // 数据目录
    $smarty->assign('action', $action);
    $smarty->assign('lang', $_LANG);
}

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
    $function_name = "action_default";
}

call_user_func($function_name);

/* 路由 */

/* 发送注册邮箱验证码到邮箱 */
function action_send_email_code ()
{
    // 获取全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];

    /* 载入语言文件 */
    require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

    require_once (ROOT_PATH . 'includes/lib_validate_record.php');

    $email = trim($_REQUEST['email']);

    /* 验证码检查 */
    if((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
    {
        if(empty($_POST['captcha']))
        {
            exit($_LANG['invalid_captcha']);
            return;
        }

        /* 检查验证码 */
        include_once ('includes/cls_captcha.php');

        $captcha = new captcha();

        if(! $captcha->check_word(trim($_POST['captcha'])))
        {
            exit($_LANG['invalid_captcha']);
            return;
        }
    }

    if(empty($email))
    {
        exit("邮箱不能为空");
        return;
    }
    else if(! is_email($email))
    {
        exit("邮箱格式不正确");
        return;
    }
    else if(check_validate_record_exist($email))
    {

        $record = get_validate_record($email);

        /**
         * 检查是过了限制发送邮件的时间
         */
        if(time() - $record['last_send_time'] < 60)
        {
            echo ("每60秒内只能发送一次注册邮箱验证码，请稍候重试");
            return;
        }
    }

    require_once (ROOT_PATH . 'includes/lib_passport.php');

    /* 设置验证邮件模板所需要的内容信息 */
    $template = get_mail_template('reg_email_code');
    // 生成邮箱验证码
    $email_code = rand_number(6);

    $GLOBALS['smarty']->assign('email_code', $email_code);
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('send_date', date($GLOBALS['_CFG']['date_format']));

    $content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

    /* 发送激活验证邮件 */
    $result = send_mail($email, $email, $template['template_subject'], $content, $template['is_html']);
    if($result)
    {
        // 保存验证码到Session中
        $_SESSION[VT_EMAIL_REGISTER] = $email;
        // 保存验证记录
        save_validate_record($email, $email_code, VT_EMAIL_REGISTER, time(), time() + 30 * 60);

        echo 'ok';
    }
    else
    {
        echo '注册邮箱验证码发送失败';
    }
}

/* 发送注册邮箱验证码到邮箱 */
function action_send_mobile_code ()
{

    // 获取全局变量
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];

    /* 载入语言文件 */
    require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

    require_once (ROOT_PATH . 'includes/lib_validate_record.php');

    $mobile_phone = trim($_REQUEST['mobile_phone']);

    /* 验证码检查 */
    if((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
    {
        if(empty($_POST['captcha']))
        {
            exit($_LANG['invalid_captcha']);
            return;
        }

        /* 检查验证码 */
        include_once ('includes/cls_captcha.php');

        $captcha = new captcha();

        if(! $captcha->check_word(trim($_POST['captcha'])))
        {
            exit($_LANG['invalid_captcha']);
            return;
        }
    }

    if(empty($mobile_phone))
    {
        exit("手机号不能为空");
        return;
    }
    else if(! is_mobile_phone($mobile_phone))
    {
        exit("手机号格式不正确");
        return;
    }
    else if(check_validate_record_exist($mobile_phone))
    {
        // 获取数据库中的验证记录
        $record = get_validate_record($mobile_phone);

        /**
         * 检查是过了限制发送短信的时间
         */
        $last_send_time = $record['last_send_time'];
        $expired_time   = $record['expired_time'];
        $create_time    = $record['create_time'];
        $count          = $record['count'];
        $count_ip       = $record['count_ip'];

        // 每天每个手机号最多发送的验证码数量
        $max_sms_count    = 3;
        $max_sms_count_ip = 5;

        // 发送最多验证码数量的限制时间，默认为24小时
        $max_sms_count_time = 60 * 60 * 24;


        if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count) {
            echo ("同一手机号24小时内只能发送3次验证码！");
            return;
        }

        if(time() - $create_time < $max_sms_count_time && $record['count_ip'] > $max_sms_count_ip) {
            echo ("同一个ip24小时内只能发送5次验证码！");
            return;
        }


        if((time() - $last_send_time) < 60)
        {
            echo ("每60秒内只能发送一次短信验证码，请稍候重试");
            return;
        }
        else if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count)
        {
            echo ("您发送验证码太过于频繁，请稍后重试！");
            return;
        }
        else
        {
            $count ++;
            $count_ip ++;
        }
    }

    require_once (ROOT_PATH . 'includes/lib_passport.php');

    // 设置为空
    $_SESSION['mobile_register'] = array();

    require_once (ROOT_PATH . 'sms/sms.php');

    // 生成6位短信验证码
    $mobile_code = rand_number(6);
    // 短信内容
    $content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['sms_sign'], $mobile_code, $GLOBALS['_CFG']['sms_sign']);

    /* 发送激活验证邮件 */
    // $result = true;
    $result = sendSMS($mobile_phone, $content);
    if($result)
    {

        if(! isset($count))
        {
            $ext_info = array(
                "count" => 1,
                "count_ip" => 1
            );
        }
        else
        {
            $ext_info = array(
                "count" => $count,
                "count_ip" => $count_ip
            );
        }

        // 保存手机号码到SESSION中
        $_SESSION[VT_MOBILE_REGISTER] = $mobile_phone;
        // 保存验证信息
        save_validate_record($mobile_phone, $mobile_code, VT_MOBILE_REGISTER, time(), time() + 30 * 60, $ext_info);
        echo 'ok';
    }
    else
    {
        echo '短信验证码发送失败';
    }
}

/**
 * 验证邮箱是否可以注册，true-已存在，不能注册 false-不存在可以注册
 */
function action_check_email_exist ()
{
    $_LANG = $GLOBALS['_LANG'];
    $_CFG = $GLOBALS['_CFG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    $email = empty($_POST['email']) ? '' : $_POST['email'];

    $user = $GLOBALS['user'];

    if($user->check_email($email))
    {
        echo 'true';
    }
    else
    {
        echo 'false';
    }
}

function action_check_mobile_exist ()
{
    $_LANG = $GLOBALS['_LANG'];
    $_CFG = $GLOBALS['_CFG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    $mobile = empty($_POST['mobile']) ? '' : $_POST['mobile'];

    $user = $GLOBALS['user'];

    if($user->check_mobile_phone($mobile))
    {
        echo 'true';
    }
    else
    {
        echo 'false';
    }
}
function action_check_tpyzm ()
{
    $_LANG = $GLOBALS['_LANG'];
    $_CFG = $GLOBALS['_CFG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    $yzm = empty($_POST['yzm']) ? '' : $_POST['yzm'];
    
    /* 检查验证码 */
    include_once ('includes/cls_captcha.php');

    $captcha = new captcha();

    if(! $captcha->check_word(trim($yzm)))
    {
        echo 'false';
    }
    else{
      echo 'true';
    }
}

/**
 * 显示会员注册界面
 */
function action_default ()
{
    // 获取全局变量
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];

    //当页面不存在是
    if(!$_SESSION['register']['invite']){
        if($_REQUEST['goods_id']){
            $_SESSION['register']['goods_id'] = $_REQUEST['goods_id'];
        }
        if($_REQUEST['id'] ){
            list($register_uid, $register_invite) = explode ('|',$_REQUEST['id']);
            $_SESSION['register']['parent_id'] = $register_uid;
            $_SESSION['register']['invite'] = $register_invite;
        }
    }

    if(isset($_SESSION['register']['invite'])){
        $smarty->assign('register_invite',$_SESSION['register']['invite']);
    }
    if(isset($_SESSION['register']['goods_id'])){
        $smarty->assign('goods_id',$_SESSION['register']['goods_id']);
    }

 

    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    /* 验证码相关设置 */
    if((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand', mt_rand());
    }

    /* 密码提示问题 */
    $smarty->assign('passwd_questions', $_LANG['passwd_questions']);
    /* 代码增加_start By www.yshop100.com */
    $smarty->assign('sms_register', $_CFG['sms_register']);
    /* 代码增加_end By www.yshop100.com */
    /* 代码增加_star By www.yshop100.com */
    $smarty->assign('sms_register', $_CFG['sms_register']);
    /* 代码增加_end By www.yshop100.com */
    /* 增加是否关闭注册 */
    $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);
    // 登陆注册-注册类型
    $register_type = empty($_REQUEST['register_type']) ? 'mobile' : $_REQUEST['register_type'];
    if($register_type != 'email' && $register_type != 'mobile')
    {
        $register_type = 'mobile';
    }
    $smarty->assign('register_type', $register_type);
    $smarty->assign('back_act', isset($_GET['_u']) ? $_GET['_u'] : '');
    $smarty->display('user_phoneset.dwt');
}

/**
 * 注册会员的处理
 */
function action_phone ()
{
    // 获取全局变量
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
 
        include_once (ROOT_PATH . 'includes/lib_passport.php'); 
        $yzm = isset($_POST['captcha']) ? $_POST['captcha'] : '';
        $mobile_phone = ! empty($_POST['mobile_phone']) ? trim($_POST['mobile_phone']) : '';
        $mobile_code = ! empty($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';
        
        $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

        require_once (ROOT_PATH . 'includes/lib_validate_record.php');
        $record = get_validate_record($mobile_phone);
            
        
         

            $session_mobile_phone = $_SESSION[VT_MOBILE_REGISTER];
            /* 检查验证码 */ 
            include_once ('includes/cls_captcha.php'); 
            $captcha = new captcha();
            if(! $captcha->check_word(trim($yzm)))
            {
                show_message($_LANG['invalid_captcha'], "重新激活", 'phoneset.php', 'error'); 
            }
            /* 手机验证码检查 */
            else if(empty($mobile_code))
            {
                show_message($_LANG['msg_mobile_phone_blank'], "重新激活", 'phoneset.php', 'error'); 
            }
            // 检查发送短信验证码的手机号码和提交的手机号码是否匹配
            else if($session_mobile_phone != $mobile_phone)
            {
                show_message($_LANG['mobile_phone_changed'], "重新激活", 'phoneset.php', 'error'); 
            }
            // 检查验证码是否正确
            else if($record['record_code'] != $mobile_code)
            {
                show_message($_LANG['invalid_mobile_phone_code'], "重新激活", 'phoneset.php', 'error'); 
            }
            // 检查过期时间
            else if($record['expired_time'] < time())
            {
                show_message($_LANG['invalid_mobile_phone_code'], "重新激活", 'phoneset.php', 'error'); 
            }
            else{ 
                $sql = 'UPDATE ' . $ecs->table('users') . " SET `mobile_phone`='$mobile_phone',validated=1  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
                $db->query($sql);
                
                show_message('绑定成功', $link_lang,$back_act, 'info');
                exit();
 
            }
         
    /* 代码增加2018-11-01 zx */
}

/**
 * 随机生成指定长度的数字
 *
 * @param number $length
 * @return number
 */
function rand_number ($length = 6)
{
    if($length < 1)
    {
        $length = 6;
    }

    $min = 1;
    for($i = 0; $i < $length - 1; $i ++)
    {
        $min = $min * 10;
    }
    $max = $min * 10 - 1;

    return rand($min, $max);
}

/**
 * 根据手机号生成用户名
 *
 * @param number $length
 * @return number
 */
function generate_username_by_mobile ($mobile)
{

    $username = 'u'.substr($mobile, 0, 3);

    $charts = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $max = strlen($charts);

    for($i = 0; $i < 4; $i ++)
    {
        $username .= $charts[mt_rand(0, $max)];
    }

    $username .= substr($mobile, -4);

    $sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where user_name = '$username'";
    $count = $GLOBALS['db']->getOne($sql);
    if($count > 0)
    {
        return generate_username_by_mobile();
    }

    return $username;
}

/**
 * 根据邮箱地址生成用户名
 *
 * @param number $length
 * @return number
 */
function generate_username ()
{

    $username = 'u'.rand_number(3);

    $charts = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $max = strlen($charts);

    for($i = 0; $i < 4; $i ++)
    {
        $username .= $charts[mt_rand(0, $max)];
    }

    $username .= rand_number(4);

    $sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where user_name = '$username'";
    $count = $GLOBALS['db']->getOne($sql);
    if($count > 0)
    {
        return generate_username();
    }

    return $username;
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

function invite_code_self(){
    $code_self = random(6);
    $res = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . " WHERE is_fenxiao = 1 AND invite_code = '$code_self'");
    if($res){
        return invite_code_self();
    }
    return $code_self;
}

/**
 * 生成邀请 公共方法二维码
 *
 * @param int $uid 会员ID
 * @param int $invite 用户填写的邀请码
 * @return string
 */
function created_invite_qrcode($invite)
{
    $invite_code_self = invite_code_self(); //每一个新注册的会员都会生成一串6位的邀请码
    $uid = $_SESSION['user_id'];

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
    //$data = 'https://www.baidu.com/';
    //生成二维码图片
    QRcode::png($data,$filename, $errorCorrectionLevel, $matrixPointSize, 2);

    //更新 注册会员的 邀请码 及 二维码 (填写邀请码之后 查询 邀请码所对应的 会员id)
    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `invite_code` = "' . $invite_code_self .'",`invite_qrcode` = "' . $file .'",`invite_parent` = "' . $invite . '" WHERE user_id = ' . $_SESSION['user_id'];
    $GLOBALS['db']->query($sql);
    //不需要申请 就可以成为 分销商
    $parent_id = '';
    $parent_id = $GLOBALS['db']->getOne("SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE   invite_code = '$invite' AND invite_code <> ''");
    if($GLOBALS['_CFG']['is_apply_distrib'] == 0) {
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `is_fenxiao` = 1,`status` = 1,`parent_id` = "' . $parent_id . '" WHERE user_id = ' . $uid;
    }else{
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `parent_id` = "' . $parent_id . '" WHERE user_id = ' . $uid;
    }
    $GLOBALS['db']->query($sql);
}

?>