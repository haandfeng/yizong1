<?php
/**
 * 查询物流信息
 */
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php'); 
  
$order_sn = $_REQUEST['no'];
$order_sn='776100067199602';
	$sql = "SELECT order_id FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '".$order_sn."' or invoice_no='".$order_sn."' ";
	$order_id = $GLOBALS['db']->getOne($sql);
        if($order_id > 0)
	{  
                //处理多物流
                $sql = "SELECT delivery_id,shipping_name,invoice_no  FROM ". $GLOBALS['ecs']->table('delivery_order'). " WHERE order_id = '$order_id' ";  
		$wuliu = $GLOBALS['db']->getAll($sql);
                $kuaidi_list = array();
                foreach($wuliu as $key=>$value){
	require_once '../admin/kuajingDAL.php';
	$kuajingDAL=new kuajingDAL(); 
		$res=$kuajingDAL ->gettrace($order_sn);
		//echo $res;
		//exit;
	$res='{"resultCode":"1","resultData":{"orderNo":"XP0020022921201449962459004670","parcelCode":"4602262070035","domesticTrace":[{"event_detail":"订单包裹开始清关","event_code":"CustomsClearStart","event_location":"DGXT","event_name":"订单包裹开始清关","event_operator":"","event_time":"2020-03-01 07:55:32"},{"event_detail":"订单包裹清关通过","event_code":"CustomsClearPass","event_location":"DGXT","event_name":"订单包裹清关通过","event_operator":"","event_time":"2020-03-01 08:01:59"},{"event_detail":"系统已经收到订单","event_code":"Order","event_location":"","event_name":"系统已经收到订单","event_operator":"","event_time":"2020-03-01 08:46:08"},{"event_detail":"订单包裹在仓库完成预分拣","event_code":"PreSort","event_location":"","event_name":"订单包裹在仓库完成预分拣","event_operator":"","event_time":"2020-03-01 08:46:08"},{"event_detail":"面单打印","event_code":"PrintLabel","event_location":"","event_name":"面单打印","event_operator":"","event_time":"2020-03-01 09:29:12"},{"event_detail":"订单包裹在仓库已出库扫描","event_code":"Outbound","event_location":"虎门保税仓","event_name":"订单包裹在仓库已出库扫描","event_operator":"001","event_time":"2020-03-01 09:48:40"},{"event_detail":"订单包裹已经离开仓库准备运输","event_code":"Mainline","event_location":"虎门保税仓","event_name":"订单包裹已经离开仓库准备运输","event_operator":"EP","event_time":"2020-03-01 10:15:42"},{"event_detail":"订单包裹已揽收扫描","event_code":"DeliveryDispatch","event_location":"广东东莞沙田公司","event_name":"订单包裹已揽收扫描","event_operator":"","event_time":"2020-03-01 20:57:21"},{"event_detail":"派送收件人","event_code":"DeliveryLast","event_location":"","event_name":"派送收件人","event_operator":"","event_time":"2020-03-02 14:28:45"},{"event_detail":"订单包裹已签收","event_code":"Signed","event_location":"","event_name":"订单包裹已签收","event_operator":"","event_time":"2020-03-02 17:08:57"}]},"resultContent":"操作成功"}';
	 $result = json_decode($res,true);
                    $kuaidi_list[$value['delivery_id']]['data'] = $result['resultData']['domesticTrace']; 
                    $kuaidi_list[$value['delivery_id']]['shipping_name'] = $value['shipping_name'];
                    $kuaidi_list[$value['delivery_id']]['invoice_no'] = $value['invoice_no'];
                } 
		 $smarty->assign('order',$order); 
		 $smarty->assign('Traceslength',count($result['resultData']['domesticTrace']));
		 $smarty->assign('kuaidi_list',$kuaidi_list);

	} 
	else
	{
		show_message('您没有权限查看此物流信息！'); 
	}
 

$smarty->display('kuaidi_search.dwt');

?>