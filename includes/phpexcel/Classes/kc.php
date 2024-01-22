<?php
define('IN_ECS', true);
require('../../init.php'); 
require_once 'PHPExcel.php'; 
require_once 'PHPExcel/IOFactory.php';  

$eid=date('YmdHis'); 
$eid=$eid.''.rand(1000,9999); 
$TB='`dbyizong`.`zs_tes`';
//ini_set('memory_limit','200M');                          
 $reader = PHPExcel_IOFactory::createReader('Excel5'); 
 $resource = $_REQUEST['f'];
 if (!file_exists($resource)) {
  exit("$resource is not exists.\n");
 }
 $PHPExcel = $reader->load($resource); 
 $sheet = $PHPExcel->getSheet(0);  
 $highestRow = $sheet->getHighestRow();  
 $highestColumn = $sheet->getHighestColumn();  
 //echo $highestRow.$highestColumn; 
 $arr = array(1=>'A',2=>'B',3=>'C',4=>'D',5=>'E',6=>'F',7=>'G',8=>'H',9=>'I',10=>'J',11=>'K',12=>'L');
  
 for ($row = 1; $row <= $highestRow; $row++) {
  for ($column = 1; $arr[$column] != 'L'; $column++) {
	$val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
	$list[$row][] = $val;
  }
 } 
foreach($list as $key =>$value){  
$sql = "INSERT INTO ".$TB." (excid,a,b,c,d,e,f,g,h,i,j,k,l,addtime) VALUES ('$eid','$value[0]', '$value[1]', '$value[2]', '$value[3]', '$value[4]', '$value[5]', '$value[6]', '$value[7]', '$value[8]', '$value[9]', '$value[10]', '$value[11]', now())";
 //echo $sql.'<br/>';   
 $GLOBALS['db']->query($sql);
 }
 
 
 $sql = "select * from ".$TB." where excid='$eid' ";
 $arr = $GLOBALS['db']->getAll($sql);
foreach ($arr as $val) {
	$id=$val['id'];
	$flag=false;
	if(strlen($val['B'])>0)
	{
		$fir=substr($val['B'],0,1);
		if($fir=='H' OR $fir=='C' OR $fir=='Y' OR $fir=='J')
		{
			$flag=true;
			$goodsn=$val['B'];
			$count=$val['E'];
			$res=0;
			//$sql = "update `dbyizong`.`ecs_goods` set goods_number=goods_number+".$count." where goods_sn='".$goodsn."' "; 
			//$res=$GLOBALS['db']->query($sql);
			
			$sql = "update ".$TB." set isexec=303,exectime=now(),execcount='".$res."' where id=".$id." "; 
			$GLOBALS['db']->query($sql);
		} 
	}
	if(!$flag)
	{
	$sql = "update ".$TB." set isexec=1,exectime=now() where id=".$id." "; 
	 $GLOBALS['db']->query($sql);
	}
 }
?>