<?php

$postUrl = 'https://www.quickact.net/customs/platDataOpen.php';  //向海关提交的注册地址

$postData['openReq'] = '{"sessionID":"000000-0000-4015-90AC-A404280DA001","orderNo":"O20190408192707913","serviceTime":1554091885129}';
$postData = http_build_query($postData);
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $postUrl);
curl_setopt($curl, CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$r = curl_exec($curl); 
curl_close($curl);

print_r($r);

?>