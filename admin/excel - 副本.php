<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ($_REQUEST['act'] == 'order_excel')
{
    // 载入入驻商
    $sql_supplier = "SELECT supplier_id, supplier_name FROM " . $GLOBALS['ecs']->table("supplier") . " WHERE status = '1' ORDER BY supplier_id";
    $res_supplier = $db->query($sql_supplier);
    while($row_supplier=$db->fetchRow($res_supplier))
    {
        $supplier_list .= "<option value='" . $row_supplier['supplier_id'] . "'>" . $row_supplier['supplier_name'] . "</option>";
    }
    $smarty->assign('supplier_list', $supplier_list);
    // 载入供货商
    $sql_suppliers = "SELECT suppliers_id, suppliers_name, is_exchange FROM " . $GLOBALS['ecs']->table("suppliers") . " ORDER BY suppliers_id";
    $res_suppliers = $db->query($sql_suppliers);
    while($row_suppliers=$db->fetchRow($res_suppliers))
    {
        $suppliers_list .= "<option value='" . $row_suppliers['suppliers_id'] . "'>" . $row_suppliers['suppliers_name'] . "</option>";
    }
    $smarty->assign('suppliers_list', $suppliers_list);
	$smarty->assign('fenxiaouser_list',   get_fenxiao_user());
    // 载入国家
    $smarty->assign('country_list', get_regions());
    $smarty->assign('ur_here', $_LANG['12_order_excel']);
    $smarty->display('excel.htm');
}
elseif($_REQUEST['act'] == 'excel')
{
    $filename='orderexcel';
    header("Content-type: application/vnd.ms-excel; charset=gbk");
    header("Content-Disposition: attachment; filename=$filename.xls");

    // 订单状态
    $order_status_new = intval($_REQUEST['order_status']);
    // 下单开始时间
    $start_time = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
    // 下单结束时间
    $end_time = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);
    // 起始订单号
    $order_sn1 = $_REQUEST['order_sn1'];
    // 终了订单号
    $order_sn2 = $_REQUEST['order_sn2'];
    // 国家
    $country = empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']);
    // 省
    $province = empty($_REQUEST['province']) ? 0 : intval($_REQUEST['province']);
    // 市
    $city = empty($_REQUEST['city']) ? 0 : intval($_REQUEST['city']);
    // 区
    $district = empty($_REQUEST['district']) ? 0 : intval($_REQUEST['district']);
    // 店铺
    $shop_id = $_REQUEST['shop_id'];
    // 入驻商
    $supplier_id = $_REQUEST['supplier_id'];
	//分销商
	$invite_parent_username=$_REQUEST['invite_parent_username'];
//    $where = 'WHERE o.supplier_id=0 ';
	//供货商
	$suppliers_id = $_REQUEST['suppliers_id'];
	$format = $_REQUEST['format'];
    $where = 'WHERE 1 ';
	if($invite_parent_username!='')
	{
		if($invite_parent_username=='670')//平台方
		{
			$invite_parent_username="0,670";
		}
		$where .= " AND u.parent_id in(" . $invite_parent_username . ")";
	}
    if($order_status_new >= 0)
    {
        $where .= " AND o.order_status = '$order_status_new' ";
    }

    if($start_time != '' && $end_time != '')
    {
        $where .= " AND o.add_time >= '$start_time' AND o.add_time <= '$end_time' ";
    }

    if($order_sn1 != '' && $order_sn2 != '')
    {
        $where .= " AND o.order_sn >= '$order_sn1' AND o.order_sn <= '$order_sn2' ";
    }

    if ($country > 0)
    {
        $where .= " AND o.country = $country ";
    }

    if ($province > 0)
    {
        $where .= " AND o.province = $province ";
    }

    if ($city > 0)
    {
        $where .= " AND o.city = $city ";
    }

    if ($district > 0)
    {
        $where .= " AND o.district = $district ";
    }

    if ($shop_id == 0)
    {
        // 自营
        $where .= 'AND o.supplier_id = 0 ';
    }
    else if ($shop_id > 0)
    {
        if ($supplier_id < 0)
        {
            // 所有入驻商
            $where .= 'AND o.supplier_id <> 0 ';
        }
        else
        {
            // 选择入驻商
            $where .= 'AND o.supplier_id = ' . $supplier_id;
        }
    }
    if ($suppliers_id > 0)
    {
        $where .= 'AND o.suppliers_id = ' . $suppliers_id;
    }

    if($format == "1"){
    	$sql = "SELECT "
    	. "o.order_id, "
        . "o.order_sn, " // 订单号
        . "o.is_pickup, " // 订单类型
        . "o.add_time, " // 下单时间
        . "o.froms, " // 订单来源
        . "o.order_status, " // 订单状态
        . "o.consignee, " // 收货人姓名
        . "o.address, " // 收货人地址
        . "o.supplier_id, " // 商家
        . "o.tel, " // 收货人电话
        . "o.mobile, " // 收货人手机
        . "o.pay_name, " // 支付方式
        . "o.shipping_name, " // 配送方式
        . "o.goods_amount, " // 总金额
        . "o.exchange_goods_amount, " // 总金额
        . "u.user_name, " // 用户名
        . "u.parent_id, " 
        . "s.supplier_name, " // 店铺名
        . "ss.suppliers_name " // 供货商
        . "FROM " . $GLOBALS['ecs']->table('order_info')
        . " AS o LEFT JOIN " . $GLOBALS['ecs']->table('users')
        . " AS u ON o.user_id = u.user_id "
        . " LEFT JOIN " . $GLOBALS['ecs']->table('supplier')
        . "AS s ON s.supplier_id = o.supplier_id "
        . " LEFT JOIN " . $GLOBALS['ecs']->table('suppliers')
        . "AS ss ON ss.suppliers_id = o.suppliers_id "
        . $where
        . " ORDER BY o.add_time DESC";
        $res=$db->getAll($sql);

        $data .= "<table border='1'>";
        $data .= "<tr>"
        . "<th colspan='2'>订单号</th>"
        . "<th>订单类型：</th>"
        . "<th>订单来源：</th>"
        . "<th>订单状态</th>"
        . "<th>收货人</th>"
        . "<th colspan='2'>联系电话</th>"
        . "<th colspan='2'>人民币金额</th>"
        . "<th colspan='2'>港币金额</th>"
        . "<th colspan='2'>送货地址</th>"
        . "<th colspan='2'>支付方式</th>"
        . "<th colspan='2'>快递名称</th>"
        . "<th colspan='2'>分销商</th>"
        . "<th colspan='2'>下单时间</th>"
        . "<th colspan='2'>供货商</th>"
        . "</tr>";
        $data .= "</table>";

        foreach ($res as $key => $rows) {
        	extract($rows);
        	$$bg_color = "";
        	// 订单状态
	        if ($order_status == 0)
	        {
	            $order_status_new = '未确认';
	            $bg_color = "#f1f1f1";
	        }
	        else if ($order_status == 1)
	        {
	            $order_status_new = '已确认';
	            $bg_color = "#fffff";
	        }
	        else if ($order_status == 2)
	        {
	            $order_status_new = '已取消';
	            $bg_color = "#ffe9e9";
	        }
	        else if ($order_status == 3)
	        {
	            $order_status_new = '无效';
	            $bg_color = "#f05069";
	        }
	        else if ($order_status == 4)
	        {
	            $order_status_new = '退货';
	            $bg_color = "#fdf7d3";
	        }
	        else if ($order_status == 5)
	        {
	            $order_status_new = '已发货';
	            $bg_color = "#fffff";
	        }
	        else if ($order_status == 6)
	        {
	            $order_status_new = '部分发货';
	        }
	        else if ($order_status == 100)
	        {
	            $order_status_new = '待付款';
	        }
	        else if ($order_status == 101)
	        {
	            $order_status_new = '待发货';
	        }
	        else if ($order_status == 102)
	        {
	            $order_status_new = '已完成';
	        }
	        else
	        {
	            $order_status_new = '';
	        }

	        /* 取得区域名 */
	        $sql = "SELECT concat('', '', IFNULL(p.region_name, ''), " .
	            "'', IFNULL(t.region_name, ''), '', IFNULL(d.region_name, '')) AS region " .
	            "FROM " . $ecs->table('order_info') . " AS o " .
	            "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
	            "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
	            "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
	            "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
	            "WHERE o.order_sn = {$rows['order_sn']}";
	        $address = $db->getOne($sql) . ' ' . $rows['address'];

        	$data .= "<table border='1'>";
            $data .= "<tr>"
                . "<td colspan='2'>" . $order_sn . "</td>"
                . "<td>" . ($is_pickup == 1 ? '自提订单' : '一般订单') . "</td>";
            $data .= "<td>" . $froms . "</td>"
                . "<td bgcolor='".$bg_color."'>" . $order_status_new . "</td>"
                . "<td>". $consignee . "</td>"
                . "<td colspan='2'>" . $mobile . "</td>"
                . "<td colspan='2'>" . $goods_amount . "</td>"
                . "<td colspan='2'>" . $exchange_goods_amount . "</td>"
                . "<td colspan='2'>" . $address . "</td>"
                . "<td colspan='2'>" . $pay_name . "</td>"
                . "<td colspan='2'>" . $shipping_name . "</td>"
                . "<td colspan='2'>" . $fenxiao_name . "</td>"
                . "<td colspan='2'>" . local_date('y-m-d H:i', $add_time) . "</td>"
                . "<td colspan='2'>" . $suppliers_name . "</td>"
                . "</tr>";
            $data .= "</table>";
        }
    }else{
    	$sql = "SELECT "
	    	. "o.order_id, "
	        . "o.order_sn, " // 订单号
	        . "o.is_pickup, " // 订单类型
	        . "o.add_time, " // 下单时间
	        . "o.froms, " // 订单来源
	        . "o.order_status, " // 订单状态
	        . "o.consignee, " // 收货人姓名
	        . "o.address, " // 收货人地址
	        . "o.supplier_id, " // 商家
	        . "o.tel, " // 收货人电话
	        . "o.mobile, " // 收货人手机
	        . "o.pay_name, " // 支付方式
	        . "o.shipping_name, " // 配送方式
	        . "g.goods_name, " // 商品名称
	        . "g.goods_sn, " // 商品货号
	        . "g.goods_price, " // 商品价格
	        . "g.goods_number, " // 购买数量
	        . "g.goods_attr, " // 商品属性
	        . "g.goods_price * g.goods_number money, " // 价格小计
	        . "u.user_name, " // 用户名
	        . "u.parent_id, " 
	        . "b.brand_name, "
	        . "s.supplier_name, " // 店铺名
	        . "ss.suppliers_name " // 供货商
	        . "FROM " . $GLOBALS['ecs']->table('order_info')
	        . " AS o LEFT JOIN " . $GLOBALS['ecs']->table('users')
	        . " AS u ON o.user_id = u.user_id "
	        . " LEFT JOIN  " . $GLOBALS['ecs']->table('order_goods')
	        . " AS g ON o.order_id = g.order_id "
	        . " LEFT JOIN " . $GLOBALS['ecs']->table('goods')
	        . " AS go ON g.goods_id = go.goods_id "
	        . " LEFT JOIN " . $GLOBALS['ecs']->table('brand')
	        . " AS b ON go.brand_id = b.brand_id "
	        . " LEFT JOIN " . $GLOBALS['ecs']->table('supplier')
	        . "AS s ON s.supplier_id = o.supplier_id "
	        . " LEFT JOIN " . $GLOBALS['ecs']->table('suppliers')
	        . "AS ss ON ss.suppliers_id = o.suppliers_id "
	        . $where
	        . " ORDER BY o.add_time DESC";
		file_put_contents('orderinvite0305.txt','--daochu---'.$sql, FILE_APPEND); 
	    $res=$db->getAll($sql);
	    $list = array();
	    foreach($res as $key => $rows)
    {
        // 订单状态
        if ($rows['order_status'] == 0)
        {
            $list[$key]['order_status'] = '未确认';
            $list[$key]['bg_color'] = "#f1f1f1";
        }
        else if ($rows['order_status'] == 1)
        {
            $list[$key]['order_status'] = '已确认';
            $list[$key]['bg_color'] = "#fffff";
        }
        else if ($rows['order_status'] == 2)
        {
            $list[$key]['order_status'] = '已取消';
            $list[$key]['bg_color'] = "#ffe9e9";
        }
        else if ($rows['order_status'] == 3)
        {
            $list[$key]['order_status'] = '无效';
            $list[$key]['bg_color'] = "#f05069";
        }
        else if ($rows['order_status'] == 4)
        {
            $list[$key]['order_status'] = '退货';
            $list[$key]['bg_color'] = "#fdf7d3";
        }
        else if ($rows['order_status'] == 5)
        {
            $list[$key]['order_status'] = '已发货';
            $list[$key]['bg_color'] = "#fffff";
        }
        else if ($rows['order_status'] == 6)
        {
            $list[$key]['order_status'] = '部分发货';
        }
        else if ($rows['order_status'] == 100)
        {
            $list[$key]['order_status'] = '待付款';
        }
        else if ($rows['order_status'] == 101)
        {
            $list[$key]['order_status'] = '待发货';
        }
        else if ($rows['order_status'] == 102)
        {
            $list[$key]['order_status'] = '已完成';
        }
        else
        {
            $$list[$key]['order_status'] = '';
        }

        /* 取得区域名 */
        $sql = "SELECT concat('', '', IFNULL(p.region_name, ''), " .
            "'', IFNULL(t.region_name, ''), '', IFNULL(d.region_name, '')) AS region " .
            "FROM " . $ecs->table('order_info') . " AS o " .
            "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
            "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
            "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
            "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
            "WHERE o.order_sn = {$rows['order_sn']}";
        $address = $db->getOne($sql) . ' ' . $rows['address'];

        $list[$key]['order_sn'] = $rows['order_sn'];
        $list[$key]['is_pickup'] = $rows['is_pickup'];
        $list[$key]['add_time'] = local_date('y-m-d H:i', $rows['add_time']);
        $list[$key]['froms'] = $rows['froms'];
        $list[$key]['consignee'] = $rows['consignee'];
        $list[$key]['address'] = $address;
        $list[$key]['supplier_id'] = $rows['supplier_id'];
        $list[$key]['tel'] = empty($rows['mobile']) ? $rows['tel'] : $rows['mobile'];
        $list[$key]['pay_name'] = $rows['pay_name'];
        $list[$key]['shipping_name'] = $rows['shipping_name'];
        $list[$key]['goods_name'] = $rows['goods_name'];
        $list[$key]['goods_sn'] = $rows['goods_sn'];
        $list[$key]['goods_price'] = $rows['goods_price'];
        $list[$key]['goods_number'] = $rows['goods_number'];
        $list[$key]['goods_attr'] = $rows['goods_attr'];
        $list[$key]['money'] = $rows['money'];
        $list[$key]['user_name'] = $rows['user_name'];
        $list[$key]['brand_name'] = $rows['brand_name'];
        $list[$key]['supplier_name'] = $rows['supplier_name'];
        $list[$key]['suppliers_name'] = $rows['suppliers_name'];
		$list[$key]['fenxiao_name'] = get_invite_parent_username($rows['parent_id']);
    }

    foreach($list as $key => $val)
    {
//        $data .= "<table border='1'>";
//        $data .= "<tr><td colspan='2'>订单号：".$val['order_sn']."</td><td>用户名：".$val['user_name']."</td><td colspan='2'>收货人：".$val['consignee']."</td><td colspan='2'>联系电话：".$val['tel']."</td></tr>";
//        $data .= "<tr><td colspan='5'>送货地址：".$val['address']."</td><td colspan='2'>下单时间：".$val['add_time']."</td></tr>";
//        $data .= "<tr bgcolor='#999999'><th>序号</th><th>货号</th><th>商品名称</th><th>市场价</th><th>本店价</th><th>购买数量</th><th>小计</th></tr>";
//        $data .= "<tr><th>1</th><th>".$val['goods_sn']."</th><th>".$val['goods_name']."</th><th>".$val['market_price']."</th><th>".$val['goods_price']."</th><th>".$val['goods_number']."</th><th>".$val['money']."</th></tr>";
//        $data .= "</table>";
//        $data .= "<br>";

        // 序号计数用
        $count++;
        if ($val['order_sn'] != $last_order_sn)
        {
            $count = 1;
            $data .= "</table>";
            $data .= "<br>";
            $data .= "<table border='1'>";
            $data .= "<tr bgcolor='".$val['bg_color']."'>"
                . "<td colspan='2'>订单号：" . $val['order_sn'] . "</td>"
                . "<td>订单类型：" . ($val['is_pickup'] == 1 ? '自提订单' : '一般订单') . "</td>"
                . "<td>订单来源：" . $val['froms'] . "</td>"
                . "<td>订单状态：" . $val['order_status'] . "</td>"
                . "<td>收货人：".$val['consignee'] . "</td>"
                . "<td colspan='2'>联系电话：" . $val['tel'] . "</td>"
                . "</tr>";

            $data .= "<tr>"
                . "<td colspan='2'>送货地址：" . $val['address'] . "</td>"
                . "<td colspan='1'>支付方式：" . $val['pay_name'] . "</td>"
                . "<td colspan='1'>配送方式：" . $val['shipping_name'] . "</td>"
                // . "<td colspan='2'>商家：" . ($val['supplier_id'] == 0 ? '平台自营' : $val['supplier_name']) . "</td>"
                . "<td colspan='2'>分销商：" . $val['fenxiao_name'] . "</td>"
                . "<td colspan='2'>下单时间：" . $val['add_time'] . "</td>"
                . "</tr>";

            $data .= "<tr>"
                . "<th bgcolor='#999999'>序号</th>"
                . "<th bgcolor='#999999'>货号</th>"
                . "<th bgcolor='#999999'>商品名称</th>"
                . "<th bgcolor='#999999'>品牌</th>"
                . "<th bgcolor='#999999'>供货商</th>"
                . "<th bgcolor='#999999'>价格</th>"
                . "<th bgcolor='#999999'>购买数量</th>"
                . "<th bgcolor='#999999'>小计</th>"
                . "</tr>";

            $data .= "<tr><th>$count</th><th>" . $val['goods_sn']
                . "</th><th>" . $val['goods_name']
                . "</th><th>" . $val['brand_name']
                . "</th><th>" . $val['suppliers_name']
                . "</th><th>" . $val['goods_price']
                . "</th><th>" . $val['goods_number']
                . "</th><th>" . $val['money']
                . "</th></tr>";
        }
        else
        {
            $data .= "<tr><th>$count</th><th>" . $val['goods_sn']
                . "</th><th>" . $val['goods_name']
                . "</th><th>" . $val['brand_name']
                . "</th><th>" . $val['goods_attr']
                . "</th><th>" . $val['goods_price']
                . "</th><th>" . $val['goods_number']
                . "</th><th>" . $val['money']
                . "</th></tr>";
        }
        $last_order_sn = $val['order_sn'];
    }
    }
  
    
        

    if (EC_CHARSET != 'gbk')
    {
        echo ecs_iconv(EC_CHARSET, 'gbk', $data) . "\t";
    }
    else
    {
        echo $data. "\t";
    }
}

function get_fenxiao_user()
{
    $sql = "select user_id,user_name from ". $GLOBALS['ecs']->table('users') .
	" where user_id in(SELECT distinct parent_id FROM " . $GLOBALS['ecs']->table('users') .") order by user_id asc";
    $res = $GLOBALS['db']->getAll($sql);

    if (!is_array($res))
    {
        $res = array();
    }

    return $res;
}
function get_invite_parent_username($invite_parent)
{
    if ($invite_parent=='0')
    {
        return "平台方";
    }
    else
    {
        $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') .
                    " WHERE user_id = '$invite_parent' ";
                    
        return $GLOBALS['db']->getOne($sql);
    }
}
?>