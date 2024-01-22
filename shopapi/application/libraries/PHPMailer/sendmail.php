<?php
include "class.phpmailer.php";
//include "class.smtp.php";
$mail=new PHPMailer();
// 设置PHPMailer使用SMTP服务器发送Email
$mail->IsSMTP();

// 设置邮件的字符编码，若不指定，则为'UTF-8'
$mail->CharSet='UTF-8';
$address = "1129297680@qq.com";
// 添加收件人地址，可以多次使用来添加多个收件人
$mail->AddAddress($address);
$message = "asdfasdflsadfljsd";
// 设置邮件正文
$mail->Body=$message;

// 设置邮件头的From字段。
$mail->From='shilh123@sina.cn';

// 设置发件人名字
$mail->FromName='LilyRecruit';
$title = "sdfasdfsd";
// 设置邮件标题
$mail->Subject=$title;

// 设置SMTP服务器。
$mail->Host='smtp.sina.cn';

// 设置为"需要验证"
$mail->SMTPAuth=true;

// 设置用户名和密码。
$mail->Username='shilh123@sina.cn';
$mail->Password='shilihui0107';

// 发送邮件。
return($mail->Send());