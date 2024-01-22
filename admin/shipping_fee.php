<?php

/**
 * ECSHOP 配送区域管理程序
 
 * $Author: liubo $
 * $Id: shipping_area.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_shipping.php'); 
$exc = new exchange($ecs->table('shipping_area'), $db, 'shipping_area_id', 'shipping_area_name');

$not_set_default = array('pups','tc_express');//设置不用设置默认配送方式的二个方式

/*------------------------------------------------------ */
//-- 配送区域列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{ 
	//	sys_msg("只有设置成默认配送方式，才可以编辑此功能", 1);
	 

    $list = get_list();
    $smarty->assign('list',    $list);

    $smarty->assign('ur_here',  '<a href="shipping.php?act=list">'.
        $_LANG['03_shipping_list'].'</a> - 设置新运费</a>');
    $smarty->assign('action_link', array('href'=>'shipping_fee.php?act=add',
        'text' => "设置新运费"));
    $smarty->assign('full_page', 1);

    assign_query_info();
    $smarty->display('shipping_fee_config.htm');
}

/*------------------------------------------------------ */
//-- 新建配送区域
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add')
{
    admin_priv('shiparea_manage'); 
  
    $smarty->assign('ur_here',  '<a href="shipping.php?act=list">'.
        $_LANG['03_shipping_list'].'</a> - <a href="shipping_fee.php?act=list">设置新运费</a> - 编辑新运费</a>');  
	//$cat_list = cat_list(0, 0, false); 
    //$smarty->assign('cat_list',          $cat_list); 
    $goods_list = goods_list(); 
    $smarty->assign('goods_list',          $goods_list); 
    $smarty->assign('isuse',  1);
    $smarty->assign('form_action',  'insert'); 
    $smarty->display('shipping_fee_config_info.htm'); 
}

elseif ($_REQUEST['act'] == 'insert')
{
    admin_priv('shiparea_manage');
  
	if($_POST['isuse']=="on")$_POST['isuse']=1;else $_POST['isuse']=0;
	
	$sql = "insert into zs_expressfeeconfig(name,goodsamount,isuse) values('".$_POST['name']."' ".  
				",'".$_POST['goodsamount']."' ". 
				",'".$_POST['isuse']."') " ; 
	$db->query($sql);

	admin_log($_POST['shipping_area_name'], 'edit', 'shipping_feeinsert');
 $lnk[] = array('text' => "返回上一页", 'href'=>'shipping_fee.php?act=list');
	sys_msg("保存成功", 1,$lnk);
}

/*------------------------------------------------------ */
//-- 编辑配送区域
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit')
{
    admin_priv('shiparea_manage');

    $sql = "SELECT * FROM `zs_expressfeeconfig` where id='$_REQUEST[id]'";
    $row = $db->getRow($sql);
  
    $smarty->assign('ur_here',  '<a href="shipping.php?act=list">'.
        $_LANG['03_shipping_list'].'</a> - <a href="shipping_fee.php?act=list">设置新运费</a> - 编辑新运费</a>');  
	//$cat_list = cat_list(0, 0, false);
    $goods_list = goods_list(); 
    $smarty->assign('id',               $_REQUEST['id']);
    $smarty->assign('name',           $row['name']);
    $smarty->assign('mark_goods_id',    $row['mark_goods_id']);
    $smarty->assign('goods_id',          $row['goods_id']);
    //$smarty->assign('cat_list',          $cat_list);
    $smarty->assign('goods_list',          $goods_list);
    $smarty->assign('mark_goodsamount',      $row['mark_goodsamount']);
    $smarty->assign('goodsamount',       $row['goodsamount']);
    $smarty->assign('isfree',  $row['isfree']);
    $smarty->assign('isuse',  $row['isuse']);
    $smarty->assign('form_action',  'update'); 
    $smarty->display('shipping_fee_config_info.htm');
}

elseif ($_REQUEST['act'] == 'update')
{
    admin_priv('shiparea_manage');
  
	if($_POST['isuse']=="on")$_POST['isuse']=1;else $_POST['isuse']=0;
	if($_POST['id']=="0")
	{
		$sql = "UPDATE zs_expressfeeconfig SET isuse='".$_POST['isuse']."',usetime=now() where id=0";
	}
	else
	{
	$sql = "UPDATE zs_expressfeeconfig SET name='".$_POST['name']."' ". 
				",goodsamount='".$_POST['goodsamount']."' ". 
				",isuse='".$_POST['isuse']."',usetime=now() ".
			"WHERE id='$_POST[id]'"; 
	}
	$db->query($sql);

	admin_log($_POST['shipping_area_name'], 'edit', 'shipping_feeupdate');
 $lnk[] = array('text' => "返回上一页", 'href'=>'shipping_fee.php?act=list');
	sys_msg("保存成功", 1,$lnk);

}



function goods_list()
{
    $list = array();
    $sql = "SELECT `goods_id` as id,CONCAT(c.cat_name,'-',goods_sn,'-',goods_name) as name  FROM `ecs_goods` g inner join ecs_category c on g.cat_id=c.cat_id where g.is_delete='0'  AND g.is_real='1' order by c.cat_name,goods_sn" ;
	$res = $GLOBALS['db']->query($sql);
	 while ($row = $GLOBALS['db']->fetchRow($res))
    { 
        $row['name'] = $row['name'];
        $row['id'] = $row['id'];
        $list[] = $row;
    }
    return $list;
}

/**
 * 取得配送区域列表
 * @param   int     $shipping_id    配送id
 */
function get_list()
{
    $list = array();
    $sql = "SELECT * FROM zs_expressfeeconfig order by ind desc,id desc" ; 
    $res = $GLOBALS['db']->query($sql);
     while ($row = $GLOBALS['db']->fetchRow($res))
    { 
        $row['name'] = str_replace("{needmoney}","-",str_replace("{weight}","-",str_replace("{money}",$row['goodsamount'],$row['name'])));
		if (intval($row['goodsamount'])==0){$row['goodsamount']="-";} 
        $row['isuse'] = $row['isuse']==1 ? "启用中":"-";
        $row['id'] = $row['id'];
        $list[] = $row;
    }
 
    return $list;
}
 
function getgoodsname($c)
{
	$result="";
	$sql="SELECT CONCAT(c.cat_name,'-',goods_sn,'-',goods_name) as name  FROM `ecs_goods` g inner join ecs_category c on g.cat_id=c.cat_id WHERE goods_id in(".$c.")"; 
	$ds = $GLOBALS['db']->getAll($sql);  
	foreach ($ds AS $row)
	{
		$result=$result.$row['name'].",";
	}
	return $result;
}
function mark($m)
{
	$result="";
	if($m=="in")
	{
		$result="包含";
	}
	else if($m=="not in")
	{
		$result="不包含";
	}
	else if($m==">")
	{
		$result="大于";
	}
	else if($m==">=")
	{
		$result="大于等于";
	}
	else if($m=="<")
	{
		$result="小于";
	}
	else if($m=="<=")
	{
		$result="大于等于";
	}
	else if($m=="=")
	{
		$result="等于";
	} 
	return $result;
}
?>
