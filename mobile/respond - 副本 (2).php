<?php

/**
 * ECSHOP 支付响应页面
 
 * $Author: liubo $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');
/* 支付方式代码 */
$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : 'weixin';
/*--start日志地址*/
$data="https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
date_default_timezone_set('PRC'); 
file_put_contents("paylog/".date('Y-m-d',time())."_pc.txt",date('H:i:s',time())."\r\n".$data."\r\n", FILE_APPEND);

$data=file_get_contents('php://input');
file_put_contents("test1.txt",$data."\n", FILE_APPEND);

/*--日志内容*/
file_put_contents("paylog/".date('Y-m-d',time())."_pc.txt",date('H:i:s',time())." :\r\n".$data."\r\n", FILE_APPEND);
file_put_contents("paylog/".date('Y-m-d',time())."_pc.txt","--------------------------------------------------------------------------------------\r\n", FILE_APPEND);
/*--end日志*/

//微信获取支付名称
if (!empty($data)) {
    $obj = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
    $post_data = json_decode(json_encode($obj), true);
    if($post_data['appid']){
        $sql = "SELECT token FROM " . $ecs->table('weixin_config') . " WHERE appid = '" . $post_data['appid'] . "' LIMIT 1";
        $row = $db->getOne($sql);
        if($row){
            $pay_code = $row;
            $_GET['code'] = $row;
        }
    }
}

//获取首信支付方式
if (empty($pay_code) && !empty($_REQUEST['v_pmode']) && !empty($_REQUEST['v_pstring']))
{
    $pay_code = 'cappay';
}

//获取快钱神州行支付方式
if (empty($pay_code) && ($_REQUEST['ext1'] == 'shenzhou') && ($_REQUEST['ext2'] == 'ecshop'))
{
    $pay_code = 'shenzhou';
}

/* 参数是否为空 */
if (empty($pay_code))
{
    $msg = $_LANG['pay_not_exist'];
}
else
{
    /* 检查code里面有没有问号 */
    if (strpos($pay_code, '?') !== false)
    {
        $arr1 = explode('?', $pay_code);
        $arr2 = explode('=', $arr1[1]);

        $_REQUEST['code']   = $arr1[0];
        $_REQUEST[$arr2[0]] = $arr2[1];
        $_GET['code']       = $arr1[0];
        $_GET[$arr2[0]]     = $arr2[1];
        $pay_code           = $arr1[0];
    }
	if($pay_code=='xcx')
	{
		$pay_code='weixin_cn';
	}
    /* 判断是否启用 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
    if ($db->getOne($sql) == 0)
    {
        $msg = $_LANG['pay_disabled'];
    }
    else
    {
        $plugin_file = ROOT_PATH.'includes/modules/payment/' . $pay_code . '.php';

        /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
        if (file_exists($plugin_file))
        {
            /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
            include_once($plugin_file);

            $payment = new $pay_code();

            $msg     = (@$payment->respond()) ? $_LANG['pay_success'] : $_LANG['pay_fail'];
            if($_GET['code'] == 'alipay_cn')
            {
                $out_trade_no=trim($_GET['out_trade_no']);
                $trade_no=trim($_GET['trade_no']);
                $amount= $_GET['total_fee']; 
                update_pay_url_response($data,$out_trade_no);
                if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS'||$_GET['trade_status'] == 'TRADE_FINISHED'||$_GET['trade_status'] == 'TRADE_SUCCESS')
                {
                    PostAction_baoguan($out_trade_no,$trade_no,$amount);
                }
            }
        }
        else
        {
            $msg = $_LANG['pay_not_exist'];
        }
    }
}

    
    function PostAction_baoguan($out_trade_no,$trade_no,$amount)
    {
        $baoguanid =get_baoguanID($out_trade_no,$trade_no,$amount);
        
            $url = 'https://www.quickact.net/customs/alipayapi_get.php?WIDout_request_no='.$baoguanid.'&WIDtrade_no='.$trade_no.'&WIDmerchant_customs_code=4401963H95&WIDmerchant_customs_name=广州快闪商贸有限公司&WIDamount='.$amount.'&WIDcustoms_place=ZONGSHU&WIDapi_type=acquire';
            file_put_contents("customs/log/".date('Y-m-d',time())."_customs.txt",date('H:i:s',time())."\r\n支付上报：".$url."\r\n", FILE_APPEND);
          $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            $res = curl_exec($curl);     //返回api的json对象
            //关闭URL请求
            curl_close($curl);
            
        file_put_contents("customs/log/".date('Y-m-d',time())."_customs.txt",date('H:i:s',time())."\r\n上报结果：".$res."\r\n", FILE_APPEND);
        update_baoguan($baoguanid);
    
    }
    
    function get_baoguanID($out_trade_no,$trade_no,$amount)
    {
        $baoguanid ="";
        //写入数据库
        $sql = "SELECT out_trade_no FROM zs_purchaseorder_alipay WHERE out_trade_no = '$out_trade_no' ";
        $res_id=$GLOBALS['db']->getOne($sql);
        if(empty($res_id))
        {
            //date('Y-m-d',time())
            $sql = "SELECT REPLACE(max(`id`),'".date('Ymd',time())."','') FROM zs_purchaseorder_alipay WHERE id like '".date('Ymd',time())."%'";
            $res_id=$GLOBALS['db']->getOne($sql);
            if ($res_id=="")
            {
                $res_id="1";
            }
            $baoguanid=date('Ymd',time()).str_pad(intval($res_id)+1,4,"0",STR_PAD_LEFT);
             //支付宝申报
            $sql = "insert into zs_purchaseorder_alipay (`id`, `out_trade_no`, `trade_no`, `amount`,addtime)".
                        "values('$baoguanid','$out_trade_no','$trade_no','$amount',now())";
            $GLOBALS['db']->query($sql);
            //平台申报
            $sql = "insert into ecs_order_info_apply (`order_id`, `order_sn`, `isapply`, `addtime`,`applytype`,trade_no)".
                        "select order_id,order_sn,0,now(),'orderlist','$trade_no' from ecs_order_info where order_sn='$out_trade_no'";
            $GLOBALS['db']->query($sql);
        }
        else
        {
            $baoguanid =$res_id;
        }
        return $baoguanid;
        
    }
    
    function update_pay_url_response($data,$order_sn)
    {
        $sql = "select order_id from  ecs_order_info where order_sn='$order_sn'";
        $order_id=$GLOBALS['db']->getOne($sql);
        //更新数据库
        $sql = "update zs_pay_url_log set reponse_content='".str_replace("'","''",$data)."',response_time=now() where order_id='$order_id'";
        $GLOBALS['db']->query($sql);
    }
    function update_baoguan($baoguanid)
    {
        //更新数据库
        $sql = "update zs_purchaseorder_alipay set  isapply=1,  applytime=now() where id='$baoguanid'";
        $GLOBALS['db']->query($sql);
    }
assign_template();
$position = assign_ur_here();
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('helps',      get_shop_help());      // 网店帮助

$smarty->assign('message',    $msg);
$smarty->assign('shop_url',   $ecs->url());

$smarty->display('respond.dwt');

?>