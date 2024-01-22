<?php

/**
 * ECSHOP ajax
 * ============================================================================
  
 * 网站地址: http://www.yshop100.com；
 * ----------------------------------------------------------------------------
  * ============================================================================
 * $Author: www.yshop100.com $
 * $Id: ajax.php 17063 2010-03-25 06:35:46Z $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ($_REQUEST['act'] == 'tipemail')
{
	require(ROOT_PATH . 'includes/cls_json.php');
	$word_www_yshop100_com = json_str_iconv($_REQUEST['word']);
	$json_www_yshop100_com   = new JSON;
	$result_www_yshop100_com = array('error' => 0, 'message' => '', 'content' => '');
	
	if(!$word_www_yshop100_com ||  strlen($word_www_yshop100_com) > 30)
	{
        $result_www_yshop100_com['error']   = 1;
		die($json_www_yshop100_com->encode($result_www_yshop100_com));
	}
	$word_www_yshop100_com = str_replace(array(' ','*', "\'"), array('', '', ''), $word_www_yshop100_com);

	$email_name_arr = explode("@", $word_www_yshop100_com);
	$email_name = $email_name_arr[0];
    
	$_CFG['email_domain'] =str_replace(" ", "",$_CFG['email_domain']);
	$email_domain_arr = explode(",", str_replace("，",",",$_CFG['email_domain']));

    $logdb=array();
	foreach($email_domain_arr AS $key=>$edomain)
	{
		$email_domain_arr[$key] = $email_name.'@'.$edomain;
	}

	foreach($email_domain_arr AS $email_domain)
    {
		if (stristr($email_domain, $word_www_yshop100_com))
		{
			$logdb[] = $email_domain;
		}
	}
	$smarty->assign('logdb', $logdb);	

	if(count($logdb)==0)
	{
		$result_www_yshop100_com['content'] = '';
	}
	else
	{		
		$result_www_yshop100_com['content'] = $smarty->fetch('library/email_tip.lbi');
	}
	

	die($json_www_yshop100_com->encode($result_www_yshop100_com));
}
?>