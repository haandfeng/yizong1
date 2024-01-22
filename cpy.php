<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

	$default_rate = "0.854820";

    $sql = "SELECT order_id, goods_amount, tax, shipping_fee, exchange_goods_amount, exchange_tax, exchange_shipping_fee, exchange_discount, exchange_bonus FROM ecs_order_info";
    $row = $GLOBALS['db']->getAll($sql);var_dump($row);exit;
    foreach ($row as $key => $val){
       if($val['goods_amount'] > 0){
       		//商品价
       		// if($val['exchange_goods_amount'] > 0){
       		// 	$new_goods_amount = $val['exchange_goods_amount'];
	       	// }else{
	       	// 	$new_goods_amount = number_format($val['goods_amount']*$default_rate, 2, '.', '');
	       	// }
	       	//税
	       	if($val['exchange_tax'] > 0){
       			$new_tax = $val['exchange_tax'];
	       	}else{
	       		$new_tax = number_format($val['tax']*$default_rate, 2, '.', '');
	       	}
	       	//运费
	       	if($val['exchange_shipping_fee'] > 0){
       			$new_shipping_fee = $val['exchange_shipping_fee'];
	       	}else{
	       		$new_shipping_fee = number_format($val['shipping_fee']*$default_rate, 2, '.', '');
	       	}
	       	//折扣
	       	if($val['exchange_discount'] > 0){
       			$new_discount = $val['exchange_discount'];
	       	}else{
	       		$new_discount = number_format($val['discount']*$default_rate, 2, '.', '');
	       	}
	       	//红包
	       	if($val['exchange_bonus'] > 0){
       			$new_bonus = $val['exchange_bonus'];
	       	}else{
	       		$new_bonus = number_format($val['bonus']*$default_rate, 2, '.', '');
	       	}

	       	// $GLOBALS['db']->query("UPDATE ecs_order_info SET goods_amount = '$new_goods_amount', tax = '$new_tax', shipping_fee = '$new_shipping_fee', discount = '$new_discount', bonus = '$new_bonus' WHERE order_id = '$val[order_id]'");
       }
    }

    // $sql = "SELECT rec_id, exchange_price, hkd_price FROM ecs_order_goods";
    // $row = $GLOBALS['db']->getAll($sql);
    // foreach ($row as $key => $val){
    //    if($val['hkd_price'] > 0){
    //    		if($val['exchange_price'] > 0){
    //    			$new_goods_price = $val['exchange_price'];
	   //     	}else{
	   //     		$new_goods_price = number_format($val['hkd_price']*$default_rate, 2, '.', '');
	   //     	}
	   //     	$GLOBALS['db']->query("UPDATE ecs_order_goods SET goods_price = '$new_goods_price' WHERE rec_id = '$val[rec_id]'");
    //    }
    // }