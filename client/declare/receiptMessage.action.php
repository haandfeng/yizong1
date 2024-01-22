<?php
/**
 * ECSHOP 接收海关回执
 * $Author: man $
 * $Id: receiptMessage.action.php 17063 2020-04-29 10:43 man $
*/

define('IN_ECS', true);

require('../../includes/init.php');

$clientid = trim($_REQUEST['clientid']);
$key = trim($_REQUEST['key']);
$messageType = trim($_REQUEST['messageType']);
$messageText = trim($_REQUEST['messageText']);
$now = date('Y-m-d h:i:s', time());

$sql = "SELECT keyname, keyvalue FROM zs_config ORDER BY id DESC";
$res = $GLOBALS['db']->query($sql);
while ($row = $db->fetchRow($res))
{
    if("gz_clientid"==$row['keyname'])
    $c_clientid = $row['keyvalue'];
    else if("gz_key"==$row['keyname'])
    $c_key = $row['keyvalue'];
}

if(isset($clientid) && isset($key) && isset($messageType) && isset($messageText)){
	// if($clientid != $c_clientid || $key != $c_key){
	// 	echo "error";
	// 	exit;
	// }

	$sql = "INSERT INTO gz_purchaseorder_return (`clientid`, `messageType`, `messageText`, return_time) VALUES ('".mysql_real_escape_string($clientid)."', '".mysql_real_escape_string($messageType)."', '".mysql_real_escape_string($messageText)."', '".$now."')";
	$GLOBALS['db']->query($sql);

	echo "success";
	exit;

}else{
	echo "error";
	exit;
}