<?php

/**
 * ECSHOP 支付插件排序文件
 
 * $Author: derek $
 * $Id: lib_compositor.php 2009-07-24 09:31:42Z derek $
 */

if(isset($modules))
{

    foreach ($modules as $k =>$v)
    {
        if($v['pay_code'] == 'epay')
        {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }

    foreach ($modules as $k =>$v)
    {
        if($v['pay_code'] == 'tenpay')
        {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }
    /* 将快钱直连银行显示在快钱之后 */
    foreach ($modules as $k =>$v)
    {
        if(strpos($v['pay_code'], 'kuaiqian')!== false)
        {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }

    /* 将快钱提升至第一个显示 */
    foreach ($modules as $k =>$v)
    {
        if($v['pay_code'] == 'kuaiqian')
        {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }

}

?>