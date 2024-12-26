<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// 调用注销函数
logout();

// 重定向到登录页面
header('Location: login.php');
exit;
?>