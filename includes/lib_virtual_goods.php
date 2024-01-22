<?php

/**
 * ECSHOP 虚拟团购函数库
 
 * $Author: sunlizhi $
 * $Id: lib_virtual_goods.php 17113 2015-07-16 03:44:19Z sunlizhi $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 查询市级列表 
 * @return $city
 */
function get_city_list(){
	$sql = "select distinct city,region_name from ".$GLOBALS['ecs']->table('virtual_goods_district'). "as d left join (select region_id,region_name from" .$GLOBALS['ecs']->table('region'). ") as r on r.region_id = d.city";
	$city= $GLOBALS['db'] -> getAll($sql);
	return $city;
}

/**
 * 根据区域id 获得区域名称
 * @param int $region_id 区域id
 * @return $region_name 区域名称
 */
function get_region_name($region_id){
    $sql = "select region_name from ".$GLOBALS['ecs']->table('region')." where region_id = $region_id";
    $region_name = $GLOBALS['db'] -> getOne($sql);
    return $region_name;
}

/**
 * 查询省级列表
 * @return $region 省级列表
 */
function get_region_list(){
    $sql = "select region_id, region_name from ".$GLOBALS['ecs']->table('region')." where parent_id = '1'";
    $region = $GLOBALS['db'] -> getAll($sql);
    return $region;
}

?>