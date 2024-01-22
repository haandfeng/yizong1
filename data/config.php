<?php
// database host
$db_host   = "localhost";

// database name
$db_name   = "dbyizong";

// database username
$db_user   = "root";

// database password
$db_pass   = "116,root";

// table prefix
$prefix    = "ecs_";

$timezone    = "PRC";

$cookie_path    = "/";

$cookie_domain    = "";

$session = "1440";

define('EC_CHARSET','utf-8');

if(!defined('ADMIN_PATH'))
{
define('ADMIN_PATH','admin');
}

define('AUTH_KEY', 'this is a key');

define('OLD_AUTH_KEY', '');

define('API_TIME', '2021-12-31 11:38:51');

define('DEBUG_MODE', 0);

//ini_set('display_errors',1);            //错误信息
//ini_set('display_startup_errors',1);    //php启动错误信息
//error_reporting(-1);

?>
