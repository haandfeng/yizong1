<?php

/**
 * ECSHOP 管理中心跨境管理
 
 * $Id: kuajing.php 20200224 $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/supplier.php');
$smarty->assign('lang', $_LANG);
 
/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list'&&$_REQUEST['t'] == 'goods')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    /* 查询 */
    //$result = log_list();

    /* 模板赋值 */
    $ur_here_lang = '跨境商品备案';
    $smarty->assign('ur_here', $ur_here_lang); // 当前导航

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('status',    $_REQUEST['status']);
	require_once 'kuajingCommon.php';
	$kuajingCommon=new kuajingCommon();
		$stockCode=$kuajingCommon ->getstockCode();
		$clientCode=$kuajingCommon ->getclientCode();
    $smarty->assign('stockCode',    $stockCode);
    $smarty->assign('clientCode',    $clientCode);
    //$smarty->assign('supplier_list',    $result['result']);
    //$smarty->assign('filter',       $result['filter']);
    //$smarty->assign('record_count', $result['record_count']);
    //$smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');
    $sql="select rank_id,rank_name from ". $ecs->table('supplier_rank') ." order by sort_order";
    $supplier_rank=$db->getAll($sql);
    $smarty->assign('supplier_rank', $supplier_rank);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('kuajing_goods.htm');
}
elseif ($_REQUEST['act'] == 'query'&&$_REQUEST['t'] == 'businessauth')
{   
	
		require_once 'kuajingCommon.php';
		$kuajingCommon=new kuajingCommon();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$method='Chigoose.businessauth.querybusinessauth';
		$notifyId=$kuajingCommon ->create_guid();
		$timestamp=$kuajingCommon ->gettimestamp();
		$dataFormat=$kuajingCommon ->getdataFormat();
		$version=$kuajingCommon ->getversion();
		$clientCode=$kuajingCommon ->getclientCode();
		$host=$kuajingCommon ->getHost();
		$message='{"clientCode":"'.$clientCode.'","status": "1"}';
	    $sign=$kuajingCommon ->getsign($security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message);
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token'];
			$res=$kuajingCommon ->postmessage($host,$security,$appKey,$method,$timestamp,$dataFormat,$version,$notifyId,$message,$sign,$token);
			//echo $res;
			//exit;
			$ret = json_decode($res,TRUE);
			if($ret['resultCode']=='1')
			{ 
		$dat=$res;  
			}
			else
			{
				echo $res;
				exit;
			} 
		}
		else
		{
			$token='';
			echo '{"resultCode":"303","resultData":"","resultContent":"token失败"}';
			exit;
		}  

    /* 模板赋值 */
    $smarty->assign('ur_here', '跨境商品备案'); // 当前导航
    //$smarty->assign('action_link', array('href' => 'kuajing.php?act=list&t=goods', 'text' => '跨境商品备案'));   
    $smarty->assign('dat',    $dat); 

    /* 显示模板 */
    assign_query_info();
    $smarty->display('kuajing_buss.htm');
}
/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query'&&$_REQUEST['t'] == 'goods')
{ 
		
		$name=$_REQUEST['name'];
		$pageSize=$_REQUEST['pageSize'];
		$currentPage=$_REQUEST['currentPage']; 
		if($currentPage>=0)
		{
			$start=($currentPage-1)*$pageSize;
			$end=($currentPage)*$pageSize-1;
		}
			$sql="";
			$sql.="select * from dw_record_goods where skuCode like '%".$name."%' or skuName like '%".$name."%' order by statetime desc limit ".$start.",".$end ." ";  
			$res= $GLOBALS['db']->getAll($sql);
		  $count = count($res);
		  $result1 = json_encode($res,TRUE);
		  $result='{"count":"'.$count.'","list":'.$result1.'}';
		echo $result; 
}
elseif ($_REQUEST['act'] == 'queryapi'&&$_REQUEST['t'] == 'goods')
{
	
	require_once 'kuajingCommon.php';
	$kuajingCommon=new kuajingCommon();
	
		$host=$kuajingCommon ->getHost();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		
		$json_data=$_REQUEST['query'];
		$pageSize=$_REQUEST['pageSize'];
		$currentPage=$_REQUEST['currentPage'];
		$json_data=str_replace('&quot;','"',$json_data);

		$jstr='{';
		$jstr.='"queryList": '.$json_data.',';
		$jstr.='"pageSize": '.$pageSize.',';
		$jstr.='"currentPage":'.$currentPage.''; 
		$jstr.='}';
		
	//$post    = (array)json_decode($jstr, true); 
	//echo '{"count":'.count($post).',"skuCode":"'.$post['skuItem']['skuCode'].'"}';
	
	$data=$jstr;
	
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
	
	 
		$url=$host.'/skuregister/queryList'; 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token'];
			$res=$kuajingCommon ->http_post_json($url,$data,$token);
			echo $res;
			exit;
			$ret = json_decode($res,TRUE);
			if($ret['resultCode']=='1')
			{
				echo $res;
			}
			else
			{
				echo $res;
			}
		}
		else
		{
			$token='';
			echo '{"resultCode":"303","resultData":"","resultContent":"token失败"}';
		} 
		
}


/*------------------------------------------------------ */
//-- 添加修改
/*------------------------------------------------------ */
elseif (($_REQUEST['act']== 'add'||$_REQUEST['act']== 'edit')&&$_REQUEST['t'] == 'goods')
{
	 $act=$_REQUEST['act'];
  if($act=='edit')
  {
	  $readonl='readonly="readonly"';
	  $code=$_REQUEST['code']; 
  }
  if($act=='add')
  {
	  $code=$_REQUEST['code'];
  }
  if($act=='add')
  {
	  $gid=$_REQUEST['gid'];
	  if($gid!="")
	  {
		  $sql="SELECT goods_sn as skuCode, ''  as skuId, goods_sn as goodsCode, gname as skuName, goods_name as shortName, ";
		  $sql.="'' as skuName_EN, barCode as barCode, '' as skuProperty, '' as stockUnit, '' as length, ";
		  $sql.="'' as width, '' as height, '' as volume, goods_weight as grossWeight, goods_weight as netWeight, ";
		  $sql.="'' as color, '' as size, '' as title, '' as categoryId, '' as categoryName, ";
		  $sql.="'' as pricingCategory, '' as safetyStock, '' as itemType, shop_price as tagPrice, shop_price as retailPrice, ";
		  $sql.="shop_price as costPrice, shop_price as purchasePrice, '' as seasonCode, '' as seasonName, '' as brandCode, ";
		  $sql.="'' as brandName, '' as isSNMgmt, '' as isShelfLifeMgmt, '' as shelfLife, '' as rejectLifecycle, ";
		  $sql.="'' as lockupLifecycle, '' as adventLifecycle, '' as isBatchMgmt, '' as batchCode, '' as batchRemark, ";
		  $sql.="'' as packCode, '' as originAddress, '' as approvalNumber, '' as isFragile, '' as isHazardous, ";
		  $sql.="'' as remark, '' as createTime, '' as updateTime, 'Y' as isValid, 'Y' as isSku, ";
		  $sql.="'' as packageMaterial, '' as hsCode, '' as mailTaxNo, '' as firstUnit, '' as secondUnit, ";
		  $sql.="'' as declareMeasureUnit, '' as productionMarketingCountry, '' as barCode2, '' as barCode3, '' as barCode4, ";
		  $sql.="'' as barCode5, '' as imgUrl, '' as imgUrl2, '' as imgUrl3, '' as imgUrl4, ";
		  $sql.="'' as imgUrl5, '' as manualNo, '' as manualSeqNo, '' as materialNo  ";
		  $sql.=" FROM `ecs_goods` where goods_id= '".$gid."'";  
				$goods= $GLOBALS['db']->getRow($sql);
				if($act=='add')
			  {
						$goods["skuId"]="";
			  }
			  
			$sql="select count(1) from dw_record_goods where skuCode = '".$code."'";  
			$rdcount= $GLOBALS['db']->getOne($sql);
			if($rdcount>0)
			{
				$readonl='readonly="readonly"';
				$sql="select * from dw_record_goods where skuCode = '".$code."'";  
				$goods= $GLOBALS['db']->getRow($sql);
				$act='edit';
			}
		  
	  }
  }
  if($code!="")
  {
	  $sql="select * from dw_record_goods where skuCode = '".$code."'";  
			$goods= $GLOBALS['db']->getRow($sql);
			if($act=='add')
		  {
					$goods["skuCode"]="";
					$goods["skuId"]="";
		  }
  }
        $smarty->assign('reqact', $act);  
        $smarty->assign('readonl', $readonl);   
        $smarty->assign('order', $goods);   
        assign_query_info();
        $smarty->display('kuaijing_goods_add.htm');
        exit;
              

} 
else if($_REQUEST['act']== 'addsave'&&$_REQUEST['t']== 'goods')
{ 
	require_once 'kuajingCommon.php';
	$kuajingCommon=new kuajingCommon();
	
		$stockCode=$kuajingCommon ->getstockCode();
		$clientCode=$kuajingCommon ->getclientCode();
		$platformCode=$kuajingCommon ->getplatformCode();
		$supplierCode=$kuajingCommon ->getsupplierCode();
		$supplierName=$kuajingCommon ->getsupplierName();
		$host=$kuajingCommon ->getHost();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$json_data=$_REQUEST['save'];
		$json_data=str_replace('&quot;','"',$json_data);

		$jstr='{';
		$jstr.='"stockCode": "'.$stockCode.'",';
		$jstr.='"clientCode": "'.$clientCode.'",';
		$jstr.='"platformCode":"'.$platformCode.'",';
		$jstr.='"supplierCode": "'.$supplierCode.'",';
		$jstr.='"supplierName": "'.$supplierName.'",';
		$jstr.='"skuItem": '.$json_data;
		$jstr.='}';
		
	$post    = (array)json_decode($jstr, true); 
	$skuCode=$post['skuItem']['skuCode'];
	$data=$jstr;
	if(isskuCode($skuCode))
	{
		echo '{"resultCode":"303","resultData":"","resultContent":"已存在'.$skuCode.'"}';
		exit;
	}
	else
	{
		addgoodslog($data);
		$isstate='0';
		$stateremark='草稿';
		updatestate($skuCode,$isstate,$stateremark);
	}
	//echo '{"count":'.count($post).',"skuCode":"'.$post['skuItem']['skuCode'].'"}';
	
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
	
	 
		$url=$host.'/skuregister/addskuregister'; 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token'];
			$res=$kuajingCommon ->http_post_json($url,$data,$token);
			$ret = json_decode($res,TRUE);
			if($ret['resultCode']=='1'&&strpos($ret['resultContent'],'成功') !== false)
			{ 
				$isstate='1000';
				$stateremark='正常';
				updatestate($skuCode,$isstate,$stateremark);
			}
			echo $res;
		}
		else
		{
			$token='';
			echo '{"resultCode":"303","resultData":"","resultContent":"token失败"}';
		} 
		
	
}
else if($_REQUEST['act']== 'editsave'&&$_REQUEST['t']== 'goods')
{ 
	require_once 'kuajingCommon.php';
	$kuajingCommon=new kuajingCommon();
	
		$stockCode=$kuajingCommon ->getstockCode();
		$clientCode=$kuajingCommon ->getclientCode();
		$platformCode=$kuajingCommon ->getplatformCode();
		$supplierCode=$kuajingCommon ->getsupplierCode();
		$supplierName=$kuajingCommon ->getsupplierName();
		$host=$kuajingCommon ->getHost();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$json_data=$_REQUEST['save'];
		$json_data=str_replace('&quot;','"',$json_data);
		$json_data = substr($json_data,0,strlen($json_data)-1); 
		$json_data = substr($json_data,1,strlen($json_data)); 
		$jstr='{';
		$jstr.='"stockCode": "'.$stockCode.'",';
		$jstr.='"clientCode": "'.$clientCode.'",';
		$jstr.='"supplierCode": "'.$supplierCode.'",';
		$jstr.='"supplierName": "'.$supplierName.'",';
		$jstr.=$json_data;
		$jstr.='}';
		
		file_put_contents('20200515.txt',"editsave:".$jstr."\r\n" , FILE_APPEND); 
		//echo $jstr;
		//exit;
	$post    = (array)json_decode($jstr, true); 
	$skuCode=$post['skuCode'];
	//echo '{"count":'.count($post).',"skuCode":"'.$post['skuCode'].'"}';
	//exit;
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
		
	//echo $result;exit;
	$data=$jstr;
	updategoodslog($data); 
	$data='{"updateSkuRegister":'.$jstr.'}';
	 
	$isstate='1';
	$stateremark='修改未通过';
	updatestate($skuCode,$isstate,$stateremark); 
	 
		$url=$host.'/skuregister/updateskuregister'; 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token']; 
			$res=$kuajingCommon ->http_post_json($url,$data,$token);
			file_put_contents('20200515.txt',"editsaveresult:".$res."\r\n" , FILE_APPEND); 
			$ret = json_decode($res,TRUE);
			if($ret['resultCode']=='1'&&strpos($ret['resultContent'],'成功') !== false)
			{
				$isstate='1000';
				$stateremark='正常';
				updatestate($skuCode,$isstate,$stateremark);
			}
			echo $res;
		}
		else
		{
			$token='';
			echo '{"resultCode":"303","resultData":"","resultContent":"token失败"}';
		} 
		
	
}
else if($_REQUEST['act']== 'del'&&$_REQUEST['t']== 'goods')
{ 
	require_once 'kuajingCommon.php';
	$kuajingCommon=new kuajingCommon();
	
		$stockCode=$kuajingCommon ->getstockCode();
		$clientCode=$kuajingCommon ->getclientCode(); 
		$host=$kuajingCommon ->getHost();
		$appKey=$kuajingCommon ->getkey();
		$security=$kuajingCommon ->getsecurity();
		$code=$_REQUEST['code']; 
		$jstr='{';
		$jstr.='"stockCode": "'.$stockCode.'",';
		$jstr.='"clientCode": "'.$clientCode.'",';
		$jstr.='"skuCode": "'.$code.'"';  
		$jstr.='}';
		
		//echo $jstr;
		//exit;
	$post    = (array)json_decode($jstr, true); 
	$skuCode=$post['skuCode'];
	//echo '{"count":'.count($post).',"skuCode":"'.$post['skuCode'].'"}';
	//exit;
		$result=$kuajingCommon ->getToken($host,$appKey,$security); 
	 
	$data='{"delteSkuRegister":'.$jstr.'}';
	 
	$isstate='3';
	$stateremark='作废未通过';
	updatestate($skuCode,$isstate,$stateremark); 
	 
		$url=$host.'/skuregister/delteskuregister'; 
		$returnarray = json_decode($result,TRUE);
		if($returnarray['resultCode']=='1')
		{ 
			$token= $returnarray['resultData']['token']; 
			$res=$kuajingCommon ->http_post_json($url,$data,$token);
			$ret = json_decode($res,TRUE);
			if($ret['resultCode']=='1'&&strpos($ret['resultContent'],'成功') !== false)
			{
				$isstate='3000';
				$stateremark='作废';
				updatestate($skuCode,$isstate,$stateremark);
			}
			echo $res;
		}
		else
		{
			$token='';
			echo '{"resultCode":"303","resultData":"","resultContent":"token失败"}';
		} 
		
	
}
else if($_REQUEST['act']== 'label'&&$_REQUEST['orderno']!= '')
{ 
	require_once 'kuajingDAL.php';
	$kuajingDAL=new kuajingDAL();
		$orderno=$_REQUEST['orderno'];  
	$post    = (array)json_decode($jstr, true);  
		$result=$kuajingDAL ->getlabel($orderno); 
	 echo $result;
	 
}
else if($_REQUEST['act']== 'weight'&&$_REQUEST['orderno']!= '')
{ 
	require_once 'kuajingDAL.php';
	$kuajingDAL=new kuajingDAL();
		$orderno=$_REQUEST['orderno'];  
	$post    = (array)json_decode($jstr, true);  
		$result=$kuajingDAL ->queryweight($orderno); 
	 echo $result;
	 
}
else if($_REQUEST['act']== 'trace'&&$_REQUEST['orderno']!= '')
{ 
	require_once 'kuajingDAL.php';
	$kuajingDAL=new kuajingDAL();
		$orderno=$_REQUEST['orderno'];  
	$post    = (array)json_decode($jstr, true);  
		$result=$kuajingDAL ->gettrace($orderno); 
	 echo $result;
	 
}
function isskuCode($skuCode)
{
			$post    = (array)json_decode($content, true);
			$sql="";
			$sql.="select count(1) from dw_record_goods   "; 
			$sql.=" where skuCode='".$skuCode."'";  
			$res= $GLOBALS['db']->getOne($sql);
			if($res>0)return true;
			else return false;
}
function updatestate($skuCode,$isstate,$stateremark)
{
			$post    = (array)json_decode($content, true);
			$sql="";
			$sql.="update dw_record_goods set ";
			$sql.="isstate='".$isstate."',";
			$sql.="stateremark='".$stateremark."',statetime=now()"; 
			$sql.=" where skuCode='".$skuCode."'";  
			$res= $GLOBALS['db']->query($sql);
}
function updategoodslog($content)
{
			$post    = (array)json_decode($content, true);
			$sql="";
			$sql.="update dw_record_goods set ";
			$sql.="skuId='".$post['skuId']."',";
			$sql.="goodsCode='".$post['goodsCode']."',";
			$sql.="skuName='".addslashes($post['skuName'])."',";
			$sql.="shortName='".addslashes($post['shortName'])."',";
			$sql.="skuName_EN='".$post['skuName_EN']."',";
			$sql.="barCode='".$post['barCode']."',";
			$sql.="skuProperty='".$post['skuProperty']."',";
			$sql.="stockUnit='".$post['stockUnit']."',";
			$sql.="length='".$post['length']."',";
			$sql.="width='".$post['width']."',";
			$sql.="height='".$post['height']."',";
			$sql.="volume='".$post['volume']."',";
			$sql.="grossWeight='".$post['grossWeight']."',";
			$sql.="netWeight='".$post['netWeight']."',";
			$sql.="color='".$post['color']."',";
			$sql.="size='".$post['size']."',";
			$sql.="title='".$post['title']."',";
			$sql.="categoryId='".$post['categoryId']."',";
			$sql.="categoryName='".$post['categoryName']."',";
			$sql.="pricingCategory='".$post['pricingCategory']."',";
			$sql.="safetyStock='".$post['safetyStock']."',";
			$sql.="itemType='".$post['itemType']."',";
			$sql.="tagPrice='".$post['tagPrice']."',";
			$sql.="retailPrice='".$post['retailPrice']."',";
			$sql.="costPrice='".$post['costPrice']."',";
			$sql.="purchasePrice='".$post['purchasePrice']."',";
			$sql.="seasonCode='".$post['seasonCode']."',";
			$sql.="seasonName='".$post['seasonName']."',";
			$sql.="brandCode='".$post['brandCode']."',";
			$sql.="brandName='".$post['brandName']."',";
			$sql.="isSNMgmt='".$post['isSNMgmt']."',";
			$sql.="isShelfLifeMgmt='".$post['isShelfLifeMgmt']."',";
			$sql.="shelfLife='".$post['shelfLife']."',";
			$sql.="rejectLifecycle='".$post['rejectLifecycle']."',";
			$sql.="lockupLifecycle='".$post['lockupLifecycle']."',";
			$sql.="adventLifecycle='".$post['adventLifecycle']."',";
			$sql.="isBatchMgmt='".$post['isBatchMgmt']."',";
			$sql.="batchCode='".$post['batchCode']."',";
			$sql.="batchRemark='".$post['batchRemark']."',";
			$sql.="packCode='".$post['packCode']."',";
			$sql.="originAddress='".$post['originAddress']."',";
			$sql.="approvalNumber='".$post['approvalNumber']."',";
			$sql.="isFragile='".$post['isFragile']."',";
			$sql.="isHazardous='".$post['isHazardous']."',";
			$sql.="remark='".$post['remark']."',";
			$sql.="createTime='".$post['createTime']."',";
			$sql.="updateTime='".$post['updateTime']."',";
			$sql.="isValid='".$post['isValid']."',";
			$sql.="isSku='".$post['isSku']."',";
			$sql.="packageMaterial='".$post['packageMaterial']."',";
			$sql.="hsCode='".$post['hsCode']."',";
			$sql.="mailTaxNo='".$post['mailTaxNo']."',";
			$sql.="firstUnit='".$post['firstUnit']."',";
			$sql.="secondUnit='".$post['secondUnit']."',";
			$sql.="declareMeasureUnit='".$post['declareMeasureUnit']."',";
			$sql.="productionMarketingCountry='".$post['productionMarketingCountry']."',";
			$sql.="barCode2='".$post['barCode2']."',";
			$sql.="barCode3='".$post['barCode3']."',";
			$sql.="barCode4='".$post['barCode4']."',";
			$sql.="barCode5='".$post['barCode5']."',";
			$sql.="imgUrl='".$post['imgUrl']."',";
			$sql.="imgUrl2='".$post['imgUrl2']."',";
			$sql.="imgUrl3='".$post['imgUrl3']."',";
			$sql.="imgUrl4='".$post['imgUrl4']."',";
			$sql.="imgUrl5='".$post['imgUrl5']."',";
			$sql.="manualNo='".$post['manualNo']."',";
			$sql.="manualSeqNo='".$post['manualSeqNo']."',";
			$sql.="materialNo='".$post['materialNo']."' ";
			$sql.=" where skuCode='".$post['skuCode']."'";  
			$res= $GLOBALS['db']->query($sql);
}
function addgoodslog($content)
{
			$post    = (array)json_decode($content, true);
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
			$sql.="'".addslashes($post['skuItem']['skuName'])."',";
			$sql.="'".addslashes($post['skuItem']['shortName'])."',";
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
			$res= $GLOBALS['db']->query($sql);
}
?>