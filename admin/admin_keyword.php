<?php

/**
 * ECSHOP 管理中心导购员管理
 
 * $Author: wanglei $
 * $Id: sellers.php 15013 2009-05-13 09:31:42Z wanglei $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 导购员列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{ 

    /* 查询 */
    $result = sellers_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', '屏蔽信息'); // 当前导航
    //$smarty->assign('action_link', array('href' => 'sellers.php?act=add', 'text' => $_LANG['add_sellers']));

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('sellers_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('admin_keyword.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{ 
    $result = sellers_list();

    $smarty->assign('sellers_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('admin_keyword.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 删除导购员
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{

    $id = intval($_REQUEST['id']);
    admin_log('编号'.$id,'remove','keyword');
    $sql = "SELECT *
            FROM ecs_keyword_log
            WHERE id = '$id'";
    $seller = $db->getRow($sql, TRUE);

    if ($seller['id'])
    {
        $sql = "DELETE FROM ecs_keyword_log WHERE id = '$id'";
        $db->query($sql);
 
    }

    $url = 'admin_keyword.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");

    exit;
}
  
/*------------------------------------------------------ */
//-- 提交添加、编辑导购员
/*------------------------------------------------------ */
elseif (in_array($_REQUEST['act'], array('insert', 'update')))
{ 

    if ($_REQUEST['act'] == 'insert')
    { 
		$word=$_POST['word'];
		$user=$_SESSION['admin_name']; 
        /* 判断名称是否重复 */
        $sql = "SELECT id
                FROM ecs_keyword_log
                WHERE word = '" . $word . "' ";
        if ($db->getOne($sql))
        {
            sys_msg('已存在屏蔽信息');
        }
		admin_log($word,'add','keyword');
		$sql="insert into ecs_keyword_log(word,user,createtime)values('".$word."','".$user."',now())"; 
		$GLOBALS['db']->query($sql);
 

        /* 提示信息 */
        $links = array(
                       array('href' => 'admin_keyword.php?act=list', 'text' => '屏蔽信息')
                       );
        sys_msg('添加成功', 0, $links);

    }
 

}

/**
 *  获取导购员列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function sellers_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'st.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'desc' : trim($_REQUEST['sort_order']);

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
        $sql = "SELECT COUNT(*) FROM ecs_keyword_log st  " . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT * 
                FROM ecs_keyword_log st  
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