<?php
// 设置响应头
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// 检查数据库连接
require_once 'includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT 1")->fetch();
    
    echo json_encode([
        'status' => 'ok',
        'timestamp' => time(),
        'ssl' => !empty($_SERVER['HTTPS']),
        'db' => !empty($result)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Service unavailable',
        'timestamp' => time()
    ]);
}
