<?php
require_once 'config.php';
require_once 'db.php';

// 用户登录
function login($username, $password) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            // 更新最后登录时间
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            return true;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

// 用户退出
function logout() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    session_destroy();
}

// 需要登录验证的页面调用此函数
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// 检查用户权限
function checkPermission($requiredPermission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return isset($user['permissions']) && in_array($requiredPermission, explode(',', $user['permissions']));
}

// 注册新用户（仅供安装使用）
function registerUser($username, $password, $email) {
    global $db;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    return $db->query(
        "INSERT INTO users (username, password, email) VALUES (?, ?, ?)",
        [$username, $hashedPassword, $email]
    );
}

// 更新用户密码
function updatePassword($userId, $newPassword) {
    global $db;
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    return $db->query(
        "UPDATE users SET password = ? WHERE id = ?",
        [$hashedPassword, $userId]
    );
}

// 验证邮箱格式
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>