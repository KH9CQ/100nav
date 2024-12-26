<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// 如果用户已登录，跳转到首页
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        error_log("Login attempt for username: " . $username); // 添加日志
        
        if (empty($username) || empty($password)) {
            $error = '用户名和密码不能为空';
        } else {
            if (login($username, $password)) {
                error_log("Login successful for user: " . $username);
                $lifetime = 30*24*60*60; // 设置会话生命周期为30天
                session_set_cookie_params($lifetime);
                session_regenerate_id(true);
                header('Location: index.php');
                exit;
            } else {
                error_log("Login failed for user: " . $username);
                $error = '用户名或密码错误';
            }
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = '系统错误，请稍后再试';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录 - <?php echo getSetting('site_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        .submit-btn {
            width: 100%;
            padding: 10px;
            background-color: var(--link-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: var(--hover-color);
        }
        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: var(--link-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>用户登录</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo e($error); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">记住我（30天）</label>
            </div>
            <button type="submit" class="submit-btn">登录</button>
        </form>
        <div class="back-link">
            <a href="index.php">返回首页</a>
        </div>
    </div>
</body>
</html>