<?php
require_once "jssdk.php";
$jssdk = new JSSDK("wx62c6a5493ee94f4c", "02705de706575a34b7f17935bda3ae22");
$href=$_REQUEST['tokenUrl'];
$signPackage = $jssdk->getSignPackageUrl($href);
echo json_encode($signPackage); 
?> 