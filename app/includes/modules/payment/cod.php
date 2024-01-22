<?php

/**
 * ECSHOP 货到付款插件
 
 * $Author: liubo $
 * $Id: cod.php 17217 2011-01-19 06:29:08Z liubo $
 */

if(!defined('IN_CTRL'))
{
	die('Hacking alert');
}
/**
 * 类
 */
class cod
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    

    function __construct()
    {
        $this->cod();
    }
	function cod()
    {
    }

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