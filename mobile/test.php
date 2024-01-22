<?php
  $arr=array('name'=>'掌声','id'=>21);
  $arr["name"]='abc';
  $str=var_export($arr,true);
file_put_contents("cc20191105.txt",date('H:i:s',time())." :\r\n".$str."\r\n", FILE_APPEND);
 
?>