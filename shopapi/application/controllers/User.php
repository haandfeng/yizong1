<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 
 * Enter description here ...
 * @author shi
 *
 */

require APPPATH.'/libraries/Alipay/alipay.config.php';
require APPPATH.'/libraries/Alipay/lib/alipay_notify.class.php';

class User extends MY_Controller {
	public function login(){
		$user = $this->_values['user'];
		$passwd = $this->_values['passwd'];
		$this->load->model('User_model');
		$uArr = $this->User_model->login($user,$passwd);
		$arr = (object)array();
		switch ($uArr){
			case 0:
				$this->_tojson('0','登录失败',$arr);
				break;
			case 2:
				$this->_tojson('2','用户名密码不匹配',$arr);
				break;
			default:
				$this->_tojson('1','登录成功',$uArr);
				break;
		}
	}

    public function seek()
    {
        $type = $this->_values['type'];
        $user_id = $this->_values['user_id'];
        $info_user_id = $type . '_' .$user_id;

        $this->load->model('User_model');
        $res = $this->User_model->resister_name($info_user_id);
        if(is_array($res))
        {
            $uArr = $this->User_model->slogin($res[0]['user_name'],$res[0]['password']);
            $arr = (object)array();
            switch ($uArr){
                case 0:
                    $this->_tojson('0','登录失败',$arr);
                    break;
                case 2:
                    $this->_tojson('2','用户名密码不匹配',$arr);
                    break;
                default:
                    $this->_tojson('1','登录成功',$uArr);
                    break;
            }

        }else{
            exit($res);
        }
    }

    /*
     * 生成用户名
     * */
    public function user_name()
    {
        $user_name = '';
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";

        $max = strlen($strPol)-1;

        for($i=0;$i<10;$i++){
            $user_name.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        $this->load->model('User_model');
        //检验用户名
        $res = $this->User_model->seek_user($user_name);
        if(!$res)
        {
           $user_naem =  $this->user_name();
        }
        return $user_name;
    }

    //新增用户
    public function int()
    {
        $type = $this->_values['type'];
        $user_id = $this->_values['user_id'];
        $info_user_id = $type . '_' .$user_id;

        $password = '123456';
        $user_name = $this->user_name();
        $this->load->model('User_model');

        //注册
        $res = $this->User_model->int_name($info_user_id,$user_name,$password);
        if(is_array($res))
        {
            //登录
            $res = $this->User_model->resister_name($info_user_id);
            if(is_array($res)) {
                $uArr = $this->User_model->slogin($res[0]['user_name'], $res[0]['password']);
                $arr = (object)array();
                switch ($uArr) {
                    case 0:
                        $this->_tojson('0', '登录失败', $arr);
                        break;
                    case 2:
                        $this->_tojson('2', '用户名密码不匹配', $arr);
                        break;
                    default:
                        $this->_tojson('1', '登录成功', $uArr);
                        break;
                }
            }
        }else{
            exit($res);
        }

    }

    //绑定老账号
    public function bang()
    {
        $type = $this->_values['type'];
        $user_id = $this->_values['user_id'];
        $info_user_id = $type . '_' .$user_id;
        
        $password = $this->_values['password'];
        $user_name = $this->_values['user_name'];

        $this->load->model('User_model');

        $res = $this->User_model->bang_name($user_name,$password);

        //账号密码不对
        if(is_array($res))
        {
            exit(json_encode($res));
        }

        $res = $this->User_model->bang_aite($info_user_id,$user_name,$res);
        //exit(json_encode($res));
        if(is_array($res))
        {
            //登录（）
            $res = $this->User_model->resister_name($info_user_id);
            if(is_array($res))
            {

                $uArr = $this->User_model->slogin($res[0]['user_name'],$res[0]['password']);
                $arr = (object)array();
                switch ($uArr){
                    case 0:
                        $this->_tojson('0','登录失败',$arr);
                        break;
                    case 2:
                        $this->_tojson('2','用户名密码不匹配',$arr);
                        break;
                    default:
                        $this->_tojson('1','登录成功',$uArr);
                        break;
                }
            }else{
                exit($res);
            }
        }else{
            exit($res);
        }
    }





	//注销登录
	public function logout(){
		$key = 'e5d8583d79c5b93625980b7ec7ef68f8';
		$this->load->model("User_model");
		$uArr = $this->User_model->logout($key);
		echo $this->db->last_query();
		if($uArr){
			$this->_tojson("200",'注销成功');
		}else{
			$this->_tojson("0",'注销失败');
		}
	}
	//注册用户
	public function register_bak(){
		$values = $this->_values;
		$username = $values['username'];
		$pwd = $values['passwd'];
		$email = $values['email'];
        $invite = $values['invite'];//邀请码
		$this->load->model("User_model");
		//$uArr = $this->User_model->register($username,$pwd,$email,$invite);
		$uArr = $this->User_model->register('tom','123456','1987736619qq.com','7788');
//        echo $this->_tojson("0","邀请码",$uArr);
//        exit;
		switch ($uArr){
			case 0 :				
				echo $this->_tojson("0","该用户名已被注册");
				break;
			case 3 :				
				echo $this->_tojson("3","该邮箱已被注册");
				break;
			case 2 :
				echo $this->_tojson("2","注册失败");
				break;
			case 4 :
				echo $this->_tojson("0","注册失败1");
				break;
            case 5 :
                echo $this->_tojson("5","邀请码有误");
                break;
			default:
				echo $this->_tojson("1","注册成功",$uArr);
				break;
		}
	}

    public function register(){
        $values = $this->_values;
        $username = $values['username'];
        $pwd = $values['pwd'];
        $type = $values['type'];
        $code = $values['code'];
        $invite = $values['invite'];//邀请码
        $this->load->model("User_model");
        $uArr = $this->User_model->register($username,$pwd,$type,$code,$invite);
//        echo $this->_tojson("0","邀请码",$uArr);
//        exit;
        switch ($uArr){
            case 1011 :
                echo $this->_tojson("1011","该邮箱已被注册！");
                break;
            case 1021 :
                echo $this->_tojson("1021","该手机已被注册！");
                break;
            case 1002 :
                echo $this->_tojson("1002","请填写验证码！");
                break;
            case 1003 :
                echo $this->_tojson("1003","注册失败");
                break;
            case 1002 :
                echo $this->_tojson("1002","邀请码有误");
                break;
            case 1022 :
                echo $this->_tojson("1022","手机验证码错误");
                break;
            case 1023 :
                echo $this->_tojson("1022","手机验证码过期，请重新获取");
                break;
            case 1012 :
                echo $this->_tojson("1032","邮箱验证码错误");
                break;
             case 1005 :
                 echo $this->_tojson("1005","验证码不能为空！");
                 break;
            case 1001 :
                echo $this->_tojson("1001","您的注册类型有误，请联系客服！");
                break;
            default:
                echo $this->_tojson("1","注册成功",$uArr);
                break;
        }
    }

    public function send_code(){

        //初始化
        $values = $this->_values;
        $username = $values['username'];
        $key_mobile_phone = $values['key_mobile_phone'];
        $type = $values['type'];
        $res = '';


        if(empty($username) || empty($key_mobile_phone || empty($type))){
        	echo $this->_tojson("1015","缺少参数");
        }


		//查看邮箱或者手机是否已经注册
		$sql = 'select '. $type .' from '.$this->db->dbprefix.'users where `' . $type . '` = ?';
		$result = $this->db->query($sql,$username);

		if($result->num_rows()){
			
			if($type == 'email'){
				echo $this->_tojson("1021","获取失败，该邮箱已被注册！");
				exit;
			}else{
				echo $this->_tojson("1021","获取失败，该手机已被注册！");
				exit;
			}
		}



        //print_r($values);die;
        $this->load->model('User_model');
        if('email' != $type && 'mobile_phone' != $type){
            $res = 1001;
        }elseif('email' == $type){//邮件验证码
            $res = $this->User_model->send_email_code($username);
        }elseif('mobile_phone' == $type){//手机验证码
            $res = $this->User_model->send_mobile_code($username,$key_mobile_phone);
        }
        //print_r($type);die;

        //根据状态码返回信息；
        switch ($res){
            case 1001 :
                echo $this->_tojson("1001","您的注册类型有误，请联系客服！");
                break;
            case 1002 :
                echo $this->_tojson("1002","短信验证码发送失败");
                break;
            case 1002 :
                echo $this->_tojson("1006","密码不能为空");
                break;
            case 1011 :
                echo $this->_tojson("1021","获取失败，该手机已被注册！");
                break;
            case 1021 :
                echo $this->_tojson("1021","获取失败，该邮箱已被注册！");
                break;
            case 1012 :
                echo $this->_tojson("1012","手机号不能为空");
                break;
            case 1013 :
                echo $this->_tojson("1013","每60秒内只能发送一次短信验证码，请稍候重试!");
                break;
            case 1014 :
                echo $this->_tojson("1014","您发送验证码太过于频繁，请稍后重试！");
                break;
            case 1015 :
                echo $this->_tojson("1015","非法请求");
                break;
            default:
                echo $this->_tojson("200","发送成功",$res);
                break;
        }
    }
	
	//订单管理
	public function order(){
		$userid = $this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model("User_model");
		$uArr = $this->User_model->order($userid);
		if($uArr){
			$this->_tojson('1','获取订单成功',$uArr);
		}else{
			$this->_tojson('0','没有订单数据',array());
		}
	}
	//用户信息
	public function userinfo(){
		$userid = $this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('User_model');
		$uArr = $this->User_model->userinfo($userid);
	//	$uArr['sex']=$this->_sex($uArr['sex']);
		if($uArr){
			$this->_tojson('1','获取用户信息成功',array($uArr));
		}else{
			$this->_tojson('0','获取用户信息失败',array());
		}
	}
	
	/*处理h5授权*/
	public function getH5AccessToken()
	{
		$userid = $this->_getId();
		if($userid==0){
			$this->_tojson('-220', '你处于未登录状态',array());
			exit;
		}
		$this->load->model('User_model');
		$user_info = $this->User_model->userinfo($userid);			
		if($user_info){	
			$member_name          = $user_info['nick_name'];
			$str_rand             = $this->get_rand(32);
			$access_token         = md5($userid.$member_name.strval(time()).$str_rand);			
			$access_token_exptime = time() + 7000;
			setcookie('accesstoken',$access_token, 7200);
			$res = $this->User_model->modifyUserToken($userid,$access_token);			
			if($res){
				$this->_tojson('1','获取授权信息成功',array('access_token' => $access_token, 'access_token_exptime' => $access_token_exptime));
			}else{
				$this->_tojson('0','获取授权信息失败【用户数据更新失败】',array());
			}	
		}else{
			$this->_tojson('0','获取授权信息失败',array());
		}
	}
	
	/*获得随机数*/
    private function get_rand($length = 32)
    {
        $str   = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $r_str = '';
        for ($i = 0; $i < $length; $i++) {
            $rand  = rand(0, 35);
            $r_str .= substr($str, $rand, 1);
        }
        return $r_str;
    }
	
	public function modifyUser(){
		$userid = $this->_getId();
		$sex  = $this->_values['sex'];
		$birthday = $this->_values['birthday'];
		if($userid == 0){
			$this->_tojson('-220', '你处于未登录状态',array());	
			exit;		
		}
		$this->load->model('User_model');
		$uArr = $this->User_model->modifyUser($userid,$sex,$birthday);
		$uArr['sex']=$this->_sex($uArr['sex']);
		if($uArr){
			$this->_tojson('1','修改成功',array($uArr));
		}else{
			$this->_tojson('0','修改失败',array());
		}
	}
	
	/*
	 * 找回密码
	 * 1、发送邮件
	 * 2、点击邮件链接更新新密码
	 */
	public function send(){
		$username = $this->_values['username'];
		$address = $this->_values['email'];
		$title = "找回密码";
		$code = rand(100000,999999);
		$time = time();
		$arr = array(
			'username' =>$username,
			'code' => $code,
			'time' => $time
		);

		$data[] = $this->values['email'];
		$data[] = $this->values['username'];
		//先判断用户名是否存在
		$sql = 'select * from '.$this->db->dbprefix.'users where user_name = ?';
		$result = $this->db->query($sql,$username);
		if(!$result->num_rows()){
			$this->_tojson('2','用户名不存在');
		}
		$sql = 'select * from '.$this->db->dbprefix.'users where user_name=?';
		$result = $this->db->query($sql,$username);
		$resulta = $result->result_array();
		if($resulta[0]['email'] !=$address){
			$this->_tojson('3','邮箱不正确');
		}
		$file = "./code/code.txt";
		$myfile = fopen($file,'a+') or die("文件不存在");
		fwrite($myfile,json_encode($arr)."\r\n");
		fclose($myfile);
		$message = "您申请的验证码为".$code;
		$bool = $this->sendEail($title,$address,$message);
		if($bool){
			$this->_tojson('1','发送验证码成功');
		}else{
			$this->_tojson("0",'发送验证码失败');
		}
	}
	public function password(){
		$username = trim($this->_values['username']);
		$code = trim($this->_values['code']);
		$passwd = trim($this->_values['passwd']);
		//检查验证吗
		$file = "./code/code.txt";
		$fread = file($file);
		foreach ($fread as $key=>$value){
			$json[]= json_decode($value,true);
			
		}
		foreach ($json as $key => $value){
			$decode[] = $value;
		}
		$str = 0;
		for($i=0;$i<count($decode);$i++){
			if($decode[$i]['code'] == $code){
				$str +=1;
			}
		}
		
		if($str == 0){
			$this->_tojson(0, '验证码不正确，请重新获取');
			exit();
		}

		$this->load->model('User_model');
		$uArr = $this->User_model->rpass($passwd,$username);
		//删除文档中的记录
		for($i=0;$i<count($fread);$i++){
			$arr[] = json_decode($fread[$i],true);
			if($arr[$i]['code'] == $code){
				unset($arr[$i]);
			}
		}
		$str = '';
		$myfile = fopen($file,'w') or die("文件不存在");
		foreach ($arr as $key=>$values){
			fwrite($myfile,json_encode($values)."\r\n");
		}
		fclose($myfile);
		if($uArr){
			$this->_tojson('1','修改密码成功');
		}else
			$this->_tojson("0",'修改密码失败');
		
		
	}
	public function modifypasswd(){
		$uid = $this->_values['username'];
		$pwd = $this->_values['user_pwd'];
		$npwd = $this->_values['new_pwd'];
		$this->load->model('User_model');
		$uArr = $this->User_model->modifypwd($uid,$pwd,$npwd);
		switch ($uArr){
			case 0:
				$this->_tojson("0",'修改密码失败');
				break;
			case 1:
				$this->_tojson('1','修改密码成功');
				break;
			case 2:
				$this->_tojson("2",'原密码不匹配');
		}
	}
	
	public function bonus(){
		$user_id = $this->_getId();
		$data[] = $user_id;
		$sql ="SELECT
					b.type_name,b.type_money,b.min_goods_amount,b.use_end_date,b.use_start_date,a.order_id
				FROM
					ecs_user_bonus AS a
				LEFT JOIN ecs_bonus_type AS b ON a.bonus_type_id = b.type_id
				WHERE
					a.user_id = ?";
		$result = $this->db->query($sql,$data);
		$result = $result->result_array();
		for($i=0;$i<count($result);$i++){
			if($result[$i]['use_start_date']<time() && $result[$i]['order_id']==0){
				$result[$i]['status'] = '未使用';
                $result[$i]['status_num'] = 0;
			}
			if($result[$i]['use_start_date']<time() && $result[$i]['order_id']!=0){
				$result[$i]['status'] = '已使用';
                $result[$i]['status_num'] = 1;
			}
			if($result[$i]['use_start_date']>time()){
				$result[$i]['status'] = '未开始';	
                $result[$i]['status_num'] = 2;		
			}
            if($result[$i]['use_end_date']<time() && $result[$i]['order_id'] !=0){
                $result[$i]['status'] = '已过期';
                $result[$i]['status_num'] = 3;
            }
		}
		if(empty($result)){
			$this->_tojson('0', '获取列表失败',array());
		}else{
			$this->_tojson('1', '获取列表成功',$result);
		}
	}
	/*
	 |-----------------------------------------------------
	 | 找详细产品
	 |-----------------------------------------------------
	 */
	public function similaritly(){
		$uid = $this->userid;
		$title = $this->values['title'];
		$this->load->model('User_model');
		$uArr = $this->User_model->similaritly($uid,$title);
	}
	//充值
	public function recharge(){		
		$uid = $this->_getId();
		$type = $this->_values['type'];
		if($uid==0){
			$this->_tojson('-220', '你处于未登录状态');
			exit();
		}
		$price = $this->_values['price'];
		$account = array(
				'user_id' => $uid,
				'amount' => $price,
				'add_time' => time(),
				'paid_time' => 0,
				'user_note' => '充值',
				'process_type' => '',
				'payment' => $type==1?'微信':'支付宝',
				'is_paid' => 0
		);
		$this->db->insert('user_account',$account);
		if($this->db->affected_rows()){
			$pay_id = $this->db->insert_id();
			$arr = array(
					'order_id' => $pay_id,
					'order_amount' => $price,
					'order_type' => 1,
					'is_paid' => 0
			);
			$this->db->insert('pay_log',$arr);
			$pay_log = $this->db->insert_id();
		}
		
		$para['partner'] = "\"".$this->config->item('partner')."\"";
		$para['seller_id'] = "\"".$this->config->item('seller_id')."\"";
		$para['out_trade_no'] ="\"".time().rand(10000,99999)."-".$uid."-".$price."\"";
		$para['subject'] = "\"用户充值\"";
		$para['body'] = "\"用户充值\"";
		$para['total_fee'] ="\"".$price."\"";
		$para['notify_url'] = "\"".$this->config->item('base_url')."index.php/user/recharback\"";
		$para['service']="\"mobile.securitypay.pay\"";
		$para['payment_type'] = "\"1\"";
		$para['_input_charset'] = "\"utf-8\"";
		$para['it_b_pay'] = "\"30m\"";
		$para['key'] = "\"".$this->config->item('key')."\"";
		//字符串编码
		$res_url = $this->createLinkstring($para);
		$path = APPPATH.'/libraries/Alipay/key/rsa_private_key.pem';
		$res_url1 = $this->rsaSign($res_url,$path);
		$partner = urlencode($res_url1);
		$partners = $res_url.'&sign='."\"$partner\"".'&sign_type='."\"RSA\"";
		$code = 1;
		$msg = "操作成功";
		$this->_tojson($code,$msg,$partners);
	}
	public function recharback(){
		//计算得出通知验证结果
		$uid = $this->_values['uid'];
		$price = $this->_values['price'];
		$alipay_config = array(
				'partner' => $this->config->item('partner'),
				'key' => $this->config->item('key'),
				'private_key_path' => APPPATH.'/libraries/Alipay/key/rsa_private_key.pem',
				'ali_public_key_path' => APPPATH.'/libraries/Alipay/key/alipay_public_key.pem',
				'sign_type' => 'RSA',
				'input_charset' => 'utf-8',
				'cacert' => APPPATH.'/libraries/Alipay/cacert.pem',
				'transport' => 'http'
		);
	//计算得出通知验证结果
	$alipayNotify = new AlipayNotify($alipay_config);
	$verify_result = $alipayNotify->verifyNotify();
	
	if($verify_result) {//验证成功
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//请在这里加上商户的业务逻辑程序代
	
		
		//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
		
	    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
		
		//商户订单号
	
		$out_trade_no = $_POST['out_trade_no'];
		//支付宝交易号
	
		$trade_no = $_POST['trade_no'];
	
		//交易状态
		$trade_status = $_POST['trade_status'];
	
	
	    if($_POST['trade_status'] == 'TRADE_FINISHED') {
			//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
					
			//注意：
			//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
			//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
	
	        //调试用，写文本函数记录程序运行情况是否正常
	        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
	    }
	    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
			//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
					
			//注意：
			//付款完成后，支付宝系统发送该交易状态通知
			//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
	
	        //调试用，写文本函数记录程序运行情况是否正常
	        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
	    $pay_log = explode('_',$out_trade_no);
	        $this->db->select('order_id');
	        $log = $this->db->get_where('pay_log',array('log_id'=>$pay_log['1']));
	        $log = $log->result_array();
	        
	        $this->db->select('user_id');
	        $user_id = $this->db->get_where('user_account',array('id'=>$log[0]['order_id']));
	        $user_id = $user_id->result_array();
	        
	        $log_info = array(
	        	'is_paid' => 1	
	        );
	        $this->db->where('log_id',$pay_log['1']);
	        $cc = $this->db->update('pay_log',$log_info);
	        
	        if($this->db->affected_rows()){
	        	$int = 0;
	        }else{
	        	$int++;
	        }
	        $account = array('is_paid'=>1);
	        $this->db->where('id',$log[0]['order_id']);
	        $aa = $this->db->update('user_account',$account);
	        if($this->db->affected_rows()){
	        	$int = 0;
	        }else{
	        	$int++;
	        }
	        
	        $price = $_POST['total_fee'];
// 	        $price = 10000;
	        //查询余额
	        $this->db->select('user_money');
	        $use_money = $this->db->get_where('users',array('user_id'=>$user_id[0]['user_id']));
	        $use_money = $use_money->result_array();
	        $data = array(
	        		'user_money'=>$price+$use_money[0]['user_money']
	        );
	        $this->db->where('user_id',$user_id[0]['user_id']);
	        $dd = $this->db->update('users',$data);

	        if($this->db->affected_rows()){
	        	$int = 0;
	        }else{
	        	$int++;
	        }
	        if($int !=0){
	        	echo 'fail';
	        }
	    }
	
		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	        
		echo "success";		//请不要修改或删除
		
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	}
	else {
	    //验证失败
	    echo "fail";
	
	    //调试用，写文本函数记录程序运行情况是否正常
	    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
	}
	}
	
	protected function createLinkstring($para){
		$arg = '';
		while(list($key,$val) = each($para)){
			$arg.=$key."=".$val."&";
		}
		$arg = substr($arg,0,count($arg)-2);
		if(get_magic_quotes_gpc()){
			$arg = stripslashes($arg);
		}
		return $arg;
	}

	/**
	 * RSA签名
	 * @param $data 待签名数据
	 * @param $private_key_path 商户私钥文件路径
	 * return 签名结果
	 */
	protected function rsaSign($data, $private_key_path){
		$priKey = file_get_contents($private_key_path);
		$res = openssl_get_privatekey($priKey);
		openssl_sign($data, $sign, $res);
		openssl_free_key($res);
		//base64编码
		$sign = base64_encode($sign);
		return $sign;
	}
	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	protected function createLinkstringUrlencode($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".urlencode($val)."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
	
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
		return $arg;
	}
	//判断用户性别
	private function _sex($num){
		switch ($num){
			case 1:
				return "男";
				break;
			case 2:
				return "女";
				break;
			case 0:
				return "保密";
		}
	}
	//订单状态
	private function order_s($num){
		switch ($num){
			case 0:
				return "未确认";
				break;
			case 1:
				return "已确认";
				break;
		}
	}
	private function pay($num){
		switch ($num){
			case 0:
				return "未付款";
				break;
			case 1:
				return "付款中";
				break;
			case 2:
				return "已付款";
				break;
		}
	}
	private function shipping($num){
		switch ($num){
			case 0:
				return "未发货";
				break;
			case 1:
				return "已发货";
				break;
			case 2:
				return "已收货";
				break;
			case 3:
				return "已配货";
				break;
		}
	}

	//更换头像
	public function changeheadimg(){
        define('IN_ECS', true);
        define('InAiteSoft',true);
        define('DS','/');
        define('BASE_UPLOAD_PATH',dirname(dirname(dirname(dirname(__FILE__)))));

//        ini_set('display_errors',1);            //错误信息
//        ini_set('display_startup_errors',1);    //php启动错误信息
//        error_reporting(-1);

        $user_id = $this->_getId();
        if($user_id==0){
            $this->_tojson('-220', '你处于未登录状态',array());
            exit;
        }

        $WAP_PATH =dirname(dirname(__FILE__));
        $DATA_HEADIMG = 'data/headimg'.'/'.date('Ym').'/'.date('d');

        $res = array('error'=>0,'msg'=>'','info'=>'');

        include_once ($WAP_PATH.'/libraries/uploadfile.php');
        $upload = new UploadFile();
        $name = 'headimg';
//        print_r($this->_values);
//        echo '/n';
//        print_r($_FILES);
//        echo '/n';
        if (!empty($_FILES[$name]['name'])) {

            $upload->set('default_dir', $DATA_HEADIMG);
            $upload->set('thumb_ext', '');
            $upload->set('file_name', '');
            $upload->set('ifremove', false);
            $result = $upload->upfile($name);
            if ($result) {
                $headimg_thumb =  $DATA_HEADIMG . '/' . $upload->file_name;
            } else {
                $this->_tojson(64002,'上传失败！',$info);
            }
        }else{
            $this->_tojson(64003,'上传失败！',$info);
        }
//        echo $headimg_thumb;


        $this->load->model('User_model');
        $res = $this->User_model->updateHeadImg($user_id,$headimg_thumb);
        if($res){
            $info['headimg_url'] = $res;
            $this->_tojson(1,'更改头像成功',$info);
        }else{
            $this->_tojson(64001,'上传失败','');
        }
    }

    //申请分销商
    public function userfenxiao(){
        $user_id = $this->_getId();
        if($user_id==0){
            $this->_tojson('-220', '你处于未登录状态',array());
            exit;
        }

        $this->load->model('User_model');
        $res = $this->User_model->userfenxiao($user_id);
        if($res){
            $this->_tojson(1,'申请成功，请耐心等待平台审核！','');
        }else{
            $this->_tojson(65001,'申请失败，请稍候再试！','');
        }
    }
}


/*
 * 上传头像使用函数
 * */

function make_dir($folder)
{
    $reval = false;

    if (!file_exists($folder))
    {
        /* 如果目录不存在则尝试创建该目录 */
        @umask(0);

        /* 将目录路径拆分成数组 */
        preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

        /* 如果第一个字符为/则当作物理路径处理 */
        $base = ($atmp[0][0] == '/') ? '/' : '';

        /* 遍历包含路径信息的数组 */
        foreach ($atmp[1] AS $val)
        {
            if ('' != $val)
            {
                $base .= $val;

                if ('..' == $val || '.' == $val)
                {
                    /* 如果目录为.或者..则直接补/继续下一个循环 */
                    $base .= '/';

                    continue;
                }
            }
            else
            {
                continue;
            }

            $base .= '/';

            if (@!file_exists($base))
            {
                /* 尝试创建目录，如果创建失败则继续循环 */
                if (@mkdir(rtrim($base, '/'), 0777))
                {
                    @chmod($base, 0777);
                    $reval = true;
                }
            }
        }
    }
    else
    {
        /* 路径已经存在。返回该路径是不是一个目录 */
        $reval = is_dir($folder);
    }

    clearstatcache();

    return $reval;
}

function gmtime()
{
    return (time() - date('Z'));
}

/**
 * 检查文件类型
 *
 * @access      public
 * @param       string      filename            文件名
 * @param       string      realname            真实文件名
 * @param       string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '')
{
    if ($realname)
    {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    }
    else
    {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false)
    {
        return '';
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file)
    {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    }
    else
    {
        if (stristr($filename, ROOT_PATH) === false)
        {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                $extname == 'xls' || $extname == 'txt'  || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                $extname == 'pdf' || $extname == 'rm'   || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                $extname == 'swf' || $extname == 'chm'  || $extname == 'sql' || $extname == 'cert'|| $extname == 'pptx' ||
                $extname == 'xlsx' || $extname == 'docx')
            {
                $format = $extname;
            }
        }
        else
        {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2 )
    {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt')
        {
            $format = 'mid';
        }
        elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav')
        {
            $format = 'wav';
        }
        elseif (substr($str ,0, 3) == "\xFF\xD8\xFF")
        {
            $format = 'jpg';
        }
        elseif (substr($str ,0, 4) == 'GIF8' && $extname != 'txt')
        {
            $format = 'gif';
        }
        elseif (substr($str ,0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")
        {
            $format = 'png';
        }
        elseif (substr($str ,0, 2) == 'BM' && $extname != 'txt')
        {
            $format = 'bmp';
        }
        elseif ((substr($str ,0, 3) == 'CWS' || substr($str ,0, 3) == 'FWS') && $extname != 'txt')
        {
            $format = 'swf';
        }
        elseif (substr($str ,0, 4) == "\xD0\xCF\x11\xE0")
        {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'doc')
            {
                $format = 'doc';
            }
            elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xls')
            {
                $format = 'xls';
            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt')
            {
                $format = 'ppt';
            }
        } elseif (substr($str ,0, 4) == "PK\x03\x04")
        {
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'docx')
            {
                $format = 'docx';
            }
            elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xlsx')
            {
                $format = 'xlsx';
            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx')
            {
                $format = 'pptx';
            }else
            {
                $format = 'zip';
            }
        } elseif (substr($str ,0, 4) == 'Rar!' && $extname != 'txt')
        {
            $format = 'rar';
        } elseif (substr($str ,0, 4) == "\x25PDF")
        {
            $format = 'pdf';
        } elseif (substr($str ,0, 3) == "\x30\x82\x0A")
        {
            $format = 'cert';
        } elseif (substr($str ,0, 4) == 'ITSF' && $extname != 'txt')
        {
            $format = 'chm';
        } elseif (substr($str ,0, 4) == "\x2ERMF")
        {
            $format = 'rm';
        } elseif ($extname == 'sql')
        {
            $format = 'sql';
        } elseif ($extname == 'txt')
        {
            $format = 'txt';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false)
    {
        $format = '';
    }

    return $format;
}


/**
 * 将上传文件转移到指定位置
 *
 * @param string $file_name
 * @param string $target_name
 * @return blog
 */
function move_upload_file($file_name, $target_name = '')
{
    if (function_exists("move_uploaded_file"))
    {
        if (move_uploaded_file($file_name, $target_name))
        {
            @chmod($target_name,0755);
            return true;
        }
        else if (copy($file_name, $target_name))
        {
            @chmod($target_name,0755);
            return true;
        }
    }
    elseif (copy($file_name, $target_name))
    {
        @chmod($target_name,0755);
        return true;
    }
    return false;
}

