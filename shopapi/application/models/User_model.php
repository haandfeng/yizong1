<?php defined('BASEPATH') OR exit('No direct script access allowed');
class User_model extends MY_Model{
	public function login($user,$passwd){
		$this->db->select('ec_salt,visit_count');
		//return $user;
		if(preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $user)){
			$user_type = 'email';
		}elseif(preg_match("/^([6|9])\d{7}$/i", $user) || preg_match("/^[1][3-8]\d{9}$|^([6|9])\d{7}$|^[6]([8|6])\d{5}$/i", $user)){
			$user_type = 'mobile_phone';
		}else{
			$user_type = 'user_name';
		}
		$ec_salt = $this->db->get_where('users',array($user_type=>$user));
		$ec_salta = $ec_salt->result_array();

		$data[] = $user;

		if($ec_salta[0]['visit_count'] == 0 ||$ec_salta[0]['visit_count'] == 1){
			$data[] = md5($passwd);
			$sql = 'select user_id,user_name,email from '.$this->db->dbprefix.'users where '.$user_type.'=? and password=?';
			$result = $this->db->query($sql,$data);

			if(!$result->num_rows()){
				return 2;
				exit();
			}
			$entry = $this->config->item("key");
			$time = time();
			$key = md5($data[0].$data[1].$entry.$time);
			$user_id = $result->result_array();


			$_datab = array(
				'sesskey' =>$key,
				'expiry' =>time(),
				'userid' =>$user_id[0]['user_id'],
				'ip' =>$_SERVER["REMOTE_ADDR"],
				'user_name' =>$user_id[0]['user_name'],
				'email' =>$user_id[0]['email'],
				'data' =>$key
			);
			$this->db->insert("sessions",$_datab);

			if($this->db->affected_rows()){
				$uArr = array(
					'key' =>$key,
					'userid' =>$user_id[0]['user_id'],
					'data' =>$key
				);
				return $uArr;
				exit();
			}else{
				return 0;
				exit();
			}
		}
		else{
			if(empty($ec_salta[0]['ec_salt'])){
				$data[] = md5($passwd);
			}else{
				$data[] = md5(md5($passwd).$ec_salta[0]['ec_salt']);
			}
				$sql = 'select user_id,user_name,email from '.$this->db->dbprefix.'users where '.$user_type.'=? and password=?';

			$result = $this->db->query($sql,$data);
			if(!$result->num_rows()){
				return 2;
				exit();
			}
			$entry = $this->config->item("key");
			$time = time();
			$key = md5($data[0].$data[1].$entry.$time);
			$user_id = $result->result_array();
			$_datab = array(
				'sesskey' =>$key,
				'expiry' =>time(),
				'userid' =>$user_id[0]['user_id'],
				'ip' =>$_SERVER["REMOTE_ADDR"],
				'user_name' =>$user_id[0]['user_name'],
				'email' =>$user_id[0]['email'],
				'data' =>$key
			);
			$this->db->insert("sessions",$_datab);
			if($this->db->affected_rows()){
				$uArr = array(
					'key' =>$key,
					'userid' =>$user_id[0]['user_id'],
					'data' =>$key
				);
				return $uArr;
				exit();
			}else{
				return 0;
				exit();
			}
		}
	}

    public function bang_name($user,$password)
    {
        if(preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $user)){
            $user_type = 'email';
        }elseif(preg_match("/^([6|9])\d{7}$/i", $user) || preg_match("/^[1][3-8]\d{9}$|^([6|9])\d{7}$|^[6]([8|6])\d{5}$/i", $user)){
            $user_type = 'mobile_phone';
        }else{
            $user_type = 'user_name';
        }

        $sql = "select ec_salt from ecs_users where $user_type=?";
        $result = $this->db->query($sql,$user);
        $res = $result->result_array();

        if($res[0]['ec_salt'] != '')
        {
            $password = md5(md5($password).$res[0]['ec_salt']);
        }else{
            $password = md5($password);
        }

        if(!empty($res))
        {
            return $password;
        }else{
            $array =array('start'=>'0','msg'=>'没有该账号');
            return $array;
        }
    }

    public function seek_user($user_name)
    {
        $data[] = $user_name;

        $sql = 'select * from ecs_users where  user_name=?';
        $res = $this->db->query($sql,$data);
        $res = $res->result_array();
        if(!empty($res))
        {
            //注册了
            return false;
        }else{
            return true;
        }

    }

    public function int_name($info_user_id,$user_name,$password)
    {
        $data[] = $info_user_id;
        $data[] = $user_name;
        $ec_salt = '';
        for($i=0;$i<4;$i++)
        {
            $ec_salt .= rand(0,9);
        }
        $password = md5(md5($password).$ec_salt);
        $data[] = $password;
        $data[] = $ec_salt;
        $data[] = time();

        $sql = "INSERT INTO ecs_users (`aite_id`,`user_name`,`password`,`ec_salt`,`reg_time`) VALUES(?,?,?,?,?)";
        if($this->db->query($sql,$data))
        {
            $array = array('start'=>'1','msg'=>'注册成功');
            return $array;
        }else{
            $array =array('start'=>'0','msg'=>'注册失败');
            return json_encode($array);
        }
    }


    public function bang_aite($info_user_id,$user,$password)
    {
        $data[] = $info_user_id;
        $data[] = $user;
        $data[] = $password;

        if(preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $user)){
            $user_type = 'email';
        }elseif(preg_match("/^([6|9])\d{7}$/i", $user) || preg_match("/^[1][3-8]\d{9}$|^([6|9])\d{7}$|^[6]([8|6])\d{5}$/i", $user)){
            $user_type = 'mobile_phone';
        }else{
            $user_type = 'user_name';
        }

        $sql1 = "UPDATE ecs_users SET `aite_id`=? WHERE $user_type =? and password=?";

        if($this->db->query($sql1,$data))
        {
            $array = array('start'=>'1','msg'=>'绑定成功');
            return $array;
        }else
        {
            $array = array('start'=>'0','msg'=>'用户密码不对');
            return json_encode($array);
        }
    }

    //登录
    public function slogin($user,$password)
    {
        $data[] = $user;
        $data[] = $password;
        $user_type = 'user_name';
        $sql = 'select user_id,user_name,email from '.$this->db->dbprefix.'users where '.$user_type.'=? and password=?';
        $result = $this->db->query($sql,$data);
        $row = $result->row_array();
        
        if(!$result->num_rows()){
            return 2;
            exit();
        }
        $entry = $this->config->item("key");
        $time = time();
        $key = md5($data[0].$data[1].$entry.$time);
        $user_id = $result->result_array();


        $_datab = array(
            'sesskey' =>$key,
            'expiry' =>time(),
            'userid' =>$user_id[0]['user_id'],
            'ip' =>$_SERVER["REMOTE_ADDR"],
            'user_name' =>$user_id[0]['user_name'],
            'email' =>$user_id[0]['email'],
            'data' =>$key
        );
        $this->db->insert("sessions",$_datab);

        if($this->db->affected_rows()){
            $uArr = array(
                'key' =>$key,
                'userid' =>$user_id[0]['user_id'],
                'data' =>$key
            );
            return $uArr;
            exit();
        }else{
            return 0;
            exit();
        }
    }

	//注销登录
	public function logout($key){
		$this->db->where('sesskey',$key);
		$this->db->delete('sessions');
		if($this->db->affected_rows()){
			return 1;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	/** 注册
	 * @param string $username [手机或邮箱]
	 * @param string $pwd [密码]
	 * @param string $type [类型] email OR mobile_phone
	 * @param string $code [验证码]
	 * @param string $invite [邀请码]
	 * 2017-09-23 余某人 修改
	 */
	public function register($username,$pwd,$type,$code,$invite){

		if('email' != $type && 'mobile_phone' != $type){//注册类型错误
			return 1001;
			exit;
		}

		if(empty($pwd)){
			return 1006;
			exit;
		}

		if($this->is_email_or_phone_existence($username,$type)){//查看邮箱或者手机是否已经注册
			return $type == 'email' ? 1011 : 1021 ;
			exit();
		}

		$time = time();
		if('' == $code){
			return 1005;
		}

		if($type == 'email'){
			// 获取数据库中的验证记录
			$record = $this->get_validate_record($username);
			if($record['record_code'] != $code){
				return 1012;
			}
		}else{
			// 获取数据库中的验证记录
			$record = $this->get_validate_record($username);
			if($record['record_code'] != $code){//验证码是否正确
				return 1022;
			}elseif($record['expired_time'] < $time){//是否超时
				return 1023;
			}
		}


		if($invite){
			/* 检测 邀请码 是否有效 */
			$sql = "SELECT user_id FROM ".$this->db->dbprefix."users WHERE invite_code = ? AND invite_code != ''";
			$result = $this->db->query($sql,$invite);
			$row = $result->row_array();
			//		return $row['user_id'];
			if($invite != '' && $row == ''){
				return 1002;
				exit();
			}

		}else{
			$row['user_id'] = '';
		}

		$nick_name = $this->username_random();//生成用户名

		$_dataa = array(
			$type =>$username,
			'username' => $nick_name,
			'ec_salt'=>null,
			'password' =>md5($pwd),
			'visit_count'=>1,
			'reg_time '=>$time,
		);

		$sql = 'insert into '.$this->db->dbprefix.'users(`' . $type . '`,`user_name`,`ec_salt`,`password`,`visit_count`,`reg_time`) values(?,?,?,?,?,?)';
		$this->db->query($sql,$_dataa);
		$user_id = $this->db->insert_id();
		$entry = $this->config->item("key");
		$key = md5($username.md5($pwd).$entry.$time);

		$this->created_invite_qrcode($invite,$user_id,(int)$row['user_id']);

		$_datab = array(
			'sesskey' =>$key,
			'expiry' =>time(),
			'userid' =>$user_id,
			'ip' =>$_SERVER["REMOTE_ADDR"],
			$type =>$username,
			'user_name' =>$nick_name,
			'data' =>$key
		);
		$this->db->insert("sessions",$_datab);
		if($this->db->affected_rows()){
			$sql = "DELETE FROM ".$this->db->dbprefix."validate_record where record_key < ? ";
			$this->db->query($sql,$username);
			return $key;
			exit();
		}else{
			return 1003;
			exit();
		}
	}

	public function is_email_or_phone_existence($username,$type){
		$sql = 'select '. $type .' from '.$this->db->dbprefix.'users where `' . $type . '` = ?';
		$result = $this->db->query($sql,$username);

		if($result->num_rows()){
			return true;
		}else{
			return false;
		}
	}



	public function register_bak($username,$pwd,$email,$invite){
		//先判断用户是否已存在
		$sql = 'select user_name from '.$this->db->dbprefix.'users where user_name = ?';
		$result = $this->db->query($sql,$username);
        print_R($result);die();
		if($result->num_rows()){
			return 0;
			exit();
		}
		$sql = 'select email from '.$this->db->dbprefix.'users where email = ?';
		$result = $this->db->query($sql,$email);
		if($result->num_rows()){
			return 3;
			exit();
		}


		/* 检测 邀请码 是否有效 */
		$sql = "SELECT user_id FROM ".$this->db->dbprefix."users WHERE invite_code = ? AND invite_code != ''";
		$result = $this->db->query($sql,$invite);
		$row = $result->row_array();
//		return $row['user_id'];
		if($invite != '' && $row == ''){
			return 5;
			exit();
		}

		$_dataa = array(
			'email' =>$email,
			'user_name' =>$username,
			'ec_salt'=>null,
			'password' =>md5($pwd),
			'visit_count'=>1
		);
		$sql = 'insert into '.$this->db->dbprefix.'users(`email`,`user_name`,`ec_salt`,`password`,`visit_count`) values(?,?,?,?,?)';
		$this->db->query($sql,$_dataa);
		$user_id = $this->db->insert_id();
		$entry = $this->config->item("key");
		$time = time();
		$key = md5($username.md5($pwd).$entry.$time);

		$this->created_invite_qrcode($invite,$user_id,(int)$row['user_id']);

		$_datab = array(
			'sesskey' =>$key,
			'expiry' =>time(),
			'userid' =>$user_id,
			'ip' =>$_SERVER["REMOTE_ADDR"],
			'user_name' =>$username,
			'email' =>$email,
			'data' =>$key
		);
		$this->db->insert("sessions",$_datab);
		if($this->db->affected_rows()){
			return $key;
			exit();
		}else{
			return 2;
			exit();
		}
	}


    public function resister_name($union)
    {
        $sql1 = 'select user_name,password from '.$this->db->dbprefix.'users  where aite_id= ?';
        $res = $this->db->query($sql1,$union);
        $re = $res->result_array();
        if(!empty($re))
        {
            return $re;
        }else{
            $array = array('start'=>'0','msg'=>'账号没有注册');
            return json_encode($array);
        }
    }


	//订单
	public function order($userid){
		//获取订单id
		$this->db->select('order_id,order_status,shipping_status,pay_status,order_sn,money_paid');
		$this->db->order_by("order_id", "desc");
		$query = $this->db->get_where('order_info',array('user_id'=>$userid));
		$uArr = $query->result_array();
// 		print_r($uArr);
		for($i=0;$i<count($uArr);$i++){
			$sql = 'SELECT
					b.goods_name,
					b.goods_price,
					c.goods_thumb,
					b.goods_number
				FROM
					'.$this->db->dbprefix.'order_info AS a
				LEFT JOIN '.$this->db->dbprefix.'order_goods AS b ON a.order_id = b.order_id
				LEFT JOIN '.$this->db->dbprefix.'goods AS c ON b.goods_id = c.goods_id
				WHERE
					a.order_id = ? order by a.add_time desc';
			$result = $this->db->query($sql,$uArr[$i]['order_id']);
			$nArr = $result->result_array();
			for($j=0;$j<count($nArr);$j++){
				$total += ($nArr[$j]['goods_price']*$nArr[$j]['goods_number']);
			}
			$status = $uArr[$i]['order_status'].'-'.$uArr[$i]['pay_status'].'-'.$uArr[$i]['shipping_status'];
			$mArr[]= array('goods'=>$nArr,'total'=>$uArr[$i]['money_paid'],'order_sn'=>$uArr[$i]['order_sn'],'order_id'=>$uArr[$i]['order_id'],'status'=>$this->order_status($status));

		}
		if(!empty($nArr)){
			return $mArr;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	//用户信息
	public function userinfo($userid,$moreinfo){
		$field = '';
		$sql = 'SELECT
				a.user_name AS nick_name,
				a.sex,
				a.user_rank,
				a.headimg,
				a.pay_points AS integration,
				b.address,
				b.mobile,
				a.is_fenxiao,
				a.status,
				a.email,
				a.user_money,
				a.birthday,
				sum(d.goods_amount) as consume
				FROM
					ecs_users AS a
				LEFT JOIN '.$this->db->dbprefix.'user_address AS b ON a.user_id = b.user_id
				left JOIN '.$this->db->dbprefix.'order_info as d on a.user_id = d.user_id
				WHERE
					a.user_id = ? 
				';
		$result = $this->db->query($sql,$userid);
		if($result->num_rows()){
			$sss = $result->result_array();
			foreach ($sss as $k=>$s){
				$uaaa = $s;
			}
			$user_rank = intval($uaaa['user_rank']);
			$rank_sql = "select rank_name from " . $this->db->dbprefix . "user_rank where rank_id = '$user_rank'";
			$rank_atten = $this->db->query($rank_sql);
			$rank_atresult = $rank_atten->result_array();
			$real_user_rank = $rank_atresult[0]['rank_name'];
			if(empty($real_user_rank)){
				$real_user_rank='普通会员';
			}
			$uaaa['rank_name'] = $real_user_rank;
			$headimgurl = $uaaa['headimg'];
			if(empty($headimgurl)){
				$wx_sql = "select headimgurl from " . $this->db->dbprefix . "weixin_user where ecuid = '$userid'";
				$wx_atten = $this->db->query($wx_sql);
				$wx_atresult = $wx_atten->result_array();
				$headimgurl = $wx_atresult[0]['headimgurl'];
			}else{
				if(strpos($headimgurl,'http://') === false){
					$headimgurl = $this->config->item('ecs_shop').$headimgurl;
				}

			}
			$uaaa['headimg'] = $headimgurl;

			//获取关注信息
			$guanzhu = "select count(*) as a from ".$this->db->dbprefix."collect_goods where user_id = ? and is_attention=1";
			$atten = $this->db->query($guanzhu,$userid);
			$atresult = $atten->result_array();
			$uaaa['attention'] = $atresult[0]['a'];
			//获取购物车产品数量
			$sql = "select sum(goods_number) as count from ".$this->db->dbprefix."cart where user_id = ?";
			$res = $this->db->query($sql,$userid);
			$cc = $res->result_array();
			$uaaa['cart_num'] = empty($cc[0]['count'])?0:$cc[0]['count'];
			//待付款
			$this->db->select('order_id');
			$query = $this->db->where(array('order_status'=>0,'pay_status'=>0,'user_id'=>$userid))
				->get('order_info');
			$uArr = $query->result_array();
			$uaaa['pay'] =empty($uArr)?0:count($uArr);

			//待收货
			$this->db->select('order_id');
			$query1 = $this->db->get_where('order_info',array('shipping_status'=>1,'user_id'=>$userid));
			$uArr1 = $query1->result_array();
			$uaaa['shipping'] =empty($uArr1)?0:count($uArr1);

			//待发货
			$query1=$this->db->select('order_id,order_status,shipping_status,pay_status,goods_amount')
				->group_start()
				->where(array('order_status'=>1,'pay_status'=>2,'shipping_status'=>0))
				->group_end()
				->where('user_id',$userid)
				->get('order_info');
			$uArr1 = $query1->result_array();
			$uaaa['shipping_send'] =empty($uArr1)?0:count($uArr1);

			//已完成
			$this->db->select('order_id');
			$query1 = $this->db->get_where('order_info',array('pay_status'=>2,'shipping_status'=>2,'order_status'=>5,'user_id'=>$userid));
			$uArr1 = $query1->result_array();
			$uaaa['end_count'] =empty($uArr1)?0:count($uArr1);
			return $uaaa;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	public function modifyUser($userid,$sex,$birthday){
		$str = '';
		if($sex==''){
			$str .= "birthday = $birthday";
		}
		if($birthday == ''){
			$str .= "sex = $sex";
		}
		else{
			$str = " sex = $sex,birthday = $birthday ";
		}
		$sql = "UPDATE ecs_users SET $str WHERE user_id=$userid";
		if($this->db->query($sql)){
			return 1;
		}else{
			return 0;
		}
	}

	public function modifyUserToken($userid,$token){
		$sql = "UPDATE ecs_users SET accesstoken = '$token' WHERE user_id=$userid";
		if($this->db->query($sql)){
			return 1;
		}else{
			return 0;
		}
	}

	public function rpass($passwd,$username){

		$this->db->select('ec_salt');
		$ec_salt = $this->db->get_where('users',array('user_name'=>$username));
		$ec_salta = $ec_salt->result_array();

		$data[] = md5(md5($passwd).$ec_salta[0]['ec_salt']);
		$data[] = $username;
		$sql = 'UPDATE '.$this->db->dbprefix.'users SET `password`=? where `user_name`=?';
		$result = $this->db->query($sql,$data);
		if($this->db->affected_rows()){
			return 1;
			exit();
		}else{
			return 0;
			exit();
		}
	}
	public function modifypwd($uid,$pwd,$npwd){
		$this->db->select('ec_salt,visit_count');
		$ec_salt = $this->db->get_where('users',array('user_name'=>$uid));
		$ec_salta = $ec_salt->result_array();
		//旧密码
		$data[] = $uid;
		$data[] = md5($pwd);
		//旧密码
		$dataa[] = $uid;
		$dataa[] = md5(md5($pwd).$ec_salta[0]['ec_salt']);
		//新密码
		$_data[] = md5($npwd);
		$_data[] = $uid;
		//新密码
		$_datac[] = md5(md5($npwd).$ec_salta[0]['ec_salt']);
		$_datac[] = $uid;
		//先判断用户的原密码是否正确
		if($ec_salta[0]['visit_count'] == 0 ||$ec_salta[0]['visit_count'] == 1 ){
			$sql = 'select * from '.$this->db->dbprefix.'users where user_name=? and password=?';
			$result = $this->db->query($sql,$data);
			if(!$result->num_rows()){
				return 2;
				exit();
			}

			$sql = 'update '.$this->db->dbprefix.'users SET `password`=? where `user_name`=?';
			$result = $this->db->query($sql,$_data);
			if($this->db->affected_rows()){
				return 1;
				exit();
			}else{
				return 0;
				exit();
			}
		}else{
			$sql = 'select * from '.$this->db->dbprefix.'users where user_name=? and password=?';
			$result = $this->db->query($sql,$dataa);
			if(!$result->num_rows()){
				return 2;
				exit();
			}

			$sql = 'update '.$this->db->dbprefix.'users SET `password`=? where `user_name`=?';
			$result = $this->db->query($sql,$_datac);
			if($this->db->affected_rows()){
				return 1;
				exit();
			}else{
				return 0;
				exit();
			}
		}
	}
	public function similaritly($uid,$title){

	}

	//更换头像
	public function updateHeadImg($user_id,$headimg_thumb){
		$ec_salt = $this->db->get_where('users',array('user_id'=>$user_id));
		$ec_salta = $ec_salt->result_array();

		if(!empty($ec_salta[0]['headimg']) && !strpos($headimgurl,'http://')){
			unlink(BASE_UPLOAD_PATH.'/'.$ec_salta[0]['headimg']);
		}

		$data[] = $headimg_thumb;
		$data[] = $user_id;
		$sql = 'UPDATE '.$this->db->dbprefix.'users SET `headimg`=? where `user_id`=?';
		$result = $this->db->query($sql,$data);
//		echo $this->db->last_query();

		if($this->db->affected_rows()){
			return $this->config->item('ecs_shop').$headimg_thumb;
			exit();
		}else{
			return 0;
			exit();
		}
	}
//申请分销商
	public function userfenxiao($user_id){
		$data[] = $user_id;
		$sql = 'UPDATE '.$this->db->dbprefix.'users SET `is_fenxiao`="1" AND `status`="2" where `user_id`=?';
		$result = $this->db->query($sql,$data);
		if($this->db->affected_rows()){
			return 1;
			exit();
		}else{
			return 0;
			exit();
		}
	}

	private function created_invite_qrcode($invite,$user_id,$parent_id)
	{
//    $uid = $_SESSION['user_id'];

		$root_path = $_SERVER['DOCUMENT_ROOT'].'/';
		//每一个新注册的会员都会生成一串6位的邀请码
		$invite_code_self = $this->invite_code_random(); //每一个新注册的会员都会生成一串6位的邀请码
//		return $invite_code_self;
//		exit;
		/* 生成邀请二维码 及 邀请码 */
		require($root_path.'includes/phpqrcode.php');
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 6;//生成图片大小
		if(!file_exists($_SERVER['DOCUMENT_ROOT'].'images/qrimage/'.date('Ym'))){
			mkdir($root_path.'images/qrimage/'.'/'.date('Ym'), 0777);
		}
		$filename = $root_path.'images/qrimage/'.date('Ym').'/'.$user_id.'.png';
		$file = date('Ym').'/'.$user_id.'.png';
		$data = 'http://'.$_SERVER['HTTP_HOST'].'/mobile/register.php?id='.$user_id.'|'.$invite_code_self;
		//生成二维码图片
		QRcode::png($data,$filename, $errorCorrectionLevel, $matrixPointSize, 2);

		$data = '';
		$data = array($invite_code_self,$file,$invite,$user_id);
		//更新 注册会员的 邀请码 及 二维码 (填写邀请码之后 查询 邀请码所对应的 会员id)
		$sql = 'UPDATE ' . $this->db->dbprefix . 'users SET `invite_code` = ?,`invite_qrcode` = ?,`invite_parent` = ? WHERE user_id = ?';
		$this->db->query($sql,$data);

		$sql = "SELECT `value` AS is_apply_distrib FROM ".$this->db->dbprefix."ecsmart_shop_config WHERE code = 'is_apply_distrib'";
		$result = $this->db->query($sql);
		$row = $result->row_array();

		//不需要申请 就可以成为 分销商
		$data = '';
		$data = array($parent_id,$user_id);
		if($row['is_apply_distrib'] == 0) {
			$sql = 'UPDATE ' . $this->db->dbprefix . 'users SET `parent_id` = ?,`status` = 1,`is_fenxiao` = 1 WHERE user_id = ?';
		}else{
			$sql = 'UPDATE ' . $this->db->dbprefix . 'users SET `parent_id` = ? WHERE user_id = ?';
		}
		$this->db->query($sql,$data);
	}

	/* 防止重复 */
	private function invite_code_random(){
		$invite_code_self = $this->random(6);
		$sql = "SELECT COUNT(*) as count FROM " . $this->db->dbprefix . "users WHERE invite_code = ?";
		$result = $this->db->query($sql,$invite_code_self);
		$result = $result->num_rows();
		if($result->count != ''){
			return $this->invite_code_random();
		}
		return $invite_code_self;
	}

	/* 防止用户名重复 */
	private function username_random(){
		$username = 'APP_' . $this->random(10);
		$sql = "SELECT COUNT(*) as count FROM " . $this->db->dbprefix . "users WHERE user_name = ?";
		$result = $this->db->query($sql,$username);
		$result = $result->num_rows();
		if($result->count != ''){
			return $this->username_random();
		}
		return $username;
	}

	/**
	 * 取得随机数
	 *
	 * @param int $length 生成随机数的长度
	 * @param int $numeric 是否只产生数字随机数 1是0否
	 * @return string
	 */
	private function random($length, $numeric = 0)
	{
		$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
		$hash = '';
		$max  = strlen($seed) - 1;
		for ($i = 0; $i < $length; $i++) {
			$hash .= $seed{mt_rand(0, $max)};
		}
		return $hash;
	}

	/* 发送手机验证码 */
	public function send_mobile_code ($mobile_phone,$key_mobile_phone)
	{
		$token = 'flkdhgf234hglkf4325jd345ghs657flhlifhgfhgfSJKGfghUHFfdgRYfdgG';

		$md5_mobile_phone = strtoupper(md5($mobile_phone.$token));


		if($md5_mobile_phone!=$key_mobile_phone){
			return 1015; 
		}

		$mobile_phone = trim($mobile_phone);

		if(empty($mobile_phone))
		{
			return 1012;
		}
		else if($this->check_validate_record_exist($mobile_phone))
		{
			// 获取数据库中的验证记录
			$record = $this->get_validate_record($mobile_phone);

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
	            //echo ("同一手机号24小时内只能发送3次验证码！");
	            return 1014;
	        }

	        if(time() - $create_time < $max_sms_count_time && $record['count_ip'] > $max_sms_count_ip) {
	            //echo ("同一个ip24小时内只能发送5次验证码！");
	            return 1014;
	        }


			if((time() - $last_send_time) < 60)
			{
				//echo ("每60秒内只能发送一次短信验证码，请稍候重试");
				return 1013;
			}
			else if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count)
			{
				//echo ("您发送验证码太过于频繁，请稍后重试！");
				return 1014;
			}
			else
			{
				$count ++;
				$count_ip ++;
			}

		}

		define('IN_ECS', true);
		$root_path = $_SERVER['DOCUMENT_ROOT'].'/';
		require_once ($root_path . 'languages/zh_cn/user.php');
		require_once ($root_path . 'includes/lib_passport.php');

		require_once ($root_path . 'sms/sms.php');

		// 设置为空
		$_SESSION['mobile_register'] = array();

		// 生成6位短信验证码
		$mobile_code = $this->random(6,1);
		$sql = "SELECT `value` FROM ".$this->db->dbprefix."shop_config WHERE code = 'sms_sign'";

		$result = $this->db->query($sql);
		$result =  $result->first_row();

		// 短信内容
		$content = sprintf($_LANG['mobile_code_template'], $result->value, $mobile_code);
		//print_r($content);die;
		/* 发送激活验证邮件 */

		//$result = true;
		//$GLOBALS['_CFG']['ecsdxt_user_name']  = 'DXX-WSS-010-10567'; // 用户账号
		//$GLOBALS['_CFG']['ecsdxt_pass_word'] = 'd8b6-1CCd-4'; // 密码


		$sql = "select value from " . $this->db->dbprefix . "shop_config where code = ? ";
		$result = $this->db->query($sql,'ecsdxt_user_name');
		$info = $result->row_array();
		$GLOBALS['_CFG']['ecsdxt_user_name'] = $info['value'];// 用户账号

		$sql = "select value from " . $this->db->dbprefix . "shop_config where code = ? ";
		$result = $this->db->query($sql,'ecsdxt_pass_word');
		$info = $result->row_array();
		$GLOBALS['_CFG']['ecsdxt_pass_word'] = $info['value'];// 用户账号

		$result = sendsms($mobile_phone, $content);
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


			// 保存验证信息
			$this->save_validate_record($mobile_phone, $mobile_code, 'mobile_register', time(), time() + 30 * 60, $ext_info);
			return 200;
		}
		else
		{
			return 1002;//echo '短信验证码发送失败';
		}
	}

	private function save_validate_record ($key, $code, $type, $last_send_time, $expired_time, $ext_info = array())
	{
		$record = array(
			// 验证代码
			"record_code" => $code,
			// 业务类型
			"record_type" => $type,
			// 业务类型
			"last_send_time" => $last_send_time,
			// 过期时间
			"expired_time" => $expired_time,
			// 扩展信息
			"ext_info" => serialize($ext_info),
			// ip地址
			"ip" => $this->GetIp()
		);


		$exist = $this->check_validate_record_exist($key);

		if(! $exist)
		{
			$record['record_key'] = $key;
			// 记录创建时间
			$record["create_time"] = time();
			/* insert */
			$this->db->insert("validate_record",$record);
		}
		else
		{
			$where['record_key'] = $key;
			/* update */
			$this->db->update("validate_record",$record,$where);
		}
	}

	private function check_validate_record_exist($key){

		$sql = "SELECT record_key FROM ".$this->db->dbprefix."validate_record WHERE record_key = ? ";
		$result = $this->db->query($sql,$key);

		if($result->num_rows())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	//查询验证码记录
	private function get_validate_record ($key)
	{
		// 移除过期的验证记录
		//$this->remove_expired_validate_record();

		$sql = "SELECT * FROM ".$this->db->dbprefix."validate_record WHERE record_key = ? ";
		$result = $this->db->query($sql,$key);
		$row = $result->row_array();

		if($row == false)
		{
			return false;
		}

		$row['ext_info'] = unserialize($row['ext_info']);

		$max_sms_count_time = 60 * 60 * 24;
		$sql_sms_time = time() - $max_sms_count_time;

		//根据ip 统计数量
		$sql = "select count(*) from ". $this->db->dbprefix . "validate_record where ip = ?  and create_time > ".$sql_sms_time;
		$result = $this->db->query($sql,$row['ip']);
		$info = $result->row_array();
		$count = $info['count(*)'];

		$record = array(
			// 验证代码
			"record_key" => $row['record_key'],
			// 验证代码
			"record_code" => $row['record_code'],
			// 业务类型
			"record_type" => $row['record_type'],
			// 开始时间
			"last_send_time" => $row['last_send_time'],
			// 过期时间
			"expired_time" => $row['expired_time'],
			// 创建时间
			"create_time" => $row['create_time']
		);

		$record = array_merge($record, $row['ext_info']);
		$record['count_ip'] = $count;
		return $record;
	}

	//清除过期验证码
	private function remove_expired_validate_record()
	{
		$current_time = time();
		$sql = "DELETE FROM ".$this->db->dbprefix."validate_record where expired_time < ? ";
		return $this->db->query($sql,$current_time);
	}

	/*获取用户IP*/
	private function GetIp(){
	  $realip = '';
	  $unknown = 'unknown';
	  if (isset($_SERVER)){
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)){
		  $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		  foreach($arr as $ip){
			$ip = trim($ip);
			if ($ip != 'unknown'){
			  $realip = $ip;
			  break;
			}
		  }
		}else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)){
		  $realip = $_SERVER['HTTP_CLIENT_IP'];
		}else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)){
		  $realip = $_SERVER['REMOTE_ADDR'];
		}else{
		  $realip = $unknown;
		}
	  }else{
		if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)){
		  $realip = getenv("HTTP_X_FORWARDED_FOR");
		}else if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)){
		  $realip = getenv("HTTP_CLIENT_IP");
		}else if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)){
		  $realip = getenv("REMOTE_ADDR");
		}else{
		  $realip = $unknown;
		}
	  }

	  $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
	  return $realip;
	}


}


?>
