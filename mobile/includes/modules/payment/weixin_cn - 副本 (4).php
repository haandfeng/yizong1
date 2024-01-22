<?php
if (! defined ( 'IN_ECS' )) {
	die ( 'Hacking attempt' );
}

$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS ['_CFG'] ['lang'] . '/payment/weixin.php';

if (file_exists ( $payment_lang )) {
	global $_LANG;
	
	include_once ($payment_lang);
}

/* 模块的基本信息 */
if (isset ( $set_modules ) && $set_modules == TRUE) {
	$i = isset ( $modules ) ? count ( $modules ) : 0;
	
	/* 代码 */
	$modules [$i] ['code'] = basename ( __FILE__, '.php' );
	
	/* 描述对应的语言项 */
	$modules [$i] ['desc'] = 'weixin_desc';
	
	/* 是否支持货到付款 */
	$modules [$i] ['is_cod'] = '0';
	
	/* 是否支持在线支付 */
	$modules [$i] ['is_online'] = '1';
	
	/* 作者 */
	$modules [$i] ['author'] = 'yshop100';
	
	/* 网址 */
	$modules [$i] ['website'] = '';
	
	/* 版本号 */
	$modules [$i] ['version'] = '2.0.0';
	
	/* 配置信息 */
	$modules [$i] ['config'] = array (
			array (
					'name' => 'appId',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'appSecret',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'partnerId',
					'type' => 'text',
					'value' => '' 
			),
			array (
					'name' => 'partnerKey',
					'type' => 'text',
					'value' => '' 
			) 
	// array('name' => 'notify_url', 'type' => 'text', 'value' => ''),
	// array('name' => 'is_instant', 'type' => 'select', 'value' => '0')
	// array('name' => 'alipay_pay_method', 'type' => 'select', 'value' => '')
		);
	
	return;
}

/**
 * 类
 */
class weixin_cn {
	
	/**
	 * 构造函数
	 *
	 * @access public
	 * @param        	
	 *
	 *
	 * @return void
	 */
	function weixin() {
	}
	function __construct() {
		$this->weixin ();
	}
	
	/**
	 * 生成支付代码
	 * 
	 * @param array $order
	 *        	订单信息
	 * @param array $payment
	 *        	支付方式信息
	 */
	function get_code($order, $payment) {
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('qf_log') .
            " WHERE order_id = '{$order[order_id]}' ORDER BY id DESC";
        $final_data = $GLOBALS['db']->getRow($sql);

        $sql_act = 'INSERT';
        if($final_data){
            $sql_act = 'UPDATE';
        }

        if(empty($final_data) || ($final_data['qrcode_endtime'] < time() && $final_data['pay_type'] == 800201) || empty($final_data['qrcode']) || ($final_data['txamt'] != $order['order_amount'] * 100)){
            date_default_timezone_set("Asia/Hong_Kong");
            $url = 'https://osqt.qfpay.com';
            $api_type = '/trade/v1/payment';
            $mchid = 'ZK7M1Zq4AxWJdS0bxQgAraGmgv'; //錢台提供的 mchid
            $app_code = '44E8F05873584C3BBDCDA4ACBF6A74BE'; //錢台提供的 App Code
            $app_key = 'F8E4CE08540C4A3F8901585FBCBAF6BA'; //錢台提供的 App Key
            $now_time = date("Y-m-d H:i:s"); //獲取當前時間

            //// 拼裝 Post 資料 ////
            $fields_string = '';
            $fields = array(
                'mchid' => urlencode($mchid),
                'out_trade_no' => urlencode($order['order_sn']),
                'pay_type' => urlencode(800207),
                'txamt' => urlencode($order['order_amount'] * 100),
                'txdtm' => $now_time,
                'product_name' => $order['order_sn'],
                'sub_openid'=>$_SESSION['wxid'],
            );
            ksort($fields); //字典排序 A-Z 升序台式
            foreach($fields as $key=>$value) {
                $fields_string .= $key.'='.$value.'&' ;
            }
            $fields_string = substr($fields_string , 0 , strlen($fields_string) - 1);

            $sign = '';
            $sign = strtoupper(md5($fields_string . $app_key));
            $header = array();
            $header[] = 'X-QF-APPCODE: ' . $app_code;
            $header[] = 'X-QF-SIGN: ' . $sign;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . $api_type);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            $output = curl_exec($ch);
            curl_close($ch);
            $final_data = json_decode($output, true);

            if($final_data['respcd'] == 0000){

                $updata['syssn']            = $final_data['syssn'];
                $updata['pay_type']         = $final_data['pay_type'];
                $updata['out_trade_no']     = $final_data['out_trade_no'];
                $updata['txamt']            = $final_data['txamt'];
                $updata['txdtm']            = $final_data['txdtm'];
                $updata['sysdtm']           = $final_data['sysdtm'];
                $updata['txcurrcd']         = $final_data['txcurrcd'];
                $updata['qrcode']           = $final_data['qrcode'];
                $updata['qrcode_endtime']   = time() + 270;
                $updata['states']           = 0;

                //if($sql_act == 'INSERT'){
                $updata['order_id']     = $order['order_id'];
                $GLOBALS['db']->autoExecute('ecs_qf_log',$updata,'INSERT');
                //}else{
                //    $GLOBALS['db']->autoExecute('ecs_qf_log',$updata,'UPDATE',"order_id = '{$order[order_id]}'");
                //}
            }
        }
//        print_r($final_data);

        //return '<i ><a href="'.$final_data['qrcode'].'">微信支付</a></i>';

//		$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';
//		define ( APPID, $payment ['appId'] ); // appid
//		define ( APPSECRET, $payment ['appSecret'] ); // appSecret
//		define ( MCHID, $payment ['partnerId'] );
//		define ( KEY, $payment ['partnerKey'] ); // 通加密串
//		define ( NOTIFY_URL, $return_url ); // 成功回调url
//
//		include_once ("weixin/WxPayPubHelper.php");
//		$selfUrl = 'http://' . $_SERVER ['HTTP_HOST'] . $_SERVER ['PHP_SELF'] . '?' . $_SERVER ['QUERY_STRING'];
//		if (! strpos ( $_SERVER ['HTTP_USER_AGENT'], 'MicroMessenger' )) {
//			return $this->natpayHtml ( $order );
//		}
//		if (strpos ( $_SERVER ['QUERY_STRING'], 'act=order_detail' ) !== false) {
//			return $this->natpayHtml ( $order );
//		}
//		$jsApi = new JsApi_pub ();
//
//		if (! isset ( $_GET ['code'] )) {
//			// 触发微信返回code码
//			$url = $jsApi->createOauthUrlForCode ( $selfUrl );
//			Header ( "Location: $url" );exit;
//		} else {
//			// 获取code码，以获取openid
//			$code = $_GET ['code'];
//			$jsApi->setCode ( $code );
//			$openid = $jsApi->getOpenId ();
//		}
//		$unifiedOrder = new UnifiedOrder_pub ();
//		// 设置统一支付接口参数
//		$unifiedOrder->setParameter ( "openid", $openid );
//		$unifiedOrder->setParameter ( "body", $order ['order_sn'] );
//		$unifiedOrder->setParameter ( "out_trade_no", $order ['order_id'] ); // 商户订单号
//		$unifiedOrder->setParameter ( "total_fee", $order ['order_amount'] * 100 ); // 总金额
//		$unifiedOrder->setParameter ( "notify_url", NOTIFY_URL ); // 通知地址
//		$unifiedOrder->setParameter ( "trade_type", "JSAPI" ); // 交易类型
//
//		$prepay_id = $unifiedOrder->getPrepayId();
//		$jsApi->setPrepayId($prepay_id);
//		return $jsApi->getParameters();
	}

	function jsApiPay($params){
        define(APPID,$params['appId']);
        define(MCHID,$params['partnerId']);
        define(KEY,$params['partnerKey']);
        include_once ("weixin/WxPayPubHelper.php");
        $out_trade_no = $params['order_sn'];
        $body = '支付订单：'.$params['order_sn'].'，共：CNY'.$params['order_amount'];
        $order_amount = $params['order_amount'];
        $order_amount = $order_amount*100;
        $notify_url = $params['notify_url'];
        //使用jsapi接口
        $jsApi = new JsApi_pub();
        $openid = $params['openid'];
        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid","$openid");//商品描述
        $unifiedOrder->setParameter("body","$body");//商品描述
        //自定义订单号，此处仅作举例
        $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
        $unifiedOrder->setParameter('fee_type','CNY');
        $unifiedOrder->setParameter("total_fee","$order_amount");//总金额
        $unifiedOrder->setParameter("notify_url",$notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
		
		$sql = "INSERT INTO zs_pay_url_log(paytype, request_url, request_time, order_id) select '微信', '".str_replace("'","''",$jsApiParameters)."', NOW(), order_id from  ecs_order_info where order_sn='".$out_trade_no."'";
		//file_put_contents('20210624.txt',date('Y-m-d H:i:s').$sql."\r\n" , FILE_APPEND); 
        $GLOBALS['db']->query($sql);
        return $jsApiParameters;
    }

	/**
	 * 响应操作
	 */
	function respond() {
		include_once ("weixin/WxPayPubHelper.php");
		// 使用通用通知接口
		$notify = new Notify_pub ();
		// 存储微信的回调
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$notify->saveData ( $xml );
		
		$payment = get_payment ( 'weixin_cn' );
		define ( KEY, $payment ['partnerKey'] ); // 通加密串
		if ($notify->checkSign () == TRUE) {		
			if ($notify->data ["return_code"] == "FAIL") {
				$this->addLog ( $notify, 401 );
			} elseif ($notify->data ["result_code"] == "FAIL") {
				$this->addLog ( $notify, 402 );
			} else {
				$this->addLog ( $notify, 200 );	
				$out_trade_no = $notify->data['out_trade_no'];
				$trade_no = $notify->data['transaction_id'];
				$amount = $notify->data['total_fee'];
				$order_sns = explode('-',$out_trade_no);
				$order_sn = $order_sns[0];
                $order_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn' limit 1");
                $pay_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id = '".$order_info['order_id']."' limit 1");
				if (! check_money ( $pay_info['log_id'], $notify->data ['total_fee']/100 )) {
					$this->addLog ( $notify, 404 );
					return true;
				}
				order_paid ($pay_info['log_id'], 2);
				
			//付款日志 
			$sql = "update zs_pay_url_log set reponse_content='".str_replace("'","''",$xml)."', response_time=NOW() where order_id='".$order_info['order_id']."'";
			file_put_contents('20210628.txt',date('Y-m-d H:i:s').$sql."\r\n" , FILE_APPEND); 
			$GLOBALS['db']->query($sql);
			
				//微信报关
			$url = 'https://www.quickact.net/customs/Custom.weixin.php?out_trade_no='.$out_trade_no.'&transaction_id='.$trade_no.''; 
		    $curl = curl_init(); 
		    curl_setopt($curl, CURLOPT_URL, $url);
		    curl_setopt($curl, CURLOPT_HEADER, 0);
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		    $res = curl_exec($curl);
		    curl_close($curl);
				
			//平台申报
			$sql = "select 1 from ecs_order_info_apply where trade_no='".$trade_no."'";
			if($GLOBALS['db']->getOne($sql)!='1')
			{
				$sql = "insert into ecs_order_info_apply (`order_id`, `order_sn`,haiguan_orderno, `isapply`, `addtime`,`applytype`,trade_no)".
							"select order_id,order_sn,order_sn,0,now(),'orderlist','".$trade_no."' from ecs_order_info where order_sn='".$out_trade_no."'";
				$GLOBALS['db']->query($sql);
			}
			
				echo 'success';exit;
			}
		}else{
			$this->addLog ( $notify, 403 );
		}
		return true;
	}

	//下载对账单
    function downloadbill($bill_date){
        $call_info = array();
        define(APPID,'wx0a9a5bcba6c15a4b');
        define(MCHID,'1525495031');
        define(KEY,'2gx8o01U9x9abastln3ttu66xdtghyha');
        include_once ("weixin/WxPayPubHelper.php");
        $download = new DownloadBill_pub();
        $download->setParameter('bill_date',$bill_date);
        $res = $download->getResult();
        if(strpos('<xml>',$res)){
            $call_info['status'] = 0;
            $call_info['data'] = '账单获取失败：错误描述：'.$res['return_msg'];
        }else{
            $str_start = strpos($res,'`');
            $res = substr_replace($res,'',0,$str_start);
            $str_2 = strpos($res,'Total');
            $res = substr_replace($res,'',$str_2,strlen($res));
            $res = str_replace(array("\r\n", "\r", "\n"),',',$res);
            $res = str_replace('`','',$res);
            $res = rtrim($res, ",");
            $res_arr = explode(",",$res);
            $call_info['status'] = 1;
            $call_info['data'] = $res_arr;
        }
        return $call_info;
    }

	//结算资金的明细
    function settlementquery($usetag,$date_start,$date_end){
        $call_info = array();
        define(APPID,'wx0a9a5bcba6c15a4b');
        define(MCHID,'1525495031');
        define(KEY,'2gx8o01U9x9abastln3ttu66xdtghyha');
        include_once ("weixin/WxPayPubHelper.php");
        $download = new Gettlement_pub();
        $download->setParameter('usetag',$usetag);
        $download->setParameter('date_start',$date_start);
        $download->setParameter('date_end',$date_end);
		
        $res = $download->getResult(); 
        if(strpos('<xml>',$res)){
            $call_info['status'] = 0;
            $call_info['data'] = '明细获取失败：错误描述：'.$res['return_msg'];
        }else{
            libxml_disable_entity_loader(true);
            $valarr = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true); 
            $record_num = $valarr['record_num'];  
			$varstr = 'this is '; 
			$varstr = '';
            for($i=0;$i<$record_num;$i++)
            {
				foreach($valarr['setteinfo_'.strval($i)] as $key=>$value)
				{ 
					$varstr=$varstr.$value.','; 
				} 
            }
			 
            $res_arr = explode(",",$varstr);
            $call_info['status'] = 1;
            $call_info['data'] = $res_arr;
        }
        return $call_info;
    }

	//查询单个订单微信支付信息
    function queryOrderWinPayInfo($out_trade_no){
        $call_info = array();
        define(APPID,'wx0a9a5bcba6c15a4b');
        define(MCHID,'1525495031');
        define(KEY,'2gx8o01U9x9abastln3ttu66xdtghyha');
        include_once ("weixin/WxPayPubHelper.php");
        $download = new OrderQueryPay_pub();
        $download->setParameter('out_trade_no',$out_trade_no);
        $res = $download->getResult();
		if (strpos('<xml>',$res)) {
			return false;
		}
		$postObj = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);

		$jsonStr = json_encode($postObj);
		$call_info = json_decode($jsonStr,true);
        return $call_info;
    }
	
	function addLog($other = array(), $type = 1) {
		$log ['ip'] = $_SERVER['REMOTE_ADDR'];
		$log ['time'] = date('Y-m-d H:i:s');
		$log ['get'] = $_REQUEST;
		$log ['other'] = $other;
		$log = serialize ( $log );
		return $GLOBALS['db']->query( "INSERT INTO " . $GLOBALS['ecs']->table('weixin_paylog') . " (`log`,`type`) VALUES ('$log','$type')" );
	}

	// 生成原生支付二维码
	function natpayHtml($order) {
		if (! strpos ( $_SERVER ['HTTP_USER_AGENT'], 'MicroMessenger' )) {
			$unifiedOrder = new UnifiedOrder_pub ();

			$order['order_id'] = $order['log_id'].'-'.$order['order_amount']*100; 

			// 设置统一支付接口参数
			$return_url = 'http://' . $_SERVER ['HTTP_HOST'].'/respond.php';
			$unifiedOrder->setParameter ( "body", $order ['order_sn'] );
			$unifiedOrder->setParameter ( "out_trade_no", $order ['order_id'] ); // 商户订单号
			$unifiedOrder->setParameter ( "total_fee", $order ['order_amount'] * 100 ); // 总金额
			$unifiedOrder->setParameter ( "notify_url", $return_url ); // 通知地址
			$unifiedOrder->setParameter ( "trade_type", "NATIVE" ); // 交易类型

            //$unifiedOrder->setParameter("fee_type", "HKD");

			$unifiedOrderResult = $unifiedOrder->getResult();
			if ($unifiedOrderResult["return_code"] == "FAIL") {
				return "通信出错：".$unifiedOrderResult['return_msg']."<br>";
			}elseif($unifiedOrderResult["result_code"] == "FAIL"){
				$log_id = $GLOBALS ['db']->getOne ( "SELECT log_id FROM " . $GLOBALS ['ecs']->table ( 'pay_log' ) . "where order_id='{$order ['order_id']}' and is_paid=0 order by log_id desc" );
				if($log_id > 0 && $unifiedOrderResult['err_code'] == 'ORDERPAID'){
					order_paid ( $log_id, 2 );
				}
				return "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
			}
			$product_url = $unifiedOrderResult["code_url"];
			return "<img src='http://qr.liantu.com/api.php?text=" . $product_url . "' alt='扫描进行支付'><iframe src='weixin_order_check.php?oid={$order['order_id']}' style='display:none'></iframe>";
		}
	}
	function get_out_trade_no() {
		include_once ("weixin/WxPayPubHelper.php");
		$out_trade_no='';
		// 使用通用通知接口
		$notify = new Notify_pub ();
		// 存储微信的回调
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$notify->saveData ( $xml );
		$payment = get_payment ( 'weixin_cn' );
		define ( KEY, $payment ['partnerKey'] ); // 通加密串
		if ($notify->checkSign () == TRUE) {		
			if ($notify->data ["return_code"] == "FAIL") { 
			} elseif ($notify->data ["result_code"] == "FAIL") { 
			} else { 
				$out_trade_no = $notify->data['out_trade_no'];
			}
		}
		return $out_trade_no;
	}
}
?>