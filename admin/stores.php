<?php

/**
 * ECSHOP 管理中心门店管理
 
 * $Author: wanglei $
 * $Id: stores.php 15013 2009-05-13 09:31:42Z wanglei $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 门店列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
     /* 检查权限 */
     admin_priv('order_seller');

    /* 查询 */
    $result = stores_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['stores_list']); // 当前导航
    $smarty->assign('action_link', array('href' => 'stores.php?act=add', 'text' => $_LANG['add_stores']));
    
    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('stores_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('stores_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('order_seller');

    $result = stores_list();

    $smarty->assign('stores_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('stores_list.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 删除门店
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('order_seller');

    $id = intval($_REQUEST['id']);
    $sql = "SELECT *
            FROM zs_stores
            WHERE store_id = '$id'";
    $store = $db->getRow($sql, TRUE);
;
    if ($store['store_id'])
    {
        /* 判断门店是否存在导购员 */
        $sql = "SELECT COUNT(*)
                FROM zs_stores_seller
                WHERE store_id = '$id'";
        $order_exists = $db->getOne($sql, TRUE);
        if ($order_exists > 0)
        {
            make_json_error($_LANG['remove_cannot_stores']);
            exit;
        }

        $sql = "DELETE FROM zs_stores WHERE store_id = '$id'";
        $db->query($sql);

        /* 记日志 */
        admin_log($store['store_name'], 'remove', 'stores');

        /* 清除缓存 */
        clear_cache_files();
    }

    $url = 'stores.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");

    exit;
}

/*------------------------------------------------------ */
//-- 添加、编辑门店
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('add', 'edit')))
{
    /* 检查权限 */
    admin_priv('order_seller');

    if ($_REQUEST['act'] == 'add')
    {
        $store = array();

        $smarty->assign('ur_here', '<a href="stores.php?act=list">'.$_LANG['stores_list'].'</a>- '.$_LANG['add_stores']);
        $smarty->assign('action_link', array('href' => 'stores.php?act=list', 'text' => $_LANG['stores_list']));

        $smarty->assign('form_action', 'insert');
        $smarty->assign('store', $store);

        assign_query_info();

        $smarty->display('stores_info.htm');

    }
    elseif ($_REQUEST['act'] == 'edit')
    {
        $store = array();

        /* 取得门店信息 */
        $id = $_REQUEST['id'];
        $sql = "SELECT * FROM zs_stores WHERE store_id = '$id'";
        $store = $db->getRow($sql);
        if (count($store) <= 0)
        {
            sys_msg('store does not exist');
        }

        $smarty->assign('ur_here', '<a href="stores.php?act=list">'.$_LANG['stores_list'].'</a>- '.$_LANG['edit_stores']);
        $smarty->assign('action_link', array('href' => 'stores.php?act=list', 'text' => $_LANG['stores_list']));

        $smarty->assign('form_action', 'update');
        $smarty->assign('store', $store);

        assign_query_info();

        $smarty->display('stores_info.htm');
    }

}

/*------------------------------------------------------ */
//-- 提交添加、编辑门店
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('insert', 'update')))
{
    /* 检查权限 */
    admin_priv('order_seller');

    if ($_REQUEST['act'] == 'insert')
    {
        /* 提交值 */
        $store = array('store_id'   => trim($_POST['id']),
                        'store_name'   => trim($_POST['store_name']),
                        'store_desc'   => trim($_POST['store_desc'])
                           );

        /* 判断名称是否重复 */
        $sql = "SELECT store_id
                FROM zs_stores
                WHERE store_name = '" . $store['store_name'] . "' ";
        if ($db->getOne($sql))
        {
            sys_msg($_LANG['stores_name_exist']);
        }

        $db->autoExecute('zs_stores', $store, 'INSERT');
        $store['store_id'] = $db->insert_id();

        /* 记日志 */
        admin_log($store['store_name'], 'add', 'stores');

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(array('href' => 'stores.php?act=add',  'text' => $_LANG['continue_add_stores']),
                       array('href' => 'stores.php?act=list', 'text' => $_LANG['back_stores_list'])
                       );
        sys_msg($_LANG['add_stores_ok'], 0, $links);

    }

    if ($_REQUEST['act'] == 'update')
    {
        /* 提交值 */
        $store = array('id'   => trim($_POST['id']));

        $store['new'] = array('store_name'   => trim($_POST['store_name']),
                           'store_desc'   => trim($_POST['store_desc'])
                           );

        /* 取得门店信息 */
        $sql = "SELECT * FROM zs_stores WHERE store_id = '" . $store['id'] . "'";
        $store['old'] = $db->getRow($sql);
        if (empty($store['old']['store_id']))
        {
            sys_msg('store does not exist');
        }

        /* 判断名称是否重复 */
        $sql = "SELECT store_id
                FROM zs_stores
                WHERE store_name = '" . $store['new']['store_name'] . "'
                AND store_id <> '" . $store['id'] . "'";
        if ($db->getOne($sql))
        {
            sys_msg($_LANG['store_name_exist']);
        }

        /* 保存门店信息 */
        $db->autoExecute('zs_stores', $store['new'], 'UPDATE', "store_id = '" . $store['id'] . "'");

        /* 记日志 */
        admin_log($store['old']['store_name'], 'edit', 'stores');

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links[] = array('href' => 'stores.php?act=list', 'text' => $_LANG['back_stores_list']);
        sys_msg($_LANG['edit_stores_ok'], 0, $links);
    }

}

/**
 *  获取门店列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function stores_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'store_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1=1 ';

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM zs_stores " . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT * 
                FROM zs_stores 
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. " 
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}


?>