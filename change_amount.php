<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
	
	$default_rate = "0.8491";//港币兑人民币汇率
	$default_rate_cth = number_format(1/$default_rate, 6, '.', '');

    // $sql = "SELECT order_id, goods_amount, tax, shipping_fee, exchange_rate, exchange_goods_amount, exchange_tax, exchange_shipping_fee, exchange_discount, exchange_bonus, hkd_goods_amount FROM ecs_order_info WHERE order_id >= 20000 AND order_id < 40000";//内存不足2W条执行
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
    //    if($val['hkd_goods_amount'] > 0){
    //    		$rate = $val['exchange_rate'] > 0 ? $val['exchange_rate'] : $default_rate;
    //    		//商品价
    //    		if($val['exchange_goods_amount'] > 0){
    //    			$new_goods_amount = $val['exchange_goods_amount'];
	   //     	}else{
	   //     		$new_goods_amount = number_format($val['hkd_goods_amount']*$rate, 2, '.', '');
	   //     	}
	   //     	//税
	   //     	if($val['exchange_tax'] > 0){
    //    			$new_tax = $val['exchange_tax'];
	   //     	}else{
	   //     		$new_tax = number_format($val['tax']*$rate, 2, '.', '');
	   //     	}
	   //     	//运费
	   //     	if($val['exchange_shipping_fee'] > 0){
    //    			$new_shipping_fee = $val['exchange_shipping_fee'];
	   //     	}else{
	   //     		$new_shipping_fee = number_format($val['shipping_fee']*$rate, 2, '.', '');
	   //     	}
	   //     	//折扣
	   //     	if($val['exchange_discount'] > 0){
    //    			$new_discount = $val['exchange_discount'];
	   //     	}else{
	   //     		$new_discount = number_format($val['discount']*$rate, 2, '.', '');
	   //     	}
	   //     	//红包
	   //     	if($val['exchange_bonus'] > 0){
    //    			$new_bonus = $val['exchange_bonus'];
	   //     	}else{
	   //     		$new_bonus = number_format($val['bonus']*$rate, 2, '.', '');
	   //     	}

	   //     	$GLOBALS['db']->query("UPDATE ecs_order_info SET goods_amount = '$new_goods_amount', tax = '$new_tax', shipping_fee = '$new_shipping_fee', discount = '$new_discount', bonus = '$new_bonus', exchange_goods_amount = '".$val['hkd_goods_amount']."',  exchange_tax = '".$val['tax']."', exchange_shipping_fee = '".$val['shipping_fee']."', exchange_discount = '".$val['discount']."', exchange_bonus = '".$val['bonus']."' WHERE order_id = '$val[order_id]'");
    //    }
    // }

    // $sql = "SELECT og.rec_id, og.exchange_price, og.hkd_price , oi.exchange_rate FROM ecs_order_goods og LEFT JOIN ecs_order_info oi ON og.order_id = oi.order_id";
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
    //    if($val['hkd_price'] > 0){
    //    		$rate = $val['exchange_rate'] > 0 ? $val['exchange_rate'] : $default_rate;
    //    		if($val['exchange_price'] > 0){
    //    			$new_goods_price = $val['exchange_price'];
	   //     	}else{
	   //     		$new_goods_price = number_format($val['hkd_price']*$rate, 2, '.', '');
	   //     	}
	   //     	$GLOBALS['db']->query("UPDATE ecs_order_goods SET goods_price = '$new_goods_price', exchange_price = '".$val['hkd_price']."' WHERE rec_id = '$val[rec_id]'");
    //    }
    // }

    // $sql = "SELECT order_id, exchange_rate FROM ecs_order_info";
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
   	// 	if($val['exchange_rate'] > 0){
   	// 		$new_exchange_rate = number_format(1/$val['exchange_rate'], 6, '.', '');
    //    	}else{
    //    		$new_exchange_rate = $default_rate_cth;
    //    	}
    //    	$GLOBALS['db']->query("UPDATE ecs_order_info SET exchange_rate = '$new_exchange_rate' WHERE order_id = '$val[order_id]'");
    // }

    // $sql = "SELECT * FROM ecs_bonus_type";
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
   	// 	$type_money = number_format($val['type_money']*$default_rate, 2, '.', '');
   	// 	// var_dump($type_money);
    //    	$GLOBALS['db']->query("UPDATE ecs_bonus_type SET type_money = '$type_money' WHERE type_id = '$val[type_id]'");
    // }

    // $sql = "SELECT * FROM ecs_bonus_type WHERE min_goods_amount > '1'";
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
   	// 	$min_goods_amount = number_format($val['min_goods_amount']*$default_rate, 2, '.', '');
   	// 	// var_dump($type_money);
    //    	$GLOBALS['db']->query("UPDATE ecs_bonus_type SET min_goods_amount = '$min_goods_amount' WHERE type_id = '$val[type_id]'");
    // }

    // $sql = "SELECT * FROM ecs_users WHERE user_money > 0";
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
    // 	$user_money = number_format($val['user_money']*$default_rate, 2, '.', '');
   	// 	// var_dump($user_money);
    //    	$GLOBALS['db']->query("UPDATE ecs_users SET user_money = '$user_money' WHERE user_id = '$val[user_id]'");
    // }

