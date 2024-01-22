<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');

/* 未登录处理 */
if(empty($_SESSION['user_id']) )
{
	ecs_header("Location: user.php?act=login\n"); 
}


$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
$action = isset($_POST['act']) ? trim($_POST['act']) :$action;

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_shiming";
}

call_user_func($function_name);

function action_agree ()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	if($_CFG['is_distrib'] == 0)
	{
		show_message('没有开启微信分销服务！','返回首页','index.php'); 
	}

	if($_SESSION['user_id'] == 0)
	{
		ecs_header("Location: ./\n");
	    exit;	 
	}

	$is_distribor = is_distribor($user_id);
	if($is_distribor != 1)
	{
	    ecs_header("Location: ./\n");
		exit;
	}

	$sql = "SELECT isagree, real_name, card FROM " . $GLOBALS['ecs']->table('user_fenxiao_period') . " WHERE user_id = '".$user_id."'";
	$row = $GLOBALS['db']->getRow($sql);
	if($row){
		extract($row);
		if($isagree == 1){
			ecs_header("Location: v_user.php"); 
			exit;
		}
		$smarty->assign('real_name', $real_name);
		$smarty->assign('card', $card);
	}else{
		ecs_header("Location: ./\n");
	    exit;
	}

	$sql = "SELECT agreement_id, agreement_name, content FROM ". $GLOBALS['ecs']->table('agreement') ." WHERE UNIX_TIMESTAMP()>start_time AND UNIX_TIMESTAMP()<end_time AND enabled = 1 ORDER BY sort_order ASC";
	$agreement_list = $GLOBALS['db']->getAll($sql);
	foreach ($agreement_list as $k => $val) {
		$agreement_list[$k]['content'] = str_replace('{real_name}',"<u>".$real_name."</u>" ,$agreement_list[$k]['content']);
		$agreement_list[$k]['content'] = str_replace('{card}',"<u>".$card."</u>" ,$agreement_list[$k]['content']);
		$agreement_list[$k]['content'] = str_replace('{now}', "<u>".local_date('Y年m月d日', gmtime())."</u>",$agreement_list[$k]['content']);
		$agreement_list[$k]['content'] = str_replace('{end}', "<u>".local_date('Y年m月d日', strtotime( "+1 years, -1 day", gmtime()))."</u>",$agreement_list[$k]['content']);
	}

	$smarty->assign('agreement_list', $agreement_list);
	$smarty->assign('page_title', '分销商个人基本信息');    // 页面标题

	$smarty->display('v_user_agree_period.dwt');
}

function action_act_agree (){
	// 获取全局变量
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	if($_SESSION['user_id'] == 0)
	{
		ecs_header("Location: ./\n");
	    exit;	 
	}

	if(!$_POST['is_agree'] || $_POST['is_agree'] != 'on'){
		show_message('请同意以上条款！'); 
	}

	$sql = "UPDATE " . $GLOBALS['ecs']->table('user_fenxiao_period') . " SET isagree='1', agreetime = '".date('Y-m-d',time())."' WHERE user_id=".$user_id;
	$GLOBALS['db']->query($sql);

	show_message('分销商开通成功！','进入我的分销','v_user.php'); 
}

function action_shiming ()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$sql = "SELECT id, isagree FROM ". $GLOBALS['ecs']->table('user_fenxiao_period') ." WHERE user_id = '".$user_id."'";
	$row = $GLOBALS['db']->getRow($sql);
	if($row['isagree'] == 1){
		ecs_header("Location: v_user.php"); 
		exit;
	}elseif($row['id'] && $row['isagree'] == 0){
		ecs_header("Location: v_user_agree_period.php?act=agree");
		exit;
	}

	if($_CFG['is_distrib'] == 0)
	{
		show_message('没有开启微信分销服务！','返回首页','index.php'); 
	}

	if($_SESSION['user_id'] == 0)
	{
		ecs_header("Location: ./\n");
	    exit;	 
	}

	$sql = "SELECT sm_status, card, real_name, mobile_phone FROM ". $GLOBALS['ecs']->table('users') ." WHERE user_id = '".$user_id."'";
	$user_info = $GLOBALS['db']->getRow($sql);
	if($user_info['sm_status'] == 1){
		$smarty->assign('user_info', $user_info);
	}

	$smarty->assign('page_title', '分销商个人基本信息');    // 页面标题

	$smarty->display('v_user_agree_period_shiming.dwt');
}


function action_act_identity()
{
	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$real_name = ! empty($_POST['real_name']) ? trim($_POST['real_name']) : '';
	$card = ! empty($_POST['card']) ? trim($_POST['card']) : '';
	$bank_card = ! empty($_POST['bank_card']) ? trim($_POST['bank_card']) : '';
	$bank_name = ! empty($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
	$mobile_phone = ! empty($_POST['mobile_phone']) ? trim($_POST['mobile_phone']) : '';

	$bank_card = str_replace(' ','',$bank_card);

	if($mobile_phone=="")
	{
		show_message('请填写手机号');			
	}
	if($real_name=="")
	{
		show_message('请填写姓名');			
	} 
	if($card=="")
	{
		show_message('请填写身份证号');			
	} 
	if($bank_card=="")
	{
		show_message('请填写银行卡号');			
	}
	if($bank_name=="")
	{
		show_message('请选择开户行');			
	}

	$sql = "select value,store_dir from " . $GLOBALS['ecs']->table('shop_config') . " where code = 'userinfo_yinhang'";
	$flagrow = $GLOBALS['db']->getRow($sql);
	if($flagrow['value']=='1')
	{
		$host = "https://b4bankcard.market.alicloudapi.com";
		$path = "/bank4Check";
		$method = "GET";
		$appcode = $flagrow['store_dir'];
		$headers = array();
		array_push($headers, "Authorization:APPCODE " . $appcode);
		$querys = "accountNo=".$bank_card."&idCard=".$card."&mobile=".$mobile_phone."&name=".$real_name;
		$bodys = "";
		$url = $host . $path . "?" . $querys;
		// file_put_contents("log/yinhang_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n地址:".$url."\r\n", FILE_APPEND);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		//curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
		//状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
		if (1 == strpos("$".$host, "https://"))
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		$out_put = curl_exec($curl);
		// file_put_contents("log/yinhang_".date('Y-m-d',time()).".txt", date('Y-m-d H:i:s',time())."\r\n返回:".$out_put."\r\n", FILE_APPEND);

		$returnarray = json_decode($out_put,TRUE);
		$jkmsg=str_replace("'","''",$returnarray['msg']);
		if($returnarray['status']=='01')
		{
			if($returnarray['cardType']=='借记卡')
			{
				$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_fenxiao_period') . " WHERE user_id = '".$user_id."'";
				$row = $GLOBALS['db']->getOne($sql);
				if($row){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('user_fenxiao_period') . " SET isagree='0', agreetime = '".date('Y-m-d H:i:s',time())."', real_name = '".$real_name."', bank_card = '".$bank_card."', bank_name = '".$bank_name."', card = '".$card."', mobile_phone = '".$mobile_phone."' WHERE user_id=".$user_id;
					$GLOBALS['db']->query($sql);
				}else{
					$sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_fenxiao_period') . " ( user_id, isagree, agreetime, real_name, bank_card, bank_name, card , mobile_phone) VALUES ( '".$user_id."', '0', '".date('Y-m-d H:i:s',time())."', '".$real_name."', '".$bank_card."', '".$bank_name."', '".$card."', '".$mobile_phone."')";
					$GLOBALS['db']->query($sql);
				}

				$sql = "SELECT real_name, mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '".$user_id."'";
				$row = $GLOBALS['db']->getRow($sql);
				if(!$row['real_name']){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET real_name = '".$real_name."' WHERE user_id = '".$user_id."'";
					$GLOBALS['db']->query($sql);
				}
				if(!$row['mobile_phone']){
					$sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET mobile_phone = '".$mobile_phone."' WHERE user_id = '".$user_id."'";
					$GLOBALS['db']->query($sql);
				}

				ecs_header("Location: v_user_agree_period.php?act=agree");
				exit;
			}
			else
			{
				$jkmsg="非借记卡";

				show_message('您申请分销商审核不通过，'.$jkmsg.'！');
			} 
		}
		else if($returnarray['status']=='02'||$returnarray['status']=='207'||$returnarray['status']=='206'||$returnarray['status']=='205'||$returnarray['status']=='204'||$returnarray['status']=='203'||$returnarray['status']=='202')
		{
			show_message('您申请实名认证与银行卡信息审核不通过，'.$jkmsg.'！');
		}
		else
		{
			show_message('您已申请分销商，请等待管理员的审核！', '返回上一页', '/mobile/');
		}
	}
}



function action_bank_card()
{
	include('includes/cls_json.php');
	$json   = new JSON;
	$cardid = $_REQUEST['cardid'];
	if($cardid){
		$card = file_get_contents("https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?cardNo={$cardid}&cardBinCheck=true");
    	$bankCard = json_decode($card,ture);
	}
    $bank_list = array(
        ""=>"请选择开户行","CDB"=>"国家开发银行","ICBC"=>"中国工商银行","ABC"=>"中国农业银行","BOC"=>"中国银行","CCB"=>"中国建设银行","PSBC"=>"中国邮政储蓄银行","COMM"=>"交通银行","CMB"=>"招商银行","SPDB"=>"上海浦东发展银行","CIB"=>"兴业银行","HXBANK"=>"华夏银行","GDB"=>"广东发展银行","CMBC"=>"中国民生银行","CITIC"=>"中信银行","CEB"=>"中国光大银行","EGBANK"=>"恒丰银行","CZBANK"=>"浙商银行","BOHAIB"=>"渤海银行","SPABANK"=>"平安银行","SHRCB"=>"上海农村商业银行","YXCCB"=>"玉溪市商业银行","YDRCB"=>"尧都农商行","BJBANK"=>"北京银行","SHBANK"=>"上海银行","JSBANK"=>"江苏银行","HZCB"=>"杭州银行","NJCB"=>"南京银行","NBBANK"=>"宁波银行","HSBANK"=>"徽商银行","CSCB"=>"长沙银行","CDCB"=>"成都银行","CQBANK"=>"重庆银行","DLB"=>"大连银行","NCB"=>"南昌银行","FJHXBC"=>"福建海峡银行","HKB"=>"汉口银行",
        "WZCB"=>"温州银行","QDCCB"=>"青岛银行","TZCB"=>"台州银行","JXBANK"=>"嘉兴银行","CSRCB"=>"常熟农村商业银行","NHB"=>"南海农村信用联社","CZRCB"=>"常州农村信用联社","H3CB"=>"内蒙古银行","SXCB"=>"绍兴银行","SDEB"=>"顺德农商银行","WJRCB"=>"吴江农商银行","ZBCB"=>"齐商银行","GYCB"=>"贵阳市商业银行","ZYCBANK"=>"遵义市商业银行","HZCCB"=>"湖州市商业银行","DAQINGB"=>"龙江银行","JINCHB"=>"晋城银行JCBANK","ZJTLCB"=>"浙江泰隆商业银行","GDRCC"=>"广东省农村信用社联合社","DRCBCL"=>"东莞农村商业银行","MTBANK"=>"浙江民泰商业银行","GCB"=>"广州银行","LYCB"=>"辽阳市商业银行","JSRCU"=>"江苏省农村信用联合社","LANGFB"=>"廊坊银行","CZCB"=>"浙江稠州商业银行","DYCB"=>"德阳商业银行","JZBANK"=>"晋中市商业银行","BOSZ"=>"苏州银行","GLBANK"=>"桂林银行","URMQCCB"=>"乌鲁木齐市商业银行","CDRCB"=>"成都农商银行",
        "ZRCBANK"=>"张家港农村商业银行","BOD"=>"东莞银行","LSBANK"=>"莱商银行","BJRCB"=>"北京农村商业银行","TRCB"=>"天津农商银行","SRBANK"=>"上饶银行","FDB"=>"富滇银行","CRCBANK"=>"重庆农村商业银行","ASCB"=>"鞍山银行","NXBANK"=>"宁夏银行","BHB"=>"河北银行","HRXJB"=>"华融湘江银行","ZGCCB"=>"自贡市商业银行","YNRCC"=>"云南省农村信用社","JLBANK"=>"吉林银行","DYCCB"=>"东营市商业银行","KLB"=>"昆仑银行","ORBANK"=>"鄂尔多斯银行","XTB"=>"邢台银行","JSB"=>"晋商银行","TCCB"=>"天津银行","BOYK"=>"营口银行","JLRCU"=>"吉林农信","SDRCU"=>"山东农信","XABANK"=>"西安银行","HBRCU"=>"河北省农村信用社","NXRCU"=>"宁夏黄河农村商业银行","GZRCU"=>"贵州省农村信用社","FXCB"=>"阜新银行","HBHSBANK"=>"湖北银行黄石分行","ZJNX"=>"浙江省农村信用社联合社","XXBANK"=>"新乡银行","HBYCBANK"=>"湖北银行宜昌分行",
        "LSCCB"=>"乐山市商业银行","TCRCB"=>"江苏太仓农村商业银行","BZMD"=>"驻马店银行","GZB"=>"赣州银行","WRCB"=>"无锡农村商业银行","BGB"=>"广西北部湾银行","GRCB"=>"广州农商银行","JRCB"=>"江苏江阴农村商业银行","BOP"=>"平顶山银行","TACCB"=>"泰安市商业银行","CGNB"=>"南充市商业银行","CCQTGB"=>"重庆三峡银行","XLBANK"=>"中山小榄村镇银行","HDBANK"=>"邯郸银行","KORLABANK"=>"库尔勒市商业银行","BOJZ"=>"锦州银行","QLBANK"=>"齐鲁银行","BOQH"=>"青海银行","YQCCB"=>"阳泉银行","SJBANK"=>"盛京银行","FSCB"=>"抚顺银行","ZZBANK"=>"郑州银行","bank_nameSRCB"=>"深圳农村商业银行","BANKWF"=>"潍坊银行","JJBANK"=>"九江银行","JXRCU"=>"江西省农村信用","HNRCU"=>"河南省农村信用","GSRCU"=>"甘肃省农村信用","SCRCU"=>"四川省农村信用","GXRCU"=>"广西省农村信用","SXRCCU"=>"陕西信合","WHRCB"=>"武汉农村商业银行","YBCCB"=>"宜宾市商业银行",
        "KSRB"=>"昆山农村商业银行","SZSBK"=>"石嘴山银行","HSBK"=>"衡水银行","XYBANK"=>"信阳银行","NBYZ"=>"鄞州银行","ZJKCCB"=>"张家口市商业银行","XCYH"=>"许昌银行","JNBANK"=>"济宁银行","CBKF"=>"开封市商业银行","WHCCB"=>"威海市商业银行","HBC"=>"湖北银行","BOCD"=>"承德银行","BODD"=>"丹东银行","JHBANK"=>"金华银行","BOCY"=>"朝阳银行","LSBC"=>"临商银行","BSB"=>"包商银行","LZYH"=>"兰州银行","BOZK"=>"周口银行","DZBANK"=>"德州银行","SCCB"=>"三门峡银行","AYCB"=>"安阳银行","ARCU"=>"安徽省农村信用社","HURCB"=>"湖北省农村信用社","HNRCC"=>"湖南省农村信用社","NYNB"=>"广东南粤银行","LYBANK"=>"洛阳银行","NHQS"=>"农信银清算中心","CBBQS"=>"城市商业银行资金清算中心"
    );

    foreach ($bank_list AS $key => $val){
    	if($key == $bankCard['bank']){
    		$result .= "<option value='$key' selected='selected'>$val</option>";
    	}else{
    		$result .= "<option value='$key'>$val</option>";
    	}
    }
    echo $json->encode($result);
    exit;
}

?>