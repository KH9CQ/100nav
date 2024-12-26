<?php
// 开启错误显示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 设置日志
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

session_start();

// 检查是否已安装
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: /install.php');
    exit;
}

try {
    require_once 'config.php';
    require_once 'db.php';
    
    // 初始化全局数据库连接
    global $db;
    $db = Database::getInstance()->getConnection();
    
    require_once 'functions.php';
} catch (Exception $e) {
    error_log("初始化错误：" . $e->getMessage());
    die("系统初始化失败，请检查错误日志");
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');
?>

