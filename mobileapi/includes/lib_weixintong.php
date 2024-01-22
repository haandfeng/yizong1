<?php
 $wxid = isset($_GET['wxid']) ? $_GET['wxid'] : '';
 if(!empty($wxid) && strlen($wxid) == 28){ 
 
	$ecuid = $GLOBALS['db']->getOne("select ecuid from " . $GLOBALS['ecs']->table('weixin_user') . " where fake_id='{$wxid}'");
	if($ecuid > 0){
		$username = $GLOBALS['db']->getOne("select user_name from ".$GLOBALS['ecs']->table('users')." where user_id='{$ecuid}'");
		if( !empty( $username )){
			$GLOBALS['user']->set_session($username);
			$GLOBALS['user']->set_cookie($username,1);
			update_user_info();  //更新用户信息
			recalculate_price(); // 重新计算购物车中的商品价格
		}

	}

}
 ?> 