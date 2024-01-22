<?php

/**
 * ECSHOP 记录管理员操作日志
 
 * $Author: liubo $
 * $Id: admin_logs.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 获取所有日志列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{ 

    $user_id   = !empty($_REQUEST['id'])       ? intval($_REQUEST['id']) : 0;
    $admin_ip  = !empty($_REQUEST['ip'])       ? $_REQUEST['ip']         : '';
    $log_date  = !empty($_REQUEST['log_date']) ? $_REQUEST['log_date']   : '';
   

    $smarty->assign('ur_here',   '访问日志'); 
    $smarty->assign('full_page', 1);

    $log_list = get_admin_logs();

    $smarty->assign('log_list',        $log_list['list']);
    $smarty->assign('filter',          $log_list['filter']);
    $smarty->assign('record_count',    $log_list['record_count']);
    $smarty->assign('page_count',      $log_list['page_count']);

    $sort_flag  = sort_flag($log_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('admin_iplogs.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $log_list = get_admin_logs();

    $smarty->assign('log_list',        $log_list['list']);
    $smarty->assign('filter',          $log_list['filter']);
    $smarty->assign('record_count',    $log_list['record_count']);
    $smarty->assign('page_count',      $log_list['page_count']);

    $sort_flag  = sort_flag($log_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('admin_iplogs.htm'), '',
        array('filter' => $log_list['filter'], 'page_count' => $log_list['page_count']));
}
 

/* 获取管理员操作记录 */
function get_admin_logs()
{
	$result = get_filter();
    if ($result === false)
	{	
		$filter['add_time1']    = empty($_REQUEST['add_time1']) ? '' : (strpos($_REQUEST['add_time1'], '-') > 0 ?  ($_REQUEST['add_time1']) : $_REQUEST['add_time1']);
		$filter['add_time2']    = empty($_REQUEST['add_time2']) ? '' : (strpos($_REQUEST['add_time2'], '-') > 0 ?  ($_REQUEST['add_time2']) : $_REQUEST['add_time2']);
		//$filter['ip'] = !empty($_REQUEST['ip']) ? $_REQUEST['ip']         : '';
		$filter['sort_by']      = empty($_REQUEST['sort_by']) ? 'al.id' : trim($_REQUEST['sort_by']);
		$filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		
		//查询条件
		$where = " WHERE 1 ";
	
		if ($filter['add_time1'])
		{
			$where .= " AND al.createtime>=  '" . $filter['add_time1'].":00' ";
		}
		if ($filter['add_time2'])
		{
			$where .= " AND al.createtime<=  '" . $filter['add_time2'].":00' ";
		}
		//if (!empty($filter['ip']))
		//{
		//	$where .= " AND al.ip_address = '$filter[ip]' ";
		//}
		
		/* 获得总记录数据 */
		$sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('ip_log'). ' AS al ' . $where;

		$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
		$filter = page_and_size($filter);
		
		set_filter($filter, $sql);
		
		/* 获取管理员日志记录 */
		$list = array();
		$sql  = 'SELECT al.* FROM ' .$GLOBALS['ecs']->table('ip_log'). ' AS al '.
				$where .' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];


	}
	else
	{       
		$sql    = $result['sql'];
        $filter = $result['filter'];		
	}
	
    $res  = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        //$rows['log_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['log_time']);

        $list[] = $rows;
    }

    return array('list' => $list, 'filter' => $filter, 'page_count' =>  $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>