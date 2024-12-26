<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT 1")->fetch();
    
    echo json_encode([
        'status' => 'ok',
        'timestamp' => time(),
        'ssl' => !empty($_SERVER['HTTPS']),
        'db' => !empty($result),
        'session' => session_status() === PHP_SESSION_ACTIVE
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Service unavailable',
        'timestamp' => time()
    ]);
}
