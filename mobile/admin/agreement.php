<?php

/**
 * ECSHOP 协议管理
 
 * $Author: derek $
 * $Id: agreement.php 17217 2020-01-02 06:29:08Z derek $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$exc   = new exchange($ecs->table("agreement"), $db, 'agreement_id');
$exc_order = new exchange($ecs->table("agreement"), $db, 'agreement_id', 'sort_order');

/*------------------------------------------------------ */
//-- 协议列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      '协议列表');
    $smarty->assign('action_link',  array('text' => '添加协议', 'href' => 'agreement.php?act=add'));
    $smarty->assign('full_page',    1);

    $agreement_list = get_agreementlist();

    $smarty->assign('agreement_list',   $agreement_list['agreement']);
    $smarty->assign('filter',       $agreement_list['filter']);
    $smarty->assign('record_count', $agreement_list['record_count']);
    $smarty->assign('page_count',   $agreement_list['page_count']);

    assign_query_info();
    $smarty->display('agreement_list.htm');
}

/*------------------------------------------------------ */
//-- 添加协议
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{

    $smarty->assign('ur_here',     '添加协议');
    $smarty->assign('action_link', array('text' => '协议列表', 'href' => 'agreement.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->assign('agreement', array('sort_order'=>0, 'enabled'=>1));
    $smarty->display('agreement_info.htm');
}
elseif ($_REQUEST['act'] == 'insert')
{
    $agreement_name = isset($_REQUEST['agreement_name']) ? trim($_REQUEST['agreement_name']) : sys_msg('协议名称不能为空');
    $content = isset($_REQUEST['content']) ? str_replace("\n","<br />", $_REQUEST['content']) : sys_msg('协议内容不能为空');
    $start_time = isset($_REQUEST['start_time']) ? local_strtotime($_REQUEST['start_time']) : sys_msg('起始时间不能为空');
    $end_time = isset($_REQUEST['end_time']) ? local_strtotime($_REQUEST['end_time']) : sys_msg('结束时间不能为空');
    $enabled = isset($_REQUEST['enabled']) ? intval($_REQUEST['enabled']) : 0;
    $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 0;

    /*插入数据*/

    $sql = "INSERT INTO ".$ecs->table('agreement')."(agreement_name, content, start_time, end_time ,enabled, sort_order) ".
           "VALUES ('".$agreement_name."', '".$content."', '".$start_time."', '".$end_time."','".$enabled."', '".$sort_order."')";
    $db->query($sql);

    admin_log($agreement_name,'add','agreement');

    /* 清除缓存 */
    clear_cache_files();

    $link[] = array('text' => '返回协议列表', 'href' => 'agreement.php?act=list');

    sys_msg('添加成功', 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑协议
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{

    $sql = "SELECT * FROM " .$ecs->table('agreement'). " WHERE agreement_id='$_REQUEST[id]'";
    $agreement = $db->GetRow($sql);

    $agreement['start_date']    = local_date($GLOBALS['_CFG']['date_format'], $agreement['start_time']);
    $agreement['end_date']      = local_date($GLOBALS['_CFG']['date_format'], $agreement['end_time']);
    $agreement['content'] = str_replace("<br />","\n", $agreement['content']);
    // $agreement['content'] = str_replace("&nbsp;"," ", $agreement['content']);

    $smarty->assign('ur_here',     '修改协议');
    $smarty->assign('action_link', array('text' => '协议列表', 'href' => 'agreement.php?act=list&'));
    $smarty->assign('agreement',    $agreement);
    $smarty->assign('form_action', 'updata');

    assign_query_info();
    $smarty->display('agreement_info.htm');
}
elseif ($_REQUEST['act'] == 'updata')
{

    $agreement_name = isset($_REQUEST['agreement_name']) ? trim($_REQUEST['agreement_name']) : sys_msg('协议名称不能为空');
    $content = isset($_REQUEST['content']) ? str_replace("\n","<br />", $_REQUEST['content']) : sys_msg('协议内容不能为空');
    $start_time = isset($_REQUEST['start_time']) ? local_strtotime($_REQUEST['start_time']) : sys_msg('起始时间不能为空');
    $end_time = isset($_REQUEST['end_time']) ? local_strtotime($_REQUEST['end_time']) : sys_msg('结束时间不能为空');
    $enabled = isset($_REQUEST['enabled']) ? intval($_REQUEST['enabled']) : 0;
    $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 0;

    $sql = "UPDATE " .$ecs->table('agreement'). " SET agreement_name = '".$agreement_name."', content = '".$content."', start_time = '".$start_time."', end_time = '".$end_time."', enabled = '".$enabled."', sort_order = '".$sort_order."' WHERE agreement_id = '".$_POST['id']."'";
    $db->query($sql);

    /* 清除缓存 */
    clear_cache_files();

    admin_log($agreement_name, 'edit', 'agreement');

    $link[] = array('text' => '返回协议列表', 'href' => 'agreement.php?act=list');

    sys_msg('修改成功', 0, $link);
}
/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("sort_order='$val'", $id);

    make_json_result($val);
}

elseif ($_REQUEST['act'] == 'toggle_enabled')
{

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("enabled='$val'", $id);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 删除协议
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{

    $id = intval($_GET['id']);

    $exc->drop($id);

    $url = 'agreement.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}


/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $agreement_list = get_agreementlist();
    $smarty->assign('agreement_list',$agreement_list['agreement']);
    $smarty->assign('filter',       $agreement_list['filter']);
    $smarty->assign('record_count', $agreement_list['record_count']);
    $smarty->assign('page_count',   $agreement_list['page_count']);

    make_json_result($smarty->fetch('agreement_list.htm'), '',
        array('filter' => $brand_list['filter'], 'page_count' => $brand_list['page_count']));
}

/**
 * 获取协议列表
 *
 * @access  public
 * @return  array
 */
function get_agreementlist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 分页大小 */
        $filter = array();

        /* 记录总数以及页数 */
        if (isset($_POST['agreement_name']))
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('agreement') .' WHERE agreement_name = \''.$_POST['agreement_name'].'\'';
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('agreement');
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 查询记录 */
        if (isset($_POST['agreement_name']))
        {
            if(strtoupper(EC_CHARSET) == 'GBK')
            {
                $keyword = iconv("UTF-8", "gb2312", $_POST['agreement_name']);
            }
            else
            {
                $keyword = $_POST['agreement_name'];
            }
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('agreement')." WHERE agreement_name like '%{$keyword}%' ORDER BY sort_order ASC";
        }
        else
        {
            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('agreement')." ORDER BY sort_order ASC";
        }

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $arr = array();
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        /* 格式化日期 */
         $rows['start_date']    = local_date($GLOBALS['_CFG']['date_format'], $rows['start_time']);
         $rows['end_date']      = local_date($GLOBALS['_CFG']['date_format'], $rows['end_time']);

        $arr[] = $rows;
    }

    return array('agreement' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>
