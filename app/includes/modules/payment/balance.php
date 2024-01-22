<?php

/**
 * ECSHOP 余额支付插件
 
 * $Author: liubo $
 * $Id: balance.php 17217 2011-01-19 06:29:08Z liubo $
 */

if(!defined('IN_CTRL'))
{
	die('Hacking alert');
}

/**
 * 类
 */
class balance
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    
    /* 代码修改_start  By  www.yshop100.com */
    function __construct()
    {
        $this->balance();
    }
	function balance()
    {
    }
	/* 代码修改_end  By  www.yshop100.com */

    /**
     * 提交函数
     */
    function get_code()
    {
        return '';
    }

    /**
     * 处理函数
     */
    function response()
    {
        return;
    }
}

?>