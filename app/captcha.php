<?php

/**
 * ECSHOP 生成验证码
 
 * $Author: liubo $
 * $Id: captcha.php 17217 2011-01-19 06:29:08Z liubo $
*/

if(!defined('IN_CTRL'))
{
	die('Hacking alert');
}

require(ROOT_PATH . 'includes/cls_captcha.php');

$img = new captcha(ROOT_PATH . 'data/captcha/', $_CFG['captcha_width'], $_CFG['captcha_height']);
@ob_end_clean(); //清除之前出现的多余输入
if (isset($_REQUEST['is_login']))
{
    $img->session_word = 'captcha_login';
}
$img->generate_image();
?>