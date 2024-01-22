<?php

/**
 * ECSHOP 降价通知
 
 * $Author: liubo $
 * $Id: message.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$goods_id = $_POST['goods_id'] ? intval($_POST['goods_id']) : 0;
$price = $_POST['price'] ? $_POST['price'] : 0;
$mobile = $_POST['mobile'] ? $_POST['mobile'] : '';
$email = $_POST['email'] ? $_POST['email'] : '';
$nowtime= gmtime();

$sql = "insert into ".$ecs->table('pricecut')." (goods_id, price, mobile, email, add_time) values('$goods_id', '$price', '$mobile', '$email', '$nowtime')";
$db->query($sql);

$goods_url =  build_uri('goods', array('gid'=>$goods_id), '');
show_message('恭喜，您的降价通知已经成功提交！', '返回上一页', $goods_url);

?>
