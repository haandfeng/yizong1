<?php 

Class kuajingDAL{
	function getlabel($orderno) { 
		 
		require_once 'kuajingCommon.php';
		$kuajingCommon=new kuajingCommon();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$method='Chigoose.order.getlabel';
		$notifyId=$kuajingCommon ->create_guid();
		$timestamp=$kuajingCommon ->gettimestamp();
		$dataFormat=$kuajingCommon ->getdataFormat();
		$version=$kuajingCommon ->getversion();
		$host=$kuajingCommon ->getHost();
		$message='{"serachNo":"'.$orderno.'"}';
	    $sign=$kuajingCommon ->getsign($security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message);
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token'];
			$result=$kuajingCommon ->postmessage($host,$security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message,$sign,$token);
			return $result;
		}
		else
		{
			$token='';
			return $result;
		} 
	} 
	
	function queryweight($orderno) { 
		 
		require_once 'kuajingCommon.php';
		$kuajingCommon=new kuajingCommon();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$method='Chigoose.order.queryweight';
		$notifyId=$kuajingCommon ->create_guid();
		$timestamp=$kuajingCommon ->gettimestamp();
		$dataFormat=$kuajingCommon ->getdataFormat();
		$version=$kuajingCommon ->getversion();
		$host=$kuajingCommon ->getHost();
		$message='{"orderNo":"'.$orderno.'"}';
	    $sign=$kuajingCommon ->getsign($security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message);
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token'];
			$result=$kuajingCommon ->postmessage($host,$security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message,$sign,$token);
			return $result;
		}
		else
		{
			$token='';
			return $result;
		} 
	}
	
	function gettrace($orderno) { 
		 
		require_once 'kuajingCommon.php';
		$kuajingCommon=new kuajingCommon();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$method='Chigoose.trace.querylist';
		$notifyId=$kuajingCommon ->create_guid();
		$timestamp=$kuajingCommon ->gettimestamp();
		$dataFormat=$kuajingCommon ->getdataFormat();
		$version=$kuajingCommon ->getversion();
		$host=$kuajingCommon ->getHost();
		$message='{"serachNo":"'.$orderno.'"}';
	    $sign=$kuajingCommon ->getsign($security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message);
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token'];
			$result=$kuajingCommon ->postmessage($host,$security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message,$sign,$token);
			//return 'aa';
			return $result;
		}
		else
		{
			$token='';
			//return 'bb';
			return $result;
		} 
		//return 'cc';
	}
}
?>