<?php

/**
 * 保存一个验证记录到数据库，如果存在则更新
 *
 * @param string $key
 *        	验证标识
 * @param string $code
 *        	验证值
 * @param string $type
 *        	验证类型
 * @param datetime $expired_time
 *        	过期时间
 * @param array $ext_info
 *        	扩展信息
 */
function save_validate_record ($key, $code, $type, $last_send_time, $expired_time, $ext_info = array())
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
		"ip" => GetIp()
	);
	
	$exist = check_validate_record_exist($key);
	
	if(! $exist)
	{
		$record['record_key'] = $key;
		// 记录创建时间
		$record["create_time"] = time();
		
		/* insert */
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('validate_record'), $record, 'INSERT');
	}
	else
	{
		/* update */
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('validate_record'), $record, 'UPDATE', "record_key = '$key'");
	}
}

/**
 * 检查验证记录在数据库中是否已经存在
 *
 * @param string $key        	
 * @return boolean
 */
function check_validate_record_exist ($key)
{
	$sql = "select count(*) from " . $GLOBALS['ecs']->table('validate_record') . " where record_key = '" . $key . "'";
	$count = $GLOBALS['db']->getOne($sql);
	
	if($count > 0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * 根据键删除验证记录
 *
 * @param string $key        	
 */
function remove_validate_record ($key)
{
	$sql = "delete from " . $GLOBALS['ecs']->table('validate_record') . " where record_key = '$key'";
	return $GLOBALS['db']->query($sql);
}

/**
 * 移除过期的验证记录
 *
 * @param string $key        	
 */
function remove_expired_validate_record ()
{
	$current_time = time();
	$sql = "delete from " . $GLOBALS['ecs']->table('validate_record') . " where expired_time < '$current_time'";
	return $GLOBALS['db']->query($sql);
}

/**
 * 基本验证
 *
 * @param string $key        	
 * @param string $value        	
 * @return int 0-验证信息不存在，1-验证码已过期, 2-验证码错误
 */
function validate_code ($key, $code)
{
	$record = get_validate_record($key);
	
	if($record == false)
	{
		return ERR_VALIDATE_KEY_NOT_EXIST;
	}
	else if($record['expired_time'] < time())
	{
		return ERR_VALIDATE_EXPIRED_TIME;
	}
	else if($record['record_code'] != $code)
	{
		return ERR_VALIDATE_CODE_NOT_MATCH;
	}
	else
	{
		return true;
	}
}

/**
 * 从数据库中获取验证记录信息，会将ext_info数组解析与结果合并
 *
 * @param string $key        	
 * @return boolean|array:
 */
function get_validate_record ($key)
{
	// 移除过期的验证记录
	//remove_expired_validate_record();  //验证码30分钟过期，此处移除记录之后，24小时内只能发送指定条数不能实现，故屏蔽
	
	
	$sql = "select * from " . $GLOBALS['ecs']->table('validate_record') . " where record_key = '$key'";
	$row = $GLOBALS['db']->getRow($sql);
	
	if($row == false)
	{
		return false;
	}
	

	$row['ext_info'] = unserialize($row['ext_info']);


	$max_sms_count_time = 60 * 60 * 24;
	$sql_sms_time = time() - $max_sms_count_time;

	//根据ip 统计数量
	$sql = "select count(*) from " . $GLOBALS['ecs']->table('validate_record') . " where ip = '" . $row['ip'] . "' and create_time > ".$sql_sms_time;
	$count = $GLOBALS['db']->getOne($sql);

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


/*获取用户IP*/
function GetIp(){
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

?>