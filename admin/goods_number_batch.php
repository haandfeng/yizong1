<?php
/**
 * ECSHOP 商品库存批量上传、修改
 
 * $Author: liubo $
 * $Id: goods_number_batch.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require('includes/lib_goods.php');
require(ROOT_PATH. 'includes/phpexcel/Classes/PHPExcel.php');
require(ROOT_PATH. 'includes/phpexcel/Classes/PHPExcel/IOFactory.php');

/*------------------------------------------------------ */
//-- 批量上传
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('goods_number_batch');

    $step_list = array(
       'add'    => '进货单',
       'change'    => '销售换货单',
       'jhchange'    => '进货换货单',
       // 'return'    => '销售退货单',
       'gift'    => '赠送单'
    );

    $data_format_array = array(
       'xls'    => $_LANG['export_xls']
    );

    $order_form_array = array(
       'yf'    => '宜峰',
       'ks'    => '快闪'
    );

    $smarty->assign('data_format', $data_format_array);
    $smarty->assign('step_list',     $step_list);
    $smarty->assign('order_form_array',     $order_form_array);
    $smarty->assign('act', "confirm");

    /* 参数赋值 */
    $smarty->assign('ur_here', $_LANG['goods_number_add']);
    $smarty->assign('action_link', array('text' => $_LANG['goods_number_log'], 'href' => 'goods_number_batch.php?act=log'));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_number_batch.htm');
}

/*------------------------------------------------------ */
//-- 确认
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'confirm')
{
    /* 检查权限 */
    admin_priv('goods_number_batch');
    $order_form = $_POST['order_form'];

    if(!empty($_FILES['file']['tmp_name'])){
      $file_name = explode('.', $_FILES['file']['name']);
      $file_format = $file_name[count($file_name) - 1];
      if($file_format != $_POST['data_format']){
        sys_msg('上传失败，失败原因：'.$_LANG['file_notfound']);
      }
    }else{
      sys_msg('上传失败，失败原因：'.$_LANG['file_notfound']);
    }


    $inputFileName = $_FILES['file']['tmp_name'];
    
    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    $reader = PHPExcel_IOFactory::createReader($inputFileType);
    $PHPExcel = $reader->load($inputFileName);

    $sheet = $PHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();

    $i=0;
    $page=0;
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowData[$page][$i] = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        // var_dump('A' . $row . ':' . $highestColumn . $row);
        if(preg_match("/^([0-9]+)\/([0-9]+)$/",$rowData[$page][$i][0][1])){
            $page++;
            $i=0;
        }else{
            $i++;
        }
    }

    $ii = 0;
    $batch_goods = "";
    if($_REQUEST['step'] == 'add'){
      for ($i = 0; $i < count($rowData); $i++){
        if($rowData[$i][1][0][1] == '进货单'){
            $order_type = "进货单";
        }else{
          sys_msg('上传失败，失败原因：进货单有误，B2必须为"进货单"');
        }
        if(isset($rowData[$i][2][0][4])){
          $files_sn = explode("：", $rowData[$i][2][0][4]);
          $file_sn = trim($files_sn[1]);
          $row = $db->getOne("SELECT file_sn FROM " . $ecs->table('goods_upload_log') . " WHERE file_sn = '".$file_sn."' AND order_form = '".$order_form."'");
          if($row){
            sys_msg('上传失败，失败原因：进货单号重复');
          }
        }else{
          sys_msg('上传失败，失败原因：进货单有误，"单据编号"不能为空');
        }

        foreach ($rowData[$i] as $lists) {
          foreach ($lists as $list) {
            if(preg_match("/^[0-9]+$/",$list[1])){
              if(isset($list[2])){
                $batch_goods[$ii]['sn'] = $goods_sn = trim($list[2]);
              }else{
                sys_msg('上传失败，失败原因：进货单有误，"商品编码"不能为空');
              }
              if(isset($list[3])){
                $batch_goods[$ii]['name'] = trim($list[3]);
              }else{
                sys_msg('上传失败，失败原因：进货单有误，"商品名称"不能为空');
              }
              if(isset($list[5])){
                $batch_goods[$ii]['number'] = trim(floor($list[5]));
              }else{
                sys_msg('上传失败，失败原因：进货单有误，"数量"不能为空');
              }

              $row = $db->getRow("SELECT goods_id, goods_number FROM " . $ecs->table('goods') . " WHERE goods_sn = '".$goods_sn."'");
              if(!empty($row['goods_id'])){
                $batch_goods[$ii]['id'] = $row['goods_id'];
                $batch_goods[$ii]['old_number'] = $row['goods_number'];
              }else{
                sys_msg('上传失败，失败原因：找不到商品编号'.$goods_sn);
              }
            }
            $ii++;
          }
        }
      }
    }elseif($_REQUEST['step'] == 'change'){
      $s = 0;
      if($rowData[$i][1][0][1] == '销售换货单'){
          $order_type = "销售换货单";
     }else{
        sys_msg('上传失败，失败原因：换货单有误，B2必须为"销售换货单"');
     }
     if(isset($rowData[$i][2][0][4])){
        $files_sn = explode("：", $rowData[$i][2][0][4]);
        $file_sn = trim($files_sn[1]);
        $row = $db->getOne("SELECT file_sn FROM " . $ecs->table('goods_upload_log') . " WHERE file_sn = '".$file_sn."' AND order_form = '".$order_form."'");
        if($row){
          sys_msg('上传失败，失败原因：换货单号重复');
        }
     }else{
        sys_msg('上传失败，失败原因：换货单有误，"单据编号"不能为空');
     }

      foreach ($rowData[$i] as $lists) {
          foreach ($lists as $list) {
            if($list[1] == '入 库 商 品'){
              $s = '';
            }elseif($list[1] == '出 库 商 品'){
              $s = '-';
            }

            if(preg_match("/^[0-9]+$/",$list[1])){
              if(isset($list[2])){
                $batch_goods[$ii]['sn'] = $goods_sn = trim($list[2]);
              }else{
                sys_msg('上传失败，失败原因：换货单有误，"商品编码"不能为空');
              }
              if(isset($list[3])){
                $batch_goods[$ii]['name'] = trim($list[3]);
              }else{
                sys_msg('上传失败，失败原因：换货单有误，"商品名称"不能为空');
              }
              if(isset($list[5])){
                $batch_goods[$ii]['number'] = $s.trim(floor($list[5]));
              }else{
                sys_msg('上传失败，失败原因：换货单有误，"数量"不能为空');
              }

              $row = $db->getRow("SELECT goods_id, goods_number FROM " . $ecs->table('goods') . " WHERE goods_sn = '".$goods_sn."'");
              if(!empty($row['goods_id'])){
                $batch_goods[$ii]['id'] = $row['goods_id'];
                $batch_goods[$ii]['old_number'] = $row['goods_number'];
              }else{
                sys_msg('上传失败，失败原因：找不到商品编号'.$goods_sn);
              }
            }
            $ii++;
          }
        }
    }elseif($_REQUEST['step'] == 'jhchange'){
      $s = 0;
      if($rowData[$i][1][0][1] == '进货换货单'){
          $order_type = "进货换货单";
     }else{
        sys_msg('上传失败，失败原因：换货单有误，B2必须为"'.$order_type.'"');
     }
     if(isset($rowData[$i][2][0][4])){
        $files_sn = explode("：", $rowData[$i][2][0][4]);
        $file_sn = trim($files_sn[1]);
        $row = $db->getOne("SELECT file_sn FROM " . $ecs->table('goods_upload_log') . " WHERE file_sn = '".$file_sn."' AND order_form = '".$order_form."'");
        if($row){
          sys_msg('上传失败，失败原因：换货单号重复');
        }
     }else{
        sys_msg('上传失败，失败原因：换货单有误，"单据编号"不能为空');
     }

      foreach ($rowData[$i] as $lists) {
          foreach ($lists as $list) {
            if($list[1] == '入 库 商 品'){
              $s = '';
            }elseif($list[1] == '出 库 商 品'){
              $s = '-';
            }

            if(preg_match("/^[0-9]+$/",$list[1])){
              if(isset($list[2])){
                $batch_goods[$ii]['sn'] = $goods_sn = trim($list[2]);
              }else{
                sys_msg('上传失败，失败原因：换货单有误，"商品编码"不能为空');
              }
              if(isset($list[3])){
                $batch_goods[$ii]['name'] = trim($list[3]);
              }else{
                sys_msg('上传失败，失败原因：换货单有误，"商品名称"不能为空');
              }
              if(isset($list[5])){
                $batch_goods[$ii]['number'] = $s.trim(floor($list[5]));
              }else{
                sys_msg('上传失败，失败原因：换货单有误，"数量"不能为空');
              }

              $row = $db->getRow("SELECT goods_id, goods_number FROM " . $ecs->table('goods') . " WHERE goods_sn = '".$goods_sn."'");
              if(!empty($row['goods_id'])){
                $batch_goods[$ii]['id'] = $row['goods_id'];
                $batch_goods[$ii]['old_number'] = $row['goods_number'];
              }else{
                sys_msg('上传失败，失败原因：找不到商品编号'.$goods_sn);
              }
            }
            $ii++;
          }
        }
    }elseif($_REQUEST['step'] == 'return'){
      if($rowData[$i][1][0][1] == '销售退货单'){
          $order_type = "销售退货单";
     }else{
        sys_msg('上传失败，失败原因：换货单有误，B2必须为"'.$order_type.'"');
     }
     if(isset($rowData[$i][2][0][4])){
        $files_sn = explode("：", $rowData[$i][2][0][4]);
        $file_sn = trim($files_sn[1]);
        $row = $db->getOne("SELECT file_sn FROM " . $ecs->table('goods_upload_log') . " WHERE file_sn = '".$file_sn."' AND order_form = '".$order_form."'");
        if($row){
          sys_msg('上传失败，失败原因：'.$order_type.'号重复');
        }
     }else{
        sys_msg('上传失败，失败原因：'.$order_type.'有误，"单据编号"不能为空');
     }

      foreach ($rowData[$i] as $lists) {
          foreach ($lists as $list) {
            if(preg_match("/^[0-9]+$/",$list[1])){
              if(isset($list[2])){
                $batch_goods[$ii]['sn'] = $goods_sn = trim($list[2]);
              }else{
                sys_msg('上传失败，失败原因：'.$order_type.'有误，"商品编码"不能为空');
              }
              if(isset($list[3])){
                $batch_goods[$ii]['name'] = trim($list[3]);
              }else{
                sys_msg('上传失败，失败原因：'.$order_type.'有误，"商品名称"不能为空');
              }
              if(isset($list[5])){
                $batch_goods[$ii]['number'] = '-'.trim(floor($list[5]));
              }else{
                sys_msg('上传失败，失败原因：'.$order_type.'有误，"数量"不能为空');
              }

              $row = $db->getRow("SELECT goods_id, goods_number FROM " . $ecs->table('goods') . " WHERE goods_sn = '".$goods_sn."'");
              if(!empty($row['goods_id'])){
                $batch_goods[$ii]['id'] = $row['goods_id'];
                $batch_goods[$ii]['old_number'] = $row['goods_number'];
              }else{
                sys_msg('上传失败，失败原因：找不到商品编号'.$goods_sn);
              }
            }
            $ii++;
          }
        }
    }elseif($_REQUEST['step'] == 'gift'){
      if($rowData[$i][1][0][1] == '赠送单'){
          $order_type = "赠送单";
     }else{
        sys_msg('上传失败，失败原因：换货单有误，B2必须为"赠送单"');
     }
     if(isset($rowData[$i][2][0][3])){
        $files_sn = explode("：", $rowData[$i][2][0][3]);
        $file_sn = trim($files_sn[1]);
        $row = $db->getOne("SELECT file_sn FROM " . $ecs->table('goods_upload_log') . " WHERE file_sn = '".$file_sn."' AND order_form = '".$order_form."'");
        if($row){
          sys_msg('上传失败，失败原因：赠送单号重复');
        }
     }else{
        sys_msg('上传失败，失败原因：赠送单有误，"单据编号"不能为空');
     }

      foreach ($rowData[$i] as $lists) {
          foreach ($lists as $list) {
            if(preg_match("/^[0-9]+$/",$list[1])){
              if(isset($list[2])){
                $batch_goods[$ii]['sn'] = $goods_sn = trim($list[2]);
              }else{
                sys_msg('上传失败，失败原因：赠送单有误，"商品编码"不能为空');
              }
              if(isset($list[3])){
                $batch_goods[$ii]['name'] = trim($list[3]);
              }else{
                sys_msg('上传失败，失败原因：赠送单有误，"商品名称"不能为空');
              }
              if(isset($list[6])){
                $batch_goods[$ii]['number'] = '-'.trim(floor($list[6]));
              }else{
                sys_msg('上传失败，失败原因：赠送单有误，"数量"不能为空');
              }

              $row = $db->getRow("SELECT goods_id, goods_number FROM " . $ecs->table('goods') . " WHERE goods_sn = '".$goods_sn."'");
              if(!empty($row['goods_id'])){
                $batch_goods[$ii]['id'] = $row['goods_id'];
                $batch_goods[$ii]['old_number'] = $row['goods_number'];
              }else{
                sys_msg('上传失败，失败原因：找不到商品编号'.$goods_sn);
              }
            }
            $ii++;
          }
        }
    }else{
      sys_msg('上传失败，失败原因：数据上传有误');
    }
    

    /* 参数赋值 */
    $ur_here = '商品库存批量上传- 导入订单确认';
    $smarty->assign('ur_here', $ur_here);

    $smarty->assign('order_form', $order_form);
    $smarty->assign('file_sn', $file_sn);
    $smarty->assign('batch_goods', $batch_goods);
    $smarty->assign('act', 'upload');
    $smarty->assign('order_type', $order_type);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_number_batch_confirm.htm');
}

/*------------------------------------------------------ */
//-- 批量上传：处理
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'upload')
{
    /* 检查权限 */
    admin_priv('goods_number_batch');

    $batch_goods = $_POST['goods'];
    $file_sn = $_POST['file_sn'];
    $order_form = $_POST['order_form'];
    $goods_sn = $_POST['goods_sn'];
    $order_type = $_POST['order_type'];

    if(!$file_sn){
      sys_msg('上传失败，失败原因：单号为空');
    }

    if(!$order_type){
      sys_msg('上传失败，失败原因：文件类型为空');
    }

    //没有错误导入数据库
    foreach ($batch_goods as $goods_id => $goods) {
      //查询已有库存
      $old_goods_info = getGoodsInfo('goods_number',$goods_id);

      /* 更新商品信息 */
      $sql = "UPDATE " . $ecs->table('goods') .
              " SET goods_number = goods_number + ('".$goods['goods_number']."')" .
              " WHERE goods_id = '".$goods_id."' LIMIT 1";
       $db->query($sql);

       //记录日志
       $goods_info = getGoodsInfo('goods_number',$goods_id);
       $log_str = "[".$goods['goods_sn']."] ".mysql_real_escape_string($goods['goods_name'])."，商品库存：".$old_goods_info['goods_number']." -> ".$goods_info['goods_number'];
       goods_upload_log($_LANG['goods_number_add'], 'edit', $log_str, $order_type, $file_sn, $order_form);
    }

    // /* 显示提示信息，返回导入记录 */
    $link[] = array('href' => 'goods_number_batch.php?act=log', 'text' => $_LANG['01_goods_list']);
    sys_msg($_LANG['batch_upload_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 批量上传记录
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'log'){
   /* 检查权限 */
    admin_priv('goods_number_batch');

    /* 参数赋值 */
    $smarty->assign('ur_here', $_LANG['goods_number_log']);
    $smarty->assign('action_link', array('text' => $_LANG['goods_number_add'], 'href' => 'goods_number_batch.php?act=add'));
    $smarty->assign('full_page',    1);

    $list = get_logs_list();

    $smarty->assign('order_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_number_batch_log.htm');
}

/*------------------------------------------------------ */
//-- 翻页、搜索、排序
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'query')
{
    $list = get_logs_list();

    $smarty->assign('order_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('goods_number_batch_log.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/**
 * 记录管理员的上传操作内容
 *
 * @access  public
 * @param   string      $sn         数据的唯一值
 * @param   string      $action     操作的类型
 * @param   string      $content    操作的内容
 * @param   string      $file_sn    文件编号
 * @return  void
 */
function goods_upload_log($sn = '', $action, $content, $order_type, $file_sn, $order_form)
{
    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('goods_upload_log') . ' (log_time, user_id, order_type, file_sn, order_form, log_info, ip_address) ' .
            " VALUES ('" . gmtime() . "', $_SESSION[admin_id], '".$order_type."', '".$file_sn."', '".$order_form."', '" . stripslashes($content) . "', '" . real_ip() . "')";
    $GLOBALS['db']->query($sql);
}

/**
 * 获取进货单列表
 * @access  public
 * @return  array
 */
function get_logs_list()
{
    /* 查询条件 */
    $filter['keywords']     = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
    if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
    {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
    }
    $filter['sort_by']      = empty($_REQUEST['sort_by']) ? 'log_time' : trim($_REQUEST['sort_by']);
    $filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

    $where = (!empty($filter['keywords'])) ? " AND file_sn LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " : '';

  
    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('goods_upload_log'). " WHERE user_id > 0 " .$where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

  
  
    /* 获取上传数据 */
    $arr = array();
    $sql  = "SELECT * FROM " .$GLOBALS['ecs']->table('goods_upload_log'). " WHERE user_id > 0 $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] ".
            " LIMIT ". $filter['start'] .", $filter[page_size]";
    $res  = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res)){
      $row['user_name'] =  $GLOBALS['db']->getOne("SELECT user_name FROM " .$GLOBALS['ecs']->table('admin_user'). " WHERE user_id = '".$row['user_id']."'");
      $row['log_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
      $arr[] = $row;
    }

    $filter['keywords'] = stripslashes($filter['keywords']);
    $arr = array('item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
  

}

?>