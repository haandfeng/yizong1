<?php
/**
 * ECSHOP 商品批量上传、修改
 
 * $Author: liubo $
 * $Id: goods_price.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require('includes/lib_goods.php');
$table='ecs_goods';
/*------------------------------------------------------ */
//-- 批量
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('13_price_add');
 
    /* 参数赋值 */
    $ur_here = '商品批量改价';
    $smarty->assign('ur_here', $ur_here);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_price.htm');
}



/*------------------------------------------------------ */
//-- 批量修改：提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('13_price_add');
 
	if (!empty($_POST['bili']))
	{
		 $time=date('Y-m-d H:i:s');
		 $bl=(float)($_POST['bili']);
		 if($bl>0)
		{
			 if($bl>1 || $bl<1)
			 { 
				$sql = "insert into ecs_goods_price_log(tid,`goods_id`, `lastprice`,lastprice_promote, `lasttime`) select '".$time."',goods_id,shop_price,promote_price,now() from ".$table;
				if($db->query($sql))
				{
					$sql = "insert into ecs_goods_price_log(tid,`goods_id`, `lastprice`,lastprice_promote, `lasttime`,bili) select '".$time."',0,0,0,now(),".$bl."  ";
					$db->query($sql);
					$sql="update ".$table." set shop_price=ROUND(shop_price*".$bl.",1),promote_price=ROUND(promote_price*".$bl.",1) ";
					$db->query($sql);
				}
			 }
		}
	}
 
    // 提示成功
    $link[] = array('href' => 'goods_price.php?act=add', 'text' => "商品批量改价 ");
    sys_msg('批量处理成功', 0, $link);
}

elseif ($_REQUEST['act'] == 'chehui')
{
    /* 检查权限 */
    admin_priv('13_price_add');

	$sql="select max(tid) from ecs_goods_price_log";
	$tid=$db->getOne($sql);
	if($tid<>"")
	{
		$sql="update ".$table." inner join (select * from ecs_goods_price_log where tid='".$tid."') ecs_goods_price_log on ecs_goods_price_log.goods_id=".$table.".goods_id set shop_price=lastprice,promote_price=lastprice_promote ";
		$db->query($sql);
	}
    // 提示成功
    $link[] = array('href' => 'goods_price.php?act=add', 'text' => "商品批量改价 ");
    sys_msg('批量撤回成功', 0, $link);
}

?>