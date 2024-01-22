<?php

/**
 * ECSHOP 会员中心
 
 * $Author: derek $
 * $Id: user.php 17217 2011-01-19 06:29:08Z derek $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');

//判断是否有邀请码且没有处于登录状态
if(@$_SESSION['register']['invite'] && $_SESSION['user_id'] == '')
{
    header("Location:register.php");
}


/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');

/*新版微信改动*/
if(isset($_GET['wxid']) && !isset($_GET['is_update']))
{
	if(inject_check($_GET['wxid'])){
		show_message("参数错误", '返回主页', 'index.php', 'info');
	}

	$sql = "SELECT ecuid FROM " . 
			$GLOBALS['ecs']->table('weixin_user') . 
			" WHERE fake_id  = '" . $_GET['wxid'] . "'"; 
	$ecuid = $GLOBALS['db']->getOne($sql);
	if($ecuid > 0)
	{
		 //已绑定标识
		 $smarty->assign('tag','1');
		 $smarty->assign('shop_name',$_CFG['shop_name']);
		 $smarty->display('weixin_open.dwt');
		 exit;
	}
}
if(isset($_GET['wxid']))
{
	$_SESSION['wxid'] = $_GET['wxid'];
}
else
{
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')&&empty($_REQUEST['nocapt']))
	{//file_put_contents('20191014.txt',"33\r\n" , FILE_APPEND); 
		require(dirname(__FILE__) . '/weixin/wechat.class.php');
		$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
		$weixin = new core_lib_wechat($weixinconfig);
		if($_GET['code']){			
			$json = $weixin->getOauthAccessToken();			
			if($json['openid'])
			{
				$_SESSION['wxid'] = $json['openid']; 
			}
		}		
		if(!isset($_SESSION['wxid']))
		{ 
			if(! empty($_SERVER['QUERY_STRING']))
			{
				$back_act = 'user.php?' . strip_tags($_SERVER['QUERY_STRING']);
				setcookie('back_act', $back_act, time()+3600);
				$url = $GLOBALS['ecs']->url().$back_act;
			}else{
				$url = $GLOBALS['ecs']->url()."/user.php";
			}
			$url = $weixin->getOauthRedirect($url,1,'snsapi_base');
			header("Location:$url");exit;
		}
	}
}
/*end*/

$user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$back_act = '';

// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr = array(
	'login','act_login','act_edit_password','get_password','send_pwd_email','password','signin','add_tag','collect','return_to_cart','logout','email_list','validate_email','send_hash_mail','order_query','is_registered','check_email','clear_history','qpassword_name','get_passwd_question','check_answer','oath','oath_login','other_login','getverifycode','act_forget_pass','re_pass','open_surplus_password','close_surplus_password','check_register','book_goods'
);

/* 显示页面的action列表 */
$ui_arr = array(
	'login','profile','order_list','order_detail','address_list','collection_list','message_list','tag_list',
        'get_password','reset_password','booking_list','add_booking','account_raply','account_deposit','account_log',
        'account_detail','act_account','pay','default','bonus','group_buy','group_buy_detail','affiliate','comment_list',
       'validate_email','track_packages','transform_points','qpassword_name','get_passwd_question','check_answer','vc_login_act',
       'vc_login','ck_email','forget_password','act_forget_pass','re_pass','forget_surplus_password','act_forget_surplus_password',
       'update_surplus_password','act_update_surplus_password','verify_reset_surplus_email','get_verify_code','check_register',
       'supplier_reg','comment_order','address','set_address','follow_shop','account_manage','my_comment','shaidan_send','back_order',
		'back_order_act','back_list', 'center', 'binding_mobile', 'fenxiao'
);

$not_login_arr[] = 'send_mobile_code';
$not_login_arr[] = 'send_email_code';
$not_login_arr[] = 'check_mobile';

if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'))
{
	$smarty->assign('iswei', 1); // 判断是否为微信
}

/* 未登录处理 */
if(empty($_SESSION['user_id']))
{
	if(! in_array($action, $not_login_arr))
	{
		if(in_array($action, $ui_arr))
		{			
			if(! empty($_SERVER['QUERY_STRING']))
			{
				$back_act = 'user.php?' . strip_tags($_SERVER['QUERY_STRING']);
			}
			$action = 'login';
		}
		else
		{
			// 未登录提交数据。非正常途径提交数据！
// 			die($_LANG['require_login']);
			show_message($_LANG['require_login'], array('</br>登录', '</br>返回首页'), array('user.php?act=login', $ecs->url()), 'error', false);
		}
	}
}else{
	setcookie("back_act", "", time()-3600);
}

/* 如果是显示页面，对页面进行相应赋值 */
if(in_array($action, $ui_arr))
{
	require_once (ROOT_PATH . 'includes/lib_v_user.php');
	if($_CFG['is_distrib'] == 0)
	{
		$is_distrib = 0;
	}
	else
	{
		$is_distribor = is_distribor($user_id);
		if($is_distribor == 1)
		{
			$is_distrib = 1;
		} 
		else
		{
			$is_distrib = 0; 
		}
	}
	//echo $is_distribor;die();
	assign_template();
	$position = assign_ur_here(0, $_LANG['user_center']);
	$smarty->assign('page_title', $position['title']); // 页面标题
	$smarty->assign('ur_here', $position['ur_here']);
	$sql = "SELECT value FROM " . $ecs->table('ecsmart_shop_config') . " WHERE id = 419";
	$row = $db->getRow($sql);
	$car_off = $row['value'];
	$smarty->assign('car_off', $car_off);
	/* 是否显示积分兑换 */
	if(! empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
	{
		$smarty->assign('show_transform_points', 1);
	}
	$smarty->assign('helps', get_shop_help()); // 网店帮助
	$smarty->assign('data_dir', DATA_DIR); // 数据目录
	$smarty->assign('action', $action);
	$smarty->assign('lang', $_LANG);
	//echo $is_distrib;die();
	$smarty->assign('is_distrib',$is_distrib);
}


/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_default";
}

call_user_func($function_name);

/* 评价订单 */
function action_comment_order(){
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
        $rec_id =  empty($_REQUEST['rec_id'])? 0 : intval($_REQUEST['rec_id']);
        $goods_id = empty($_REQUEST['goods_id'])? $db->getOne("select goods_id from ".$ecs->table('order_goods')." where rec_id = $rec_id") : intval($_REQUEST['goods_id']);
        $goods = get_goods_info_wap($goods_id);
        $smarty -> assign('goods',$goods);
        $smarty -> assign('rec_id',$rec_id);
        $order_id = get_order_id_wap($rec_id);
        $smarty -> assign('order_id',$order_id);
        $smarty -> assign('goods_id',$goods_id);

        $smarty -> display('comment_order.dwt');        
}
/* 路由 */

function action_account_manage(){
    	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$smarty->assign('user_info', get_user_info());
    $smarty->display('user_transaction.dwt');
}

function action_supplier_reg()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$sql = "select * from " . $ecs->table('supplier') . " where user_id='" . $_SESSION['user_id'] . "' ";
	$supplier = $db->getRow($sql);
	
	$smarty->assign('supplier', $supplier);
	
	$supplier_country = $supplier['country'] ? $supplier['country'] : $_CFG['shop_country'];
	$smarty->assign('country_list', get_regions());
	$smarty->assign('province_list', get_regions(1, $supplier_country));
	$smarty->assign('city_list', get_regions(2, $supplier['province']));
	$smarty->assign('district_list', get_regions(3, $supplier['city']));
        $smarty->assign('xiangcun_list', get_regions(4, $supplier['district']));//morestock_morecity
	$smarty->assign('supplier_country', $supplier_country);
	
	$sql = "select rank_id,rank_name from " . $ecs->table('supplier_rank') . " order by sort_order";
	$supplier_rank = $db->getAll($sql);
	$smarty->assign('supplier_rank', $supplier_rank);
	
	$company_type = explode("\n", str_replace("\r\n", "\n", $_CFG['company_type']));
	$smarty->assign('company_type', $company_type);
	
	$smarty->assign('user_id', $_SESSION['user_id']);
	$smarty->assign('mydomain', $ecs->url());

	$smarty->display('user_transaction.dwt');
}
function action_act_supplier_reg()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	
	$supplier_name = isset($_POST['supplier_name']) ? trim($_POST['supplier_name']) : '';
	$rank_id = isset($_POST['rank_id']) ? intval($_POST['rank_id']) : 0;
	$company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
	$country = isset($_POST['country']) ? intval($_POST['country']) : 1;
	$province = isset($_POST['province']) ? intval($_POST['province']) : 1;
	$city = isset($_POST['city']) ? intval($_POST['city']) : 1;
	$district = isset($_POST['district']) ? intval($_POST['district']) : 1;
	$country = isset($_POST['country']) ? intval($_POST['country']) : 1;
	$address = isset($_POST['address']) ? trim($_POST['address']) : '';
	$tel = isset($_POST['tel']) ? trim($_POST['tel']) : '';
	$guimo = isset($_POST['guimo']) ? trim($_POST['guimo']) : '';
	$email = isset($_POST['email']) ? trim($_POST['email']) : '';
	$company_type = isset($_POST['company_type']) ? trim($_POST['company_type']) : '';
	$bank = isset($_POST['bank']) ? trim($_POST['bank']) : '';
	$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
	$contact_back = isset($_POST['contact_back']) ? trim($_POST['contact_back']) : '';
	$contact_shop = isset($_POST['contact_shop']) ? trim($_POST['contact_shop']) : '';
	$contact_yunying = isset($_POST['contact_yunying']) ? trim($_POST['contact_yunying']) : '';
	$contact_shouhou = isset($_POST['contact_shouhou']) ? trim($_POST['contact_shouhou']) : '';
	$contact_caiwu = isset($_POST['contact_caiwu']) ? trim($_POST['contact_caiwu']) : '';
	$contact_jishu = isset($_POST['contact_jishu']) ? trim($_POST['contact_jishu']) : '';
	$add_time = gmtime();
	
	/* 图片上传处理 */
	$upload_size_limit = $_CFG['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $_CFG['upload_size_limit'];
	$last_char = strtolower($upload_size_limit{strlen($upload_size_limit) - 1});
	switch($last_char)
	{
		case 'm':
			$upload_size_limit *= 1024 * 1024;
			break;
		case 'k':
			$upload_size_limit *= 1024;
			break;
	}
	if(isset($_FILES['zhizhao']) && $_FILES['zhizhao']['tmp_name'] != '' && isset($_FILES['zhizhao']['tmp_name']) && $_FILES['zhizhao']['tmp_name'] != 'none')
	{
		if($_FILES['zhizhao']['size'] / 1024 > $upload_size_limit)
		{
			$GLOBALS['err']->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
			$GLOBALS['err']->show($_LANG['back_up_page']);
		}
		$zhizhao_img = upload_file($_FILES['zhizhao'], 'supplier');
		if($zhizhao_img === false)
		{
			$GLOBALS['err']->add('业执照图片上传失败！');
			$GLOBALS['err']->show($_LANG['back_up_page']);
		}
		else
		{
			$sql_img = "zhizhao='$zhizhao_img',";
		}
	}
	if(isset($_FILES['id_card']) && $_FILES['id_card']['tmp_name'] != '' && isset($_FILES['id_card']['tmp_name']) && $_FILES['id_card']['tmp_name'] != 'none')
	{
		if($_FILES['id_card']['size'] / 1024 > $upload_size_limit)
		{
			$GLOBALS['err']->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
			$GLOBALS['err']->show($_LANG['back_up_page']);
		}
		$id_card_img = upload_file($_FILES['id_card'], 'supplier');
		if($id_card_img === false)
		{
			$GLOBALS['err']->add('身份证图片上传失败！');
			$GLOBALS['err']->show($_LANG['back_up_page']);
		}
		else
		{
			$sql_img .= "id_card='$id_card_img', ";
		}
	}
	
	$sql = "select supplier_id from " . $ecs->table('supplier') . " where user_id='$user_id' ";
	$supplier_id = $db->getOne($sql);
	
	if($supplier_id)
	{
		$mes = '供货商申请修改成功，已经重新进入审核流程，请留意审核结果！';
		$sql = "update " . $ecs->table('supplier') . " set supplier_name='$supplier_name', rank_id='$rank_id', company_name='$company_name', " . "country='$country', province='$province', city='$city', district='$district', address='$address', tel='$tel', guimo='$guimo', email='$email', " . "company_type='$company_type', bank='$bank', " . $sql_img . " contact='$contact', contact_back='$contact_back', contact_shop='$contact_shop', contact_yunying='$contact_yunying', contact_shouhou='$contact_shouhou', contact_caiwu='$contact_caiwu', contact_jishu='$contact_jishu'," . "status='0' " . " where supplier_id='$supplier_id' ";
	}
	else
	{
		$mes = '供货商申请提交成功，已经进入审核流程，请留意审核结果！';
		$sql = "insert into " . $ecs->table('supplier') . "(user_id, supplier_name, rank_id, company_name, country, province, city, district, address, tel, guimo, email," . "company_type, bank, zhizhao, id_card, contact, contact_back, contact_shop, contact_yunying, contact_shouhou, contact_caiwu, contact_jishu, add_time) " . " values('$user_id', '$supplier_name', '$rank_id', '$company_name', '$country', '$province', '$city', '$district', '$address', '$tel', '$guimo', '$email', " . "'$company_type', '$bank',  '$zhizhao_img', '$id_card_img',  '$contact', '$contact_back', '$contact_shop', '$contact_yunying', '$contact_shouhou', '$contact_caiwu', '$contact_jishu', '$add_time')";
	}
	$db->query($sql);
	show_message($mes, '返回上一页', 'user.php?act=supplier_reg', 'info');
}
function action_act_supplier_del()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$userid = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$supid = isset($_POST['supid']) ? intval($_POST['supid']) : 0;
	if(empty($userid) || empty($supid))
	{
		show_message('请刷新页面，重新操作！', '返回上一页', 'user.php?act=supplier_reg', 'wrong');
	}
	if($userid != $user_id)
	{
		show_message('你没权限删除此申请！', '返回首页', '', 'wrong');
	}
	$sql = "select supplier_id from " . $ecs->table('supplier') . " where user_id='$user_id'";
	$supplier_id = $db->getOne($sql);
	if($supid != $supplier_id)
	{
		show_message('你没权限删除此申请！', '返回首页', '', 'wrong');
	}
	$sql = "delete from " . $ecs->table('supplier') . "  where supplier_id=" . $supplier_id;
	$db->query($sql);
	show_message('操作成功！', '返回上一页', 'user.php', 'info');
}
// 用户中心欢迎页
function action_default()
{
	//全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
        $ex_where = " and user_id=$user_id and extension_code <> 'virtual_good'" ;
        include_once (ROOT_PATH . 'includes/lib_order.php');
	/* 全部订单*/
        $order_count['all'] = $db->GetOne('SELECT COUNT(*) FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where");
	/* 已完成的订单 */
	$order_count['finished'] = $db->GetOne('SELECT COUNT(*) FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('finished'));
	$status['finished'] = CS_FINISHED;
	
	/* 待发货的订单： */
	$order_count['await_ship'] = $db->GetOne('SELECT COUNT(*)' . ' FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_ship'));
	$status['await_ship'] = CS_AWAIT_SHIP;
	
	/* 待付款的订单： */
	$order_count['await_pay'] = $db->GetOne('SELECT COUNT(*)' . ' FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_pay'));
	$status['await_pay'] = CS_AWAIT_PAY;

        /* 待收货的订单　*/
	$order_count['await_receipt'] = $db->GetOne("SELECT COUNT(*) FROM " . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_receipt'));
	$status['await_receipt'] = CS_AWAIT_RECEIPT;
        
	/* “未确认”的订单 */
	$order_count['unconfirmed'] = $db->GetOne('SELECT COUNT(*) FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('unconfirmed'));
	$status['unconfirmed'] = OS_UNCONFIRMED;
        $smarty->assign('order_count', $order_count);
	$collect_count = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('collect_goods') . " where user_id = " . $user_id);
	$smarty->assign('collect_count', $collect_count);
	$comment_count = $GLOBALS['db']->getOne("select count(*) from " . $GLOBALS['ecs']->table('comment') . " where user_id = " . $user_id);
	$smarty->assign('comment_count', $comment_count);
	include_once (ROOT_PATH . 'includes/lib_clips.php');
	if($rank = get_rank_info())
	{
		$smarty->assign('rank_name', sprintf($rank['rank_name']));
		if(! empty($rank['next_rank_name']))
		{
			$smarty->assign('next_rank_name', sprintf($_LANG['next_level'], $rank['next_rank'], $rank['next_rank_name']));
		}
	}

	$headimg = $db->getOne("select headimg from " . $ecs->table('users') . " where user_id = $user_id");


        if(!$headimgurl)
        {
			if($headimg){
				$smarty->assign('headimgurl',$headimg);
			}

        }else{
			$sql = "select headimgurl from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid = '$user_id'";
			$headimgurl = $GLOBALS['db']->getOne($sql);
			$smarty->assign('headimgurl', $headimgurl);
        }
	
	$recomm = $db->getOne("SELECT is_recomm FROM " . $GLOBALS['ecs']->table('user_rank') . " r" . " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON r.rank_id = u.user_rank" . " WHERE u.user_id = '$user_id'");
	
	$smarty->assign('recomm', $recomm);
	//申请成为分销商
	if($_GET['applyid'] == 1){
        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('users') . " SET is_fenxiao=1,status=2 WHERE user_id = '$user_id'");
	}

    $is_fenxiao = $db->getOne("select is_fenxiao from " . $ecs->table('users') . " where user_id = $user_id and status = '1'");
    $smarty->assign('is_fenxiao', $is_fenxiao);
	$smarty->assign('info', get_user_default($user_id));
	$smarty->assign('user_notice', $_CFG['user_notice']);
	$smarty->assign('prompt', get_user_prompt($user_id));

	$smarty->display('user_clips.dwt');
}

function action_check_mobile()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if($_REQUEST['mobile'])
	{
		$sql = "select user_id from " . $ecs->table('users') . " where mobile_phone = '$_REQUEST[mobile]' ";
		$user_id = $db->getOne($sql);
		if($user_id)
		{
			echo 'no';
		}
		else
		{
			echo 'yes';
		}
	}
	else
	{
		echo 'yes';
	}
}


/* 发送注册邮箱验证码到邮箱 */
function action_send_email_code ()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];

	require_once (ROOT_PATH . 'includes/lib_validate_record.php');

	$email = trim($_REQUEST['email']);

	if(empty($email))
	{
		exit("邮箱不能为空");
		return;
	}
	else if(! is_email($email))
	{
		exit("邮箱格式不正确");
		return;
	}
	else if(check_validate_record_exist($email))
	{

		$record = get_validate_record($email);

		/**
		 * 检查是过了限制发送邮件的时间
		*/
		if(time() - $record['last_send_time'] < 60)
		{
			echo ("每60秒内只能发送一次注册邮箱验证码，请稍候重试");
			return;
		}
	}

	require_once (ROOT_PATH . 'includes/lib_passport.php');

	/* 设置验证邮件模板所需要的内容信息 */
	$template = get_mail_template('reg_email_code');
	// 生成邮箱验证码
	$email_code = rand_number(6);

	$GLOBALS['smarty']->assign('email_code', $email_code);
	$GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
	$GLOBALS['smarty']->assign('send_date', date($GLOBALS['_CFG']['date_format']));

	$content = $GLOBALS['smarty']->fetch('str:' . $template['template_content']);

	/* 发送激活验证邮件 */
	$result = send_mail($email, $email, $template['template_subject'], $content, $template['is_html']);
	if($result)
	{
		// 保存验证码到Session中
		$_SESSION[VT_EMAIL_REGISTER] = $email;
		// 保存验证记录
		save_validate_record($email, $email_code, VT_EMAIL_REGISTER, time(), time() + 30 * 60);

		echo 'ok';
	}
	else
	{
		echo '注册邮箱验证码发送失败';
	}
}

/* 发送注册邮箱验证码到邮箱 */
function action_send_mobile_code ()
{

	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];

	require_once (ROOT_PATH . 'includes/lib_validate_record.php');

	$mobile_phone = trim($_REQUEST['mobile_phone']);

	if(empty($mobile_phone))
	{
		exit("手机号不能为空");
		return;
	}
	else if(! is_mobile_phone($mobile_phone))
	{
		exit("手机号格式不正确");
		return;
	}
	else if(check_validate_record_exist($mobile_phone))
	{
		// 获取数据库中的验证记录
		$record = get_validate_record($mobile_phone);

		/**
		 * 检查是过了限制发送短信的时间
		*/
		$last_send_time = $record['last_send_time'];
		$expired_time   = $record['expired_time'];
		$create_time    = $record['create_time'];
		$count          = $record['count'];
		$count_ip       = $record['count_ip'];

		// 每天每个手机号最多发送的验证码数量
		$max_sms_count    = 3;
		$max_sms_count_ip = 5;

		// 发送最多验证码数量的限制时间，默认为24小时
		$max_sms_count_time = 60 * 60 * 24;


		if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count) {
			echo ("同一手机号24小时内只能发送3次验证码！");
			return;
		}

		if(time() - $create_time < $max_sms_count_time && $record['count_ip'] > $max_sms_count_ip) {
			echo ("同一个ip24小时内只能发送5次验证码！");
			return;
		}


		if((time() - $last_send_time) < 60)
		{
			echo ("每60秒内只能发送一次短信验证码，请稍候重试");
			return;
		}
		else if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count)
		{
			echo ("您发送验证码太过于频繁，请稍后重试！");
			return;
		}
		else
		{
			$count ++;
			$count_ip ++;
		}
	}

	require_once (ROOT_PATH . 'includes/lib_passport.php');

	// 设置为空
	$_SESSION['mobile_register'] = array();

	require_once (ROOT_PATH . 'sms/sms.php');

	// 生成6位短信验证码
	$mobile_code = rand_number(6);
	// 短信内容
	$content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['sms_sign'], $mobile_code, $GLOBALS['_CFG']['sms_sign']);

	// file_put_contents("D:/mobile_code.txt", $content."\n");

	/* 发送激活验证邮件 */
	// $result = true;
	$result = sendSMS($mobile_phone, $content);
	if($result)
	{

		if(! isset($count))
		{
			$ext_info = array(
				"count" => 1,
				"count_ip" => 1
			);
		}
		else
		{
			$ext_info = array(
				"count" => $count,
				"count_ip" => $count_ip
			);
		}

		// 保存手机号码到SESSION中
		$_SESSION[VT_MOBILE_REGISTER] = $mobile_phone;
		// 保存验证信息
		save_validate_record($mobile_phone, $mobile_code, VT_MOBILE_REGISTER, time(), time() + 30 * 60, $ext_info);
		echo 'ok';
	}
	else
	{
		echo '短信验证码发送失败';
	}
}

// 第三方登录接口
function action_oath()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
	
	if($type == "taobao")
	{
		header("location:includes/website/tb_index.php");
		exit();
	}
	
	include_once (ROOT_PATH . 'includes/website/jntoo.php');
	
	$c = &website($type);
	if($c)
	{
		if(empty($_REQUEST['callblock']))
		{
			if(empty($_REQUEST['callblock']) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
			{
				$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? 'index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
			}
			else
			{
				$back_act = 'index.php';
			}
		}
		else
		{
			$back_act = trim($_REQUEST['callblock']);
		}
		
		if($back_act[4] != ':')
			$back_act = $ecs->url() . $back_act;
		$open = empty($_REQUEST['open']) ? 0 : intval($_REQUEST['open']);
		
		$url = $c->login($ecs->url() . 'user.php?act=oath_login&type=' . $type . '&callblock=' . urlencode($back_act) . '&open=' . $open);
		if(! $url)
		{
			show_message($c->get_error(), '首页', $ecs->url(), 'error');
		}
		header('Location: ' . $url);
	}
	else
	{
		show_message('服务器尚未注册该插件！', '首页', $ecs->url(), 'error');
	}
}

// 处理第三方登录接口
function action_oath_login()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
	
	include_once (ROOT_PATH . 'includes/website/jntoo.php');
	$c = &website($type);
	if($c)
	{
		$access = $c->getAccessToken();
		if(! $access)
		{
			show_message($c->get_error(), '首页', $ecs->url(), 'error');
		}
		$c->setAccessToken($access);
		$info = $c->getMessage();
		if(! $info)
		{
			show_message($c->get_error(), '首页', $ecs->url(), 'error', false);
		}
		if(! $info['user_id'])
			show_message($c->get_error(), '首页', $ecs->url(), 'error', false);
		
		$info_user_id = $type . '_' . $info['user_id']; // 加个标识！！！防止 其他的标识 一样 //
		                                             // 以后的ID 标识 将以这种形式 辨认
		$info['name'] = str_replace("'", "", $info['name']); // 过滤掉 逗号 不然出错 很难处理
		if(! $info['user_id'])
			show_message($c->get_error(), '首页', $ecs->url(), 'error', false);
		
		$sql = 'SELECT user_name,password,aite_id FROM ' . $ecs->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\' OR aite_id=\'' . $info['user_id'] . '\'';
		
		$count = $db->getRow($sql);
		if(! $count) // 没有当前数据
		{
			if($user->check_user($info['name'])) // 重名处理
			{
				$info['name'] = $info['name'] . '_' . $type . (rand(10000, 99999));
			}
			$user_pass = $user->compile_password(array(
				'password' => $info['user_id']
			));
			$sql = 'INSERT INTO ' . $ecs->table('users') . '(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '$info[rank_id]' , '1')";
			$db->query($sql);
			$tag = 1; //第一次注册标记
		}
		else
		{
			$sql = '';
			if($count['aite_id'] == $info['user_id'])
			{
				$sql = 'UPDATE ' . $ecs->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
				$db->query($sql);
			}
			if($info['name'] != $count['user_name']) // 这段可删除
			{
				if($user->check_user($info['name'])) // 重名处理
				{
					$info['name'] = $info['name'] . '_' . $type . (rand() * 1000);
				}
				$sql = 'UPDATE ' . $ecs->table('users') . " SET user_name = '$info[name]' WHERE aite_id = '$info_user_id'";
				$db->query($sql);
				$tag = 2;
			}
		}
		$user->set_session($info['name']);
		$user->set_cookie($info['name']);
		update_user_info();
		recalculate_price();
		
		//绑定微信
		// if(isset($_SESSION['wxid']))
		// {
		// 	$sql = "UPDATE " . $GLOBALS['ecs']->table('weixin_user') . 
		// 		   " SET ecuid = 0 WHERE ecuid = '" . $_SESSION['user_id'] . "'";
		// 	$GLOBALS['db']->query($sql);
		// 	$sql = "UPDATE " . $GLOBALS['ecs']->table('weixin_user') . 
		// 		   " SET ecuid = '" . $_SESSION['user_id'] . "'" . 
		// 		   " WHERE fake_id = '" . $_SESSION['wxid'] . "'";
		// 	$num = $GLOBALS['db']->query($sql);
		// 	if($num > 0)
		// 	{
				if($tag == 1) //第一次注册绑定上级分销商
				{
					//修改新注册的用户成为普通分销商
					$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET is_fenxiao = 2 WHERE user_id = '" . $_SESSION['user_id'] . "'");
					$sql = "SELECT parent_id FROM " . 
							$GLOBALS['ecs']->table('bind_record') . 
							" WHERE wxid = '" . $_SESSION['wxid'] . "'";
					$parent_id = $GLOBALS['db']->getOne($sql);
					if($parent_id)
					{
						//扫描分销商二维码，绑定上级分销商
						$GLOBALS['db']->query("UPDATE " . 
								$GLOBALS['ecs']->table('users') . 
								" SET parent_id = '$parent_id'" .
								" WHERE user_id = '" . $_SESSION['user_id'] . "'");
						$GLOBALS['db']->query("DELETE FROM " . 
								$GLOBALS['ecs']->table('bind_record') . 
								" WHERE wxid = '" . $_SESSION['wxid'] . "'");
					}
				}
		// 	}
		// }
		
		if(! empty($_REQUEST['open']))
		{
			die('<script>window.opener.window.location.reload(); window.close();</script>');
		}
		else
		{
			ecs_header('Location: ' . $_REQUEST['callblock']);
		}
	}
}

// 处理其它登录接口
function action_other_login()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];
	session_start();
	$info = $_SESSION['user_info'];
	
	if(empty($info))
	{
		show_message("非法访问或请求超时！", '首页', $ecs->url(), 'error', false);
	}
	if(! $info['user_id'])
		show_message("非法访问或访问出错，请联系管理员！", '首页', $ecs->url(), 'error', false);
	
	$info_user_id = $type . '_' . $info['user_id']; // 加个标识！！！防止 其他的标识 一样 // 以后的ID
	                                             // 标识 将以这种形式 辨认
	$info['name'] = str_replace("'", "", $info['name']); // 过滤掉 逗号 不然出错 很难处理
	
	$sql = 'SELECT user_name,password,aite_id FROM ' . $ecs->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\' OR aite_id=\'' . $info['user_id'] . '\'';
	
	$count = $db->getRow($sql);
	$login_name = $info['name'];
	if(! $count) // 没有当前数据
	{
		if($user->check_user($info['name'])) // 重名处理
		{
			$info['name'] = $info['name'] . '_' . $type . (rand() * 1000);
		}
		$login_name = $info['name'];
		$user_pass = $user->compile_password(array(
			'password' => $info['user_id']
		));
		$sql = 'INSERT INTO ' . $ecs->table('users') . '(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '$info[rank_id]' , '1')";
		$db->query($sql);
	}
	else
	{
		$login_name = $count['user_name'];
		$sql = '';
		if($count['aite_id'] == $info['user_id'])
		{
			$sql = 'UPDATE ' . $ecs->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
			$db->query($sql);
		}
	}
	
	$user->set_session($login_name);
	$user->set_cookie($login_name);
	update_user_info();
	recalculate_price();
	
	$redirect_url = "http://" . $_SERVER["HTTP_HOST"] . str_replace("user.php", "index.php", $_SERVER["REQUEST_URI"]);
	header('Location: ' . $redirect_url);
}

/* 验证用户注册邮件 */
function action_validate_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$hash = empty($_GET['hash']) ? '' : trim($_GET['hash']);
	if($hash)
	{
		include_once (ROOT_PATH . 'includes/lib_passport.php');
		$id = register_hash('decode', $hash);
		if($id > 0)
		{
			$sql = "UPDATE " . $ecs->table('users') . " SET is_validated = 1 WHERE user_id='$id'";
			$db->query($sql);
			$sql = 'SELECT user_name, email FROM ' . $ecs->table('users') . " WHERE user_id = '$id'";
			$row = $db->getRow($sql);
			show_message(sprintf($_LANG['validate_ok'], $row['user_name'], $row['email']), $_LANG['profile_lnk'], 'user.php');
		}
	}
	show_message($_LANG['validate_fail']);
}

/* 验证用户注册用户名是否可以注册 */
function action_is_registered()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_passport.php');
	
	$username = trim($_GET['username']);
	$username = json_str_iconv($username);
	
	if($user->check_user($username) || admin_registered($username))
	{
		echo 'false';
	}
	else
	{
		echo 'true';
	}
}

/* 验证用户邮箱地址是否被注册 */
function action_check_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];


	$email = trim($_REQUEST['email']);
	if($user->check_email($email))
	{
		echo 'false';
	}
	else
	{
		echo 'ok';
	}
}
function action_check_register()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'includes/lib_sms.php');
	$json = new JSON();
	$username = trim($_POST['username']);
	$re = $json->decode($_POST['username']);
	$username = $re->username;
	$result = array(
		'error' => '','message' => ''
	);
	
	if(preg_match("/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i", $username))
	{
		$sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where email = '$username'";
		$num = $GLOBALS['db']->getOne($sql);
		if($num > 0)
		{
			$result['error'] = 2;
			$result['message'] = '邮箱已存在，请重新输入！';
		}
		else
		{
			$result['error'] = 0;
			$result['message'] = '可以注册';
		}
	}
	else if(ismobile($username))
	{
		$sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where mobile_phone = '$username'";
		$num = $GLOBALS['db']->getOne($sql);
		if($num > 0)
		{
			$result['error'] = 2;
			$result['message'] = '手机号已存在，请重新输入！';
		}
		else
		{
			$result['error'] = 0;
			$result['message'] = '可以注册';
		}
	}
	else
	{
		$sql = "update " . $GLOBALS['ecs']->table('goods') . " set goods_name = 'ddd' where goods_id = '32'";
		$GLOBALS['db']->query($sql);
		$sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where user_name = '$username'";
		$num = $GLOBALS['db']->getOne($sql);
		if($num > 0)
		{
			$result['error'] = 3;
			$result['message'] = '用户名已存在，请重新输入！';
		}
		else
		{
			$result['error'] = 0;
			$result['message'] = '可以注册';
		}
	}
	
	die($json->encode($result));
}

/* 用户登录界面 */
function action_login()
{

	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(empty($back_act))
	{
		if(empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
		{
			$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
		}
		else
		{
                    if(isset($_REQUEST['jump']) && trim($_REQUEST['jump'])=='jszx'){
                            $back_act = 'flow.php';
                    }else{
			$back_act = 'user.php';
                    }
		}
	}
	
	/* 取出注册扩展字段 */
	$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
	$extend_info_list = $db->getAll($sql);
	$smarty->assign('extend_info_list', $extend_info_list);
	
	$captcha = intval($_CFG['captcha']);
	if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
	{
		$GLOBALS['smarty']->assign('enabled_captcha', 1);
	}
	if((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)
	{
		$GLOBALS['smarty']->assign('enabled_captcha_re', 1);
	}
	$GLOBALS['smarty']->assign('rand', mt_rand());

	$smarty->assign('back_act', $back_act);
	$smarty->assign('sms_register', $_CFG['sms_register']);
	$smarty->display('user_passport.dwt');
}

/* 处理会员的登录 */
function action_act_login()
{

	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
	$password = isset($_REQUEST['password']) ? trim($_REQUEST['password']) : '';
	$pat = isset($_REQUEST['pat']) ? trim($_REQUEST['pat']) : '';
	if($pat=='1') $password=null;
	$nocapt = isset($_REQUEST['nocapt']) ? trim($_REQUEST['nocapt']) : '';
	$topatuin = isset($_REQUEST['topatuin']) ? trim($_REQUEST['topatuin']) : 0;
	$back_act = isset($_COOKIE['back_act']) ? trim($_COOKIE['back_act']) : '';	
	$_wxt = isset($_REQUEST['_wxt']) ? trim($_REQUEST['_wxt']) : '';
	$captcha = intval($_CFG['captcha']);

	if($nocapt!='abcd')
	{
		if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
		{
			if(empty($_REQUEST['captcha']))
			{
				show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
			}		
			/* 检查验证码 */
			include_once ('includes/cls_captcha.php');
			
			$validator = new captcha();
			$validator->session_word = 'captcha_login';
			if(! $validator->check_word($_REQUEST['captcha']))
			{
				show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
			}
		}	
	}
	/* 代码增加2014-12-23 by www.yshop100.com _star */
	if(is_email($username))
	{
		$sql = "select user_name from " . $ecs->table('users') . " where email='" . $username . "'";
		$username_e = $db->getOne($sql);
		if($username_e)
		{
			$username = $username_e;
		}
	}
	if(is_telephone($username))
	{
		$sql = "select user_name from " . $ecs->table('users') . " where mobile_phone='" . $username . "'";
		$username_res = $db->query($sql);
		$kkk = 0;
		while($username_row = $db->fetchRow($username_res))
		{
			$username_e = $username_row['user_name'];
			$kkk = $kkk + 1;
		}
		if($kkk > 1)
		{
			show_message('本网站有多个会员ID绑定了和您相同的手机号，请使用其他登录方式，如：邮箱或用户名。', $_LANG['relogin_lnk'], 'user.php', 'error');
		}
		if($username_e)
		{
			$username = $username_e;
		}
	}
	if($_wxt=='unionid')
	{
		$sql = "select user_name from " . $ecs->table('users') . " where wx_unionid='" . $username . "'";
		$username_res = $db->query($sql);
		$kkk = 0;
		while($username_row = $db->fetchRow($username_res))
		{
			$username_e = $username_row['user_name'];
			$kkk = $kkk + 1;
		}
		if($kkk > 1)
		{
			show_message('本网站有多个会员ID绑定了和您相同的手机号，请使用其他登录方式，如：邮箱或用户名。', $_LANG['relogin_lnk'], 'user.php', 'error');
		}
		if($username_e)
		{
			$username = $username_e;
		}
	}
	if($nocapt=='abcd'&&$topatuin==0)
	{ 
		$cuid=$user->loginmsg($username, $password, isset($_REQUEST['remember']));
		if($cuid>0)
		{
			update_user_info();
			recalculate_price(); 		
			echo $cuid;
		}
		else
		{
			echo "0";
		}
	}
	else
	{
		if($user->login($username, $password, isset($_REQUEST['remember'])))
		{//file_put_contents('20191029.txt',"loginok\r\n" , FILE_APPEND);
			update_user_info();
			recalculate_price();

			//绑定微信
			// if(isset($_SESSION['wxid']))
			// {
			// 	$sql = "UPDATE " . $GLOBALS['ecs']->table('weixin_user') . 
			// 		   " SET ecuid = 0 WHERE ecuid = '" . $_SESSION['user_id'] . "'";
			// 	$GLOBALS['db']->query($sql);
			// 	$sql = "UPDATE " . $GLOBALS['ecs']->table('weixin_user') . 
			// 		   " SET ecuid = '" . $_SESSION['user_id'] . "'" . 
			// 		   " WHERE fake_id = '" . $_SESSION['wxid'] . "'";
			// 	$num = $GLOBALS['db']->query($sql);
			// }		
			$ucdata = isset($user->ucdata) ? $user->ucdata : '';
			if($topatuin>0)
			{
				ecs_header("Location: index.php");
				exit();
			}
			else
			{
				show_message($_LANG['login_success'] . $ucdata, array(
					$_LANG['back_up_page'],$_LANG['profile_lnk']
				), array(
					$back_act,'user.php'
				), 'info');
			}
		}
		else
		{
			$_SESSION['login_fail'] ++;
			show_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
		}
	}
}

/* 处理 ajax 的登录请求 */
function action_signin()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once ('includes/cls_json.php');
	$json = new JSON();
	
	$username = ! empty($_POST['username']) ? json_str_iconv(trim($_POST['username'])) : '';
	$password = ! empty($_POST['password']) ? trim($_POST['password']) : '';
	$captcha = ! empty($_POST['captcha']) ? json_str_iconv(trim($_POST['captcha'])) : '';
	$result = array(
		'error' => 0,'content' => ''
	);
	
	$captcha = intval($_CFG['captcha']);
	if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
	{
		if(empty($captcha))
		{
			$result['error'] = 1;
			$result['content'] = $_LANG['invalid_captcha'];
			die($json->encode($result));
		}
		
		/* 检查验证码 */
		include_once ('includes/cls_captcha.php');
		
		$validator = new captcha();
		$validator->session_word = 'captcha_login';
		if(! $validator->check_word($_POST['captcha']))
		{
			
			$result['error'] = 1;
			$result['content'] = $_LANG['invalid_captcha'];
			die($json->encode($result));
		}
	}
	
	if($user->login($username, $password))
	{
		update_user_info(); // 更新用户信息
		recalculate_price(); // 重新计算购物车中的商品价格
		$smarty->assign('user_info', get_user_info());
		$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
		$result['ucdata'] = $ucdata;
		$result['content'] = $smarty->fetch('library/member_info.lbi');
	}
	else
	{
		$_SESSION['login_fail'] ++;
		if($_SESSION['login_fail'] > 2)
		{
			$smarty->assign('enabled_captcha', 1);
			$result['html'] = $smarty->fetch('library/member_info.lbi');
		}
		$result['error'] = 1;
		$result['content'] = $_LANG['login_failure'];
	}
	die($json->encode($result));
}

/* 退出会员中心 */
function action_logout()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if((! isset($back_act) || empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
	{
		$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
	}
	
	$user->logout();
	$ucdata = empty($user->ucdata) ? "" : $user->ucdata;
	show_message($_LANG['logout'] . $ucdata, array(
		$_LANG['back_up_page'],$_LANG['back_home_lnk']
	), array(
		$back_act,'index.php'
	), 'info');
}

/* 个人资料页面 */
function action_profile()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	$op = $_GET['op'] ? $_GET['op'] : '';
	if($op == 'change_headimg'){

		$user_id = $_SESSION['user_id'];
		$sql = "select headimgurl from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid = '$user_id'";

		$headimg = $db->getOne("select headimg from " . $ecs->table('users') . " where user_id = $user_id");
		if(!$headimgurl)
		{
			if($headimg){
				$smarty->assign('headimgurl',$headimg);
			}

		}else{
			$sql = "select headimgurl from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid = '$user_id'";
			$headimgurl = $GLOBALS['db']->getOne($sql);
			$smarty->assign('headimgurl', $headimgurl);
		}

	}else {
		$user_info = get_profile($user_id);

		/* 取出注册扩展字段 */
		$sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
		$extend_info_list = $db->getAll($sql);

		$sql = 'SELECT reg_field_id, content ' . 'FROM ' . $ecs->table('reg_extend_info') . " WHERE user_id = $user_id";
		$extend_info_arr = $db->getAll($sql);

		$temp_arr = array();
		foreach ($extend_info_arr as $val) {
			$temp_arr[$val['reg_field_id']] = $val['content'];
		}

		foreach ($extend_info_list as $key => $val) {
			switch ($val['id']) {
				case 1:
					$extend_info_list[$key]['content'] = $user_info['msn'];
					break;
				case 2:
					$extend_info_list[$key]['content'] = $user_info['qq'];
					break;
				case 3:
					$extend_info_list[$key]['content'] = $user_info['office_phone'];
					break;
				case 4:
					$extend_info_list[$key]['content'] = $user_info['home_phone'];
					break;
				case 5:
					$extend_info_list[$key]['content'] = $user_info['mobile_phone'];
					break;
				default:
					$extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
			}
		}

		$smarty->assign('extend_info_list', $extend_info_list);

		/* 密码提示问题 */
		$smarty->assign('passwd_questions', $_LANG['passwd_questions']);

		$smarty->assign('profile', $user_info);
	}
	$smarty->assign('op', $op);
	$smarty->display('user_transaction.dwt');
}

/* 修改个人资料的处理 */
function action_act_edit_profile()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	$user_name = trim($_POST['username']);
	$birthday = trim($_POST['birthdayYear']) . '-' . trim($_POST['birthdayMonth']) . '-' . trim($_POST['birthdayDay']);
	//$email = trim($_POST['email']);
	$other['msn'] = $msn = isset($_POST['extend_field1']) ? trim($_POST['extend_field1']) : '';
	$other['qq'] = $qq = isset($_POST['extend_field2']) ? trim($_POST['extend_field2']) : '';
	$other['office_phone'] = $office_phone = isset($_POST['extend_field3']) ? trim($_POST['extend_field3']) : '';
	$other['home_phone'] = $home_phone = isset($_POST['extend_field4']) ? trim($_POST['extend_field4']) : '';
	//$other['mobile_phone'] = $mobile_phone = isset($_POST['extend_field5']) ? trim($_POST['extend_field5']) : '';
	$sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
	$passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';
	$other_only = array_filter($other);
	/* 更新用户扩展字段的数据 */
	$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有扩展字段的id
	$fields_arr = $db->getAll($sql);
	
	foreach($fields_arr as $val) // 循环更新扩展用户信息
	{
		$extend_field_index = 'extend_field' . $val['id'];
		if(isset($_POST[$extend_field_index]))
		{
			$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
			$sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
			if($db->getOne($sql)) // 如果之前没有记录，则插入
			{
				$sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
			}
			else
			{
				$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
			}
			$db->query($sql);
		}
	}

    //修改密码
   if($_POST['password'])
   {
       $id = $_SESSION['user_id'];
       $sql = 'select ec_salt from '.$GLOBALS['ecs']->table('users').' where user_id = '.$id;
       $row = $db->getRow($sql);
       $ec_salt = $row['ec_salt'];

       $odl = $_POST['password'];
       $odl = md5(md5($odl).$ec_salt);
       $new = $_POST['password1'];
       $new = md5(md5($new).$ec_salt);

       $sql = 'select user_id from '.$GLOBALS['ecs']->table('users').' where user_id ='.$id.' and password = \''.$odl.'\'';
       $row = $db->getRow($sql);
       if($row['user_id'] <= 0)
       {
           show_message('你的原密码错误');
           exit;
       }
       $sql = 'update '.$GLOBALS['ecs']->table('users').'set password = \''.$new.'\' where user_id='.$id;
       $row = $db->getRow($sql);

   }


	if(! empty($passwd_answer) && ! empty($sel_question))
	{
		$sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
		$db->query($sql);
	}
	
	if(empty($user_name))
	{
		show_message('用户名不能为空！');
	}
        
	if(! empty($office_phone) && ! preg_match('/^[\d|\_|\-|\s]+$/', $office_phone))
	{
		show_message($_LANG['passport_js']['office_phone_invalid']);
	}
	if(! empty($home_phone) && ! preg_match('/^[\d|\_|\-|\s]+$/', $home_phone))
	{
		show_message($_LANG['passport_js']['home_phone_invalid']);
	}

	if(! empty($msn) && ! is_email($msn))
	{
		show_message($_LANG['passport_js']['msn_invalid']);
	}
	if(! empty($qq) && ! preg_match('/^\d+$/', $qq))
	{
		show_message($_LANG['passport_js']['qq_invalid']);
	}

	$profile = array(
		'user_id' => $user_id, 'user_name'=> $user_name,'sex' => isset($_POST['sex']) ? intval($_POST['sex']) : 0,'birthday' => $birthday,'other' => isset($other_only) ? $other_only : array()
	);

	if(edit_profile($profile))
	{
		$sql = 'UPDATE ' . $ecs->table('users') . " SET `user_name`='$user_name' WHERE `user_id`='" . $_SESSION['user_id'] . "'";
		$db->query($sql);		
		if(!empty($_SESSION['comefrom'])){
			show_message($_LANG['edit_profile_success'], $_LANG['back_user'], 'javascript:H5ToAppMember()', 'info');
		}else{
			show_message($_LANG['edit_profile_success'], $_LANG['back_user'], 'user.php', 'info');
		}		
	}
	else
	{
           
//		if($user->error == ERR_EMAIL_EXISTS)
//		{
//			$msg = sprintf($_LANG['email_exist'], $profile['email']);
//		}
//		else
//		{
//			$msg = $_LANG['edit_profile_failed'];
//		}
//		show_message($msg, '', '', 'info');
                $msg = implode(',', $GLOBALS['err']->_message);
                show_message($msg, '', '', 'info');
             //$GLOBALS['err']->show($msg, 'user.php?act=profile');
	}
}

function action_act_edit_img()
{

	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(empty($_FILES['headimg']['name']))
	{
		show_message("头像文件缺失");
	}
	else
	{
		include_once ('../includes/lib_transaction.php');
		include_once ('./includes/cls_image.php');

		$image = new cls_image($_CFG['bgcolor']);
		$headimg_original = $image->UPLOAD_IMAGE($_FILES['headimg'], 'headimg/' . date('Ym'));
		//$image->images_dir = 'data';
//		$thumb_path = '/data/headimg/' . date('Ym') . '/';
//		$headimg_thumb = $image->make_thumb($headimg_original, '80', '50', $thumb_path);
//		$headimg_thumb = $headimg_thumb ? '/mobile'.$headimg_thumb : '/mobile'.$headimg_original;
		$sql = 'UPDATE ' . $ecs->table('users') . " SET `headimg`='$headimg_original'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";

		$db->query($sql);
		
		if(empty($sql))
		{
			show_message("头像上传失败");
		}
		else
		{
			show_message($_LANG['edit_profile_success'], $_LANG['profile_lnk'], 'user.php?act=profile', 'info');
		}
	}
}

function action_account_security()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	$user_info = get_profile($user_id);
	
	$smarty->assign('info', $user_info);
	$smarty->display('user_transaction.dwt');
}
function action_act_identity()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . '/includes/cls_image.php');
	$image = new cls_image($_CFG['bgcolor']);
	$real_name = $_POST['real_name'];
	$card = $_POST['card'];
	$country = $_POST['country'];
	$province = $_POST['province'];
	$city = $_POST['city'];
	$district = $_POST['district'];
	$address = $_POST['address'];
	if(isset($_FILES['face_card']) && $_FILES['face_card']['tmp_name'] != '')
	{
		if($_FILES['face_card']['width'] > 800)
		{
			show_message('图片宽度不能超过800像素！');
		}
		if($_FILES['face_card']['height'] > 800)
		{
			show_message('图片高度不能超过800像素！');
		}
		$face_card = $image->upload_image($_FILES['face_card']);
		if($face_card === false)
		{
			show_message($image->error_msg());
		}
	}
	if(isset($_FILES['back_card']) && $_FILES['back_card']['tmp_name'] != '')
	{
		if($_FILES['back_card']['width'] > 800)
		{
			show_message('图片宽度不能超过800像素！');
		}
		if($_FILES['back_card']['height'] > 800)
		{
			show_message('图片高度不能超过800像素！');
		}
		$back_card = $image->upload_image($_FILES['back_card']);
		if($back_card === false)
		{
			show_message($image->error_msg());
		}
	}
	
	$sql = "select face_card,back_card from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
	$rows = $GLOBALS['db']->getRow($sql);
	if($rows['face_card'] == '')
	{
		if($face_card == '')
		{
			show_message('请上传身份证正面照！');
		}
	}
	
	if($rows['back_card'] == '')
	{
		if($back_card == '')
		{
			show_message('请上传身份证背面照！');
		}
	}
	
	$sql = 'update ' . $GLOBALS['ecs']->table('users') . " set real_name = '$real_name',card='$card',country='$country',province='$province',city='$city',district='$district',address='$address',status = '2'";
	if($face_card != '')
	{
		$sql .= " ,face_card = '$face_card'";
	}
	if($back_card != '')
	{
		$sql .= " ,back_card = '$back_card'";
	}
	$sql .= " where user_id = '" . $_SESSION['user_id'] . "'";
	$num = $GLOBALS['db']->query($sql);
	if($num > 0)
	{
		show_message('您已申请实名认证，请等待管理员的审核！', '返回上一页', 'user.php?act=profile');
	}
	else
	{
		show_message('实名认证失败！', '返回上一页', 'user.php?act=profile');
	}
}
function action_update_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$sql = "select email from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
	$email = $GLOBALS['db']->getOne($sql);
	$smarty->assign('email', $email);
	$smarty->display('user_transaction.dwt');
}
function action_act_update_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_passport.php');
	if(empty($_POST['v_captcha']))
	{
		show_message('验证码不能为空！', '返回', 'user.php?act=update_email', 'error');
	}
	
	/* 检查验证码 */
	include_once ('includes/cls_captcha.php');
	
	$validator = new captcha();
	$validator->session_word = 'captcha_login';
	if(! $validator->check_word($_POST['v_captcha']))
	{
		show_message($_LANG['invalid_captcha'], '返回', 'user.php?act=update_email', 'error');
	}
	else
	{
		$sql = "select email,user_name from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
		$rows = $GLOBALS['db']->getRow($sql);
		$tpl = get_mail_template('verify_mail');
		$run = "0123456789abcdefghijklmnopqrstuvwxyz";
		$hash = mc_random(16, $run);
		$email = $GLOBALS['ecs']->url() . 'user.php?act=valid_email&hash=' . $hash;
		
		$smarty->assign('shop_name', $_CFG['shop_name']);
		$smarty->assign('send_date', date($_CFG['time_format']));
		$smarty->assign('user_name', $rows['user_name']);
		$smarty->assign('email', $email);
		$smarty->assign('v_email', $rows['email']);
		$content = $smarty->fetch('str:' . $tpl['template_content']);
		$result = send_mail($_CFG['shop_name'], $rows['email'], $tpl['template_subject'], $content, $tpl['is_html']);
		if($result == true)
		{
			$add_time = time();
			$sql = "insert into " . $GLOBALS['ecs']->table('email') . "(`email`,`hash`,`add_time`,`user_id`) values('" . $rows['email'] . "','$hash','$add_time','" . $_SESSION['user_id'] . "')";
			$GLOBALS['db']->query($sql);
			$smarty->display('user_transaction.dwt');
		}
		else
		{
			show_message('邮件发送失败！');
		}
	}
}

function action_valid_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$hash = empty($_REQUEST['hash']) ? '' : trim($_REQUEST['hash']);
	$sql = "select * from " . $GLOBALS['ecs']->table('email') . " where hash = '$hash'";
	$row = $GLOBALS['db']->getRow($sql);
	$now_time = time();
	if($now_time - $row['add_time'] > 24 * 60 * 60)
	{
		$sql = "delete from " . $GLOBALS['ecs']->table('email') . " where hash = '$hash'";
		$GLOBALS['db']->query($sql);
		show_message('验证邮件已发送超过24小时，请重新验证！');
	}
	else
	{
		$sql = "select count(*) from " . $GLOBALS['ecs']->table('email') . " where hash = '$hash'";
		$count = $GLOBALS['db']->getOne($sql);
		if($count > 0)
		{
			ecs_header("Location: user.php?act=re_binding_email\n");
			exit();
		}
	}
}

function action_re_binding_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$smarty->display('user_transaction.dwt');
}

function action_act_binding_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_passport.php');
	$email = $_POST['new_email'];
	$code = $_POST['code'];
	
	$sql = "select count(*) from " . $GLOBALS['ecs']->table('users') . " where email = '$email'";
	$num = $GLOBALS['db']->getOne($sql);
	if($num > 0)
	{
		show_message('邮箱已经存在请重新输入！');
	}
	
	if(empty($_POST['code']))
	{
		show_message('验证码不能为空！', '返回', 'user.php?act=re_binding_email', 'error');
	}
	
	/* 检查验证码 */
	include_once ('includes/cls_captcha.php');
	
	$validator = new captcha();
	$validator->session_word = 'captcha_login';
	if(! $validator->check_word($_POST['code']))
	{
		show_message($_LANG['invalid_captcha'], '返回', 'user.php?act=re_binding_email', 'error');
	}
	else
	{
		$tpl = get_mail_template('verify_mail');
		
		$run = "0123456789abcdefghijklmnopqrstuvwxyz";
		$hash = mc_random(16, $run);
		
		$validate_email = $GLOBALS['ecs']->url() . 'user.php?act=re_validate_email&hash=' . $hash;
		
		$sql = "select user_name from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
		$user_name = $GLOBALS['db']->getOne($sql);
		
		$smarty->assign('shop_name', $_CFG['shop_name']);
		$smarty->assign('send_date', date($_CFG['time_format']));
		$smarty->assign('user_name', $user_name);
		$smarty->assign('email', $validate_email);
		$content = $smarty->fetch('str:' . $tpl['template_content']);
		$result = send_mail($_CFG['shop_name'], $email, $tpl['template_subject'], $content, $tpl['is_html']);
		if($result == true)
		{
			$sql = "update " . $GLOBALS['ecs']->table('users') . " set is_validated = 0,email='$email' where user_id = '" . $_SESSION['user_id'] . "'";
			$GLOBALS['db']->query($sql);
			$sql = "delete from " . $GLOBALS['ecs']->table('email') . " where user_id = '" . $_SESSION['user_id'] . "'";
			$GLOBALS['db']->query($sql);
			$add_time = time();
			$sql = "insert into " . $GLOBALS['ecs']->table('email') . "(`email`,`hash`,`add_time`,`user_id`) values('$email','$hash','$add_time','" . $_SESSION['user_id'] . "')";
			$GLOBALS['db']->query($sql);
			show_message('已发送邮件，请前往邮箱点击链接完成验证！');
		}
		else
		{
			show_message('发送邮件失败！');
		}
	}
}

function action_re_validate_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$hash = empty($_REQUEST['hash']) ? '' : trim($_REQUEST['hash']);
	$sql = "select * from " . $GLOBALS['ecs']->table('email') . " where hash = '$hash'";
	$row = $GLOBALS['db']->getRow($sql);
	$now_time = time();
	if($now_time - $row['add_time'] > 24 * 60 * 60)
	{
		$sql = "delete from " . $GLOBALS['ecs']->table('email') . " where hash = '$hash'";
		$GLOBALS['db']->query($sql);
		show_message('验证邮件已发送超过24小时，请重新验证！');
	}
	else
	{
		$sql = "select count(*) from " . $GLOBALS['ecs']->table('email') . " where hash = '$hash'";
		$count = $GLOBALS['db']->getOne($sql);
		if($count > 0)
		{
			$sql = "update " . $GLOBALS['ecs']->table('users') . " set is_validated = 1 where user_id = '" . $_SESSION['user_id'] . "'";
			$GLOBALS['db']->query($sql);
			$sql = "delete from " . $GLOBALS['ecs']->table('email') . " where user_id = '" . $_SESSION['user_id'] . "'";
			$GLOBALS['db']->query($sql);
			show_message('绑定成功！', '返回账户安全', 'user.php?act=account_security');
		}
		else
		{
			show_message('绑定失败！');
		}
	}
}

function action_update_phone()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$sql = "select mobile_phone from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $_SESSION['user_id'] . "'";
	$mobile_phone = $GLOBALS['db']->getOne($sql);
	$smarty->assign('phone', $mobile_phone);
	$smarty->display('user_transaction.dwt');
}
function action_act_update_phone()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$phone = isset($_POST['v_phone']) ? trim($_POST['v_phone']) : '';
	$verifycode = isset($_POST['v_code']) ? trim($_POST['v_code']) : '';
	if($phone == '')
	{
		show_message('手机号不能为空！');
	}
	else
	{
		if(is_telephone($phone))
		{
			if($verifycode == '')
			{
				show_message('手机验证码不能为空！');
			}
			else
			{
				/* 验证手机号验证码和IP */
				$sql = "SELECT COUNT(id) FROM " . $ecs->table('verifycode') . " WHERE mobile='$phone' AND verifycode='$verifycode' AND getip='" . real_ip() . "' AND status=1 AND dateline>'" . gmtime() . "'-86400"; // 验证码一天内有效
				
				if($db->getOne($sql) == 0)
				{
					show_message('手机号和验证码不匹配，请重新输入！');
				}
				else
				{
					ecs_header("Location: user.php?act=re_binding\n");
					exit();
				}
			}
		}
		else
		{
			show_message('请输入正确的手机号！');
		}
	}
}

function action_re_binding()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$smarty->display('user_transaction.dwt');
}
function action_binding()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
	$verifycode = isset($_POST['verifycode']) ? trim($_POST['verifycode']) : '';
	if($phone == '')
	{
		show_message('手机号不能为空！');
	}
	else
	{
		if(is_telephone($phone))
		{
			$sql = "SELECT COUNT(user_id) FROM " . $ecs->table('users') . " WHERE mobile_phone = '$phone'";
			if($db->getOne($sql) > 0)
			{
				show_message('手机号已经存在，请重新输入！');
			}
			else
			{
				if($verifycode == '')
				{
					show_message('手机验证码不能为空！');
				}
				else
				{
					/* 验证手机号验证码和IP */
					$sql = "SELECT COUNT(id) FROM " . $ecs->table('verifycode') . " WHERE mobile='$phone' AND verifycode='$verifycode' AND getip='" . real_ip() . "' AND status=1 AND dateline>'" . gmtime() . "'-86400"; // 验证码一天内有效
					
					if($db->getOne($sql) == 0)
					{
						show_message('手机号和验证码不匹配，请重新输入！');
					}
					else
					{
						$sql = "update " . $ecs->table('users') . " set mobile_phone = '$phone',validated = 1 where user_id = '" . $_SESSION['user_id'] . "'";
						$num = $db->query($sql);
						if($num > 0)
						{
							show_message('绑定手机号成功！', '返回账户安全', 'user.php?act=account_security');
						}
						else
						{
							show_message('绑定手机号失败！');
						}
					}
				}
			}
		}
		else
		{
			show_message('请输入正确的手机号！');
		}
	}
}

/* 密码找回-->修改密码界面 */
function action_get_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_passport.php');
	
	if(isset($_GET['code']) && isset($_GET['uid'])) // 从邮件处获得的act
	{
		$code = trim($_GET['code']);
		$uid = intval($_GET['uid']);
		
		/* 判断链接的合法性 */
		$user_info = $user->get_profile_by_id($uid);
		if(empty($user_info) || ($user_info && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) != $code))
		{
			show_message($_LANG['parm_error'], $_LANG['back_home_lnk'], './', 'info');
		}
		
		$smarty->assign('uid', $uid);
		$smarty->assign('code', $code);
		$smarty->assign('action', 'reset_password');
		$smarty->display('user_passport.dwt');
	}
	else
	{
		// 显示用户名和email表单
		$smarty->display('user_passport.dwt');
	}
}

/* 密码找回-->输入用户名界面 */
function action_qpassword_name()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	// 显示输入要找回密码的账号表单
	$smarty->display('user_passport.dwt');
}

/* 密码找回-->根据注册用户名取得密码提示问题界面 */
function action_get_passwd_question()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(empty($_POST['user_name']))
	{
		show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
	}
	else
	{
		$user_name = trim($_POST['user_name']);
	}
	
	// 取出会员密码问题和答案
	$sql = 'SELECT user_id, user_name, passwd_question, passwd_answer FROM ' . $ecs->table('users') . " WHERE user_name = '" . $user_name . "'";
	$user_question_arr = $db->getRow($sql);
	
	// 如果没有设置密码问题，给出错误提示
	if(empty($user_question_arr['passwd_answer']))
	{
		show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
	}
	
	$_SESSION['temp_user'] = $user_question_arr['user_id']; // 设置临时用户，不具有有效身份
	$_SESSION['temp_user_name'] = $user_question_arr['user_name']; // 设置临时用户，不具有有效身份
	$_SESSION['passwd_answer'] = $user_question_arr['passwd_answer']; // 存储密码问题答案，减少一次数据库访问
	
	$captcha = intval($_CFG['captcha']);
	if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
	{
		$GLOBALS['smarty']->assign('enabled_captcha', 1);
		$GLOBALS['smarty']->assign('rand', mt_rand());
	}
	
	$smarty->assign('passwd_question', $_LANG['passwd_questions'][$user_question_arr['passwd_question']]);
	$smarty->display('user_passport.dwt');
}

/* 密码找回-->根据提交的密码答案进行相应处理 */
function action_check_answer()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$captcha = intval($_CFG['captcha']);
	if(($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
	{
		if(empty($_POST['captcha']))
		{
			show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
		}
		
		/* 检查验证码 */
		include_once ('includes/cls_captcha.php');
		
		$validator = new captcha();
		$validator->session_word = 'captcha_login';
		if(! $validator->check_word($_POST['captcha']))
		{
			show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
		}
	}
	
	if(empty($_POST['passwd_answer']) || $_POST['passwd_answer'] != $_SESSION['passwd_answer'])
	{
		show_message($_LANG['wrong_passwd_answer'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'info');
	}
	else
	{
		$_SESSION['user_id'] = $_SESSION['temp_user'];
		$_SESSION['user_name'] = $_SESSION['temp_user_name'];
		unset($_SESSION['temp_user']);
		unset($_SESSION['temp_user_name']);
		$smarty->assign('uid', $_SESSION['user_id']);
		$smarty->assign('action', 'reset_password');
		$smarty->display('user_passport.dwt');
	}
}

/* 发送密码修改确认邮件 */
function action_send_pwd_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_passport.php');
	
	/* 初始化会员用户名和邮件地址 */
	$user_name = ! empty($_POST['user_name']) ? trim($_POST['user_name']) : '';
	$email = ! empty($_POST['email']) ? trim($_POST['email']) : '';
	
	// 用户名和邮件地址是否匹配
	$user_info = $user->get_user_info($user_name);
	
	if($user_info && $user_info['email'] == $email)
	{
		// 生成code
		// $code = md5($user_info[0] . $user_info[1]);
		
		$code = md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']);
		// 发送邮件的函数
		if(send_pwd_email($user_info['user_id'], $user_name, $email, $code))
		{
			show_message($_LANG['send_success'] . $email, $_LANG['back_home_lnk'], './', 'info');
		}
		else
		{
			// 发送邮件出错
			show_message($_LANG['fail_send_password'], $_LANG['back_page_up'], './', 'info');
		}
	}
	else
	{
		// 用户名与邮件地址不匹配
		show_message($_LANG['username_no_email'], $_LANG['back_page_up'], '', 'info');
	}
}

/* 重置新密码 */
function action_reset_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	// 显示重置密码的表单
	$smarty->display('user_passport.dwt');
}

/* 修改会员密码 */
function action_act_edit_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_passport.php');
	
	$old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : null;
	$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
	$user_id = isset($_POST['uid']) ? intval($_POST['uid']) : $user_id;
	$code = isset($_POST['code']) ? trim($_POST['code']) : '';
	
	if(strlen($new_password) < 6)
	{
		show_message($_LANG['passport_js']['password_shorter']);
	}
	
	$user_info = $user->get_profile_by_id($user_id); // 论坛记录
	
	if(($user_info && (! empty($code) && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) == $code)) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id && $user->check_user($_SESSION['user_name'], $old_password)))
	{
		
		if($user->edit_user(array(
			'username' => (empty($code) ? $_SESSION['user_name'] : $user_info['user_name']),'old_password' => $old_password,'password' => $new_password
		), empty($code) ? 0 : 1))
		{
			$sql = "UPDATE " . $ecs->table('users') . "SET `ec_salt`='0' WHERE user_id= '" . $user_id . "'";
			$db->query($sql);
			$user->logout();
			show_message($_LANG['edit_password_success'], $_LANG['relogin_lnk'], 'user.php?act=login', 'info');
		}
		else
		{
			show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
		}
	}
	else
	{
		show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
	}
}

/* 添加一个红包 */
function action_act_add_bonus()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	$bouns_sn = isset($_POST['bonus_sn']) ? intval($_POST['bonus_sn']) : '';
	
	if(add_bonus($user_id, $bouns_sn))
	{
		show_message($_LANG['add_bonus_sucess'], $_LANG['back_up_page'], 'user.php?act=bonus', 'info');
	}
	else
	{
		$GLOBALS['err']->show($_LANG['back_up_page'], 'user.php?act=bonus');
	}
}

/* 查看订单列表 */
function action_order_list()
{
	//全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	

	include_once (ROOT_PATH . 'includes/lib_transaction.php');
//	include_once (ROOT_PATH . 'includes/lib_payment.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
//	include_once (ROOT_PATH . 'includes/lib_clips.php');
//	
        // 手机端暂时不显示虚拟团购
	$ex_where = " and user_id=$user_id and extension_code <> 'virtual_good'" ;
	/* 全部订单*/
        $order_count['all'] = $db->GetOne('SELECT COUNT(*) FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where");
        
	/* 已发货的订单 */
	$order_count['shipped'] = $db->GetOne("SELECT COUNT(*) FROM " . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('shipped'));
	$status['shipped'] = SS_SHIPPED;
        
        /* 待收货的订单　*/
	$order_count['await_receipt'] = $db->GetOne("SELECT COUNT(*) FROM " . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_receipt'));
	$status['await_receipt'] = CS_AWAIT_RECEIPT;
        
	/* 已取消的订单 */
	$order_count['canceled'] = $db->GetOne("SELECT COUNT(*) FROM " . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('canceled'));
	$status['canceled'] = OS_CANCELED;
	
	/* 退款中的订单 */
	$order_count['payback'] = $db->GetOne("SELECT COUNT(*) FROM " . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('payback'));
	$status['payback'] = PS_PAYBACK;
	
	/* 待评价的订单： */
	$order_count['await_comment'] = $db->GetOne("SELECT COUNT(*)" . " FROM " . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_comment'));
	$status['await_comment'] = CS_AWAIT_COMMENT;
        
	/* 已完成的订单 */
	$order_count['finished'] = $db->GetOne('SELECT COUNT(*) FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('finished'));
	$status['finished'] = CS_FINISHED;
	
	/* 待发货的订单： */
	$order_count['await_ship'] = $db->GetOne('SELECT COUNT(*)' . ' FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_ship'));
	$status['await_ship'] = CS_AWAIT_SHIP;
	
	/* 待付款的订单： */
	$order_count['await_pay'] = $db->GetOne('SELECT COUNT(*)' . ' FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('await_pay'));
	$status['await_pay'] = CS_AWAIT_PAY;
	
	/* “未确认”的订单 */
	$order_count['unconfirmed'] = $db->GetOne('SELECT COUNT(*) FROM ' . $ecs->table('order_info') . " WHERE 1 $ex_where " . order_query_sql('unconfirmed'));
	$status['unconfirmed'] = OS_UNCONFIRMED;
	$smarty->assign('order_count', $order_count);

	$composite_status = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : - 1;
        $smarty->assign('composite_status', $composite_status);
	$merge = get_user_merge($user_id);
	$smarty->assign('merge', $merge);
	$smarty->assign('pager', $pager);
	$smarty->assign('orders', $orders);
	$smarty->display('user_transaction.dwt');
}


function action_ajax_order_list(){
	//全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
        $last = isset($_REQUEST['last'])?trim($_REQUEST['last']):'';
        $amount = isset($_REQUEST['amount'])?trim($_REQUEST['amount']):'';
        include('includes/cls_json.php');
        include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'includes/lib_payment.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	include_once (ROOT_PATH . 'includes/lib_clips.php');
        $limit = " limit $last,$amount";//每次加载的个数
        $json   = new JSON; 
        // 过滤虚拟商品
	$where = " and o.extension_code <> 'virtual_good'";
	$composite_status = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : - 1;
	switch($composite_status)
	{
		case CS_AWAIT_PAY:
			$where .= order_query_sql('await_pay');
			break;
		
		case CS_AWAIT_SHIP:
			$where .= order_query_sql('await_ship');
			break;
		
		case CS_FINISHED:
			$where .= order_query_sql('finished');
			break;
		
		case SS_SHIPPED:
			$where .= order_query_sql('shipped');
			break;
		
		case OS_CANCELED:
			$where .= order_query_sql('canceled');
			break;
		
		case PS_PAYBACK:
			$where .= order_query_sql('payback');
			break;
		
		case CS_AWAIT_COMMENT:
			$where .= order_query_sql('await_comment');
			break;
                    
                case CS_AWAIT_RECEIPT:
                        $where .= order_query_sql('await_receipt');
			break;
		default:
			if($composite_status != - 1)
			{
				$where .= " AND o.order_status = '$composite_status' ";
			}
	}
	
	
	$orders = get_user_orders_ajax($user_id, $limit, $where);
                /* 获取即时通讯客服信息 */
        include_once ("includes/lib_chat.php");
        foreach($orders as $key=>$val){
            $GLOBALS['smarty']->assign('order',$val);
            $result[]['info']  = $GLOBALS['smarty']->fetch('library/user_order_list.lbi');
        }
    die($json->encode($result));
        
}


/* 商品评价/晒单 增加 by www.yshop100.com */
function action_my_comment()
{
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];
    $action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
    $min_time = gmtime() - 86400 * $_CFG['comment_youxiaoqi'];
    $state = empty($_REQUEST['state'])?'all':$_REQUEST['state'];
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = " AND o.extension_code <> 'virtual_good' ";
    switch ($state){
        case 'all' :
            break;
        case 'wait' :
            $where = " AND og.comment_state = ".COMMENT_UNCHECKED."  AND o.shipping_time_end > ".$min_time;
            break;
        case 'finish' :
            $where = " AND c.status = ".COMMENT_CHECKED;
            break;      
    }
    $count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('order_goods') . " AS og 
						  LEFT JOIN " . $ecs->table('order_info') . " AS o ON og.order_id=o.order_id ".
                                                 " LEFT JOIN " . $ecs->table('comment') . " AS c ON og.rec_id=c.rec_id
						  WHERE o.user_id = '$user_id' AND o.shipping_time_end > 0 AND og.is_back = 0 $where");
    $size = 10;
    $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

            $sql = "SELECT og.*, o.add_time, o.shipping_time_end, o.order_id, g.goods_thumb, g.shop_price, s.shaidan_id, s.pay_points AS 	shaidan_points, s.status AS shaidan_status, 
                            c.status AS comment_status,g.supplier_id,ifnull(ssc.value,'网站自营') AS shopname 
                            FROM " . $ecs->table('order_goods') . " AS og 
                            LEFT JOIN " . $ecs->table('order_info') . " AS o ON og.order_id=o.order_id
                            LEFT JOIN " . $ecs->table('goods') . " AS g ON og.goods_id=g.goods_id
                            LEFT JOIN " . $ecs->table('shaidan') . " AS s ON og.rec_id=s.rec_id
                            LEFT JOIN " . $ecs->table('comment') . " AS c ON og.rec_id=c.rec_id
                            LEFT JOIN " . $ecs->table('supplier_shop_config') . " AS ssc ON ssc.supplier_id=g.supplier_id AND ssc.code='shop_name'
                            WHERE o.user_id = '$user_id' AND o.shipping_time_end > 0 AND og.is_back = 0 " .$where. " ORDER BY o.add_time DESC ".
                            " LIMIT " . ($page - 1) * $size . ",$size";

       $res = $db->getAll($sql);
        foreach ($res as $key=>$row) {
            $row['thumb'] = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $row['url'] = build_uri('goods', array(
                'gid' => $row['goods_id']
                    ), $row['goods_name']);
            $row['add_time_str'] = local_date("Y-m-d", $row['add_time']);
            $row['goods_tags'] = $db->getAll("SELECT * FROM " . $ecs->table('goods_tag') . " WHERE goods_id = '$row[goods_id]'");
            $row['shop_price'] = price_format($row['shop_price']);
            $item_list[] = $row;
        }
        //代码增加 for 循环
        for ($i = 1; $i < count($item_list); $i++) {
            $item_list[$i]['o_id'] = $item_list[$i]['order_id'];
            unset($item_list[$i]['order_id']);
        }

        /* 获得评价信息　 */
        foreach ($item_list as $key => $value) {
            $sql_comm = "SELECT c.*, u.headimg FROM " . $ecs->table('comment') . " AS c " .
                    " LEFT JOIN " . $ecs->table('users') . " AS u ON c.user_name = u.user_name" .
                    " WHERE c.rec_id = '$value[rec_id]'";
            $comment = $db->getRow($sql_comm);
            $goods_id = $comment['id_value'];

            $res = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('goods_tag') . " WHERE goods_id = '$goods_id'");
            foreach ($res as $v) {
                $tags[$v['tag_id']] = $v['tag_name'];
            }

            if ($value['shaidan_id'] > 0) {
                $comment['shaidan'] = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('shaidan') . " WHERE shaidan_id = '$value[shaidan_id]'");
                $comment['shaidan_img'] = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('shaidan_img') . " WHERE shaidan_id = '$value[shaidan_id]'");
           
            }
            if ($comment['comment_tag']) {
                $comment_tag = explode(',', $comment['comment_tag']);
                foreach ($comment_tag as $tag_id) {
                    $comment['tags'][] = $tags[$tag_id];
                }
            }
            if ($comment['comment_id'] > 0) {
                $parent_res = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('comment') . " WHERE parent_id = '" . $comment['comment_id'] . "'");
                $comment['comment_reps'] = $parent_res;
            }
            $item_list[$key]['comment'] = $comment;
        }
            $smarty->assign('item_list', $item_list);

    $pager = get_pager('user.php', array(
        'act' => $action, 'state'=>$state
            ), $count, $page, $size);
    
    $smarty->assign('min_time',$min_time);
    $smarty->assign('state',$state);
    $smarty->assign('action',$action);
    $smarty->assign('pager', $pager);
    $smarty->display('user_transaction.dwt');
}


function action_my_comment_send()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$rec_id = $_POST['rec_id'];
	$now_date = date("Y-m-d");
	$now_time = time();
        //判断是否已经评价
        $res_comment_id = $db->getOne("select comment_id from ".$ecs->table('comment') ." where rec_id = $rec_id and user_id = $user_id");
        if($res_comment_id){
            echo "<script>alert('此订单商品您已经发表过评价！');location='user.php?act=my_comment';</script>";
            exit();
        }
        
	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$user_info = $db->getRow("SELECT * FROM " . $ecs->table('users') . " WHERE user_id = '$user_id'");
	$comment_type = 0;
	$id_value = $_POST['goods_id'];
	$email = $user_info['email'];
	$user_name = $user_info['user_name'];
	$content = $_POST['content'];
	$comment_rank = $_POST['comment_rank'];
	
	//代码增加 
	$server = $_POST['server'];
	$send = $_POST['send'];
	$shipping = $_POST['shipping'];
	$o_id = $_REQUEST['o_id'];
//
//	if(!$o_id)
//	{
//		$o_id = $_REQUEST['o1_id'];
//	}
	
	//代码增加 

	$add_time = gmtime();
	$ip_address = real_ip();
	$status = ($_CFG['comment_check'] == 1) ? 0 : 1;
	$hide_username = intval($_POST['hide_username']);
	$buy_time = $db->getOne("SELECT o.add_time FROM " . $ecs->table('order_info') . " AS o
							 LEFT JOIN " . $ecs->table('order_goods') . " AS og ON o.order_id=og.order_id
							 WHERE og.rec_id = '$rec_id'");
	
	/* 自定义标签 */
	$tags = ($_POST['comment_tag']) ? explode(",", $_POST['comment_tag']) : array();
	if(is_array($_POST['tags_zi']))
	{
		foreach($_POST["tags_zi"] as $tag)
		{
			$status = $_CFG['user_tag_check'];
			$db->query("INSERT INTO " . $ecs->table('goods_tag') . " (goods_id, tag_name, is_user, state) VALUES ('$id_value', '$tag', 1, '$status')");
			$tags[] = $db->insert_id();
		}
	}
	foreach($tags as $tagid)
	{
		if($tagid > 0)
		{
			$tagids[] = $tagid;
		}
	}
	$comment_tag = (is_array($tagids)) ? implode(",", $tagids) : '';

//代码增加o_id
	$sql = "INSERT INTO " . $ecs->table('comment') . "(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, user_id, status, rec_id, comment_tag, buy_time, hide_username, order_id)" . "VALUES ('$comment_type', '$id_value', '$email', '$user_name', '$content', '$comment_rank', '$add_time', '$ip_address', '$user_id', '$status', '$rec_id', '$comment_tag', '$buy_time', '$hide_username', '$o_id')";

	$db->query($sql);
	$db->query("UPDATE " . $ecs->table('order_goods') . " SET comment_state = 1 WHERE rec_id = '$rec_id'");

	//代码增加
	if($o_id)
	{
		$o_sn =  $db->getOne("SELECT order_sn FROM " . $ecs->table('order_info') . " 				
							 WHERE order_id = '$o_id'");
		$sql = "INSERT INTO " . $ecs->table('shop_grade') . "(user_id, user_name, add_time,  server, send, shipping, order_id, order_sn)" . "VALUES ('$user_id', '$user_name', '$add_time', '$server', '$send', '$shipping', '$o_id', '$o_sn')";
		$db->query($sql);

		//评价赠送积分
		$pingjia = $db->getOne("SELECT pid FROM " . $ecs->table('pingjia') . " WHERE order_sn = '$o_sn'");
		if(!$pingjia){
			$row = $db->getRow("SELECT num, mincomment_rank, minserver, minsend, minshipping FROM " . $ecs->table('pingjiaconf') . " WHERE startymd < '$now_date' AND endymd > '$now_date' ORDER BY cid DESC LIMIT 1");
			if($row){
				extract($row);
				if($comment_rank >= $mincomment_rank && $server >= $minserver && $send >= $minsend && $shipping >= $minshipping && $num > 0){
					$addnum = $num;
					$db->query("INSERT INTO " . $ecs->table('pingjia') . "(`user_id`,`addtime`,`addymd`, `order_sn`) " . "values('$user_id','$now_time','$now_date', '$o_sn')");
					log_account_change($user_id, '', '', '', $addnum, '订单 '.$o_sn.' 评价赠送积分 '.$now_date, ACT_ADJUSTING);
				}
			}
		}
	}
	//代码增加
	
	clear_cache_files();
	
	if($status == 0)
	{
		$msg = '您的信息提交成功，需要管理员审核后才能显示！';
	}
	else
	{
		$msg = '您的信息提交成功！';
	}

	if($addnum > 0){
		$msg .= '增加'.$addnum.'积分';
	}
	echo "<script>alert('$msg');self.location='user.php?act=my_comment';</script>";
	exit();
}

function action_shaidan_send()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
        $simgnum = intval($_CFG['simgnum'])?intval($_CFG['simgnum']):4;
	$rec_id = intval($_GET['id']);
	$goods = $db->getRow("SELECT o.rec_id,g.* FROM " . $ecs->table('order_goods') . " as o left join ".$ecs->table('goods')." as g on o.goods_id = g.goods_id WHERE rec_id = '$rec_id'");
        $goods['goods_thumb'] = get_pc_url().'/'.get_image_path($goods['goods_id'],$goods['goods_thumb']);
        $smarty->assign('shaidan_img', get_array($simgnum));
        $smarty->assign('goods', $goods);
	$smarty->display('shaidan_order.dwt');
}

function action_shaidan_save()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
        $simgnum = intval($_CFG['simgnum'])?intval($_CFG['simgnum']):5;
	include_once (dirname(__FILE__) . '/includes/cls_image.php');
	$image = new cls_image($_CFG['bgcolor']);
	
	$rec_id = intval($_POST['rec_id']);
	$goods_id = intval($_POST['goods_id']);
	$title = trim($_POST['title']);
	$message = $_POST['message'];
	$add_time = gmtime();
	$status = $_CFG['shaidan_check'];
	$hide_username = intval($_POST['hide_username']);
        $res_shaidan_id = $db->getOne("select shaidan_id from ".$ecs->table('shaidan') ." where rec_id = $rec_id and user_id = $user_id");
        if($res_shaidan_id){
            echo "<script>alert('此订单商品您已经发表过晒单！');location='user.php?act=my_comment';</script>";
            exit();
        }else{
        $sql = "INSERT INTO " . $ecs->table('shaidan') . "(rec_id, goods_id, user_id, title, message, add_time, status, hide_username)" . "VALUES ('$rec_id', '$goods_id', '$user_id', '$title', '$message', '$add_time', '$status', '$hide_username')";
        $db->query($sql);
        $shaidan_id = $db->insert_id();
	// 处理图片
        for($i=0;$i<$simgnum;$i++){
            $img_srcs[$i] = $_FILES['img_srcs'.$i];
        }
        include_once(ROOT_PATH . 'includes/cls_image.php');
        $path_a = "images/Image/".date('Ym')."/";
        $path = "./../".$path_a;
        if (!file_exists($path)){
            mkdir ($path);   
        }    
        foreach($img_srcs as $k=>$v){ 
            if(!empty($v["name"])){
             $arr=explode(".", $v["name"]);
             $hz=$arr[count($arr)-1];
             $v["name"] = time().'_'.$k.'.'.$hz;
             if (file_exists($path . $v["name"])) 
                { 
                    $msg = '该文件已经存在！';
                } 
                else 
                { 
                    if(move_uploaded_file($v["tmp_name"],$path . $v["name"])){
                   
                        ini_set("memory_limit",-1);
                        $thumb = $image->make_thumb($path.$v["name"], 100, 100);
                        
                        $path_img = $path_a.$v["name"];
                        $sql = "INSERT INTO " . $ecs->table('shaidan_img') . "(shaidan_id, `desc`, image, thumb)" . "VALUES ('$shaidan_id', '" . $v["name"] . "', '$path_img', '$thumb')";
                        $db->query($sql);
                    }else{
                            echo "<script>alert('图片上传失败，请重新提交！');self.location='user.php?act=shaidan_send&id=$rec_id';</script>";
                            exit();
                    }
                   //echo "存储路径: " . $path . $v["name"];    
                } 
            }
        }     
     
        
	// 需要审核
	if($status == 0)
	{
		$msg = '您的信息提交成功，需要管理员审核后才能显示！';
	}
	
	// 不需要审核
	else
	{
		$info = $db->GetRow("SELECT * FROM " . $ecs->table('shaidan') . " WHERE shaidan_id='$shaidan_id'");
		// 该商品第几位晒单者
		$res = $db->getAll("SELECT shaidan_id FROM " . $ecs->table("shaidan") . " WHERE goods_id = '$info[goods_id]' ORDER BY add_time ASC");
		foreach($res as $key => $value)
		{
			if($shaidan_id == $value['shaidan_id'])
			{
				$weizhi = $key + 1;
			}
		}
		// 图片数量
		$imgnum = count($img_srcs);
		
		// 是否赠送积分
		if($info['is_points'] == 0 && $weizhi <= $_CFG['shaidan_pre_num'] && $imgnum >= $_CFG['shaidan_img_num'])
		{
			$pay_points = $_CFG['shaidan_pay_points'];
			$db->query("UPDATE " . $ecs->table('shaidan') . " SET pay_points = '$pay_points', is_points = 1 WHERE shaidan_id = '$shaidan_id'");
			$db->query("INSERT INTO " . $ecs->table('account_log') . "(user_id, rank_points, pay_points, change_time, change_desc, change_type) " . "VALUES ('$info[user_id]', 0, '" . $pay_points . "', " . gmtime() . ", '晒单获得积分', '99')");
			$log = $db->getRow("SELECT SUM(rank_points) AS rank_points, SUM(pay_points) AS pay_points FROM " . $ecs->table("account_log") . " WHERE user_id = '$info[user_id]'");
			$db->query("UPDATE " . $ecs->table('users') . " SET rank_points = '" . $log['rank_points'] . "', pay_points = '" . $log['pay_points'] . "' WHERE user_id = '$info[user_id]'");
		}
		
		$msg = '您的信息提交成功！';
            }

            $db->query("UPDATE " . $ecs->table('order_goods') . " SET shaidan_state = 1 WHERE rec_id = '$rec_id'"); 
        }

	echo "<script>alert('$msg');self.location='user.php?act=my_comment';</script>";
	exit();
}

/* 查看订单详情 */
function action_order_detail()
{
	//全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$is_pay = isset($_REQUEST['is_pay'])?intval($_REQUEST['is_pay']):0;

	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'includes/lib_payment.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	include_once (ROOT_PATH . 'includes/lib_clips.php');
	include_once (ROOT_PATH . 'kuaidi/kuaidi.php');
	
	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

	/* 订单详情 */
	$order = get_order_detail($order_id, $user_id);

//	if($is_pay == 1 && $order['pay_name'] == '微信支付'){
//		header("Location: {$order[pay_online]}");
//		exit;
//	}

	if($order === false)
	{
		$GLOBALS['err']->show($_LANG['back_home_lnk'], './');
		
		exit();
	}
	
	/* 是否显示添加到购物车 */
	if($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods')
	{
		$smarty->assign('allow_to_cart', 1);
	}
	
	/* 订单商品 */
	$goods_list = order_goods($order_id);
	foreach($goods_list as $key => $value)
	{
		$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
		$goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
		$goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
	}
	//mantsui
	$suppliers_id = $goods_list[0]['suppliers_id'];
	
	/* 设置能否修改使用余额数 */
	if($order['order_amount'] > 0)
	{
		if($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)
		{
			$user = user_info($order['user_id']);
			if($user['user_money'] + $user['credit_line'] > 0)
			{
				$smarty->assign('allow_edit_surplus', 1);
                                $order_surplus = floatval($order['order_amount'])>floatval($user['user_money'])?$user['user_money']:$order['order_amount'];
				$smarty->assign('order_surplus', $order_surplus);
                                $smarty->assign('max_surplus', sprintf($_LANG['max_surplus'], $user['user_money']));
			}
		}
	}
	
	/* 未发货，未付款时允许更换支付方式 */
	if($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED)
	{
		$payment_list = available_payment_list(false, 0, true, $suppliers_id);

		/* 过滤掉当前支付方式和余额支付方式 */
		if(is_array($payment_list))
		{
			foreach($payment_list as $key => $payment)
			{
				//if($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance')
                            if($payment['pay_code'] == 'balance')
				{
					unset($payment_list[$key]);
				}
			}
		}
		$smarty->assign('payment_list', $payment_list);
		
		
	}
	$iszhigou=$suppliers_id;
	
	$sql = 'SELECT * FROM `ecs_period_config` where id>0';
	$period_list = $GLOBALS['db']->getAll($sql); 
	
	$sql = 'SELECT minorderamount FROM `ecs_period_config` where id=0';
	$minorderamount = $GLOBALS['db']->getOne($sql);
	
	$sql = 'SELECT maxorderamount FROM `ecs_period_config` where id=0';
	$maxorderamount = $GLOBALS['db']->getOne($sql);
	
	$sql = 'SELECT * FROM `ecs_users_period` where user_id='.$_SESSION['user_id'];
	$period_user = $GLOBALS['db']->getRow($sql);
	
	$sql = "select value from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_fuyuan'";
	$isksf = $GLOBALS['db']->getOne($sql);
	
	$sql = "select open_quickpay from " . $GLOBALS['ecs']->table('users') . " where user_id=".$_SESSION['user_id'];
	$open_quickpay = $GLOBALS['db']->getOne($sql);
		
	
	$sql = 'SELECT min(minpoints) FROM `ecs_period_points`  ';
	$minpoints = $GLOBALS['db']->getOne($sql);
	
        $handler = get_order_handler($order);

	/* 订单 支付 配送 状态语言项 */
	$order['order_status'] = $_LANG['os'][$order['order_status']];
	$order['pay_status'] = $_LANG['ps'][$order['pay_status']];
	$order['shipping_status'] = $_LANG['ss'][$order['shipping_status']];
        $order['topay'] = in_array($order['pay_id'],payment_id_list(true));
	// 快递跟踪
	$kuaidi = new Express();
//	$invoices = explode('<br>',$order['invoice']);
//	foreach ($invoices as $invoice_info)
//	{
//		$result = $kuaidi->getorder($order['shipping_name'], $invoice_info);
//		$kuaidis[] = $result['data'][0];
//	}
        $sql = "SELECT delivery_id,shipping_name,invoice_no  FROM ". $GLOBALS['ecs']->table('delivery_order'). " WHERE order_id = '$order_id'AND user_id = '$user_id'";  
        $wuliu = $GLOBALS['db']->getAll($sql); 
        $kuaidi_list = array();
        foreach($wuliu as $key=>$value){
            if($value['shipping_name'] == '同城快递'){
                    $result = getkosorder($value['invoice_no']);
            }else{
                    $result = $kuaidi->getorder($value['shipping_name'],$value['invoice_no']);
            }
            $kuaidi_list[$value['delivery_id']]['data'] = $result['data'][0];
            $kuaidi_list[$value['delivery_id']]['shipping_name'] = $value['shipping_name'];
            $kuaidi_list[$value['delivery_id']]['invoice_no'] = $value['invoice_no'];
        }
	$smarty->assign('kuaidi_list', $kuaidi_list);
	$smarty->assign('order', $order);
        $smarty->assign('handler',$handler);
	$smarty->assign('goods_list', $goods_list);
        $smarty->assign('is_pay',$is_pay);
	$smarty->display('user_transaction.dwt');
}

/* 取消订单 */
function action_cancel_order()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	
	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
	
	if(cancel_order($order_id, $user_id))
	{
		ecs_header("Location: user.php?act=order_list\n");
		exit();
	}
	else
	{
		$GLOBALS['err']->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
	}
}

/* 收货地址列表界面 */
function action_address_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
	$smarty->assign('lang', $_LANG);
	
	/* 取得国家列表、商店所在国家、商店所在国家的省列表 */
	$smarty->assign('country_list', get_regions());
	$smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));
	
	/* 获得用户所有的收货人信息 */
	$consignee_list = get_consignee_list($_SESSION['user_id']);
	
//	if(count($consignee_list) < 5 && $_SESSION['user_id'] > 0)
//	{
//		/* 如果用户收货人信息的总数小于5 则增加一个新的收货人信息 */
//		$consignee_list[] = array(
//			'country' => $_CFG['shop_country'],'email' => isset($_SESSION['email']) ? $_SESSION['email'] : ''
//		);
//	}
	 foreach($consignee_list as $k=>$v){
                $consignee_list[$k]['country_name'] = get_region_info_wap($v['country']);
                $consignee_list[$k]['province_name'] = get_region_info_wap($v['province']);
                $consignee_list[$k]['city_name'] = get_region_info_wap($v['city']);
                $consignee_list[$k]['district_name'] = get_region_info_wap($v['district']);
                $consignee_list[$k]['xiangcun_name'] = get_region_info_wap($v['xiangcun']);
            }
	$smarty->assign('consignee_list', $consignee_list);
	
	// 取得国家列表，如果有收货人列表，取得省市区列表
	foreach($consignee_list as $region_id => $consignee)
	{
		$consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
		$consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
		$consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;
		$consignee['district'] = isset($consignee['district']) ? intval($consignee['district']) : 0;
		$province_list[$region_id] = get_regions(1, $consignee['country']);
		$city_list[$region_id] = get_regions(2, $consignee['province']);
		$district_list[$region_id] = get_regions(3, $consignee['city']);
                $xiangcun_list[$region_id] = get_regions(4, $consignee['district']);
	}
	
	/* 获取默认收货ID */
	$address_id = $db->getOne("SELECT address_id FROM " . $ecs->table('users') . " WHERE user_id='$user_id'");
	
	// 赋值于模板
	$smarty->assign('real_goods_count', 1);
	$smarty->assign('shop_country', $_CFG['shop_country']);
	$smarty->assign('shop_province', get_regions(1, $_CFG['shop_country']));
	$smarty->assign('province_list', $province_list);
	$smarty->assign('address', $address_id);
	$smarty->assign('city_list', $city_list);
	$smarty->assign('district_list', $district_list);
        $smarty->assign('xiangcun_list', $xiangcun_list);
	$smarty->assign('currency_format', $_CFG['currency_format']);
	$smarty->assign('integral_scale', $_CFG['integral_scale']);
	$smarty->assign('name_of_region', array(
		$_CFG['name_of_region_1'],$_CFG['name_of_region_2'],$_CFG['name_of_region_3'],$_CFG['name_of_region_4']
	));
	
	$smarty->display('user_transaction.dwt');
}

function action_address(){
    	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
        $address_id = empty($_REQUEST['address_id'])?0:intval($_REQUEST['address_id']);
	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
	$smarty->assign('lang', $_LANG);
	
	/* 取得国家列表、商店所在国家、商店所在国家的省列表 */
	$smarty->assign('country_list', get_regions());
	$smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));
	
	/* 获得用户所有的收货人信息 */
        
	$consignee = get_consignee_by_id($address_id);
	
	$smarty->assign('consignee', $consignee);

	// 取得国家列表，如果有收货人列表，取得省市区列表
                                 
		$consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 1;
		$consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : -1;
		$consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : -1;
                $consignee['district'] = isset($consignee['district']) ? intval($consignee['district']) : -1;
                $province_list = get_regions_wap($consignee['country']);
		$city_list = get_regions_wap($consignee['province']);
		$district_list = get_regions_wap($consignee['city']);
                $xiangcun_list = get_regions_wap($consignee['district']);

	// 赋值于模板
	$smarty->assign('real_goods_count', 1);
	$smarty->assign('shop_country', $_CFG['shop_country']);
	$smarty->assign('shop_province', get_regions(1, $_CFG['shop_country']));
	$smarty->assign('province_list', $province_list);
	$smarty->assign('city_list', $city_list);
	$smarty->assign('district_list', $district_list);
        $smarty->assign('xiangcun_list', $xiangcun_list);
        $smarty->assign('address_id',$address_id);
	$smarty->assign('currency_format', $_CFG['currency_format']);
	$smarty->assign('integral_scale', $_CFG['integral_scale']);
	$smarty->assign('name_of_region', array(
	$_CFG['name_of_region_1'],$_CFG['name_of_region_2'],$_CFG['name_of_region_3'],$_CFG['name_of_region_4']
	));
	
	$smarty->display('user_transaction.dwt');
}


/* 添加/编辑收货地址的处理 */
function action_act_edit_address()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
	$smarty->assign('lang', $_LANG);
	$address = array(
		'user_id' => $user_id,'address_id' => intval($_POST['address_id']),'country' => isset($_POST['country']) ? intval($_POST['country']) : 0,'province' => isset($_POST['province']) ? intval($_POST['province']) : 0,'city' => isset($_POST['city']) ? intval($_POST['city']) : 0,'district' => isset($_POST['district']) ? intval($_POST['district']) : 0,'xiangcun' => isset($_POST['xiangcun']) ? intval($_POST['xiangcun']) : 0,'address' => isset($_POST['address']) ? compile_str(trim($_POST['address'])) : '','consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee'])) : '','email' => isset($_POST['email']) ? compile_str(trim($_POST['email'])) : '','tel' => isset($_POST['tel']) ? compile_str(make_semiangle(trim($_POST['tel']))) : '','mobile' => isset($_POST['mobile']) ? compile_str(make_semiangle(trim($_POST['mobile']))) : '',
		'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time'])) : '','sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '','zipcode' => isset($_POST['zipcode']) ? compile_str(make_semiangle(trim($_POST['zipcode']))) : ''
	);
	
	if(update_address($address))
	{
		show_message($_LANG['edit_address_success'], $_LANG['address_list_lnk'], 'user.php?act=address_list');
	}
}

/* 设置默认地址 */
function action_set_address(){
    	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
        $user_id = $GLOBALS['user_id'];
        $address_id = empty($_REQUEST['address_id'])?0:intval($_REQUEST['address_id']);
        if($db->query("UPDATE " . $ecs->table('users') . " set address_id = $address_id  WHERE user_id='$user_id'")){ 
            show_message("默认地址设置成功", $_LANG['address_list_lnk'], 'user.php?act=address_list');
        }
}
/* 删除收货地址 */
function action_drop_consignee()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once ('includes/lib_transaction.php');
	
	$consignee_id = intval($_GET['id']);
	
	if(drop_consignee($consignee_id))
	{
		ecs_header("Location: user.php?act=address_list\n");
		exit();
	}
	else
	{
		show_message($_LANG['del_address_false']);
	}
}

/* 显示收藏商品列表 */
function action_collection_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('collect_goods') . " WHERE user_id='$user_id' ORDER BY add_time DESC");
	
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	$smarty->assign('pager', $pager);
	$smarty->assign('goods_list', get_collection_goods($user_id, $pager['size'], $pager['start']));
	$smarty->assign('url', $ecs->url());
	$lang_list = array(
		'UTF8' => $_LANG['charset']['utf8'],'GB2312' => $_LANG['charset']['zh_cn'],'BIG5' => $_LANG['charset']['zh_tw']
	);
	$smarty->assign('lang_list', $lang_list);
	$smarty->assign('user_id', $user_id);
	$smarty->display('user_clips.dwt');
}

/* 删除收藏的商品 */
function action_delete_collection()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
        include('includes/cls_json.php');
        $json   = new JSON; 

	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$collection_id = isset($_REQUEST['collection_id']) ? intval($_REQUEST['collection_id']) : 0;
	
	if($collection_id > 0)
	{
		$res = $db->query('DELETE FROM ' . $ecs->table('collect_goods') . " WHERE rec_id='$collection_id' AND user_id ='$user_id'");
	}
        if($res){
            $result['message']="删除成功!";
        }else{
            $result['message']="删除失败!";
        }
	//ecs_header("Location: user.php?act=collection_list\n");
        die($json->encode($result));
	exit();
}

/* 添加关注商品 */
function action_add_to_attention()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$rec_id = (int)$_GET['rec_id'];
	if($rec_id)
	{
		$db->query('UPDATE ' . $ecs->table('collect_goods') . "SET is_attention = 1 WHERE rec_id='$rec_id' AND user_id ='$user_id'");
	}
	ecs_header("Location: user.php?act=collection_list\n");
	exit();
}
/* 取消关注商品 */
function action_del_attention()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$rec_id = (int)$_GET['rec_id'];
	if($rec_id)
	{
		$db->query('UPDATE ' . $ecs->table('collect_goods') . "SET is_attention = 0 WHERE rec_id='$rec_id' AND user_id ='$user_id'");
	}
	ecs_header("Location: user.php?act=collection_list\n");
	exit();
}

//显示关注店铺列表
function action_follow_shop()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$action = $GLOBALS['action'];

	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('supplier_guanzhu') . " WHERE userid='$user_id'");
	
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	$smarty->assign('pager', $pager);
	$smarty->assign('shop_list', get_follow_shops($user_id, $pager['size'], $pager['start']));
	$smarty->assign('url', $ecs->url());
	$lang_list = array(
		'UTF8' => $_LANG['charset']['utf8'], 'GB2312' => $_LANG['charset']['zh_cn'], 'BIG5' => $_LANG['charset']['zh_tw']
	);
	$smarty->assign('lang_list', $lang_list);
	$smarty->assign('user_id', $user_id);
	$smarty->display('user_clips.dwt');
}

/* 取消关注店铺 */
function action_del_follow()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
        include('includes/cls_json.php');
        $json   = new JSON; 
	$rec_id = empty($_REQUEST['rec_id'])?0:  intval($_REQUEST['rec_id']);
	if($rec_id)
	{
		$res = $db->query('DELETE FROM ' . $ecs->table('supplier_guanzhu') . " WHERE id='$rec_id' AND userid ='$user_id'");
	}
        if($res){
            $result['message'] ="删除成功!";
        }else{
            $result['message'] ="删除失败!";
        }
        die($json->encode($result));
	//ecs_header("Location: user.php?act=follow_shop\n");
	exit();
}
/* 显示留言列表 */
function action_message_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];

	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	$order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
	$order_info = array();
	
	/* 获取用户留言的数量 */
	if($order_id)
	{
		$sql = "SELECT COUNT(*) FROM " . $ecs->table('feedback') . " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
		$order_info = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id' AND user_id = '$user_id'");
		$order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
	}
	else
	{
		$sql = "SELECT COUNT(*) FROM " . $ecs->table('feedback') . " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
	}
	
	$record_count = $db->getOne($sql);
	
	if($order_id != '')
	{
		$act['order_id'] = $order_id;
	}
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page, 5);

	$message_board = isset($GLOBALS['_CFG']['message_board'])?$GLOBALS['_CFG']['message_board']:1;
	$smarty->assign('message_list', get_message_list($user_id, $_SESSION['user_name'], $pager['size'], $pager['start'], $order_id));
	$smarty->assign('message_board',$message_board);
        $smarty->assign('pager', $pager);
	$smarty->assign('order_info', $order_info);
	$smarty->display('user_clips.dwt');
}

/* 显示评论列表 */
function action_comment_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	/* 获取用户留言的数量 */
	$sql = "SELECT COUNT(*) FROM " . $ecs->table('comment') . " WHERE parent_id = 0 AND user_id = '$user_id'";
	$record_count = $db->getOne($sql);
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page, 5);
	$smarty->assign('comment_list', get_comment_list($user_id, $pager['size'], $pager['start']));
	$smarty->assign('pager', $pager);
	$smarty->display('user_clips.dwt');
}

/* 添加我的留言 */
function action_act_add_message()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$message = array(
		'user_id' => $user_id,'user_name' => $_SESSION['user_name'],'user_email' => $_SESSION['email'],'msg_type' => isset($_POST['msg_type']) ? intval($_POST['msg_type']) : 0,'msg_title' => isset($_POST['msg_title']) ? trim($_POST['msg_title']) : '','msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '','order_id' => empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (! isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none') ? $_FILES['message_img'] : array()
	);
	
	if(add_message($message))
	{
		show_message($_LANG['add_message_success'], $_LANG['message_list_lnk'], 'user.php?act=message_list&order_id=' . $message['order_id'], 'info');
	}
	else
	{
		$GLOBALS['err']->show($_LANG['message_list_lnk'], 'user.php?act=message_list');
	}
}

/* 标签云列表 */
function action_tag_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$good_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	
	$smarty->assign('tags', get_user_tags($user_id));
	$smarty->assign('tags_from', 'user');
	$smarty->display('user_clips.dwt');
}

/* 删除标签云的处理 */
function action_act_del_tag()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$tag_words = isset($_GET['tag_words']) ? trim($_GET['tag_words']) : '';
	delete_tag($tag_words, $user_id);
	
	ecs_header("Location: user.php?act=tag_list\n");
	exit();
}

/* 显示缺货登记列表 */
function action_booking_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	/* 获取缺货登记的数量 */
	$sql = "SELECT COUNT(*) " . "FROM " . $ecs->table('booking_goods') . " AS bg, " . $ecs->table('goods') . " AS g " . "WHERE bg.goods_id = g.goods_id AND user_id = '$user_id'";
	$record_count = $db->getOne($sql);
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	
	$smarty->assign('booking_list', get_booking_list($user_id, $pager['size'], $pager['start']));
	$smarty->assign('pager', $pager);
	$smarty->display('user_clips.dwt');
}
/* 添加缺货登记页面 */
function action_add_booking()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if($goods_id == 0)
	{
		show_message($_LANG['no_goods_id'], $_LANG['back_page_up'], '', 'error');
	}
	
	/* 根据规格属性获取货品规格信息 */
	$goods_attr = '';
	if($_GET['spec'] != '')
	{
		$goods_attr_id = $_GET['spec'];
		
		$attr_list = array();
		$sql = "SELECT a.attr_name, g.attr_value " . "FROM " . $ecs->table('goods_attr') . " AS g, " . $ecs->table('attribute') . " AS a " . "WHERE g.attr_id = a.attr_id " . "AND g.goods_attr_id " . db_create_in($goods_attr_id);
		$res = $db->query($sql);
		while($row = $db->fetchRow($res))
		{
			$attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
		}
		$goods_attr = join(chr(13) . chr(10), $attr_list);
	}
	$smarty->assign('goods_attr', $goods_attr);
	
	$smarty->assign('info', get_goodsinfo($goods_id));
	$smarty->display('user_clips.dwt');
}

/* 添加缺货登记的处理 */
function action_act_add_booking()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$booking = array(
		'goods_id' => isset($_POST['id']) ? intval($_POST['id']) : 0,'goods_amount' => isset($_POST['number']) ? intval($_POST['number']) : 0,'desc' => isset($_POST['desc']) ? trim($_POST['desc']) : '','linkman' => isset($_POST['linkman']) ? trim($_POST['linkman']) : '','email' => isset($_POST['email']) ? trim($_POST['email']) : '','tel' => isset($_POST['tel']) ? trim($_POST['tel']) : '','booking_id' => isset($_POST['rec_id']) ? intval($_POST['rec_id']) : 0
	);
	
	// 查看此商品是否已经登记过
	$rec_id = get_booking_rec($user_id, $booking['goods_id']);
	if($rec_id > 0)
	{
		show_message($_LANG['booking_rec_exist'], $_LANG['back_page_up'], '', 'error');
	}
	
	if(add_booking($booking))
	{
		show_message($_LANG['booking_success'], $_LANG['back_booking_list'], 'user.php?act=booking_list', 'info');
	}
	else
	{
		$GLOBALS['err']->show($_LANG['booking_list_lnk'], 'user.php?act=booking_list');
	}
}

/* 删除缺货登记 */
function action_act_del_booking()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if($id == 0 || $user_id == 0)
	{
		ecs_header("Location: user.php?act=booking_list\n");
		exit();
	}
	
	$result = delete_booking($id, $user_id);
	if($result)
	{
		ecs_header("Location: user.php?act=booking_list\n");
		exit();
	}
}

/* 确认收货 */
function action_affirm_received()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];

	require_once(ROOT_PATH . '/includes/lib_order.php');
	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
	
	if(affirm_received($order_id, $user_id))
	{
		if($GLOBALS['_CFG']['distrib_style'] == 0)
		{
			include_once (ROOT_PATH . 'includes/lib_v_user.php');
			//确认收货，自动分成
			$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    		empty($affiliate) && $affiliate = array();
			$separate_by = $affiliate['config']['separate_by'];
			//获取订单分成金额
			$split_money = get_split_money_by_orderid($order_id);
			$row = $GLOBALS['db']->getRow("SELECT o.order_sn,u.parent_id, o.is_separate,(o.goods_amount - o.discount) AS goods_amount, o.user_id,o.supplier_id  FROM " . $GLOBALS['ecs']->table('order_info') . " o"." LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id"." WHERE order_id = '$order_id'");
    		$order_sn = $row['order_sn'];
			if($row['supplier_id'] == 0 || $GLOBALS['_CFG']['is_add_distrib'] == 1)
			{
				if($split_money > 0)
				{
					$num = count($affiliate['item']);
					for ($i=0; $i < $num; $i++)
					{
						$affiliate['item'][$i]['level_money'] = (float)$affiliate['item'][$i]['level_money'];
						if($affiliate['config']['level_money_all']==100 )
						{
							$setmoney = $split_money;
						}
						else 
						{
							if ($affiliate['item'][$i]['level_money'])
							{
								$affiliate['item'][$i]['level_money'] /= 100;
							}
							$setmoney = round($split_money * $affiliate['item'][$i]['level_money'], 2);
						}
						$row = $GLOBALS['db']->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
										" LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
										" WHERE o.user_id = '$row[user_id]'"
								);
						$up_uid = $row['user_id'];
						if (empty($up_uid) || empty($row['user_name']))
						{
							break;
						}
						else
						{
							$info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, 0);
							push_user_msg($up_uid,$order_sn,$setmoney);
							insert_affiliate_log($order_id, $up_uid, $row['user_name'], $setmoney, $separate_by,$_LANG['order_separate']);
						}
						$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
							   " SET is_separate = 1" .
							   " WHERE order_id = '$order_id'";
						$db->query($sql);
					}
					$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : "/mobile/";
					$autoUrl = str_replace($_SERVER['REQUEST_URI'],"",$GLOBALS['ecs']->url());
					@file_get_contents($autoUrl."/weixin/auto_do.php?type=1&is_affiliate=1");
				} 
			}
		}
		ecs_header("Location: user.php?act=order_list\n");
		exit();
	}
	else
	{
		$GLOBALS['err']->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
	}
}

/* 会员退款申请界面 */
function action_account_raply()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];

    /*系统设置费率为零时 微信端则不显示*/
    $smarty->assign('deposit_least_rate',$_CFG['deposit_least_rate']);
	$smarty->assign('user_info', get_user_info());
	$smarty->display('user_transaction.dwt');
}

/* 会员预付款界面 */
function action_account_deposit()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$account = get_surplus_info($surplus_id);
	
	$smarty->assign('payment', get_online_payment_list(false));
	$smarty->assign('order', $account);
	$smarty->display('user_transaction.dwt');
}

/* 会员账目明细界面 */
function action_account_detail()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	$account_type = 'user_money';
	
	/* 获取记录条数 */
	$sql = "SELECT COUNT(*) FROM " . $ecs->table('account_log') . " WHERE user_id = '$user_id'" . " AND $account_type <> 0 ";
	$record_count = $db->getOne($sql);
	
	// 分页函数
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	
	// 获取剩余余额
	$surplus_amount = get_user_surplus($user_id);
	if(empty($surplus_amount))
	{
		$surplus_amount = 0;
	}
	
	// 获取余额记录
	$account_log = array();
	$sql = "SELECT * FROM " . $ecs->table('account_log') . " WHERE user_id = '$user_id'" . " AND $account_type <> 0 " . " ORDER BY log_id DESC";
	$res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
	while($row = $db->fetchRow($res))
	{
		$row['change_time'] = local_date($_CFG['date_format'], $row['change_time']);
		$row['type'] = $row[$account_type] > 0 ? $_LANG['account_inc'] : $_LANG['account_dec'];
		$row['user_money'] = price_format(abs($row['user_money']), false);
		$row['frozen_money'] = price_format(abs($row['frozen_money']), false);
		$row['rank_points'] = abs($row['rank_points']);
		$row['pay_points'] = abs($row['pay_points']);
		$row['short_change_desc'] = sub_str($row['change_desc'], 60);
		$row['amount'] = $row[$account_type];
		$account_log[] = $row;
	}
	
	// 模板赋值
	$smarty->assign('surplus_amount', price_format($surplus_amount, false));
	$smarty->assign('account_log', $account_log);
	$smarty->assign('pager', $pager);
	$smarty->display('user_transaction.dwt');
}

/* 会员充值和提现申请记录 */
function action_account_log()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	/* 获取记录条数 */
	$sql = "SELECT COUNT(*) FROM " . $ecs->table('user_account') . " WHERE user_id = '$user_id'" . " AND process_type " . db_create_in(array(
		SURPLUS_SAVE,SURPLUS_RETURN
	));
	$record_count = $db->getOne($sql);
	
	// 分页函数
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	
	// 获取剩余余额
	$surplus_amount = get_user_surplus($user_id);
	if(empty($surplus_amount))
	{
		$surplus_amount = 0;
	}
	
	// 获取余额记录
	$account_log = get_account_log($user_id, $pager['size'], $pager['start']);
	
	// 模板赋值
	$smarty->assign('surplus_amount', price_format($surplus_amount, false));
	$smarty->assign('account_log', $account_log);
	$smarty->assign('pager', $pager);
	$smarty->display('user_transaction.dwt');
}

/* 对会员余额申请的处理 */
function action_act_account()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
	if($amount <= 0)
	{
		show_message($_LANG['amount_gt_zero']);
	}
	
	/* 变量初始化 */
	$surplus = array(
		'user_id' => $user_id,'rec_id' => ! empty($_POST['rec_id']) ? intval($_POST['rec_id']) : 0,'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,'payment_id' => isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0,'user_note' => isset($_POST['user_note']) ? trim($_POST['user_note']) : '','amount' => $amount
	);
	
	/* 退款申请的处理 */
	if($surplus['process_type'] == 1)
	{
		/* 判断是否有足够的余额的进行退款的操作 */
		$sur_amount = get_user_surplus($user_id);
		if($amount > $sur_amount)
		{
			$content = $_LANG['surplus_amount_error'];
			show_message($content, $_LANG['back_page_up'], '', 'info');
		}
		
		// 插入会员账目明细
		$amount = '-' . $amount;
		$surplus['payment'] = '';
		$surplus['rec_id'] = insert_user_account($surplus, $amount);
		
		/* 如果成功提交 */
		if($surplus['rec_id'] > 0)
		{
			$content = $_LANG['surplus_appl_submit'];
			show_message($content, $_LANG['back_account_log'], 'user.php?act=account_log', 'info');
		}
		else
		{
			$content = $_LANG['process_false'];
			show_message($content, $_LANG['back_page_up'], '', 'info');
		}
	}
	/* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
	else
	{
		if($surplus['payment_id'] <= 0)
		{
			show_message($_LANG['select_payment_pls']);
		}
		
		include_once (ROOT_PATH . 'includes/lib_payment.php');
		
		// 获取支付方式名称
		$payment_info = array();
		$payment_info = payment_info($surplus['payment_id']);
		$surplus['payment'] = $payment_info['pay_name'];
		
		if($surplus['rec_id'] > 0)
		{
			// 更新会员账目明细
			$surplus['rec_id'] = update_user_account($surplus);
		}
		else
		{
			// 插入会员账目明细
			$surplus['rec_id'] = insert_user_account($surplus, $amount);
		}
		
		// 取得支付信息，生成支付代码
		$payment = unserialize_config($payment_info['pay_config']);
		
		// 生成伪订单号, 不足的时候补0
		$order = array();
		$order['order_sn'] = $surplus['rec_id'];
		$order['user_name'] = $_SESSION['user_name'];
		$order['surplus_amount'] = $amount;
		$order['order_id'] 	= $surplus['rec_id'].'-'.$amount;
		// 计算支付手续费用
		$payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);
		
		// 计算此次预付款需要支付的总金额
		$order['order_amount'] = $amount + $payment_info['pay_fee'];
		
		// 记录支付log
		$order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type = PAY_SURPLUS, 0);
		
		/* 调用相应的支付方式文件 */
		include_once (ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');
		
		/* 取得在线支付方式的支付按钮 */
		$pay_obj = new $payment_info['pay_code']();
		if (! strpos ( $_SERVER ['HTTP_USER_AGENT'], 'MicroMessenger' )) {
			 $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
                    /* 修改支付按钮 针对微信支付 支付宝*/
                    if($payment_info['pay_name'] == '支付宝'){
                        $payment_info['pay_button'] = '<div style="text-align:center"><input type="button" value="使用支付宝支付" onclick="window.location.href=\'./pay/alipayapi.php?out_trade_no='.$order['log_id'].'&total_fee='.$order['order_amount'].'\'"/></div>';
                    }
                    if($payment_info['pay_name'] == '微信支付'){
                        $payment_info['pay_button'] = '<div style="text-align:center"><input type="button" onclick="window.location.href=\'./weixinpay.php?oid='.$order['order_id'].'\'"/></div>';
                    }
		}
		
		/* 模板赋值 */
		$smarty->assign('payment', $payment_info);
		$smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
		$smarty->assign('amount', price_format($amount, false));
		$smarty->assign('order', $order);
		$smarty->display('user_transaction.dwt');
	}
}

/* 删除会员余额 */
function action_cancel()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if($id == 0 || $user_id == 0)
	{
		ecs_header("Location: user.php?act=account_log\n");
		exit();
	}
	
	$result = del_user_account($id, $user_id);
	if($result)
	{
		ecs_header("Location: user.php?act=account_log\n");
		exit();
	}
}

/* 会员通过帐目明细列表进行再付款的操作 */
function action_pay()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	include_once (ROOT_PATH . 'includes/lib_payment.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	
	// 变量初始化
	$surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
	
	if($surplus_id == 0)
	{
		ecs_header("Location: user.php?act=account_log\n");
		exit();
	}
	
	// 如果原来的支付方式已禁用或者已删除, 重新选择支付方式
	if($payment_id == 0)
	{
		ecs_header("Location: user.php?act=account_deposit&id=" . $surplus_id . "\n");
		exit();
	}
	
	// 获取单条会员帐目信息
	$order = array();
	$order = get_surplus_info($surplus_id);
	
	// 支付方式的信息
	$payment_info = array();
	$payment_info = payment_info($payment_id);
	
	/* 如果当前支付方式没有被禁用，进行支付的操作 */
	if(! empty($payment_info))
	{
		// 取得支付信息，生成支付代码
		$payment = unserialize_config($payment_info['pay_config']);
		
		// 生成伪订单号
		$order['order_sn'] = $surplus_id;
		
		// 获取需要支付的log_id
		$order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);
		
		$order['user_name'] = $_SESSION['user_name'];
		$order['surplus_amount'] = $order['amount'];
		
		// 计算支付手续费用
		$payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);
		
		// 计算此次预付款需要支付的总金额
		$order['order_amount'] = $order['surplus_amount'] + $payment_info['pay_fee'];
		
		// 如果支付费用改变了，也要相应的更改pay_log表的order_amount
		$order_amount = $db->getOne("SELECT order_amount FROM " . $ecs->table('pay_log') . " WHERE log_id = '$order[log_id]'");
		if($order_amount != $order['order_amount'])
		{
			$db->query("UPDATE " . $ecs->table('pay_log') . " SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
		}
		
		/* 调用相应的支付方式文件 */
		include_once (ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');
		
		/* 取得在线支付方式的支付按钮 */
		$pay_obj = new $payment_info['pay_code']();
		$payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
		
		/* 模板赋值 */
		$smarty->assign('payment', $payment_info);
		$smarty->assign('order', $order);
		$smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
		$smarty->assign('amount', price_format($order['surplus_amount'], false));
		$smarty->assign('action', 'act_account');
		$smarty->display('user_transaction.dwt');
	}
	/* 重新选择支付方式 */
	else
	{
		include_once (ROOT_PATH . 'includes/lib_clips.php');
		
		$smarty->assign('payment', get_online_payment_list());
		$smarty->assign('order', $order);
		$smarty->assign('action', 'account_deposit');
		$smarty->display('user_transaction.dwt');
	}
}

/* 添加标签(ajax) */
function action_add_tag()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once ('includes/cls_json.php');
	include_once ('includes/lib_clips.php');
	
	$result = array(
		'error' => 0,'message' => '','content' => ''
	);
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$tag = isset($_POST['tag']) ? json_str_iconv(trim($_POST['tag'])) : '';
	
	if($user_id == 0)
	{
		/* 用户没有登录 */
		$result['error'] = 1;
		$result['message'] = $_LANG['tag_anonymous'];
	}
	else
	{
		add_tag($id, $tag); // 添加tag
		clear_cache_files('goods'); // 删除缓存
		
		/* 重新获得该商品的所有缓存 */
		$arr = get_tags($id);
		
		foreach($arr as $row)
		{
			$result['content'][] = array(
				'word' => htmlspecialchars($row['tag_words']),'count' => $row['tag_count']
			);
		}
	}
	
	$json = new JSON();
	
	echo $json->encode($result);
	exit();
}

/* 添加收藏商品(ajax) */
function action_collect()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/cls_json.php');
	$json = new JSON();
	$result = array(
		'error' => 0,'message' => ''
	);
	$goods_id = $_GET['id'];
	
	if(! isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
	{
		$result['error'] = 1;
		$result['message'] = $_LANG['login_please'];
		die($json->encode($result));
	}
	else
	{
		/* 检查是否已经存在于用户的收藏夹 */
		$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('collect_goods') . " WHERE user_id='$_SESSION[user_id]' AND goods_id = '$goods_id'";
		if($GLOBALS['db']->GetOne($sql) > 0)
		{
			$result['error'] = 1;
			$result['message'] = $GLOBALS['_LANG']['collect_existed'];
			die($json->encode($result));
		}
		else
		{
			$time = gmtime();
			$sql = "INSERT INTO " . $GLOBALS['ecs']->table('collect_goods') . " (user_id, goods_id, add_time)" . "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";
			
			if($GLOBALS['db']->query($sql) === false)
			{
				$result['error'] = 1;
				$result['message'] = $GLOBALS['db']->errorMsg();
				die($json->encode($result));
			}
			else
			{
				$result['error'] = 0;
				$result['message'] = $GLOBALS['_LANG']['collect_success'];
				die($json->encode($result));
			}
		}
	}
}

/* 删除留言 */
function action_del_msg()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
	
	if($id > 0)
	{
		$sql = 'SELECT user_id, message_img FROM ' . $ecs->table('feedback') . " WHERE msg_id = '$id' LIMIT 1";
		$row = $db->getRow($sql);
		if($row && $row['user_id'] == $user_id)
		{
			/* 验证通过，删除留言，回复，及相应文件 */
			if($row['message_img'])
			{
				@unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/' . $row['message_img']);
			}
			$sql = "DELETE FROM " . $ecs->table('feedback') . " WHERE msg_id = '$id' OR parent_id = '$id'";
			$db->query($sql);
		}
	}
	ecs_header("Location: user.php?act=message_list&order_id=$order_id\n");
	exit();
}

/* 删除评论 */
function action_del_cmt()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if($id > 0)
	{
		$sql = "DELETE FROM " . $ecs->table('comment') . " WHERE comment_id = '$id' AND user_id = '$user_id'";
		$db->query($sql);
	}
	ecs_header("Location: user.php?act=comment_list\n");
	exit();
}

/* 合并订单 */
function action_merge_order()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	$from_order = isset($_POST['from_order']) ? trim($_POST['from_order']) : '';
	$to_order = isset($_POST['to_order']) ? trim($_POST['to_order']) : '';
	$sql = "select supplier_id from " . $ecs->table('order_info') . " where order_sn='$from_order' ";
	$supplier_id_from = $db->getOne($sql);
	$sql = "select supplier_id from " . $ecs->table('order_info') . " where order_sn='$to_order' ";
	$supplier_id_to = $db->getOne($sql);
	if($supplier_id_from != $supplier_id_to)
	{
		show_message('由于供货商不同,订单合并失败', $_LANG['order_list_lnk'], 'user.php?act=order_list', 'info');
	}
	if(merge_user_order($from_order, $to_order, $user_id))
	{
		show_message($_LANG['merge_order_success'], $_LANG['order_list_lnk'], 'user.php?act=order_list', 'info');
	}
	else
	{
		$GLOBALS['err']->show($_LANG['order_list_lnk']);
	}
}
/* 将指定订单中商品添加到购物车 */
function action_return_to_cart()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/cls_json.php');
	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	$json = new JSON();
	
	$result = array(
		'error' => 0,'message' => '','content' => ''
	);
	$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
	if($order_id == 0)
	{
		$result['error'] = 1;
		$result['message'] = $_LANG['order_id_empty'];
		die($json->encode($result));
	}
	
	if($user_id == 0)
	{
		/* 用户没有登录 */
		$result['error'] = 1;
		$result['message'] = $_LANG['login_please'];
		die($json->encode($result));
	}
	
	/* 检查订单是否属于该用户 */
	$order_user = $db->getOne("SELECT user_id FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id'");
	if(empty($order_user))
	{
		$result['error'] = 1;
		$result['message'] = $_LANG['order_exist'];
		die($json->encode($result));
	}
	else
	{
		if($order_user != $user_id)
		{
			$result['error'] = 1;
			$result['message'] = $_LANG['no_priv'];
			die($json->encode($result));
		}
	}
	
	$message = return_to_cart($order_id);
	
	if($message === true)
	{
		$result['error'] = 0;
		$result['message'] = $_LANG['return_to_cart_success'];
		die($json->encode($result));
	}
	else
	{
		$result['error'] = 1;
		$result['message'] = $_LANG['order_exist'];
		die($json->encode($result));
	}
}

/* 编辑使用余额支付的处理 */
function action_act_edit_surplus()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	/* 检查是否登录 */
	if($_SESSION['user_id'] <= 0)
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 检查订单号 */
	$order_id = intval($_POST['order_id']);
	if($order_id <= 0)
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 检查余额 */
	$surplus = floatval($_POST['surplus']);
	if($surplus <= 0)
	{
		$GLOBALS['err']->add($_LANG['error_surplus_invalid']);
		$GLOBALS['err']->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	
	include_once (ROOT_PATH . 'includes/lib_order.php');
	
	/* 取得订单 */
	$order = order_info($order_id);
	if(empty($order))
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 检查订单用户跟当前用户是否一致 */
	if($_SESSION['user_id'] != $order['user_id'])
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 检查订单是否未付款，检查应付款金额是否大于0 */
	if($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0)
	{
		$GLOBALS['err']->add($_LANG['error_order_is_paid']);
		$GLOBALS['err']->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	
	/* 计算应付款金额（减去支付费用） */
	$order['order_amount'] -= $order['pay_fee'];
	
	/* 余额是否超过了应付款金额，改为应付款金额 */
	if($surplus > $order['order_amount'])
	{
		$surplus = $order['order_amount'];
	}
	
	/* 取得用户信息 */
	$user = user_info($_SESSION['user_id']);
	
	/* 用户帐户余额是否足够 */
	if($surplus > $user['user_money'] + $user['credit_line'])
	{
		$GLOBALS['err']->add($_LANG['error_surplus_not_enough']);
		$GLOBALS['err']->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
	}
	
	/* 修改订单，重新计算支付费用 */
	$order['surplus'] += $surplus;
	$order['order_amount'] -= $surplus;
	if($order['order_amount'] > 0)
	{
		$cod_fee = 0;
		if($order['shipping_id'] > 0)
		{
			$regions = array(
				$order['country'],$order['province'],$order['city'],$order['district']
			);
			$shipping = shipping_area_info($order['shipping_id'], $regions);
			if($shipping['support_cod'] == '1')
			{
				$cod_fee = $shipping['pay_fee'];
			}
		}
		
		$pay_fee = 0;
		if($order['pay_id'] > 0)
		{
			$pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
		}
		
		$order['pay_fee'] = $pay_fee;
		$order['order_amount'] += $pay_fee;
	}
	
	/* 如果全部支付，设为已确认、已付款 */
	if($order['order_amount'] == 0)
	{
		if($order['order_status'] == OS_UNCONFIRMED)
		{
			$order['order_status'] = OS_CONFIRMED;
			$order['confirm_time'] = gmtime();
		}
		$order['pay_status'] = PS_PAYED;
		$order['pay_time'] = gmtime();
	}
	$order = addslashes_deep($order);
	update_order($order_id, $order);
	
	/* 更新用户余额 */
	$change_desc = sprintf($_LANG['pay_order_by_surplus'], $order['order_sn']);
	log_account_change($user['user_id'], (- 1) * $surplus, 0, 0, 0, $change_desc);
	
	/* 跳转 */
	ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
	exit();
}

/* 编辑使用余额支付的处理 */
function action_act_edit_payment()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$is_pay = isset($_REQUEST['is_pay'])?intval($_REQUEST['is_pay']):0;

	/* 检查是否登录 */
	if($_SESSION['user_id'] <= 0)
	{
		ecs_header("Location: ./\n");
		exit();
	}

	$sql = "SELECT pay_id FROM " . $ecs->table('payment') . " WHERE pay_code = '" . $_POST['pay_code'] . "'";
	$row = $db->getRow($sql);
	/* 检查支付方式 */
	$pay_id = $row['pay_id'];
	if($pay_id <= 0)
	{
		ecs_header("Location: ./\n");
		exit();
	}

	include_once (ROOT_PATH . 'includes/lib_order.php');
	$payment_info = payment_info($pay_id);
	if(empty($payment_info))
	{
		ecs_header("Location: ./\n");
		exit();
	}

	/* 检查订单号 */
	$order_id = intval($_POST['order_id']);
	if($order_id <= 0)
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 取得订单 */
	$order = order_info($order_id);
	if(empty($order))
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 检查订单用户跟当前用户是否一致 */
	if($_SESSION['user_id'] != $order['user_id'])
	{
		ecs_header("Location: ./\n");
		exit();
	}
	
	/* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变 */
	if($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id)
	{
		ecs_header("Location: user.php?act=order_detail&order_id=$order_id&is_pay=$is_pay\n");
		exit();
	}
	
	$order_amount = $order['order_amount'] - $order['pay_fee'];
	$pay_fee = pay_fee($pay_id, $order_amount);
	$order_amount += $pay_fee;
	
	$sql = "UPDATE " . $ecs->table('order_info') . " SET pay_id='$pay_id', pay_name='$payment_info[pay_name]', pay_fee='$pay_fee', order_amount='$order_amount'" . " WHERE order_id = '$order_id'";
	$db->query($sql);
	
	/* 跳转 */
	ecs_header("Location: user.php?act=order_detail&order_id=$order_id&is_pay=$is_pay\n");
	exit();
}

/* 保存订单详情收货地址 */
function action_save_order_address()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	$address = array(
		'consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee'])) : '','email' => isset($_POST['email']) ? compile_str(trim($_POST['email'])) : '','address' => isset($_POST['address']) ? compile_str(trim($_POST['address'])) : '','zipcode' => isset($_POST['zipcode']) ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '','tel' => isset($_POST['tel']) ? compile_str(trim($_POST['tel'])) : '','mobile' => isset($_POST['mobile']) ? compile_str(trim($_POST['mobile'])) : '','sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '','best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time'])) : '','order_id' => isset($_POST['order_id']) ? intval($_POST['order_id']) : 0
	);
	if(save_order_address($address, $user_id))
	{
		ecs_header('Location: user.php?act=order_detail&order_id=' . $address['order_id'] . "\n");
		exit();
	}
	else
	{
		$err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
	}
}

/* 我的红包列表 */
function action_bonus()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
    $action = empty($_REQUEST['act'])?'':trim($_REQUEST['act']);

	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('user_bonus') . " WHERE user_id = '$user_id'");
	
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	$bonus = get_user_bouns_list($user_id, $pager['size'], $pager['start']);
	
	$smarty->assign('pager', $pager);
	$smarty->assign('bonus', $bonus);
	$smarty->display('user_transaction.dwt');
}

/**
 * 到货通知
 */
function action_book_goods ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	include_once (ROOT_PATH . 'includes/cls_json.php');
	$json = new JSON();
	$result = array(
		'error' => 0, 'message' => '', 'tel' => '', 'email' => ''
	);
	$goods_id = $_GET['id'];
	$result['goods_id'] = $goods_id;
	//$result['no_have'] = $_GET['no_have'];
	if(! isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
	{
		$result['error'] = 0;
		$result['message'] = $_LANG['login_please'];
		die($json->encode($result));
	}
	else
	{
		$sql = "SELECT user_id,goods_id FROM " . $GLOBALS['ecs']->table('booking_goods') . " WHERE user_id='$_SESSION[user_id]' AND is_dispose=0 AND goods_id = '$goods_id'";
		$b_goods = $GLOBALS['db']->GetOne($sql);
		
		if($b_goods)
		{
			$result['error'] = 0;
			$result['message'] = "您已经登记过了";
			die($json->encode($result));
		}
		else
		{
			$sql = "SELECT email,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='$_SESSION[user_id]'";
			$user_msg = $db->getRow($sql);
			
			$result['error'] = 1;
			$result['tel'] = $user_msg['mobile_phone'];
			$result['email'] = $user_msg['email'];
			die($json->encode($result));
		}
	}
}

/**
 * 到货通知
 */
function action_add_book_goods ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	include_once (ROOT_PATH . 'includes/cls_json.php');
	$json = new JSON();
	$result = array(
		'error' => 0, 'message' => ''
	);
	$goods_id = $_GET['id'];
	$number = $_GET['num'];
	$tel = $_GET['tel'];
	$email = $_GET['em'];
	if(! preg_match("/^1(3|5|8)[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/", $tel))
	{
		$result['error'] = 0;
		$result['message'] = "手机格式不正确。";
		die($json->encode($result));
	}
	elseif(! preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $email))
	{
		$result['error'] = 0;
		$result['message'] = "邮箱格式不正确。";
		die($json->encode($result));
	}
	else
	{
		$time = gmtime();
		$sql = "INSERT INTO " . $ecs->table('booking_goods') . " (user_id,email,tel,goods_id,goods_number,booking_time,link_man) VALUES ('$_SESSION[user_id]','$email','$tel','$goods_id','$number','$time','$_SESSION[user_name]')";
		if($db->query($sql))
		{
			$result['error'] = 2;
			$result['message'] = "登记成功。";
			die($json->encode($result));
		}
		else
		{
			$result['error'] = 0;
			$result['message'] = "登记失败。";
			die($json->encode($result));
		}
	}
}

/* 我的团购列表 */
function action_group_buy()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	// 待议
	$smarty->display('user_transaction.dwt');
}

/* 团购订单详情 */
function action_group_buy_detail()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	// 待议
	$smarty->display('user_transaction.dwt');
}

// 用户推荐页面
function action_affiliate()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$goodsid = intval(isset($_REQUEST['goodsid']) ? $_REQUEST['goodsid'] : 0);
	if(empty($goodsid))
	{
		// 我的推荐页面
		
		$page = ! empty($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		$size = ! empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
		
		// empty($affiliate) && $affiliate = array();
		
		$sql = "SELECT value FROM ".$ecs->table('shop_config')." WHERE code='affiliate'";
		$affiliate = $db->getOne($sql);
		$affiliate = unserialize($affiliate);
		if(empty($affiliate['config']['separate_by']))
		{
			// 推荐注册分成
			$affdb = array();
			$num = count($affiliate['item']);
			$up_uid = "'$user_id'";
			$all_uid = "'$user_id'";
			for($i = 1; $i <= $num; $i ++)
			{
				$count = 0;
				if($up_uid)
				{
					$sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
					$query = $db->query($sql);
					$up_uid = '';
					while($rt = $db->fetch_array($query))
					{
						$up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
						if($i < $num)
						{
							$all_uid .= ", '$rt[user_id]'";
						}
						$count ++;
					}
				}
				$affdb[$i]['num'] = $count;
				$affdb[$i]['point'] = $affiliate['item'][$i - 1]['level_point'];
				$affdb[$i]['money'] = $affiliate['item'][$i - 1]['level_money'];
			}
			$smarty->assign('affdb', $affdb);
			
			$sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o" . " LEFT JOIN" . $ecs->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";
			
			$sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $ecs->table('order_info') . " o" . " LEFT JOIN" . $ecs->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
			
			/*
			 * SQL解释：
			 *
			 * 订单、用户、分成记录关联
			 * 一个订单可能有多个分成记录
			 *
			 * 1、订单有效 o.user_id > 0
			 * 2、满足以下之一：
			 * a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
			 * 其中$all_uid为该ID及其下线(不包含最后一层下线)
			 * b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0
			 *
			 */
			
			$affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
		}
		else
		{
			// 推荐订单分成
			$sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o" . " LEFT JOIN" . $ecs->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";
			
			$sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $ecs->table('order_info') . " o" . " LEFT JOIN" . $ecs->table('users') . " u ON o.user_id = u.user_id" . " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
			
			/*
			 * SQL解释：
			 *
			 * 订单、用户、分成记录关联
			 * 一个订单可能有多个分成记录
			 *
			 * 1、订单有效 o.user_id > 0
			 * 2、满足以下之一：
			 * a.订单下线的未分成订单 o.parent_id = '$user_id' AND o.is_separate = 0
			 * b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0
			 *
			 */
			
			$affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
		}
		
		$count = $db->getOne($sqlcount);
		
		$max_page = ($count > 0) ? ceil($count / $size) : 1;
		if($page > $max_page)
		{
			$page = $max_page;
		}
		
		$res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
		$logdb = array();
		while($rt = $GLOBALS['db']->fetchRow($res))
		{
			if(! empty($rt['suid']))
			{
				// 在affiliate_log有记录
				if($rt['separate_type'] == - 1 || $rt['separate_type'] == - 2)
				{
					// 已被撤销
					$rt['is_separate'] = 3;
				}
			}
			$rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], - 2, 2);
			$logdb[] = $rt;
		}
		
		$url_format = "user.php?act=affiliate&page=";
		
		$pager = array(
			'page' => $page,'size' => $size,'sort' => '','order' => '','record_count' => $count,'page_count' => $max_page,'page_first' => $url_format . '1','page_prev' => $page > 1 ? $url_format . ($page - 1) : "javascript:;",'page_next' => $page < $max_page ? $url_format . ($page + 1) : "javascript:;",'page_last' => $url_format . $max_page,'array' => array()
		);
		for($i = 1; $i <= $max_page; $i ++)
		{
			$pager['array'][$i] = $i;
		}
		
		$smarty->assign('url_format', $url_format);
		$smarty->assign('pager', $pager);
		
		$smarty->assign('affiliate_intro', $affiliate_intro);
		$smarty->assign('affiliate_type', $affiliate['config']['separate_by']);
		
		$smarty->assign('logdb', $logdb);
	}
	else
	{
		// 单个商品推荐
		$smarty->assign('userid', $user_id);
		$smarty->assign('goodsid', $goodsid);
		
		$types = array(
			1,2,3,4,5
		);
		$smarty->assign('types', $types);
		
		$goods = get_goods_info($goodsid);
		$shopurl = $ecs->url();
		$goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $shopurl . $goods['goods_img'] : $goods['goods_img'];
		$goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $shopurl . $goods['goods_thumb'] : $goods['goods_thumb'];
		$goods['shop_price'] = price_format($goods['shop_price']);
		
		$smarty->assign('goods', $goods);
	}
	
	$smarty->assign('shopname', $_CFG['shop_name']);
	$smarty->assign('userid', $user_id);
	$smarty->assign('shopurl', $ecs->url());
	$smarty->assign('logosrc', 'themesmobile/' . $_CFG['template'] . '/images/logo.gif');
	
	$smarty->display('user_clips.dwt');
}

// 首页邮件订阅ajax操做和验证操作
function action_email_list()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$job = $_GET['job'];
	
	if($job == 'add' || $job == 'del')
	{
		if(isset($_SESSION['last_email_query']))
		{
			if(time() - $_SESSION['last_email_query'] <= 30)
			{
				die($_LANG['order_query_toofast']);
			}
		}
		$_SESSION['last_email_query'] = time();
	}
	
	$email = trim($_GET['email']);
	$email = htmlspecialchars($email);
	
	if(! is_email($email))
	{
		$info = sprintf($_LANG['email_invalid'], $email);
		die($info);
	}
	$ck = $db->getRow("SELECT * FROM " . $ecs->table('email_list') . " WHERE email = '$email'");
	if($job == 'add')
	{
		if(empty($ck))
		{
			$hash = substr(md5(time()), 1, 10);
			$sql = "INSERT INTO " . $ecs->table('email_list') . " (email, stat, hash) VALUES ('$email', 0, '$hash')";
			$db->query($sql);
			$info = $_LANG['email_check'];
			$url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
			send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
		}
		elseif($ck['stat'] == 1)
		{
			$info = sprintf($_LANG['email_alreadyin_list'], $email);
		}
		else
		{
			$hash = substr(md5(time()), 1, 10);
			$sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
			$db->query($sql);
			$info = $_LANG['email_re_check'];
			$url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
			send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
		}
		die($info);
	}
	elseif($job == 'del')
	{
		if(empty($ck))
		{
			$info = sprintf($_LANG['email_notin_list'], $email);
		}
		elseif($ck['stat'] == 1)
		{
			$hash = substr(md5(time()), 1, 10);
			$sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
			$db->query($sql);
			$info = $_LANG['email_check'];
			$url = $ecs->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
			send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
		}
		else
		{
			$info = $_LANG['email_not_alive'];
		}
		die($info);
	}
	elseif($job == 'add_check')
	{
		if(empty($ck))
		{
			$info = sprintf($_LANG['email_notin_list'], $email);
		}
		elseif($ck['stat'] == 1)
		{
			$info = $_LANG['email_checked'];
		}
		else
		{
			if($_GET['hash'] == $ck['hash'])
			{
				$sql = "UPDATE " . $ecs->table('email_list') . "SET stat = 1 WHERE email = '$email'";
				$db->query($sql);
				$info = $_LANG['email_checked'];
			}
			else
			{
				$info = $_LANG['hash_wrong'];
			}
		}
		show_message($info, $_LANG['back_home_lnk'], 'index.php');
	}
	elseif($job == 'del_check')
	{
		if(empty($ck))
		{
			$info = sprintf($_LANG['email_invalid'], $email);
		}
		elseif($ck['stat'] == 1)
		{
			if($_GET['hash'] == $ck['hash'])
			{
				$sql = "DELETE FROM " . $ecs->table('email_list') . "WHERE email = '$email'";
				$db->query($sql);
				$info = $_LANG['email_canceled'];
			}
			else
			{
				$info = $_LANG['hash_wrong'];
			}
		}
		else
		{
			$info = $_LANG['email_not_alive'];
		}
		show_message($info, $_LANG['back_home_lnk'], 'index.php');
	}
}

/* ajax 发送验证邮件 */
function action_send_hash_mail()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/cls_json.php');
	include_once (ROOT_PATH . 'includes/lib_passport.php');
	$json = new JSON();
	
	$result = array(
		'error' => 0,'message' => '','content' => ''
	);
	
	if($user_id == 0)
	{
		/* 用户没有登录 */
		$result['error'] = 1;
		$result['message'] = $_LANG['login_please'];
		die($json->encode($result));
	}
	
	if(send_regiter_hash($user_id))
	{
		$result['message'] = $_LANG['validate_mail_ok'];
		die($json->encode($result));
	}
	else
	{
		$result['error'] = 1;
		$result['message'] = $GLOBALS['err']->last_message();
	}
	
	die($json->encode($result));
}
function action_track_packages()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'includes/lib_order.php');
	
	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	$orders = array();
	
	$sql = "SELECT order_id,order_sn,invoice_no,shipping_id FROM " . $ecs->table('order_info') . " WHERE user_id = '$user_id' AND shipping_status = '" . SS_SHIPPED . "'";
	$res = $db->query($sql);
	$record_count = 0;
	while($item = $db->fetch_array($res))
	{
		$shipping = get_shipping_object($item['shipping_id']);
		
		if(method_exists($shipping, 'query'))
		{
			$query_link = $shipping->query($item['invoice_no']);
		}
		else
		{
			$query_link = $item['invoice_no'];
		}
		
		if($query_link != $item['invoice_no'])
		{
			$item['query_link'] = $query_link;
			$orders[] = $item;
			$record_count += 1;
		}
	}
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);
	$smarty->assign('pager', $pager);
	$smarty->assign('orders', $orders);
	$smarty->display('user_transaction.dwt');
}
function action_order_query()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$_GET['order_sn'] = trim(substr($_GET['order_sn'], 1));
	$order_sn = empty($_GET['order_sn']) ? '' : addslashes($_GET['order_sn']);
	include_once (ROOT_PATH . 'includes/cls_json.php');
	$json = new JSON();
	
	$result = array(
		'error' => 0,'message' => '','content' => ''
	);
	
	if(isset($_SESSION['last_order_query']))
	{
		if(time() - $_SESSION['last_order_query'] <= 10)
		{
			$result['error'] = 1;
			$result['message'] = $_LANG['order_query_toofast'];
			die($json->encode($result));
		}
	}
	$_SESSION['last_order_query'] = time();
	
	if(empty($order_sn))
	{
		$result['error'] = 1;
		$result['message'] = $_LANG['invalid_order_sn'];
		die($json->encode($result));
	}
	
	$sql = "SELECT order_id, order_status, shipping_status, pay_status, " . " shipping_time, shipping_id, invoice_no, user_id " . " FROM " . $ecs->table('order_info') . " WHERE order_sn = '$order_sn' LIMIT 1";
	
	$row = $db->getRow($sql);
	if(empty($row))
	{
		$result['error'] = 1;
		$result['message'] = $_LANG['invalid_order_sn'];
		die($json->encode($result));
	}
	
	$order_query = array();
	$order_query['order_sn'] = $order_sn;
	$order_query['order_id'] = $row['order_id'];
	$order_query['order_status'] = $_LANG['os'][$row['order_status']] . ',' . $_LANG['ps'][$row['pay_status']] . ',' . $_LANG['ss'][$row['shipping_status']];
	
	if($row['invoice_no'] && $row['shipping_id'] > 0)
	{
		$sql = "SELECT shipping_code FROM " . $ecs->table('shipping') . " WHERE shipping_id = '$row[shipping_id]'";
		$shipping_code = $db->getOne($sql);
		$plugin = ROOT_PATH . '../includes/modules/shipping/' . $shipping_code . '.php';
		if(file_exists($plugin))
		{
			include_once ($plugin);
			$shipping = new $shipping_code();
			$order_query['invoice_no'] = $shipping->query((string)$row['invoice_no']);
		}
		else
		{
			$order_query['invoice_no'] = (string)$row['invoice_no'];
		}
	}
	
	$order_query['user_id'] = $row['user_id'];
	/* 如果是匿名用户显示发货时间 */
	if($row['user_id'] == 0 && $row['shipping_time'] > 0)
	{
		$order_query['shipping_date'] = local_date($GLOBALS['_CFG']['date_format'], $row['shipping_time']);
	}
	$smarty->assign('order_query', $order_query);
	$result['content'] = $smarty->fetch('library/order_query.lbi');
	die($json->encode($result));
}
function action_transform_points()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	$rule = array();
	if(! empty($_CFG['points_rule']))
	{
		$rule = unserialize($_CFG['points_rule']);
	}
	$cfg = array();
	if(! empty($_CFG['integrate_config']))
	{
		$cfg = unserialize($_CFG['integrate_config']);
		$_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0]) ? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
		$_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0]) ? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
	}
	$sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users') . " WHERE user_id='$user_id'";
	$row = $db->getRow($sql);
	if($_CFG['integrate_code'] == 'ucenter')
	{
		$exchange_type = 'ucenter';
		$to_credits_options = array();
		$out_exchange_allow = array();
		foreach($rule as $credit)
		{
			$out_exchange_allow[$credit['appiddesc'] . '|' . $credit['creditdesc'] . '|' . $credit['creditsrc']] = $credit['ratio'];
			if(! array_key_exists($credit['appiddesc'] . '|' . $credit['creditdesc'], $to_credits_options))
			{
				$to_credits_options[$credit['appiddesc'] . '|' . $credit['creditdesc']] = $credit['title'];
			}
		}
		$smarty->assign('selected_org', $rule[0]['creditsrc']);
		$smarty->assign('selected_dst', $rule[0]['appiddesc'] . '|' . $rule[0]['creditdesc']);
		$smarty->assign('descreditunit', $rule[0]['unit']);
		$smarty->assign('orgcredittitle', $_LANG['exchange_points'][$rule[0]['creditsrc']]);
		$smarty->assign('descredittitle', $rule[0]['title']);
		$smarty->assign('descreditamount', round((1 / $rule[0]['ratio']), 2));
		$smarty->assign('to_credits_options', $to_credits_options);
		$smarty->assign('out_exchange_allow', $out_exchange_allow);
	}
	else
	{
		$exchange_type = 'other';
		
		$bbs_points_name = $user->get_points_name();
		$total_bbs_points = $user->get_points($row['user_name']);
		
		/* 论坛积分 */
		$bbs_points = array();
		foreach($bbs_points_name as $key => $val)
		{
			$bbs_points[$key] = array(
				'title' => $_LANG['bbs'] . $val['title'],'value' => $total_bbs_points[$key]
			);
		}
		
		/* 兑换规则 */
		$rule_list = array();
		foreach($rule as $key => $val)
		{
			$rule_key = substr($key, 0, 1);
			$bbs_key = substr($key, 1);
			$rule_list[$key]['rate'] = $val;
			switch($rule_key)
			{
				case TO_P:
					$rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
					$rule_list[$key]['to'] = $_LANG['pay_points'];
					break;
				case TO_R:
					$rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
					$rule_list[$key]['to'] = $_LANG['rank_points'];
					break;
				case FROM_P:
					$rule_list[$key]['from'] = $_LANG['pay_points'];
					$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
					$rule_list[$key]['to'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
					break;
				case FROM_R:
					$rule_list[$key]['from'] = $_LANG['rank_points'];
					$rule_list[$key]['to'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
					break;
			}
		}
		$smarty->assign('bbs_points', $bbs_points);
		$smarty->assign('rule_list', $rule_list);
	}
	$smarty->assign('shop_points', $row);
	$smarty->assign('exchange_type', $exchange_type);
	$smarty->assign('action', $action);
	$smarty->assign('lang', $_LANG);
	$smarty->display('user_transaction.dwt');
}
function action_act_transform_points()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];

	$rule_index = empty($_POST['rule_index']) ? '' : trim($_POST['rule_index']);
	$num = empty($_POST['num']) ? 0 : intval($_POST['num']);
	
	if($num <= 0 || $num != floor($num))
	{
		show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
	}
	
	$num = floor($num); // 格式化为整数
	
	$bbs_key = substr($rule_index, 1);
	$rule_key = substr($rule_index, 0, 1);
	
	$max_num = 0;
	
	/* 取出用户数据 */
	$sql = "SELECT user_name, user_id, pay_points, rank_points FROM " . $ecs->table('users') . " WHERE user_id='$user_id'";
	$row = $db->getRow($sql);
	$bbs_points = $user->get_points($row['user_name']);
	$points_name = $user->get_points_name();
	
	$rule = array();
	if($_CFG['points_rule'])
	{
		$rule = unserialize($_CFG['points_rule']);
	}
	list($from, $to) = explode(':', $rule[$rule_index]);
	
	$max_points = 0;
	switch($rule_key)
	{
		case TO_P:
			$max_points = $bbs_points[$bbs_key];
			break;
		case TO_R:
			$max_points = $bbs_points[$bbs_key];
			break;
		case FROM_P:
			$max_points = $row['pay_points'];
			break;
		case FROM_R:
			$max_points = $row['rank_points'];
	}
	
	/* 检查积分是否超过最大值 */
	if($max_points <= 0 || $num > $max_points)
	{
		show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
	}
	
	switch($rule_key)
	{
		case TO_P:
			$result_points = floor($num * $to / $from);
			$user->set_points($row['user_name'], array(
				$bbs_key => 0 - $num
			)); // 调整论坛积分
			log_account_change($row['user_id'], 0, 0, 0, $result_points, $_LANG['transform_points'], ACT_OTHER);
			show_message(sprintf($_LANG['to_pay_points'], $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');
		
		case TO_R:
			$result_points = floor($num * $to / $from);
			$user->set_points($row['user_name'], array(
				$bbs_key => 0 - $num
			)); // 调整论坛积分
			log_account_change($row['user_id'], 0, 0, $result_points, 0, $_LANG['transform_points'], ACT_OTHER);
			show_message(sprintf($_LANG['to_rank_points'], $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');
		
		case FROM_P:
			$result_points = floor($num * $to / $from);
			log_account_change($row['user_id'], 0, 0, 0, 0 - $num, $_LANG['transform_points'], ACT_OTHER); // 调整商城积分
			$user->set_points($row['user_name'], array(
				$bbs_key => $result_points
			)); // 调整论坛积分
			show_message(sprintf($_LANG['from_pay_points'], $num, $result_points, $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
		
		case FROM_R:
			$result_points = floor($num * $to / $from);
			log_account_change($row['user_id'], 0, 0, 0 - $num, 0, $_LANG['transform_points'], ACT_OTHER); // 调整商城积分
			$user->set_points($row['user_name'], array(
				$bbs_key => $result_points
			)); // 调整论坛积分
			show_message(sprintf($_LANG['from_rank_points'], $num, $result_points, $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
	}
}
function action_act_transform_ucenter_points()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$rule = array();
	if($_CFG['points_rule'])
	{
		$rule = unserialize($_CFG['points_rule']);
	}
	$shop_points = array(
		0 => 'rank_points',1 => 'pay_points'
	);
	$sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users') . " WHERE user_id='$user_id'";
	$row = $db->getRow($sql);
	$exchange_amount = intval($_POST['amount']);
	$fromcredits = intval($_POST['fromcredits']);
	$tocredits = trim($_POST['tocredits']);
	$cfg = unserialize($_CFG['integrate_config']);
	if(! empty($cfg))
	{
		$_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0]) ? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
		$_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0]) ? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
	}
	list($appiddesc, $creditdesc) = explode('|', $tocredits);
	$ratio = 0;
	
	if($exchange_amount <= 0)
	{
		show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
	}
	if($exchange_amount > $row[$shop_points[$fromcredits]])
	{
		show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
	}
	foreach($rule as $credit)
	{
		if($credit['appiddesc'] == $appiddesc && $credit['creditdesc'] == $creditdesc && $credit['creditsrc'] == $fromcredits)
		{
			$ratio = $credit['ratio'];
			break;
		}
	}
	if($ratio == 0)
	{
		show_message($_LANG['exchange_deny'], $_LANG['transform_points'], 'user.php?act=transform_points');
	}
	$netamount = floor($exchange_amount / $ratio);
	include_once (ROOT_PATH . './includes/lib_uc.php');
	$result = exchange_points($row['user_id'], $fromcredits, $creditdesc, $appiddesc, $netamount);
	if($result === true)
	{
		$sql = "UPDATE " . $ecs->table('users') . " SET {$shop_points[$fromcredits]}={$shop_points[$fromcredits]}-'$exchange_amount' WHERE user_id='{$row['user_id']}'";
		$db->query($sql);
		$sql = "INSERT INTO " . $ecs->table('account_log') . "(user_id, {$shop_points[$fromcredits]}, change_time, change_desc, change_type)" . " VALUES ('{$row['user_id']}', '-$exchange_amount', '" . gmtime() . "', '" . $cfg['uc_lang']['exchange'] . "', '98')";
		$db->query($sql);
		show_message(sprintf($_LANG['exchange_success'], $exchange_amount, $_LANG['exchange_points'][$fromcredits], $netamount, $credit['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
	}
	else
	{
		show_message($_LANG['exchange_error_1'], $_LANG['transform_points'], 'user.php?act=transform_points');
	}
}
/* 清除商品浏览历史 */
function action_clear_history()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	setcookie('ECS[history]', '', 1);
}
function action_vc_login()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	$smarty->assign('info', get_user_default($user_id));
	
	$smarty->display('user_transaction.dwt');
}
function action_vc_login_act()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	$nowtime = gmtime();
	$vc_sn = isset($_POST['vcard']) ? trim($_POST['vcard']) : '';
	$vc_pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
	if(empty($vc_sn) || empty($vc_pwd))
	{
		show_message('卡号或密码都不能为空', '返回重新登录', 'user.php?act=vc_login');
	}
	$sql = "select vc.*, vt.type_money, vt.use_start_date, vt.use_end_date from " . $ecs->table('valuecard') . " AS vc " . " left join " . $ecs->table('valuecard_type') . " AS vt " . "on vc.vc_type_id = vt.type_id where vc.vc_sn= '$vc_sn' ";
	$vcrow = $db->getRow($sql);
	if(! $vcrow)
	{
		show_message('该储值卡号不存在', '请查证后重新输入', 'user.php?act=vc_login');
	}
	if($vc_pwd != $vcrow['vc_pwd'])
	{
		show_message('密码错误', '请查证后重新登录', 'user.php?act=vc_login');
	}
	if($nowtime < $vcrow['use_start_date'])
	{
		show_message('对不起，该储值卡还未到开始使用日期', '请过几天再登录试试', 'user.php?act=vc_login');
	}
	if($nowtime > $vcrow['use_end_date'])
	{
		show_message('对不起，该储值卡已过期', '请换个卡号重新登录', 'user.php?act=vc_login');
	}
	if($vcrow['user_id'])
	{
		show_message('对不起，该储值卡已使用', '请换个卡号重新登录', 'user.php?act=vc_login');
	}
	
	$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('user_account') . ' (user_id, admin_user, amount, add_time, paid_time, admin_note, user_note, process_type, payment, is_paid)' . " VALUES ('$user_id', '', '$vcrow[type_money]', '" . gmtime() . "', '" . gmtime() . "', '', '储值卡充值', '0', '储值卡号：$vc_sn', 1)";
	$GLOBALS['db']->query($sql);
	log_account_change($user_id, $vcrow['type_money'], 0, 0, 0, '储值卡充值，卡号：' . $vc_sn, ACT_OTHER);
	
	$sql = "update " . $ecs->table('valuecard') . " set user_id='$user_id', used_time='$nowtime' where vc_id='$vcrow[vc_id]' ";
	$db->query($sql);
	
	show_message('恭喜，已成功充值！', '返回上一页', 'user.php?act=vc_login');
	
	$smarty->display('user_transaction.dwt');
}
function action_tg_login()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	
	$smarty->display('user_transaction.dwt');
}
function action_tg_login_act()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_clips.php');
	$nowtime = gmtime();
	$tg_sn = isset($_POST['tcard']) ? trim($_POST['tcard']) : '';
	$tg_pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
	if(empty($tg_sn) || empty($tg_pwd))
	{
		show_message('卡号或密码都不能为空', '返回重新登录', 'user.php?act=tg_login');
	}
	$sql = "select tg.*, tt.type_money, tt.type_money_count, tt.use_start_date, tt.use_end_date from " . $ecs->table('takegoods') . " AS tg " . " left join " . $ecs->table('takegoods_type') . " AS tt " . "on tg.type_id = tt.type_id where tg.tg_sn= '$tg_sn' ";
	$tgrow = $db->getRow($sql);
	if(! $tgrow)
	{
		show_message('该提货券不存在', '请查证后重新登录', 'user.php?act=tg_login');
	}
	if($tg_pwd != $tgrow['tg_pwd'])
	{
		show_message('密码错误', '请查证后重新登录', 'user.php?act=tg_login');
	}
	if($nowtime < $tgrow['use_start_date'])
	{
		show_message('对不起，该提货券 开始使用日期为 ' . local_date('Y-m-d H:i:s', $tgrow['use_start_date']), '请过几天再登录试试', 'user.php?act=tg_login');
	}
	if($nowtime > $tgrow['use_end_date'])
	{
		show_message('对不起，该提货券已过期', '请换个券号重新登录', 'user.php?act=tg_login');
	}
	
	if($tgrow['used_time'] and (count(explode('@', $tgrow['used_time'])) >= $tgrow['type_money_count']))
	{
		show_message('对不起，该提货券使用次数已用尽', '请换个券号重新登录', 'user.php?act=tg_login');
	}
	
	$_SESSION['takegoods_sn'] = $tg_sn;
	$_SESSION['takegoods_id'] = $tgrow['tg_id'];
	
	ecs_header("Location:takegoods.php");
}

function action_tg_order()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];
	$action = $GLOBALS['action'];


	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	
	$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('takegoods_order') . " WHERE user_id = '$user_id'");
	
	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page, 10);
	
	$orders = get_takegoods_orders($user_id, $pager['size'], $pager['start']);
	
	$smarty->assign('pager', $pager);
	$smarty->assign('orders', $orders);
	
	$smarty->display('user_transaction.dwt');
}
function action_tg_order_confirm()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$sql = "update " . $ecs->table('takegoods_order') . " set order_status='2' where rec_id= '$id' ";
	$db->query($sql);
	show_message('恭喜，成功确认收货！', '返回提货列表页', 'user.php?act=tg_order');
}

function action_check_surplus_open()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$sql = 'SELECT `is_surplus_open`' . 'FROM `ecs_users`' . 'WHERE `user_id` = \'' . $_SESSION['user_id'] . '\'' . 'LIMIT 1';
	$is_surplus_open = $GLOBALS['db']->getOne($sql);
	echo $is_surplus_open;
	exit();
}

function action_verify_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$sql = 'SELECT COUNT( * )' . 'FROM `ecs_users`' . 'WHERE `user_id` = \'' . $_SESSION['user_id'] . '\'' . 'AND `surplus_password` = \'' . md5($_GET['surplus_password']) . '\'';
	$count = $GLOBALS['db']->getOne($sql);
	echo $count;
	exit();
}

function action_open_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(isset($_GET['surplus_password']))
	{
		$surplus_password = trim($_GET['surplus_password']);
		$surplus_password = md5($surplus_password);
		$sql = 'UPDATE ' . $ecs->table('users') . ' SET `surplus_password`=\'' . $surplus_password . '\',`is_surplus_open`=\'1\' WHERE `user_id`=\'' . $user_id . '\'';
		$db->query($sql);
		$affected_rows = $db->affected_rows();
		if($affected_rows == 1)
		{
			echo '1';
		}
		else
		{
			echo '0';
		}
	}
	else
	{
		echo '-1';
	}
}

function action_close_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(isset($_GET['surplus_password']))
	{
		$surplus_password = trim($_GET['surplus_password']);
		$surplus_password = md5($surplus_password);
		$sql = 'UPDATE ' . $ecs->table('users') . ' SET `is_surplus_open` = 0 WHERE `user_id` = \'' . $user_id . '\' AND `surplus_password` = \'' . $surplus_password . '\'';
		$db->query($sql);
		$affected_rows = $db->affected_rows();
		if($affected_rows == 1)
		{
			echo '1';
		}
		else
		{
			echo '0';
		}
	}
	else
	{
		echo '0';
	}
}

function action_update_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	$smarty->display('user_transaction.dwt');
}

function action_act_update_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(! empty($_REQUEST['course']))
	{
		if($_REQUEST['course'] == 'update')
		{
			if(isset($_POST['prev_surplus_password']) && isset($_POST['new_surplus_password1']) && isset($_POST['new_surplus_password2']))
			{
				$prev_surplus_password = trim($_POST['prev_surplus_password']);
				$new_surplus_password1 = trim($_POST['new_surplus_password1']);
				$new_surplus_password2 = trim($_POST['new_surplus_password2']);
				if($new_surplus_password1 == $new_surplus_password2)
				{
					if($new_surplus_password1 != $prev_surplus_password)
					{
						$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `surplus_password` = \'' . md5($new_surplus_password1) . '\' WHERE `user_id` = \'' . $user_id . '\' AND `surplus_password` = \'' . md5($prev_surplus_password) . '\' LIMIT 1';
						
						$GLOBALS['db']->query($sql);
						if($GLOBALS['db']->affected_rows() == 1)
						{
							show_message('余额支付密码修改成功！', '返回', 'user.php?act=account_security', 'success');
						}
						else
						{
							show_message('余额支付密码修改失败！', '返回', 'user.php?act=account_security', 'fail');
						}
					}
					else
					{
						show_message('新的余额支付密码不能与旧的密码相同！');
					}
				}
				else
				{
					show_message('新的余额支付密码不匹配！');
				}
			}
			else
			{
				show_message('密码不能为空！', '返回', 'user.php?act=update_surplus_password', 'error');
			}
		}
		elseif($_REQUEST['course'] == 'reset')
		{
			if(isset($_REQUEST['verify_method']))
			{
				$validated = false;
				if($_REQUEST['verify_method'] == 'phone')
				{
					$v_code = isset($_REQUEST['v_code']) ? trim($_REQUEST['v_code']) : '';
					if($v_code == '')
					{
						show_message('非法重置余额支付密码操作！');
					}
					else
					{
						$sql = 'SELECT mobile_phone FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE `user_id` = \'' . $user_id . '\' LIMIT 1';
						$mobile_phone = $db->getOne($sql);
						$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('verifycode') . ' WHERE `mobile` = \'' . $mobile_phone . '\' AND `verifycode` = \'' . $v_code . '\' AND `status` = 1' . ' AND `dateline` + \'86400\' > \'' . gmtime() . '\' LIMIT 1';
						if($db->getOne($sql) == 0)
						{
							show_message('手机和验证码不匹配，请重新输入！');
						}
						else
						{
							$sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('verifycode') . ' WHERE `mobile` = \'' . $mobile_phone . '\'';
							$db->query($sql);
							$validated = true;
						}
					}
				}
				elseif($_REQUEST['verify_method'] == 'email')
				{
					$hash = isset($_REQUEST['hash']) ? trim($_REQUEST['hash']) : '';
					if($hash == '')
					{
						show_message('非法重置余额支付密码操作！');
					}
					else
					{
						$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('email') . ' WHERE `hash` = \'' . $hash . '\'' . ' AND `email` = \'' . $_SESSION['email'] . '\'' . ' LIMIT 1';
						if($GLOBALS['db']->getOne($sql) == 0)
						{
							show_message('非法重置余额支付密码操作！');
						}
						else
						{
							$sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('email') . ' WHERE `email` = \'' . $_SESSION['email'] . '\'';
							$db->query($sql);
							$validated = true;
						}
					}
				}
				if($validated)
				{
					if(isset($_POST['surplus_password1']) && isset($_POST['surplus_password2']))
					{
						$surplus_password1 = trim($_POST['surplus_password1']);
						$surplus_password2 = trim($_POST['surplus_password2']);
						
						if($surplus_password1 == $surplus_password1)
						{
							$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET `surplus_password` = \'' . md5($surplus_password1) . '\' WHERE `user_id` = \'' . $user_id . '\' LIMIT 1';
							$GLOBALS['db']->query($sql);
							if($GLOBALS['db']->affected_rows() == 1)
							{
								show_message('余额支付密码修改成功！', '返回', 'user.php?act=account_security', 'success');
							}
							else
							{
								$info_str = mysql_info($GLOBALS['db']->link_id);
								;
								$info_array1 = explode('  ', $info_str);
								$info_array2 = array();
								foreach($info_array1 as $value)
								{
									$temp_array = explode(': ', $value);
									$info_array2[$temp_array[0]] = $temp_array[1];
								}
								if($info_array2['Rows matched'] == '1' && $info_array2['Changed'] == '0')
								{
									show_message('余额支付密码修改成功！', '返回', 'user.php?act=account_security', 'success');
								}
								else
								{
									show_message('余额支付密码修改失败！', '返回', 'user.php?act=account_security', 'fail');
								}
							}
						}
						else
						{
							show_message('新的余额支付密码不匹配！');
						}
					}
				}
			}
			else
			{
				show_message('未知错误');
			}
		}
	}
}

function action_forget_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	
	$user_info = get_profile($user_id);
	
	$smarty->assign('info', $user_info);
	$smarty->display('user_transaction.dwt');
}

function action_act_forget_surplus_password()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(empty($_POST['verify_method']))
	{
		show_message('未知错误！', '返回', 'user.php?act=forget_surplus_password', 'error');
	}
	else
	{
		$verify_method = $_REQUEST['verify_method'];
		if($verify_method == 'phone')
		{
			if(empty($_REQUEST['v_code']))
			{
				show_message('请输入手机验证码！', '返回', 'user.php?act=forget_surplus_password', 'error');
			}
			if(empty($_REQUEST['v_phone']))
			{
				show_message('请输入手机号！', '返回', 'user.php?act=forget_surplus_password', 'error');
			}
			$v_code = $_REQUEST['v_code'];
			$v_phone = $_REQUEST['v_phone'];
			
			$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('verifycode') . ' WHERE `mobile` = \'' . $v_phone . '\' AND `verifycode` = \'' . $v_code . '\' AND `status` = 1' . ' AND dateline + 86400 > \'' . gmtime() . '\'';
			if($GLOBALS['db']->getOne($sql) == 0)
			{
				show_message('手机号和验证码不匹配，请重新输入！');
			}
			else
			{
				$smarty->assign('verify_method', 'phone');
				$smarty->assign('v_code', $v_code);
				$smarty->assign('action', 'reset_surplus_password');
				$smarty->assign('validated', 1);
				$smarty->display('user_transaction.dwt');
			}
		}
		elseif($verify_method == 'email')
		{
			if(empty($_REQUEST['v_captcha']))
			{
				show_message('请输入验证码！', '返回', 'user.php?act=forget_surplus_password', 'error');
			}
			if(empty($_REQUEST['v_email']))
			{
				show_message('请输入邮箱！', '返回', 'user.php?act=forget_surplus_password', 'error');
			}
			$v_captcha = trim($_REQUEST['v_captcha']);
			$v_email = trim($_REQUEST['v_email']);
			
			include_once ('includes/cls_captcha.php');
			
			$validator = new captcha();
			$validator->session_word = 'captcha_login';
			if(! $validator->check_word($v_captcha))
			{
				show_message($_LANG['invalid_captcha'], $_LANG['back_up_page'], 'user.php?act=forget_surplus_password', 'error');
			}
			else
			{
				$sql = 'SELECT `user_name`,`email` ' . ' FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE `user_id` = \'' . $user_id . '\'';
				$row = $GLOBALS['db']->getRow($sql);
				
				if($row['email'] != $v_email)
				{
					show_message('邮箱输入错误！', '返回', 'user.php?act=forget_surplus_password', 'error');
				}
				
				$template = get_mail_template('reset_surplus_password');
				
				$scope = '02456789abdefghjknoqrstwyz13u';
				$hash = mc_random(16, $scope);
				
				$reset_link = $GLOBALS['ecs']->url() . 'user.php?act=verify_reset_surplus_email' . '&hash=' . $hash;
				$user_name = $row['user_name'];
				
				$smarty->assign('user_name', $user_name);
				$smarty->assign('reset_link', $reset_link);
				$smarty->assign('shop_name', $_CFG['shop_name']);
				$smarty->assign('send_date', date($_CFG['time_format']));
				
				$content = $smarty->fetch('str:' . $template['template_content']);
				$result = send_mail($_CFG['shop_name'], $v_email, $template['template_subject'], $content, $template['is_html']);
				
				if($result == true)
				{
					$add_time = gmtime();
					$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('email') . '(`email`,`hash`,`add_time`,`user_id`)' . 'VALUES(\'' . $v_email . '\',\'' . $hash . '\',\'' . $add_time . '\',\'' . $user_id . '\')';
					$GLOBALS['db']->query($sql);
					if($GLOBALS['db']->affected_rows() == 1)
					{
						show_message('已发送邮件，请前往邮箱点击链接完成密码重置！', '返回', 'user.php?act=account_security', 'success');
					}
					else
					{
						show_message('发送邮件失败！');
					}
				}
				else
				{
					show_message('发送邮件失败！');
				}
			}
		}
		else
		{
			show_message('未知错误！', '返回', 'user.php?act=forget_surplus_password', 'error');
		}
	}
}

function action_get_verify_code()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	include_once ('includes/cls_json.php');
	require (dirname(__FILE__) . '/send.php');
	$json = new JSON();
	$result = array();
	
	$phone = trim($_REQUEST['phone']);
	
	$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE `user_id` = \'' . $user_id . '\' AND `mobile_phone` = \'' . $phone . '\'';
	$count = $GLOBALS['db']->getOne($sql);
	
	if($count == 0)
	{
		$result['result'] = 'fail';
		$result['message'] = '手机号跟用户不匹配';
		echo $json->encode($result);
	}
	else
	{
		$seed = "0123456789";
		$verifycode = mc_random(6, $seed);
		
		$content = '您的验证码为：' . $verifycode;
		
		$ret = sendSMS($phone, $content);
		
		$sql = 'INSERT INTO ' . $ecs->table('verifycode') . '(`mobile`, `getip`, `verifycode`, `dateline`) VALUES (\'' . $phone . '\',\'' . real_ip() . '\',\'' . $verifycode . '\',\'' . gmtime() . '\')';
		$db->query($sql);
		if($ret == '发送成功!' && $db->affected_rows() == 1)
		{
			$result['result'] = 'success';
			$result['message'] = '短信发送成功';
			echo $json->encode($result);
		}
		else
		{
			$result['result'] = 'fail';
			$result['message'] = '短信发送失败！';
			echo $json->encode($result);
		}
	}
}
function action_verify_reset_surplus_email()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];


	if(empty($_REQUEST['hash']))
	{
		show_message('未知错误！', '返回', 'user.php?act=forget_surplus_password', 'error');
	}
	else
	{
		$hash = trim($_REQUEST['hash']);
		$sql = 'SELECT `hash`,`add_time` FROM ' . $GLOBALS['ecs']->table('email') . ' WHERE `hash` = \'' . $hash . '\'' . ' LIMIT 1';
		$row = $GLOBALS['db']->getRow($sql);
		if($row)
		{
			if((gmtime() - $row['add_time']) > 86400)
			{
				$sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('email') . ' WHERE `hash` = \'' . $hash . '\'';
				$GLOBALS['db']->query($sql);
				show_message('验证邮件已发送超过24小时，请重新验证！');
			}
			else
			{
				$smarty->assign('verify_method', 'email');
				$smarty->assign('hash', $hash);
				$smarty->assign('action', 'reset_surplus_password');
				$smarty->assign('validated', 1);
				$smarty->display('user_transaction.dwt');
			}
		}
		else
		{
			show_message('未知错误！', '返回', 'user.php?act=forget_surplus_password', 'error');
		}
	}
}

function get_takegoods_orders ($user_id, $num = 10, $start = 0)
{
	$order_status = array(
		'0' => '提货成功，等待发货','1' => '确认收货','2' => '完成'
	);
	/* 取得订单列表 */
	$arr = array();
	
	$sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('takegoods_order') . " WHERE user_id = '$user_id' ORDER BY rec_id DESC";
	$res = $GLOBALS['db']->SelectLimit($sql, $num, $start);
	
	while($row = $GLOBALS['db']->fetchRow($res))
	{
		$row['country_name'] = $GLOBALS['db']->getOne("select region_name from " . $GLOBALS['ecs']->table('region') . " where region_id='$row[country]' ");
		$row['province_name'] = $GLOBALS['db']->getOne("select region_name from " . $GLOBALS['ecs']->table('region') . " where region_id='$row[province]' ");
		$row['city_name'] = $GLOBALS['db']->getOne("select region_name from " . $GLOBALS['ecs']->table('region') . " where region_id='$row[city]' ");
		$row['district_name'] = $GLOBALS['db']->getOne("select region_name from " . $GLOBALS['ecs']->table('region') . " where region_id='$row[district]' ");
		$row['goods_url'] = build_uri('goods', array(
			'gid' => $row['goods_id']
		), $row['goods_name']);
		$arr[] = array(
			'rec_id' => $row['rec_id'],'tg_sn' => $row['tg_sn'],'goods_name' => $row['goods_name'],'address' => $row['country_name'] . $row['province_name'] . $row['city_name'] . $row['district_name'] . $row['address'],'add_time' => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),'order_status' => $row['order_status'],'order_status_name' => $order_status[$row['order_status']],'goods_url' => $row['goods_url'],'handler' => $row['handler']
		);
	}
	
	return $arr;
}

/**
 * 随机生成指定长度的数字
 *
 * @param number $length
 * @return number
 */
function rand_number ($length = 6)
{
	if($length < 1)
	{
		$length = 6;
	}

	$min = 1;
	for($i = 0; $i < $length - 1; $i ++)
	{
		$min = $min * 10;
	}
	$max = $min * 10 - 1;

	return rand($min, $max);
}

function is_telephone ($phone)
{
	$chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/";
	if(preg_match($chars, $phone))
	{
		return true;
	}
}

function get_goods_info_wap($goods_id){
    $sql = "select * from ".$GLOBALS['ecs']->table('goods')." where goods_id=".$goods_id;
    $goods = $GLOBALS['db']->getRow($sql);
    $goods['goods_thumb'] = get_pc_url().'/'.get_image_path($goods_id,$goods['goods_thumb']);
    if($goods['supplier_id'] == '0'){
        $goods['supplier_name']=" 网站自营";
    }else{
        $sqla = "select supplier_name from ".$GLOBALS['ecs']->table('supplier')." where supplier_id = ".$goods['supplier_id'];
        $supplier_name = $GLOBALS['db']->getOne($sqla);
        $goods['supplier_name']=$supplier_name;
    }
    return $goods;
}

function get_order_id_wap($rec_id){
    $sql = "select order_id from ".$GLOBALS['ecs']->table('order_goods')." where rec_id=".$rec_id;
    $order_id = $GLOBALS['db']->getOne($sql);
    return $order_id;
}

function get_region_info_wap($region_id)
{
    $sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id = '$region_id' ";
    return $GLOBALS['db']->getOne($sql);
}

function get_regions_wap($region_id){
    $sql = 'SELECT region_id,region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE parent_id = '$region_id' ";
    return $GLOBALS['db']->getAll($sql);
}

function get_array($num = 0){
    $res = array();
    if(intval($num)>0){
        for($i=0;$i<$num;$i++){
            $res[$i] = $i;
        }
    }
    return $res;
}

function inject_check($str) {
	$check = preg_match('/select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile/i', $str);	
	Return $check;
}

/* 新“退换货”订单表单 */
function action_back_order ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$order_id = ! empty($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
	$have_back_order = $db->getRow("SELECT * FROM " . $ecs->table('back_order') . " WHERE order_id = {$order_id}");
	if($have_back_order){
		show_message('同一张订单不可重复提交', '返回订单列表页', 'user.php?act=order_list', 'info');
	}
	if(!$_REQUEST['order_all'])
	{
		$goods_id = ! empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
		$product_id = ! empty($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
		$sql = "select og.goods_id, og.goods_name, og.goods_sn, og.goods_number, og.goods_price, og.product_id, og.goods_attr, o.order_id, o.order_sn, o.user_id, o.shipping_time_end " . " from " . $GLOBALS['ecs']->table('order_info') . " AS o left join " . $GLOBALS['ecs']->table('order_goods') . " AS og " . " on o.order_id=og.order_id where og.goods_id='$goods_id' and og.order_id='$order_id' and og.product_id='$product_id'";
		$row_goods = $GLOBALS['db']->getRow($sql);

		if(! $row_goods || $row_goods['user_id'] != $_SESSION['user_id'])
		{
			show_message('对不起！您没权限针对该商品发起退款/退货及维修', '返回订单列表页', 'user.php?act=order_list', 'info');
		}
		else
		{
			$row_goods['total_price'] = $row_goods['goods_price'] * $row_goods['goods_number'];
			$row_goods['goods_price_format'] = price_format($row_goods['goods_price'], false);
			$row_goods['total_price_format'] = price_format($row_goods['total_price'], false);
			$smarty->assign('back_goods', $row_goods);

			$properties = get_goods_properties($goods_id); // 获得商品的规格和属性
			$smarty->assign('specification', $properties['spe']); // 商品规格
		}
	}
	else
	{
		$sql_oi = "SELECT * FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $order_id;
		$order_info = $GLOBALS['db']->getRow($sql_oi);
		$sql_og = "SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $order_id;
		$goods_list = $GLOBALS['db']->getAll($sql_og);
		foreach ($goods_list as $key => $goods_info)
		{
			$goods_info['total_price'] = $goods_info['goods_price'] * $goods_info['goods_number'];
			$goods_list[$key]['goods_price_format'] = price_format($goods_info['goods_price'], false);
			$goods_list[$key]['total_price_format'] = price_format($goods_info['total_price'], false);
		}
		$order_info['goods_list'] = $goods_list;

		if (!$order_info || $order_info['user_id'] != $_SESSION['user_id'])
		{
			show_message('对不起！您没权限针对该订单发起退款', '返回订单列表页', 'user.php?act=order_list', 'info');
		}
		else
		{
			$smarty->assign('order_info', $order_info);

			$properties = get_goods_properties($goods_id); // 获得商品的规格和属性
			$smarty->assign('specification', $properties['spe']); // 商品规格
		}
	}

	// 收货地址 www.yshop100.com增加
	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	$order = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE order_id='$order_id'");
	$smarty->assign('order', $order);
	$smarty->assign('shop_province', get_regions(1, $order['country']));
	$smarty->assign('shop_city', get_regions(2, $order['province']));
	$smarty->assign('shop_district', get_regions(3, $order['city']));
	$smarty->assign('name_of_region', array(
		$_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']
	));
	$smarty->assign('country_list', get_regions());

	$smarty->display('user_transaction.dwt');
}

/*
 * 代码增加_start By www.yshop100.com
 * 退换货订单详情
 */
function action_back_order_detail ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$back_id = ! empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$sql = 'SELECT shipping_id, shipping_code, shipping_name ' . 'FROM ' . $GLOBALS['ecs']->table('shipping') . 'WHERE enabled = 1 and supplier_id = 0   ORDER BY shipping_order';
	$shipping_list = $db->getAll($sql);

	$smarty->assign('shipping_list', $shipping_list);

	$sql = "SELECT * " . " FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE back_id= '$back_id' ";
	$back_shipping = $db->getRow($sql);

	$sql_og = "SELECT * FROM " . $GLOBALS['ecs']->table('back_goods') . " WHERE back_id = " . $back_id;
	$back_shipping['goods_list'] = $GLOBALS['db']->getAll($sql_og);

	$back_shipping['add_time'] = local_date("Y-m-d H:i", $back_shipping['add_time']);
	$back_shipping['refund_money_1'] = price_format($back_shipping['refund_money_1'], false);
	$back_shipping['refund_money_2'] = price_format($back_shipping['refund_money_2'], false);
	$back_shipping['refund_type_name'] = $back_shipping['refund_type'] == '0' ? '' : ($back_shipping['refund_type'] == '1' ? '退回用户余额' : '线下退款');
	$back_shipping['country_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[country]'");
	$back_shipping['province_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[province]'");
	$back_shipping['city_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[city]'");
	$back_shipping['district_name'] = $db->getOne("SELECT region_name FROM " . $ecs->table('region') . " WHERE region_id = '$back_shipping[district]'");

	$back_shipping['status_back_1'] = $back_shipping['status_back'];
	$back_shipping['status_back'] = $_LANG['bos'][$back_shipping['status_back']] . ($back_shipping['status_back'] == '3' && $back_shipping['back_type'] && $back_shipping['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : '');
	$back_shipping['status_refund'] = $_LANG['bps'][$back_shipping['status_refund']];

	$smarty->assign('back_shipping', $back_shipping);

	// 退货商品 + 换货商品 详细信息
	$list_backgoods = array();
	$sql = "select * from " . $ecs->table('back_goods') . " where back_id = '$back_id' order by back_type ";
	$res_backgoods = $db->query($sql);
	while($row_backgoods = $db->fetchRow($res_backgoods))
	{
		$back_type_temp = $row_backgoods['back_type'] == '2' ? '1' : $row_backgoods['back_type'];
		$list_backgoods[$back_type_temp]['goods_list'][] = array(
			'goods_name' => $row_backgoods['goods_name'], 'goods_attr' => $row_backgoods['goods_attr'], 'back_goods_number' => $row_backgoods['back_goods_number'], 'back_goods_money' => price_format($row_backgoods['back_goods_number'] * $row_backgoods['back_goods_price'], false), 'status_back' => $_LANG['bos'][$row_backgoods['status_back']] . ($row_backgoods['status_back'] == '3' && $row_backgoods['back_type'] && $row_backgoods['back_type'] != '4' ? ' (换回商品已寄出，请注意查收) ' : ''), 'status_refund' => $_LANG['bps'][$row_backgoods['status_refund']], 'back_type' => $row_backgoods['back_type']
		);
	}
	$smarty->assign('list_backgoods', $list_backgoods);

	/* 回复留言 www.yshop100.com增加 */
	$res = $db->getAll("SELECT * FROM " . $ecs->table('back_replay') . " WHERE back_id = '$back_id' ORDER BY add_time ASC");
	foreach($res as $value)
	{
		$value['add_time'] = local_date("Y-m-d H:i", $value['add_time']);
		$back_replay[] = $value;
	}

	$smarty->assign('back_replay', $back_replay);

	$smarty->assign('back_id', $back_id);
	$smarty->display('user_transaction.dwt');
}

/*
 * 留言回复
 */
function action_back_replay ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$back_id = intval($_REQUEST['back_id']);
	$message = $_POST['message'];
	$add_time = gmtime();

	$db->query("INSERT INTO " . $ecs->table('back_replay') . " (back_id, message, add_time, type) VALUES ('$back_id', '$message', '$add_time', 1)");

	show_message('恭喜，回复成功！', '返回', 'user.php?act=back_order_detail&id=' . $back_id);
}

/*
 * 取消退换货订单
 */
function action_del_back_order ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$back_id = intval($_REQUEST['id']);
	$sql = "select status_back from " . $ecs->table('back_order') . " where back_id='$back_id' ";
	$status_back = $db->getOne($sql);
	if($status_back != 0 && $status_back != 5)
	{
		show_message('对不起，该退货单无法取消', '返回退货订单列表页');
	}
	else
	{
		$sql = "update " . $ecs->table('back_goods') . " set status_back = 8 where back_id='$back_id' ";
		$db->query($sql);
		$sql = "update " . $ecs->table('back_order') . " set status_back = 8 where back_id='$back_id' ";
		$db->query($sql);
		show_message('恭喜，您已经成功取消该退货单', '返回退货订单列表页', 'user.php?act=back_list', 'info');
	}
}

/*
 * 更新退换货订单的快递方式和运单号
 */
function action_back_order_detail_edit ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	if(empty($_POST['shipping_id']))
	{
		show_message('快递公司不能为空');
	}
	if(empty($_POST['invoice_no']))
	{
		show_message('快递运单号不能为空');
	}
	$back_id = ! empty($_POST['back_id']) ? intval($_POST['back_id']) : 0;
	$invoice_no = trim($_POST['invoice_no']);
	$shipping_id = intval($_POST['shipping_id']);
	if($shipping_id)
	{
		$sql = "SELECT shipping_name FROM " . $GLOBALS['ecs']->table('shipping') . " where shipping_id='$shipping_id' ";
		$shipping_name = $db->getOne($sql);
	}
	$sql = "update " . $ecs->table('back_order') . " set shipping_id='$shipping_id', shipping_name='$shipping_name', invoice_no='$invoice_no' where back_id='$back_id' ";
	$db->query($sql);
	show_message('恭喜，您已经成功更新快递方式和运单号', '返回退货订单详情页');
}

function action_back_list ()
{

	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$action = $GLOBALS['action'];

	include_once (ROOT_PATH . 'includes/lib_transaction.php');

	$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

	$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('back_order') . " WHERE user_id = '$user_id'");

	$pager = get_pager('user.php', array(
		'act' => $action
	), $record_count, $page);

	$orders = get_user_backorders($user_id, $pager['size'], $pager['start']);

	$smarty->assign('pager', $pager);
	$smarty->assign('orders', $orders);
	$smarty->display('user_transaction.dwt');
}

/* 保存退换货订单 */
function action_back_order_act ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$add_time = gmtime();
	$order_id = ! empty($_POST['order_id']) ? trim($_POST['order_id']) : "0";
	$have_back_order = $db->getRow("SELECT * FROM " . $ecs->table('back_order') . " WHERE order_id = {$order_id}");
	if($have_back_order){
		show_message('同一张订单不可重复提交', '返回订单列表页', 'user.php?act=order_list', 'info');
	}
	if (!$_POST['order_all'])
	{
		$order_sn = ! empty($_POST['order_sn']) ? trim($_POST['order_sn']) : "";
		$goods_id = ! empty($_POST['goods_id']) ? trim($_POST['goods_id']) : "";
		$goods_name = ! empty($_POST['goods_name']) ? trim($_POST['goods_name']) : "";
		$goods_sn = ! empty($_POST['goods_sn']) ? trim($_POST['goods_sn']) : "";
	}
	$back_reason = ! empty($_POST['back_reason']) ? trim($_POST['back_reason']) : "";
	$country = intval($_POST['country']);
	$province = intval($_POST['province']);
	$city = intval($_POST['city']);
	$district = intval($_POST['district']);
	$consignee = ! empty($_POST['back_consignee']) ? trim($_POST['back_consignee']) : "";
	$address = ! empty($_POST['back_address']) ? trim($_POST['back_address']) : "";
	$zipcode = ! empty($_POST['back_zipcode']) ? trim($_POST['back_zipcode']) : "";
	$mobile = ! empty($_POST['back_mobile']) ? trim($_POST['back_mobile']) : "";
	$postscript = ! empty($_POST['back_postscript']) ? trim($_POST['back_postscript']) : "";
	$imgs = ($_POST['imgs']) ? implode(',', $_POST['imgs']) : '';
	$back_pay = intval($_POST['back_pay']);
	$back_type = intval($_POST['back_type']);
	$back_type_list = $_POST['back_type'];

	if(! $order_id)
	{
		show_message('对不起，您进行了错误操作！');
		exit();
	}

	$sql = "select * from " . $ecs->table('order_info') . " where order_id='$order_id' ";
	$order_info = $db->getRow($sql);

	if(empty($order_info))
	{
		show_message('对不起，此订单不存在！');
		exit();
	}

	if ($_POST['order_all'])
	{
		$order_sn = $order_info['order_sn'];
		$goods_id = 0;

		$sql_og = "SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $order_id;
		$order_info['goods_list'] = $GLOBALS['db']->getAll($sql_og);
	}

	$sql = "insert into " . $ecs->table('back_order') . "(order_sn, order_id, goods_id,  user_id, shipping_fee, consignee, address, " . "zipcode, mobile, add_time, postscript , back_reason, goods_name, imgs, back_pay, country, province, city, district, back_type, status_back, supplier_id) " . " values('$order_sn', '$order_id', '$goods_id',  '$user_id', '$order_info[shipping_fee]', '$consignee', '$address', " . "'$zipcode', '$mobile', '$add_time', '$postscript', '$back_reason', '$goods_name', '$imgs', '$back_pay', '$country', '$province', '$city', '$district', '$back_type', '5', '$order_info[supplier_id]')";

	$db->query($sql);

	// 插入退换货商品 80_back_goods
	$back_id = $db->insert_id();
	$have_tuikuan = 0; // 是否有退货
	// foreach($back_type_list as $back_type)
	// {
	if($back_type == 1)
	{
		$have_tuikuan = 1;
		$tui_goods_number = $_REQUEST['tui_goods_number'] ? intval($_REQUEST['tui_goods_number']) : 1;
		$sql = "insert into " . $ecs->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back ) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '0', " . " '$tui_goods_number', '$_REQUEST[tui_goods_price]', '5') ";
		$db->query($sql);
	}
	if($back_type == 4)
	{
		$have_tuikuan = 1;
		$have_tuikuan2 = 1;
		$price_refund_all = 0;

		foreach($order_info['goods_list'] as $goods_info)
		{
			$price_refund_all += ($goods_info['goods_price'] * $goods_info['goods_number']);

			$sql = "INSERT INTO " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back) " . " values('$back_id', '".$goods_info['goods_id']."', '".$goods_info['goods_name']."', '".$goods_info['goods_sn']."', '".$goods_info['product_id']."', '".$goods_info['goods_attr']."', '4', '".$goods_info['goods_number']."', '".$goods_info['goods_price']."', '5') ";
			$db->query($sql);
		}
	}
	if($back_type == 2)
	{
		$huan_count = count($_POST['product_id_huan']);
		if($huan_count)
		{
			$sql = "insert into " . $ecs->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, status_refund, back_goods_number, status_back) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '1', '9', '$huan_count', '5') ";
			$db->query($sql);
			$parent_id_huan = $db->insert_id();
			foreach($_POST['product_id_huan'] as $pid_key => $pid_huan)
			{
				$sql = "insert into " . $ecs->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr,  back_type, parent_id, status_refund, back_goods_number, status_back) " . "values('$back_id', '$goods_id', '$goods_name', '$goods_sn',  '$pid_huan', '" . $_POST['goods_attr_huan'][$pid_key] . "', '2', '$parent_id_huan', '9', '1', '5')";
				$db->query($sql);
			}
		}
	}
	if($back_type == 3)
	{
		$have_weixiu = 1;
		$tui_goods_number = $_REQUEST['tui_goods_number'] ? intval($_REQUEST['tui_goods_number']) : 1;
		$sql = "insert into " . $ecs->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, " . "back_goods_number, back_goods_price, status_back) " . " values('$back_id', '$goods_id', '$goods_name', '$goods_sn', '$_REQUEST[product_id_tui]', '$_REQUEST[goods_attr_tui]', '3', " . " '$tui_goods_number', '$_REQUEST[tui_goods_price]', '5') ";
		$db->query($sql);
	}
	// }

	/* 更新back_order */
	if($have_tuikuan)
	{
		if ($_POST['order_all'])
		{
			$price_refund = $GLOBALS['db']->getOne("SELECT money_paid FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $order_id);
		}
		else
		{
			$price_refund = $_REQUEST['tui_goods_price'] * $tui_goods_number;
		}
		$sql = "update " . $ecs->table('back_order') . " set refund_money_1= '$price_refund' where back_id='$back_id' ";
		$db->query($sql);
	}
	else
	{
		$sql = "update " . $ecs->table('back_order') . " set status_refund= '9' where back_id='$back_id' ";
		$db->query($sql);
	}

	if($have_tuikuan2)
	{
		$smarty->assign('back_act_w', 'tuikuan');
	}
	else if($have_weixiu)
	{
		$smarty->assign('back_act_w', 'weixiu');
	}
	else
	{
		$smarty->assign('back_act_w', 'tuihuo');
	}

	$smarty->assign('back_consignee', $consignee);
	$smarty->assign('back_address', $address);
	$smarty->assign('back_zipcode', $zipcode);

	$smarty->display('user_transaction.dwt');
}

/* 代码增加_end By www.yshop100.com */
/* 代码增加_start By www.yshop100.com */
function get_user_backorders ($user_id, $num = 10, $start = 0)
{
	/* 取得订单列表 */
	$arr = array();

	$sql = "SELECT bo.*, g.goods_name " . " FROM " . $GLOBALS['ecs']->table('back_order') . " AS bo left join " . $GLOBALS['ecs']->table('goods') . " AS g " . " on bo.goods_id=g.goods_id  " . " WHERE user_id = '$user_id' ORDER BY add_time DESC";
	$res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

	while($row = $GLOBALS['db']->fetchRow($res))
	{

		$row['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
		$row['refund_money_1'] = price_format($row['refund_money_1'], false);

		$row['goods_url'] = build_uri('goods', array(
			'gid' => $row['goods_id']
		), $row['goods_name']);
		$row['status_back_1'] = $row['status_back'];
		$row['status_back'] = $GLOBALS['_LANG']['bos'][(($row['back_type'] == 4 && $row['status_back'] != 8) ? $row['back_type'] : $row['status_back'])] . ' - ' . $GLOBALS['_LANG']['bps'][$row['status_refund']];

		$arr[] = $row;
	}

	return $arr;
}

/* 会员中心 */
function action_center ()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$now_date = date("Y-m-d");

	include_once (ROOT_PATH . 'includes/lib_clips.php');
    $smarty->assign('info', get_user_default($user_id));

	$sql = "SELECT headimg, rank_points, pay_points FROM " . $ecs->table('users') . " WHERE user_id = $user_id";
	$row = $db->getRow($sql);

	$smarty->assign('headimgurl',$row['headimg']);

	$rank_points = $row['rank_points'];
	$pay_points = $row['pay_points'];

    $ranks = array();
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_rank') . " ORDER BY min_points ASC";
    $ranks = $db->query($sql);
	$ranks_count = count($db->getAll($sql));
	$user_ranks = '';
	
	$i=1;
    while($row = $db->fetchRow($ranks)){
		$user_ranks[] = $row;

		if($rank_points >= $row['min_points'] && $rank_points <= $row['max_points']){
			if($i == $ranks_count){
				$smarty->assign('rank_l',$i-1);
				$smarty->assign('rank_r',$i);
				$smarty->assign('levelup_num',"");
				$smarty->assign('levelup_percent',"100%");
			}else{
				$smarty->assign('rank_l',$i);
				$smarty->assign('rank_r',$i+1);
				$levelup_num = $row['max_points'] - $rank_points+1;
				$smarty->assign('levelup_num', "还有".$levelup_num."积分升级");
				$levelup_percent = ($rank_points - $row['min_points']) / ($row['max_points'] - $row['min_points']);
				$levelup_percent = (round($levelup_percent, 2)*100)."%";
				$smarty->assign('levelup_percent', $levelup_percent);
			}
			$smarty->assign('rank_now', $i);
		}
		$i++;
    }

    //积分兑换
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, eg.exchange_integral, ' .
                'g.goods_type, g.goods_brief, g.goods_thumb , g.goods_img, eg.is_hot ' .
            'FROM ' . $GLOBALS['ecs']->table('exchange_goods') . ' AS eg LEFT JOIN ' .$GLOBALS['ecs']->table('goods') . ' AS g ' .
            "ON eg.goods_id = g.goods_id WHERE eg.is_hot = '1' ORDER BY eg.exchange_integral DESC LIMIT 6";
    $exchange_goods = $GLOBALS['db']->getAll($sql);
	
	//每日签到
	 $sql = "SELECT num, bignum, addnum FROM " . $ecs->table('signconf') . " WHERE startymd < '$now_date' AND endymd > '$now_date' ORDER BY cid DESC LIMIT 1";
	 $signconf = $db->getRow($sql);

	 if($signconf){
		$sql = "SELECT sid, signnum, signymd FROM " . $ecs->table('sign') . " WHERE user_id = '$user_id' ORDER BY signtime DESC";
		$row = $db->getRow($sql);

		if($row){
			$is_sign = $now_date == $row['signymd'] ? 1 : 0;
		}
	}

	//绑定手机
	$sql = "SELECT mobile_phone, validated, validated_time FROM " . $ecs->table('users') . " WHERE user_id = '$user_id' ";
	$row = $db->getRow($sql);
	if($row['mobile_phone'] && $row['validated'] == 1 &&  $row['validated_time'] != "0000-00-00 00:00:00"){
		$smarty->assign('isvalidated_mobile', '1');
	}
    
	$smarty->assign('rank_points',$rank_points);
	$smarty->assign('pay_points',$pay_points);
   	$smarty->assign('exchange_goods', $exchange_goods);
    $smarty->assign('user_ranks', $user_ranks);
    $smarty->assign('is_sign', $is_sign);
    $smarty->display('user_center.dwt');
}

/* 每日签到*/
function action_tosign()
{
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$now_date = date("Y-m-d");
	$now_time = time();

    $sql = "SELECT num, bignum, addnum FROM " . $ecs->table('signconf') . " WHERE startymd < '$now_date' AND endymd > '$now_date' ORDER BY cid DESC LIMIT 1";
	$signconf = $db->getRow($sql);

	if($signconf){
		$sql = "SELECT sid, signnum, signymd FROM " . $ecs->table('sign') . " WHERE user_id = $user_id ORDER BY signtime DESC LIMIT 1";
		$row = $db->getRow($sql);

		if($row){
			if($now_date == $row['signymd']){
			//已签到
				echo "0";
				exit;
			}elseif(date("Y-m-d",strtotime("$now_date -1 day")) == $row['signymd']){
			//连续签到
				$signnum = $row['signnum']++;
				$sign_point = $row['signnum'] * $signconf['num'] + $signconf['addnum'];
			}else{
			//断签
				$signnum = '1';
				$sign_point = $signconf['num'];
			}
		}else{
			//首次签到
			$signnum = '1';
			$sign_point = $signconf['num'];
		}

		$pay_points = $sign_point > $signconf['bignum'] ? $signconf['bignum'] : $sign_point;
		$sql = "INSERT INTO " . $ecs->table('sign') . " ( user_id, signtime, signymd, signnum) VALUES ( '$user_id', '$now_time', '$now_date', '$signnum')";
		$db->query($sql);

		log_account_change($user_id, '', '', '', $pay_points, '每日签到 '.$now_date, ACT_ADJUSTING);

		echo $pay_points;
		exit;
	}
}

function action_binding_mobile()
{
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];
	$now_date = date("Y-m-d");
	$now_datetime = date("Y-m-d H:i:s");

	include_once (ROOT_PATH . 'includes/lib_clips.php');
    $smarty->assign('info', get_user_default($user_id));
    $smarty->assign('user_info', get_user_info());
	$smarty->assign('username', $username);

    if($_POST['mobile_phone']){
    	require_once (ROOT_PATH . 'includes/lib_validate_record.php');
        $mobile_phone = ! empty($_POST['mobile_phone']) ? trim($_POST['mobile_phone']) : '';
        $mobile_code = ! empty($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';
        $record = get_validate_record($mobile_phone);

        $session_mobile_phone = $_SESSION[VT_MOBILE_REGISTER];

        $sql = "SELECT COUNT(user_id) FROM " . $ecs->table('users') . " WHERE mobile_phone = '$mobile_phone'";
		if($db->getOne($sql) > 0)
		{
			show_message('手机号已经存在，请重新输入！');
		}else{
			if(empty($mobile_code))
	        {
	            show_message("请输入验证码！");
	        }
	        // 检查验证码是否正确
	        else if($record['record_code'] != $mobile_code)
	        {
	            show_message("验证码有误,请重新输入！");
	        }
	        // 检查过期时间
	        else if($record['expired_time'] < time())
	        {
	           show_message("验证码已过期,请重新获取！");
	        }

	        $sql = "UPDATE " . $ecs->table('users') . " SET mobile_phone = '$mobile_phone', validated = '1', validated_time = '$now_datetime' WHERE user_id = '$user_id'";
			$db->query($sql);

			log_account_change($user_id, '', '', '', '50', '绑定手机 '.$now_date, ACT_ADJUSTING);

			show_message('恭喜，已成功绑定手机！', '返回会员中心', 'user.php?act=center');
		}
    }else{
    	$sql = "SELECT mobile_phone, validated, validated_time FROM " . $ecs->table('users') . " WHERE user_id = '$user_id' ";
		$row = $db->getRow($sql);
		if(!empty($row['mobile_phone'])){
			if($row['validated'] == '1' && $row['validated_time'] == "0000-00-00 00:00:00"){
				//手机注册，且没有完成积分任务
				$sql = "UPDATE " . $ecs->table('users') . " SET validated_time = '$now_datetime' WHERE user_id = '$user_id'";
				$db->query($sql);

				log_account_change($user_id, '', '', '', '50', '绑定手机 '.$now_date, ACT_ADJUSTING);

				show_message('恭喜，已成功绑定手机！', '返回会员中心', 'user.php?act=center');
			}else if($row['validated'] != '1'){
				//没有验证手机
				$smarty->display('user_binding_mobile.dwt');
			}else{
				show_message("您的手机已经绑定账号！");
			}
		}else{
			$smarty->display('user_binding_mobile.dwt');
		}
    }
}
function action_fenxiao()
{
	//全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	if($_POST['real_name']){
		$real_name = ! empty($_POST['real_name']) ? trim($_POST['real_name']) : '';
		$mobile = ! empty($_POST['mobile']) ? trim($_POST['mobile']) : '';
		$sex = ! empty($_POST['sex']) ? trim($_POST['sex']) : '';
		$country = ! empty($_POST['country']) ? trim($_POST['country']) : '';
		$province = ! empty($_POST['province']) ? trim($_POST['province']) : '';
		$city = ! empty($_POST['city']) ? trim($_POST['city']) : '';
		$district = ! empty($_POST['district']) ? trim($_POST['district']) : '';
		$job = ! empty($_POST['job']) ? trim($_POST['job']) : '';
		$age = ! empty($_POST['age']) ? trim($_POST['age']) : '';
		$jieshao = ! empty($_POST['jieshao']) ? trim($_POST['jieshao']) : '';
		$weixin = ! empty($_POST['weixin']) ? trim($_POST['weixin']) : '';
		$weishang = ! empty($_POST['weishang']) ? trim($_POST['weishang']) : '';
		$add_time = gmtime();

		if(!$real_name){
			show_message("姓名不能为空！");
		}elseif(!preg_match('/^1[0-9]{10}$/', $mobile)){
			show_message("请输入正确的手机号码！");
		}
		$remark = "";
		if($sex == 0){
			$remark .= "性别：女";
		}else{
			$remark .= "性别：男";
		}

		if(!$district){
			show_message("居住地不能为空！");
		}
		$sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id in (".$province.",".$city.",".$district.") order by region_id";
		$region = $db->getAll($sql);
		if($region){
			$remark .= "；居住地：中国";
			foreach ($region as $value) {
				$remark .= "-".$value['region_name'];
			}
		}
		if($job){
			$remark .= "；职业：".$job;
		}
		if($age){
			$remark .= "；年龄：".$age;
		}
		if($jieshao){
			$remark .= "；介绍人：".$jieshao;
		}
		if($weixin){
			$remark .= "；微信号：".$weixin;
		}
		if($weishang == 0){
			$remark .= "；是否有从事过微商?：否";
		}else{
			$remark .= "；是否有从事过微商?：是";
		}

		$sql = "UPDATE " . $ecs->table('users') . " SET is_fenxiao = '1' , status = '2' WHERE user_id = '".$user_id."'";
		$db->query($sql);

		$sql = "SELECT apply_id FROM  " . $ecs->table('fenxiao_apply') . " WHERE user_id = '".$user_id."' LIMIT 1";
		$apply_id = $db->getOne($sql);
		if(!empty($apply_id)){
			$sql = "UPDATE " . $ecs->table('fenxiao_apply') . " SET real_name = '".$real_name."' , mobile = '".$mobile."', remark = '".$remark."', add_time = '".$add_time."' WHERE apply_id = '".$apply_id."'";
		}else{
			$sql = "INSERT INTO " . $ecs->table('fenxiao_apply') . " (user_id, real_name, mobile, remark, add_time) VALUES ('".$user_id."', '".$real_name."', '".$mobile."', '".$remark."', '".$add_time."')";
		}
		$db->query($sql);

		

		show_message('提交申请成功！', '返回会员中心', 'user.php');
	}else{

		$sql ="SELECT status FROM " . $ecs->table('users') . " WHERE is_fenxiao = '1' AND user_id = '".$user_id."'";
		$row = $db->getRow($sql);
		if($row['status'] == '1'){
			header("Location:v_user.php");
			exit;
		}elseif($row['status'] == '2'){
			$sql ="SELECT apply_id FROM " . $ecs->table('fenxiao_apply') . " WHERE user_id = '".$user_id."'";
			$row = $db->getRow($sql);
			if($row){
				show_message("您已经提交申请，审核中，请等待分销专员联系您！");
			}
		}

		// 取得国家省市区列表                
		$consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 1;
		$consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : -1;
		$consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : -1;
        $consignee['district'] = isset($consignee['district']) ? intval($consignee['district']) : -1;
        $province_list = get_regions_wap($consignee['country']);
		$city_list = get_regions_wap($consignee['province']);
		$district_list = get_regions_wap($consignee['city']);

		// 赋值于模板
		$smarty->assign('province_list', $province_list);
		$smarty->assign('city_list', $city_list);
		$smarty->assign('district_list', $district_list);
		$smarty->assign('action', 'fenxiao');

		$smarty->display('user_fenxiao.dwt');
	}
}
?>
