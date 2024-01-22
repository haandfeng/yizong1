<?php

/**
 * ECSHOP 支付响应页面

 * $Author: liubo $
 * $Id: respondwx.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');
/* 支付方式代码 */
$pay_code="wxnative";
$_GET["code"]="wxnative";

error_reporting(E_ALL);
//ini_set('display_errors', '1');
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

file_put_contents("test.txt",date('Y-m-d H:i:s',time()).":\n",FILE_APPEND);

//file_put_contents("test.txt","Parse  Response Header \n", FILE_APPEND);
//foreach (getallheaders() as $name => $value) {
//    file_put_contents("test.txt",$name.":".$value."\n", FILE_APPEND);
//}
$data=file_get_contents('php://input');
file_put_contents("test.txt",$data."\n", FILE_APPEND);


$header = getallheaders();
$qf_sign = $header['X-Qf-Sign'];
$arr = json_decode($data,true, 512, JSON_BIGINT_AS_STRING);
$app_key = 'F8E4CE08540C4A3F8901585FBCBAF6BA'; //錢台提供的 App Key
$fields_string = '';
ksort($arr); //字典排序 A-Z 升序台式
foreach($arr as $key=>$value) {
    $fields_string .= $key.'='.$value.'&' ;
}

$fields_string = substr($fields_string , 0 , strlen($fields_string) - 1);
$sign = '';
$sign = strtoupper(md5($fields_string . $app_key));

//qf_sign means the md5 value from response header.Note:product_name is not in notify url.
//file_put_contents("test.txt",'sign:'.$sign."------------qf_sign:".$qf_sign."\n", FILE_APPEND);
if($sign != $qf_sign){
    echo '签名错误';
}else{
    if($arr['respcd'] == '0000'){
        $syssn = $arr['syssn'];

        $order_id = $GLOBALS ['db']->getOne ( "SELECT order_id FROM " . $GLOBALS ['ecs']->table ( 'qf_log' ) . "where syssn='{$syssn}'" );
        $log_id = $GLOBALS ['db']->getOne ( "SELECT log_id FROM " . $GLOBALS ['ecs']->table ( 'pay_log' ) . "where order_id='{$order_id}' and is_paid=0 order by log_id desc" );

        $updata['status'] = 1;
        $GLOBALS ['db']->autoExecute('ecs_qf_log',$updata,'UPDATE',"syssn = {$syssn}");

        order_paid($log_id, 2);
        file_put_contents("test.txt",'order_id:'.$order_id."----log_id".$log_id."----syssn".$syssn."\n", FILE_APPEND);
        echo 'success';
    }

}


//function logResultx($word = '',$var=array()) {
//    $output = $word . print_r($var, true);
//    $fp = fopen(ROOT_PATH . "/data/log/wxnative.txt", "a");
//    flock($fp, LOCK_EX);
//    fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $output . "\n");
//    flock($fp, LOCK_UN);
//    fclose($fp);
//}
//
//logResultx("resposdwx:",$_GET);
//
///* 判断是否启用 */
//$sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
//if ($db->getOne($sql) == 0)
//{
//    $msg = $_LANG['pay_disabled'];
//}
//else
//{
//    $plugin_file = 'includes/modules/payment/' . $pay_code . '.php';
//
//    /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
//    if (file_exists($plugin_file))
//    {
//        /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
//        include_once($plugin_file);
//
//        $payment = new $pay_code();
//        $msg     = (@$payment->qf_respond()) ? $_LANG['pay_success'] : $_LANG['pay_fail'];
//    }
//    else
//    {
//        $msg = $_LANG['pay_not_exist'];
//    }
//
//}

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