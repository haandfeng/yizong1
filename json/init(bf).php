<?php

/**
 * ECSHOP 前台公用文件
 
 * $Author: liubo $
 * $Id: init.php 17217 2011-01-19 06:29:08Z liubo $
*/

require('../data/config.php');

/* 初始化数据库类 */
require('../includes/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db->set_disable_cache_tables(array($ecs->table('sessions'), $ecs->table('sessions_data'), $ecs->table('cart')));
$db_host = $db_user = $db_pass = $db_name = NULL;


?>