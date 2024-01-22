<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php'); 

if($_REQUEST['act']== 'p')
{ 
//print_r($_SERVER); exit;
//echo $_SERVER['REQUEST_METHOD'];
		if (false !== strpos($_SERVER["CONTENT_TYPE"], 'application/json')) {
            $content = file_get_contents('php://input');
            $post    = (array)json_decode($content, true);
			
			echo '{"count":'.count($post).',"skuCode":"'.$post['skuItem']['skuCode'].'"}';
			
			$sql="";
			$sql.="insert into dw_record_goods(";
			$sql.="skuCode,";
			$sql.="skuId,";
			$sql.="goodsCode,";
			$sql.="skuName,";
			$sql.="shortName,";
			$sql.="skuName_EN,";
			$sql.="barCode,";
			$sql.="skuProperty,";
			$sql.="stockUnit,";
			$sql.="length,";
			$sql.="width,";
			$sql.="height,";
			$sql.="volume,";
			$sql.="grossWeight,";
			$sql.="netWeight,";
			$sql.="color,";
			$sql.="size,";
			$sql.="title,";
			$sql.="categoryId,";
			$sql.="categoryName,";
			$sql.="pricingCategory,";
			$sql.="safetyStock,";
			$sql.="itemType,";
			$sql.="tagPrice,";
			$sql.="retailPrice,";
			$sql.="costPrice,";
			$sql.="purchasePrice,";
			$sql.="seasonCode,";
			$sql.="seasonName,";
			$sql.="brandCode,";
			$sql.="brandName,";
			$sql.="isSNMgmt,";
			$sql.="isShelfLifeMgmt,";
			$sql.="shelfLife,";
			$sql.="rejectLifecycle,";
			$sql.="lockupLifecycle,";
			$sql.="adventLifecycle,";
			$sql.="isBatchMgmt,";
			$sql.="batchCode,";
			$sql.="batchRemark,";
			$sql.="packCode,";
			$sql.="originAddress,";
			$sql.="approvalNumber,";
			$sql.="isFragile,";
			$sql.="isHazardous,";
			$sql.="remark,";
			$sql.="createTime,";
			$sql.="updateTime,";
			$sql.="isValid,";
			$sql.="isSku,";
			$sql.="packageMaterial,";
			$sql.="hsCode,";
			$sql.="mailTaxNo,";
			$sql.="firstUnit,";
			$sql.="secondUnit,";
			$sql.="declareMeasureUnit,";
			$sql.="productionMarketingCountry,";
			$sql.="barCode2,";
			$sql.="barCode3,";
			$sql.="barCode4,";
			$sql.="barCode5,";
			$sql.="imgUrl,";
			$sql.="imgUrl2,";
			$sql.="imgUrl3,";
			$sql.="imgUrl4,";
			$sql.="imgUrl5,";
			$sql.="manualNo,";
			$sql.="manualSeqNo,";
			$sql.="materialNo";
			$sql.=")";
			$sql.="values(";
			$sql.="'".$post['skuItem']['skuCode']."',";
			$sql.="'".$post['skuItem']['skuId']."',";
			$sql.="'".$post['skuItem']['goodsCode']."',";
			$sql.="'".$post['skuItem']['skuName']."',";
			$sql.="'".$post['skuItem']['shortName']."',";
			$sql.="'".$post['skuItem']['skuName_EN']."',";
			$sql.="'".$post['skuItem']['barCode']."',";
			$sql.="'".$post['skuItem']['skuProperty']."',";
			$sql.="'".$post['skuItem']['stockUnit']."',";
			$sql.="'".$post['skuItem']['length']."',";
			$sql.="'".$post['skuItem']['width']."',";
			$sql.="'".$post['skuItem']['height']."',";
			$sql.="'".$post['skuItem']['volume']."',";
			$sql.="'".$post['skuItem']['grossWeight']."',";
			$sql.="'".$post['skuItem']['netWeight']."',";
			$sql.="'".$post['skuItem']['color']."',";
			$sql.="'".$post['skuItem']['size']."',";
			$sql.="'".$post['skuItem']['title']."',";
			$sql.="'".$post['skuItem']['categoryId']."',";
			$sql.="'".$post['skuItem']['categoryName']."',";
			$sql.="'".$post['skuItem']['pricingCategory']."',";
			$sql.="'".$post['skuItem']['safetyStock']."',";
			$sql.="'".$post['skuItem']['itemType']."',";
			$sql.="'".$post['skuItem']['tagPrice']."',";
			$sql.="'".$post['skuItem']['retailPrice']."',";
			$sql.="'".$post['skuItem']['costPrice']."',";
			$sql.="'".$post['skuItem']['purchasePrice']."',";
			$sql.="'".$post['skuItem']['seasonCode']."',";
			$sql.="'".$post['skuItem']['seasonName']."',";
			$sql.="'".$post['skuItem']['brandCode']."',";
			$sql.="'".$post['skuItem']['brandName']."',";
			$sql.="'".$post['skuItem']['isSNMgmt']."',";
			$sql.="'".$post['skuItem']['isShelfLifeMgmt']."',";
			$sql.="'".$post['skuItem']['shelfLife']."',";
			$sql.="'".$post['skuItem']['rejectLifecycle']."',";
			$sql.="'".$post['skuItem']['lockupLifecycle']."',";
			$sql.="'".$post['skuItem']['adventLifecycle']."',";
			$sql.="'".$post['skuItem']['isBatchMgmt']."',";
			$sql.="'".$post['skuItem']['batchCode']."',";
			$sql.="'".$post['skuItem']['batchRemark']."',";
			$sql.="'".$post['skuItem']['packCode']."',";
			$sql.="'".$post['skuItem']['originAddress']."',";
			$sql.="'".$post['skuItem']['approvalNumber']."',";
			$sql.="'".$post['skuItem']['isFragile']."',";
			$sql.="'".$post['skuItem']['isHazardous']."',";
			$sql.="'".$post['skuItem']['remark']."',";
			$sql.="'".$post['skuItem']['createTime']."',";
			$sql.="'".$post['skuItem']['updateTime']."',";
			$sql.="'".$post['skuItem']['isValid']."',";
			$sql.="'".$post['skuItem']['isSku']."',";
			$sql.="'".$post['skuItem']['packageMaterial']."',";
			$sql.="'".$post['skuItem']['hsCode']."',";
			$sql.="'".$post['skuItem']['mailTaxNo']."',";
			$sql.="'".$post['skuItem']['firstUnit']."',";
			$sql.="'".$post['skuItem']['secondUnit']."',";
			$sql.="'".$post['skuItem']['declareMeasureUnit']."',";
			$sql.="'".$post['skuItem']['productionMarketingCountry']."',";
			$sql.="'".$post['skuItem']['barCode2']."',";
			$sql.="'".$post['skuItem']['barCode3']."',";
			$sql.="'".$post['skuItem']['barCode4']."',";
			$sql.="'".$post['skuItem']['barCode5']."',";
			$sql.="'".$post['skuItem']['imgUrl']."',";
			$sql.="'".$post['skuItem']['imgUrl2']."',";
			$sql.="'".$post['skuItem']['imgUrl3']."',";
			$sql.="'".$post['skuItem']['imgUrl4']."',";
			$sql.="'".$post['skuItem']['imgUrl5']."',";
			$sql.="'".$post['skuItem']['manualNo']."',";
			$sql.="'".$post['skuItem']['manualSeqNo']."',";
			$sql.="'".$post['skuItem']['materialNo']."'";
			$sql.=")";
			//$res= $GLOBALS['db']->query($sql);
			//echo $res;
			//echo '----';
			$sql="";
			$sql.="update dw_record_goods set ";
			$sql.="skuId='".$post['skuItem']['skuId']."',";
			$sql.="goodsCode='".$post['skuItem']['goodsCode']."',";
			$sql.="skuName='".$post['skuItem']['skuName']."',";
			$sql.="shortName='".$post['skuItem']['shortName']."',";
			$sql.="skuName_EN='".$post['skuItem']['skuName_EN']."',";
			$sql.="barCode='".$post['skuItem']['barCode']."',";
			$sql.="skuProperty='".$post['skuItem']['skuProperty']."',";
			$sql.="stockUnit='".$post['skuItem']['stockUnit']."',";
			$sql.="length='".$post['skuItem']['length']."',";
			$sql.="width='".$post['skuItem']['width']."',";
			$sql.="height='".$post['skuItem']['height']."',";
			$sql.="volume='".$post['skuItem']['volume']."',";
			$sql.="grossWeight='".$post['skuItem']['grossWeight']."',";
			$sql.="netWeight='".$post['skuItem']['netWeight']."',";
			$sql.="color='".$post['skuItem']['color']."',";
			$sql.="size='".$post['skuItem']['size']."',";
			$sql.="title='".$post['skuItem']['title']."',";
			$sql.="categoryId='".$post['skuItem']['categoryId']."',";
			$sql.="categoryName='".$post['skuItem']['categoryName']."',";
			$sql.="pricingCategory='".$post['skuItem']['pricingCategory']."',";
			$sql.="safetyStock='".$post['skuItem']['safetyStock']."',";
			$sql.="itemType='".$post['skuItem']['itemType']."',";
			$sql.="tagPrice='".$post['skuItem']['tagPrice']."',";
			$sql.="retailPrice='".$post['skuItem']['retailPrice']."',";
			$sql.="costPrice='".$post['skuItem']['costPrice']."',";
			$sql.="purchasePrice='".$post['skuItem']['purchasePrice']."',";
			$sql.="seasonCode='".$post['skuItem']['seasonCode']."',";
			$sql.="seasonName='".$post['skuItem']['seasonName']."',";
			$sql.="brandCode='".$post['skuItem']['brandCode']."',";
			$sql.="brandName='".$post['skuItem']['brandName']."',";
			$sql.="isSNMgmt='".$post['skuItem']['isSNMgmt']."',";
			$sql.="isShelfLifeMgmt='".$post['skuItem']['isShelfLifeMgmt']."',";
			$sql.="shelfLife='".$post['skuItem']['shelfLife']."',";
			$sql.="rejectLifecycle='".$post['skuItem']['rejectLifecycle']."',";
			$sql.="lockupLifecycle='".$post['skuItem']['lockupLifecycle']."',";
			$sql.="adventLifecycle='".$post['skuItem']['adventLifecycle']."',";
			$sql.="isBatchMgmt='".$post['skuItem']['isBatchMgmt']."',";
			$sql.="batchCode='".$post['skuItem']['batchCode']."',";
			$sql.="batchRemark='".$post['skuItem']['batchRemark']."',";
			$sql.="packCode='".$post['skuItem']['packCode']."',";
			$sql.="originAddress='".$post['skuItem']['originAddress']."',";
			$sql.="approvalNumber='".$post['skuItem']['approvalNumber']."',";
			$sql.="isFragile='".$post['skuItem']['isFragile']."',";
			$sql.="isHazardous='".$post['skuItem']['isHazardous']."',";
			$sql.="remark='".$post['skuItem']['remark']."',";
			$sql.="createTime='".$post['skuItem']['createTime']."',";
			$sql.="updateTime='".$post['skuItem']['updateTime']."',";
			$sql.="isValid='".$post['skuItem']['isValid']."',";
			$sql.="isSku='".$post['skuItem']['isSku']."',";
			$sql.="packageMaterial='".$post['skuItem']['packageMaterial']."',";
			$sql.="hsCode='".$post['skuItem']['hsCode']."',";
			$sql.="mailTaxNo='".$post['skuItem']['mailTaxNo']."',";
			$sql.="firstUnit='".$post['skuItem']['firstUnit']."',";
			$sql.="secondUnit='".$post['skuItem']['secondUnit']."',";
			$sql.="declareMeasureUnit='".$post['skuItem']['declareMeasureUnit']."',";
			$sql.="productionMarketingCountry='".$post['skuItem']['productionMarketingCountry']."',";
			$sql.="barCode2='".$post['skuItem']['barCode2']."',";
			$sql.="barCode3='".$post['skuItem']['barCode3']."',";
			$sql.="barCode4='".$post['skuItem']['barCode4']."',";
			$sql.="barCode5='".$post['skuItem']['barCode5']."',";
			$sql.="imgUrl='".$post['skuItem']['imgUrl']."',";
			$sql.="imgUrl2='".$post['skuItem']['imgUrl2']."',";
			$sql.="imgUrl3='".$post['skuItem']['imgUrl3']."',";
			$sql.="imgUrl4='".$post['skuItem']['imgUrl4']."',";
			$sql.="imgUrl5='".$post['skuItem']['imgUrl5']."',";
			$sql.="manualNo='".$post['skuItem']['manualNo']."',";
			$sql.="manualSeqNo='".$post['skuItem']['manualSeqNo']."',";
			$sql.="materialNo='".$post['skuItem']['materialNo']."' ";
			$sql.=" where skuCode='".$post['skuItem']['skuCode']."'";
			//echo $sql;
			//$res= $GLOBALS['db']->query($sql);
			//echo $res;
			//echo '===';
        }  
}

if($_REQUEST['act']== 'a')
{

//$data='{"stockCode": "","clientCode": "IKATS","platformCode":"","supplierCode": "","supplierName": "","skuItem": {"skuCode": "89","skuId": "","goodsCode": "","skuName": "","shortName": "","skuName_EN": "","barCode": "","skuProperty": "","stockUnit": "","length": "","width": "","height": "","volume": "","grossWeight": "","netWeight": "","color": "","size": "","title": "","categoryId": "","categoryName": "","pricingCategory": "","safetyStock": "","itemType": "","tagPrice": "","retailPrice": "","costPrice": "","purchasePrice": "","seasonCode": "","seasonName": "","brandCode": "","brandName": "","isSNMgmt": "N","isShelfLifeMgmt": "Y","isShelfLifeMgmt": "N","shelfLife": "","rejectLifecycle": "","lockupLifecycle": "","adventLifecycle": "","isBatchMgmt": "N","batchCode": "","batchRemark": "","packCode": "","originAddress": "","approvalNumber": "","isFragile": "N","isHazardous": "N","remark": "","createTime": "","updateTime": "","isValid": "N","isSku": "N","packageMaterial": "","hsCode": "","mailTaxNo": "","firstUnit": "","secondUnit": "","declareMeasureUnit": "","productionMarketingCountry": "","barCode2": "","barCode3": "","barCode4": "","barCode5": "","imgUrl": "","imgUrl2": "","imgUrl3": "","imgUrl4": "","imgUrl5": "","manualNo": "","manualSeqNo": "","materialNo": ""}}';
$data='{"stockCode": "hak","clientCode": "IKATS","platformCode":"","supplierCode": "","supplierName": "","skuItem": {"skuCode": "30","skuId": "99","goodsCode": "1","skuName": "2","shortName": "3","skuName_EN": "4","barCode": "5","skuProperty": "6","stockUnit": "7","length": "8","width": "9","height": "0","volume": "11","grossWeight": "12","netWeight": "13","color": "14","size": "15","title": "16","categoryId": "17","categoryName": "18","pricingCategory": "19","safetyStock": "20","itemType": "ZC","tagPrice": "21","retailPrice": "22","costPrice": "23","purchasePrice": "24","seasonCode": "25","seasonName": "26","brandCode": "27","brandName": "28","isSNMgmt": "N","isShelfLifeMgmt": "Y","isShelfLifeMgmt": "N","shelfLife": "29","rejectLifecycle": "30","lockupLifecycle": "31","adventLifecycle": "32","isBatchMgmt": "N","batchCode": "","batchRemark": "33","packCode": "34","originAddress": "35","approvalNumber": "36","isFragile": "N","isHazardous": "N","remark": "37","createTime": "2020-02-27 09:09:09","updateTime": "2020-02-27 09:09:09","isValid": "N","isSku": "N","packageMaterial": "40","hsCode": "41","mailTaxNo": "42","firstUnit": "43","secondUnit": "44","declareMeasureUnit": "45","productionMarketingCountry": "46","barCode2": "47","barCode3": "48","barCode4": "49","barCode5": "50","imgUrl": "51","imgUrl2": "52","imgUrl3": "53","imgUrl4": "54","imgUrl5": "55","manualNo": "56","manualSeqNo": "57","materialNo": "58"}}';
		//$url='https://www.quickact.net/PTEST.php?act=p';
		$url='http://uat.hak.oms.chigoose.com/skuregister/addskuregister';
echo http_post_json($url,$data);


 
}
function http_post_json($url, $jsonStr)
{
	$str=getToken();return $str;
	$tokens    = (array)json_decode($str, true);
	$token=$tokens['resultData']['token'];
	 
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
	function getToken()
	{  
		$host='http://uat.hak.oms.chigoose.com';
		$appKey='ikats';
		$security='70038ecfe77e0d77b8fdc1796609f23e';
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
?>