<?php

						$parameters = array();
						$parameters['out_trade_no'] = $_GET['out_trade_no'];
						$parameters['customs'] = 'GUANGZHOU_ZS';
						$parameters['mch_id'] = '1525495031';
						$parameters['transaction_id'] = $_GET['transaction_id'];
						$parameters["appid"] = 'wx0a9a5bcba6c15a4b';
						$parameters["mch_customs_no"] = '4401963H95';
						$key = '2gx8o01U9x9abastln3ttu66xdtghyha';
						ksort($parameters);
						$buff = "";
						foreach ($parameters as $k => $v){
							$buff .= $k . "=" . $v . "&";
						}
						$str = $buff.'key='.$key;
						$mysign = md5($str);
						$mysign = strtoupper($mysign);
						$url = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';
						$xmlData = "<xml><appid>wx0a9a5bcba6c15a4b</appid><customs>GUANGZHOU_ZS</customs><mch_customs_no>4401963H95</mch_customs_no><mch_id>1525495031</mch_id><out_trade_no>".$parameters['out_trade_no']."</out_trade_no><sign>".$mysign."</sign><transaction_id>".$parameters['transaction_id']."</transaction_id></xml>";
						
						$ch = curl_init();  // 初始一个curl会话
						$timeout = 30;  // php运行超时时间，单位秒
						curl_setopt($ch, CURLOPT_URL, $url);    // 设置url
						curl_setopt($ch, CURLOPT_POST, 1);  // post 请求
						curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml; charset=utf-8"));    // 一定要定义content-type为xml，要不然默认是text/html！
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);//post提交的数据包
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // PHP脚本在成功连接服务器前等待多久，单位秒
						curl_setopt($ch, CURLOPT_HEADER, 0);
						header('content-type:text/xml;charset=utf-8');
						$result = curl_exec($ch);   // 抓取URL并把它传递给浏览器
						// 是否报错
						if(curl_errno($ch))
						{
							print curl_error($ch);
						}
						curl_close($ch);    // //关闭cURL资源，并且释放系统资源
						
						
		file_put_contents("log/".date('Y-m-d',time())."_customs.txt",date('H:i:s',time())." https://www.quickact.net/customs/Custom.weixin.php?out_trade_no=".$_GET['out_trade_no']."&transaction_id=".$_GET['transaction_id']."\r\n微信上报结果:".$result."\r\n", FILE_APPEND);
		
?>