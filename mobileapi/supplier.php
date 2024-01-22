<?php

/**
 * 店铺的控制器文件
 
 * $Author: liubo $
 * $Id: index.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);


require(dirname(__FILE__) . '/includes/init_supplier.php');


if($_GET['suppId']<=0){
	
	ecs_header("Location: index.php");
    exit;
}
$sql="SELECT s.*,sr.rank_name FROM ". $ecs->table("supplier") . " as s left join ". $ecs->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id
 WHERE s.supplier_id=".$_GET['suppId']." AND s.status=1";
$suppinfo=$db->getRow($sql);
$smarty->assign('suppid', $suppinfo['supplier_id']);
if(empty($suppinfo['supplier_id']) || $_GET['suppId'] != $suppinfo['supplier_id'])
{
	 ecs_header("Location: index.php");
     exit;
}

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
$typeinfo = array('index','category','search','article','other','about','activity');
$go = (isset($_GET['go']) && !empty($_GET['go'])) ? $_GET['go'] : 'index';
if(!in_array($go,$typeinfo)){
	ecs_header("Location: index.php");
    exit;
}else{
	require(dirname(__FILE__) . '/supplier_'.$go.'.php');
}

?>