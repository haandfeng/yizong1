<?php

 
 define('IN_ECS', true); 
require('../includes/init.php'); 
$data =  $_POST['openReq'];
$data=str_replace('\"','"',$data );
if ($data!="")
{
	$arr = json_decode($data,true);
	
		
	$sql = "INSERT INTO zs_haiguan_request(sessionID, orderNo, serviceTime, addtime, issearch) VALUES ('".$arr['sessionID']."', '".$arr['orderNo']."', '".$arr['serviceTime']."', now(),0)";
	$vres=$db->query($sql); 
	if($vres=='1')
	{
		print ('{"code":"10000","message":"","serviceTime":'.time().'}');
	}  
}
  
?>