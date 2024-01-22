<?php

/**
 * ECSHOP 支付宝插件
 
 * $Author: douqinghua $
 * $Id: alipay.php 17217 2011-01-19 06:29:08Z douqinghua $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/alipay.php';

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
    $modules[$i]['desc']    = 'alipay_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.alipay.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.2';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'alipay_account',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_key',               'type' => 'text',   'value' => ''),
        array('name' => 'alipay_partner',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_pay_method',        'type' => 'select', 'value' => ''),//接口
        array('name' => 'alipay_pay_currency',        'type' => 'text', 'value' => '')//币种
    );

    return;
}

/**
 * 类
 */
class alipay
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */

    /* 代码修改_start  By  www.yshop100.com */
    function __construct()
    {
        $this->alipay();
    }

     function alipay()
    {
    }
    /* 代码修改_end  By  www.yshop100.com */

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        if (!defined('EC_CHARSET'))
        {
            $charset = 'utf-8';
        }
        else
        {
            $charset = EC_CHARSET;
        }

        $real_method = $payment['alipay_pay_method'];

        switch ($real_method){
            case '0':
                $service = 'trade_create_by_buyer';
                break;
            case '1':
                $service = 'create_partner_trade_by_buyer';
                break;
            case '2':
                $service = 'create_direct_pay_by_user';
                break;
            case '3'://境外
                $service = 'create_forex_trade';
                break;
        }

        $extend_param = 'isv^sh22';

        $parameter = array(
            'extend_param'      => $extend_param,
            'service'           => $service,
            'partner'           => $payment['alipay_partner'],
            //'partner'           => ALIPAY_ID,
            '_input_charset'    => $charset,
            'notify_url'        => return_url(basename(__FILE__, '.php')),
            'return_url'        => return_url(basename(__FILE__, '.php')),
            /* 业务参数 */
            'subject'           => $order['order_sn'],
            'out_trade_no'      => $order['order_sn'],

            'quantity'          => 1,
            'payment_type'      => 1,
            /* 物流参数 */
            'logistics_type'    => 'EXPRESS',
            'logistics_fee'     => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            /* 买卖双方信息 */
            'seller_email'      => $payment['alipay_account']
        );

        if($real_method == 3){
            $parameter['currency'] = $payment['alipay_pay_currency'];
            $parameter['total_fee'] = $order['order_amount'];
        }else{
            $parameter['price'] = $order['order_amount'];
        }

        ksort($parameter);
        reset($parameter);

        $param = '';
        $sign  = '';

        foreach ($parameter AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }

        $param = substr($param, 0, -1);
        $sign  = substr($sign, 0, -1). $payment['alipay_key'];
        //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;

        $button = 'https://mapi.alipay.com/gateway.do?'.$param. '&sign='.md5($sign).'&sign_type=MD5';
        //写入数据库
        $sql = "select id from zs_pay_url_log where order_id='".$order['order_id']."'";
        $rowid    = $GLOBALS['db']->getOne($sql);
        if($rowid['id']<>"")
        {
            $sql = "update zs_pay_url_log set request_url='https://mapi.alipay.com/gateway.do?".str_replace("'","''",$param)."',paytype='支付宝',request_time=NOW() where order_id='".$order['order_id']."'";
            $GLOBALS['db']->query($sql);
        }
        else
        {
            $sql = "INSERT INTO zs_pay_url_log(paytype, request_url, request_time, order_id) VALUES ('支付宝', 'https://mapi.alipay.com/gateway.do?".str_replace("'","''",$param)."', NOW(), '".$order['order_id']."')";
            $GLOBALS['db']->query($sql);
        }
        file_put_contents("paylog/".date('Ymd',time())."_payurl.txt",date('H:i:s',time()).' sql:'.$sql.'\r\n', FILE_APPEND);
        return $button;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        if (!empty($_POST))
        {
            foreach($_POST as $key => $data)
            {
                $_GET[$key] = $data;
            }
        }
        $payment  = get_payment($_GET['code']);

        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = str_replace($_GET['subject'], '', $_GET['out_trade_no']);
        $order_sn = trim(addslashes($order_sn));

        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);

        $sign = '';
        foreach ($_GET AS $key=>$val)
        {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code')
            {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $payment['alipay_key'];
//        print_r(md5($sign));echo '-----'.$_GET['sign'];die;
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        if (md5($sign) != $_GET['sign'])
        {
            return false;
        }
        /* 取得订单信息 */ //jx
        $sql = 'SELECT pl.log_id FROM ' . $GLOBALS['ecs']->table('pay_log') .
            " AS pl LEFT JOIN ". $GLOBALS['ecs']->table('order_info') ." AS oi ON (pl.order_id = oi.order_id) WHERE oi.order_sn = '$order_sn' ";
        file_put_contents("test.txt",$sql."\n", FILE_APPEND);
        $orderinfo    = $GLOBALS['db']->getRow($sql);
        $order_sn = $orderinfo['log_id'];

        //echo check_money($order_sn, $_GET['total_fee']);die;
        /* 检查支付的金额是否相符 */
        if (!check_money($order_sn, $_GET['total_fee']))
        {
            return false;
        }

        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS')
        {
            /* 改变订单状态 */
            order_paid($order_sn, 2);

            return true;
        }
        elseif ($_GET['trade_status'] == 'TRADE_FINISHED')
        {
            /* 改变订单状态 */
            order_paid($order_sn);

            return true;
        }
        elseif ($_GET['trade_status'] == 'TRADE_SUCCESS')
        {


            /* 改变订单状态 */
            order_paid($order_sn, 2);

            return true;
        }
        else
        {
            return false;
        }
    }
}

?>