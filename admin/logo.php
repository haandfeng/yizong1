<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . '/includes/lib_order.php');
require(ROOT_PATH . 'includes/cls_image.php');

if($_REQUEST['act']=='add')
{
    $image = new cls_image();
    $tuijie_img_name = basename($image->upload_image($_FILES['file'],'tuijie_img'));
    //存在data目录下
    $tuijie_img = '/data/tuijie_img/'. $tuijie_img_name;
    $time = time();
    //$sql = "insert into ecs_logo(path,is_show,addtime)values('$tuijie_img',1,'$time')";
    $sql = "update ecs_logo set path='$tuijie_img',addtime='$time' where id=1";
    
    $res = $GLOBALS['db']->query($sql);
    if($res){
        echo '<script>
            alert("修改成功");
        </script>';
    }else{
        echo '<script>
            alert("修改失败");
        </script>';
    }
}


$smarty->display('logo.htm');





