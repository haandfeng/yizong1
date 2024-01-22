<?php 

Class kuajingCommon{
	function getkey() { 
		return 'GZKS';//'ikatsuser2'; 
	} 
	function getsecurity()
	{
		return '7ff4b9d236515733a2dea807dc3acc08';//'ddd957250acc61cfb741761990c93114'; 
	}
	function getstockCode()
	{
		return 'HMBS';//'hak'; 
	}
	function getclientCode()
	{
		return 'GZKS';//'IKATSTEST'; 
	}
	function getplatformCode()
	{
		return 'KUAISHAN'; 
	}
	function getsupplierCode()
	{
		return ''; 
	}
	function getsupplierName()
	{
		return ''; 
	}
	function getHost()
	{
		return 'http://oms.chigoose.com';//'http://uat.oms.chigoose.com';
	} 
	function getBusinessModelCode()
	{
		return 'PIB000206'; 
	}
	function create_guid() { 
		$charid = strtoupper(md5(uniqid(mt_rand(), true))); 
		$hyphen = chr(45);// "-" 
		$uuid = substr($charid, 0, 8).$hyphen 
		.substr($charid, 8, 4).$hyphen 
		.substr($charid,12, 4).$hyphen 
		.substr($charid,16, 4).$hyphen 
		.substr($charid,20,12);
		return $uuid; 
	} 
	function gettimestamp() {  
		return date("Y-m-d H:i:s"); 
	}
	function getdataFormat() {  
		return 'json';
	}
	function getversion() {  
		return '1.0';
	}
	//报文发送 zx-20200225
	function postmessage($host,$security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message,$sign,$token){    
		
		switch ($method) {
			case 'Chigoose.order.addorder': 
				$posturl='/order/addorder';
				break;
			case 'Chigoose.order.getlabel': 
				$posturl='/order/getlabel';
				break;
			case 'Chigoose.order.queryweight': 
				$posturl='/order/queryweight';
				break;
			case 'Chigoose.order.outboundmsg': 
				$posturl='/order/outboundmsg';
				break;
			case 'Chigoose.order.cancelorder': 
				$posturl='/order/cancelorder';
				break;
			case 'Chigoose.trace.querylist': 
				$posturl='/trace/querylist';
				break;
			case 'Chigoose.skuregister.addskuregister': 
				$posturl='/skuregister/addskuregister';
				break;
			case 'Chigoose.skuregister.updateskuregister': 
				$posturl='/skuregister/updateskuregister';
				break;
			case 'Chigoose.skuregister.queryList': 
				$posturl='/skuregister/queryList';
				break;
			case 'Chigoose.skuregister.delteskuregister': 
				$posturl='/skuregister/delteskuregister';
				break;
			case 'Chigoose.businessauth.querybusinessauth': 
				$posturl='/businessauth/querybusinessauth';
				break;
		}

		$post_data = array(
		  'appKey' => $appKey,
		  'method' => $method,
		  'timestamp' => $timestamp,
		  'dataFormat' => $dataFormat,
		  'version' => $version,
		  'notifyId' => $notifyId,  
		  'message' => $message 
		);
		$ul=$host.''.$posturl;
		  
		$header = array("Content-Type:application/x-www-form-urlencoded", "token:".$token, "Sign:".$sign);
		//$header = array("Content-Type:application/json;charset=utf-8");
		$header = empty($header) ? '' : $header;
		$post_string = http_build_query($post_data, '', '&');
		 
		
		$ch = curl_init();     
		curl_setopt($ch, CURLOPT_URL, $ul);      
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   
		curl_setopt($ch, CURLOPT_POST, true);  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);     
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);     
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  
		$result = curl_exec($ch);
 
		curl_close($ch); 
		
		
		return $result; 
	} 
	//签名 zx-20200225
	function getsign($security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message){ 
		$str='appKey='.$appKey.':dataFormat='.$dataFormat.':message='.$message.':method='.$method.':notifyId='.$notifyId.':timestamp='.$timestamp.':version='.$version;
		$str=$security.''.$str.''.$security;
		 
		$sign=md5($str);
		$sign=strtoupper($sign);
		return $sign;
	}
	 
	//token zx-20200225
	function getToken($host,$appKey,$security)
	{  
		$post_data = array(
		  'appKey' => $appKey,
		  'security' => $security
		);
		$url=$host.'/login/getToken';
		
		  $postdata = http_build_query($post_data);
		  $options = array(
			'http' => array(
			  'method' => 'POST',
			  'header' => 'Content-type:application/x-www-form-urlencoded',
			  'content' => $postdata,
			  'timeout' => 15 * 60  
			)
		  );
		  $context = stream_context_create($options);
		  $result = file_get_contents($url, false, $context);
		return $result;
	}
	//postjson zx-20200227
	function http_post_json($url, $jsonStr,$token)
	{
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'token:'.$token,
				'Content-Type: application/json; charset=utf-8',
				'Content-Length: ' . strlen($jsonStr)
			)
		);
		$response = curl_exec($ch); 
		curl_close($ch); 
		return $response;
	}
}
?>