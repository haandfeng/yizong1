<?php

				//微信报关
			$url = 'https://www.quickact.net/customs/Custom.weixin.php?out_trade_no=2019070362719&transaction_id=4200000310201907033335940986'; 
		    $curl = curl_init(); 
		    curl_setopt($curl, CURLOPT_URL, $url);
		    curl_setopt($curl, CURLOPT_HEADER, 0);
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		    $res = curl_exec($curl);
		    curl_close($curl);
			echo($res);
?>