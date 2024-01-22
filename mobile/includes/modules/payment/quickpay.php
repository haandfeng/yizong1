<?php

/**
 * ECSHOP 余额支付插件
 
 * $Author: derek $
 * $Id: quickpay.php 17217 2011-01-19 06:29:08Z derek $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/quickpay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'quickpay_desc';

    /* 是否货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'ECSMART TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array();

    return;
}

/**
 * 类
 */
class quickpay
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function quickpay()
    {
    }

    function __construct()
    {
        $this->quickpay();
    }

    /**
     * 提交函数
     */
    function get_code($order, $payment)
    {  
		$sql = "SELECT `monthrate`, `dayrate` FROM `ecs_period_config` WHERE period=".$order['quickpay_period'];
		$periodinfo=$GLOBALS['db']->getRow($sql);
		$needamount=number_format($order['exchange_amount']/$order['quickpay_period'], 2, '.', '');
		$ratemount=number_format($periodinfo['monthrate']*$order['exchange_amount'], 2, '.', '');
		$tax=$ratemount*$order['quickpay_period'];
		
		$orderinfo="";
        $sql = "SELECT * FROM ecs_order_goods WHERE order_id = '". $order['order_id'] ."'";
        $rows = $GLOBALS['db']->getAll($sql);
        foreach ($rows as $row) {
            if($row['goods_price'] > 0){
                $exchange_price  = number_format($row['goods_price']*$GLOBALS['_CFG']['exchange_rate'], 2, '.', '');
                $orderinfo .= $row['goods_name']."x".$row['goods_number'].".";
            }
        }
		 $needdate=""; 
		$curdate=date('Y-m-d',time());
		$intdd=strtotime($curdate);
		$day=date("d",$intdd);
		$BeginDate=date('Y-m-01', strtotime($curdate));
		for ($x=1; $x<=$order['quickpay_period']; $x++) {
			$year=date("Y",strtotime("+".$x." months",strtotime($BeginDate) ));
			$month=date("m",strtotime("+".$x." months",strtotime($BeginDate) ));
			$firstdate=date("Y-m-01",strtotime("+".$x." months",strtotime($BeginDate) ));
			if($day==31)
			{
				$lastdate=date('Y-m-d', strtotime("$firstdate +1 month -1 day"));
			}
			else if($day==30)
			{
				if($month==2)
				$lastdate=date('Y-m-d', strtotime("$firstdate +1 month -1 day"));
				else
					$lastdate=date('Y-m-'.$day, strtotime("$firstdate +1 month -1 day"));
			}
			else if($day==29)
			{
				if($month==2)
				$lastdate=date('Y-m-d', strtotime("$firstdate +1 month -1 day"));
				else
					$lastdate=date('Y-m-'.$day, strtotime("$firstdate +1 month -1 day"));
			}
			else
			{
				$lastdate=date('Y-m-'.$day, strtotime("$firstdate +1 month -1 day"));
			}
		  $needdate.="&needdate".$x."=".$lastdate; 
		} 

	$sql = "select store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
	$fuyuanurl = $GLOBALS['db']->getOne($sql);
	
		$exchange_rate=$order['exchange_amount']/$order['order_amount'];
		
		//订单接入福源
			$url=$fuyuanurl."index.php?a=save&m=mode_periodorders|input&d=flow&ajaxbool=true&rnd=432185&id=";
			$url.="&order_sn=".$order['order_sn'];
			$url.="&fromuser_id=".$order['user_id'];
			$url.="&period=".$order['quickpay_period'];
			$url.="&amount=".$order['exchange_amount'];
			$url.="&amountHDK=".$order['order_amount'];
			$url.="&exchange=".$exchange_rate;
			$url.="&monthrate=".$periodinfo['monthrate'];
			$url.="&tax_amount=".$tax;
			$url.="&dayrate=".$periodinfo['dayrate'];
			$url.="&return_amount=0";
			$url.="&order_info=".urlencode($orderinfo);
			$url.="&needtotalamount=".($needamount+$ratemount);
			$url.="&needamount=".$needamount;
			$url.="&needtaxamount=".$ratemount;
			$url.="&state=0&stateremark=&createtime=".urlencode(date('Y-m-d H:i:s',time()));
			$url.=$needdate;
			$url.="&sysmodeid=95&sysmodenum=periodorders&jk802=0"; 
			$url.="&sign=".md5(md5("order_sn=".$order["order_sn"]."|fromuser_id=".$order["user_id"]."|period=".$order["quickpay_period"]."|iopjkg7867gh84g000f46")); 
			
file_put_contents("log/paytofuyuan_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND);			
			$curl = curl_init(); 
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$out_put = curl_exec($curl); 
			curl_close($curl);  
file_put_contents("log/paytofuyuan_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
			$returnarray = json_decode($out_put,TRUE);
			 if($returnarray["success"]==true&&$returnarray["data"]["id"]>0)
			 {
				$sql = "update ecs_order_info set confirm_time=unix_timestamp(now()),pay_time=unix_timestamp(now()),pay_id=130,order_status=1,pay_status=2 where order_id=".$order['order_id'];
				$aaaa=$GLOBALS['db']->query($sql);
				
				$notuseamount_qc=$returnarray["data"]["notuseamount_qc"];
				$notuseamount=$returnarray["data"]["notuseamount"];
				$useamount=$returnarray["data"]["useamount"];
				$sql = "update ecs_users_period set useamount=".$useamount.",notuseamount=".$notuseamount."  where user_id=".$order['user_id'];
				$GLOBALS['db']->query($sql);
				
				$sql = "INSERT INTO `ecs_period_log`(`order_id`,fuyuan_id, `notuseamount_qc`, `amount`, `notuseamount_qm`, `createtime`) VALUES (";
				$sql.=$order['order_id'].",".$returnarray["data"]["id"].",".$notuseamount_qc.",".$order['exchange_amount'].",".$notuseamount.",NOW())";
				$GLOBALS['db']->query($sql);
				return "";
			 }
			 else
			 {
				$sql = "update ecs_order_info set pay_id=130,order_status=0,pay_status=0 where order_id=".$order['order_id'];
				$aaaa=$GLOBALS['db']->query($sql);
				return $returnarray["msg"];
			 } 
        
    }

    /**
     * 处理函数
     */
    function response()
    {
        return;
    }
	
	
	 
    function getquickpayquery($date_start,$date_end){ 
        
			$sql = "select store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
			$fuyuanurl = $GLOBALS['db']->getOne($sql);
		//2019-08-23
		//订单接入福源
			$url=$fuyuanurl."index.php?a=publicstore&m=mode_periodorders|input&d=flow&modeid=95&ajaxbool=true&rnd=79055&pnum=";
			$url.="&jk802=0&tablename_abc=ee0po0hl0ojo0ep0hp0yp0obp0hh0yb0yp0ojo0eb0po0hl0ojo0ee0ooe0yh0yh05&defaultorder=&keywhere=&where=&sort=&dir=&loadci=3&storebeforeaction=storebeforeshow&storeafteraction=storeaftershow&modenum=periodorders&start=0&page=1&limit=150&atype=&soufields_real_name=&soufields_card=&soufields_createtime_start=".$date_start."&soufields_createtime_end=".$date_end."&key=&search_value="; 
			
file_put_contents("log/searchtofuyuan_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND);			
			$curl = curl_init(); 
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$out_put = curl_exec($curl); 
			curl_close($curl);  
file_put_contents("log/searchtofuyuan_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);
			$returnarray = json_decode($out_put,TRUE); 
        return $returnarray;
    }

	
}

?>