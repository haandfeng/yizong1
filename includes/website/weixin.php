<?php 



if (defined('WEBSITE'))
{
	
	global $_LANG;
	$_LANG['help']['APP_KEY'] = '微信应用的AppID';
	$_LANG['help']['APP_SECRET'] = '微信应用的AppSecret';
	
	$_LANG['APP_KEY'] = 'AppID';
	$_LANG['APP_SECRET'] = 'AppSecret';
	
	$i = isset($web) ? count($web) : 0;
	// 类名
	$web[$i]['name'] = '微信';
	
	// 文件名，不包含后缀
	
	$web[$i]['type'] = 'weixin';
	
	$web[$i]['className'] = 'weixin';
	
	// 作者信息
	$web[$i]['author'] = 'yshop100';
	
	// 作者QQ
	$web[$i]['qq'] = 'yshop100';
	
	// 作者邮箱
	$web[$i]['email'] = 'yshop100@yshop100.com';
	
	// 申请网址
	$web[$i]['website'] = 'http://open.weixin.qq.com';
	
	// 版本号
	$web[$i]['version'] = '1.0v';
	
	// 更新日期
	$web[$i]['date']  = '2015-5-1';
	
	// 配置信息
	$web[$i]['config'] = array(
		array('type'=>'text' , 'name'=>'APP_KEY', 'value'=>''),
		array('type'=>'text' , 'name' => 'APP_SECRET' , 'value' => ''),
	);
}

if (!defined('WEBSITE'))
{
	include 'oath2.class.php';
	class website extends oath2
	{
		var $token;
		var $error_msg;
		var $parameter = array();
		
		function __construct(){
			$this->website();
		}
		
		function website()
		{
            $state = md5(time()+rand(0,9999));
            $_SESSION['weixin_state'] = $state;
            
            $this->authorizeURL = 'https://open.weixin.qq.com/connect/qrconnect';
            $this->app_key = APP_KEY;
            $this->app_secret = APP_SECRET;
			$this->parameter = array(
                'response_type' => 'code',
                'scope' => 'snsapi_login',
                'state' => $state,
			);
		}
		
		function login($redirect_uri='')
		{
            $url = $this->authorizeURL.'?';
            $url .= 'appid='.$this->app_key;
            if(!empty($redirect_uri))
            {
                $this->parameter['redirect_uri'] = $redirect_uri;
            }var_dump($this->parameter);exit;
            $url .= '&'.http_build_query($this->parameter);
            return $url;
		}
		
		function getAccessToken()
		{
            $code = empty($_REQUEST['code'])?'':$_REQUEST['code'];
            $url = sprintf('https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code',$this->app_key,$this->app_secret,$code);
            $result = $this->http($url);
            $result_arr = json_decode($result,true);
            $token = $result_arr['access_token'];
            $refresh = $result_arr['refresh_token'];
            $openid = $result_arr['openid'];
            $_SESSION['openid'] = $openid;
            return $token;
		}
		
		function setAccessToken($token)
		{
			$this->token = $token;
			return true;
		}
        
        function getMessage()
		{
            $url = sprintf('https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s',$this->token,$_SESSION['openid']);
            $result = $this->http($url);
            $result_arr = json_decode($result,true);
			$ret = array();
			$ret['name'] = $result_arr['nickname'];
			$ret['sex'] = $result_arr['sex'];
			$ret['user_id'] = $result_arr['openid'];
			$ret['img'] = $result_arr['headimgurl'];
			
			return $ret;
		}
         function get_error()
        {
            return '未知错误';
        }
	}
}