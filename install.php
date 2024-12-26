<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/install_error.log');

// 检查是否已安装
if (file_exists('includes/config.php')) {
    die('系统已经安装，如需重新安装请删除 includes/config.php 文件。');
}

// 获取当前域名和协议
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$currentDomain = $protocol . $_SERVER['HTTP_HOST'];

// 环境检查
$checkResults = [
    'success' => true,
    'messages' => []
];

// 1. 检查 includes 目录
if (!is_dir('includes')) {
    if (!@mkdir('includes', 0777, true)) {
        $checkResults['success'] = false;
        $checkResults['messages'][] = '未发现存放配置文件目录，请手动创建/includes目录并设置权限为777';
    }
}

if (!is_writable('includes')) {
    $checkResults['success'] = false;
    $checkResults['messages'][] = 'includes 目录不可写，设置权限为777';
}

// 2. 检查 PHP 版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $checkResults['success'] = false;
    $checkResults['messages'][] = 'PHP 版本必须 >= 7.4.0，当前版本：' . PHP_VERSION;
}

// 3. 检查必要的 PHP 扩展
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $checkResults['success'] = false;
        $checkResults['messages'][] = "缺少必要的 PHP 扩展：{$ext}";
    }
}

// 只有在环境检查通过后才处理安装表单
if ($checkResults['success'] && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 1. 首先检查目录权限
        if (!is_dir('includes')) {
            if (!@mkdir('includes', 0777, true)) {
                throw new Exception('无法创建 includes 目录，请检查权限');
            }
        }

        if (!is_writable('includes')) {
            throw new Exception('includes 目录不可写，请设置正确的权限(777)');
        }

        // 2. 测试配置文件写入权限
        if (@file_put_contents('includes/config.php.tmp', 'test') === false) {
            throw new Exception('无法写入配置文件，请检查目录权限');
        }
        @unlink('includes/config.php.tmp');

        // 3. 验证表单数据
        $dbHost = trim($_POST['db_host']);  // 从表单获取数据库地址
        $dbName = trim($_POST['db_name']);
        $dbUser = trim($_POST['db_user']);
        $dbPass = trim($_POST['db_pass']);
        $adminUser = trim($_POST['admin_user']);
        $adminPass = trim($_POST['admin_pass']);
        $adminEmail = trim($_POST['admin_email']);

        if (empty($dbName) || empty($dbUser) || empty($dbPass) || 
            empty($adminUser) || empty($adminPass) || empty($adminEmail)) {
            throw new Exception('所有字段都必须填写');
        }

        // 4. 测试数据库连接
        try {
            $pdo = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败：' . $e->getMessage());
        }

        // 5. 执行安装
        try {
            // 清理旧表
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $tables = ['users', 'categories', 'links', 'search_engines', 'settings'];
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // 执行安装SQL
            $sql = file_get_contents('sql/install.sql');
            if (!$sql) {
                throw new Exception('无法读取安装SQL文件');
            }
            $pdo->exec($sql);

            // 创建管理员账户
            $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute([$adminUser, $hashedPassword, $adminEmail]);

            // 更新网站设置
            $stmt = $pdo->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'site_author'");
            $stmt->execute([$adminUser]);

            // 创建配置文件
            if (!is_dir('includes')) {
                mkdir('includes', 0777, true);
            }

            $configContent = "<?php
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');
define('SITE_URL', '$currentDomain');
define('SESSION_LIFETIME', 30 * 24 * 60 * 60);

// 建立数据库连接
try {
    \$pdo = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException \$e) {
    die(\"数据库连接失败: \" . \$e->getMessage());
}

// 引入函数库
require_once __DIR__ . '/functions.php';

// 获取调试设置
\$debug_mode = getSetting('debug_mode') === '1';
\$display_errors = getSetting('display_errors') === '1';

ini_set('display_errors', \$display_errors);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/error.log');

// Session配置
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 日志清理功能
\$log_file = __DIR__ . '/error.log';
\$max_age = 24 * 60 * 60; // 1天的秒数

if (file_exists(\$log_file) && (time() - filemtime(\$log_file) > \$max_age)) {
    file_put_contents(\$log_file, '');
}";

            if (!file_put_contents('includes/config.php', $configContent)) {
                throw new Exception('无法写入配置文件');
            }

            $installSuccess = true;

        } catch (Exception $e) {
            // 如果数据库操作失败，清理已创建的文件
            if (file_exists('includes/config.php')) {
                @unlink('includes/config.php');
            }
            throw $e;
        }

    } catch (Exception $e) {
        $error = '安装失败：' . $e->getMessage();
        error_log($error);
    }
}

// 获取数据库连接信息（仅在显示表单时需要）
$dbHost = getenv('DB_HOST') ?: 'localhost';
$defaultDbName = getenv('DB_NAME') ?: '';
$defaultDbUser = getenv('DB_USER') ?: '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>安装导航网站</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background: #45a049; }
        .error { color: #ff0000; margin-bottom: 15px; }
        
        .success-message {
            background: white;
            color: #333;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .button-group {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .button-group a {
            flex: 1;
            text-align: center;
            padding: 15px;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        .button-group a:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }
        .check-results {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 4px;
        }
        .check-error {
            background: #ffe6e6;
            border: 1px solid #ffb3b3;
            color: #cc0000;
        }
        .check-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        ul.check-list {
            margin: 10px 0;
            padding-left: 20px;
        }
        .check-list li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$checkResults['success']): ?>
            <div class="check-results check-error">
                <h2>❌ 环境检查未通过</h2>
                <p>请先解决以下问题：</p>
                <ul class="check-list">
                    <?php foreach ($checkResults['messages'] as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (isset($installSuccess)): ?>
            <div class="success-message">
                <h2>🎉 安装完成！</h2>
                <p>网站已经成功安装，您现在可以：</p>
                <div class="button-group">
                    <a href="/admin/">进入管理后台</a>
                    <a href="/">访问网站首页</a>
                </div>
                <p style="margin-top: 20px;">请删除 install.php 文件以确保安全</p>
            </div>
        <?php else: ?>
            <h1>安装导航网站</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>数据库主机地址 <span class="required">*</span></label>
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($dbHost); ?>" required>
                    <small>例如：localhost 或 127.0.0.1 或 数据库服务器IP</small>
                </div>
                <div class="form-group">
                    <label>数据库名称 <span class="required">*</span></label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($defaultDbName); ?>" required>
                </div>
                <div class="form-group">
                    <label>数据库用户名 <span class="required">*</span></label>
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($defaultDbUser); ?>" required>
                </div>
                <div class="form-group">
                    <label>数据库密码 <span class="required">*</span></label>
                    <input type="password" name="db_pass" required>
                </div>
                <div class="form-group">
                    <label>管理员用户名 <span class="required">*</span></label>
                    <input type="text" name="admin_user" required>
                </div>
                <div class="form-group">
                    <label>管理员密码 <span class="required">*</span></label>
                    <input type="password" name="admin_pass" required>
                </div>
                <div class="form-group">
                    <label>管理员邮箱 <span class="required">*</span></label>
                    <input type="email" name="admin_email" required>
                </div>
                <button type="submit">开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>