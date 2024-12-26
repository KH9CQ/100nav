<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态
requireLogin();

$message = '';
$error = '';

// 处理用户删除
if (isset($_POST['delete']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    // 防止删除最后一个管理员
    $adminCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($adminCount > 1) {
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
        $message = '用户已删除';
    } else {
        $error = '无法删除最后一个管理员';
    }
}

// 处理用户添加/编辑
if (isset($_POST['submit'])) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($username) || empty($email) || (!$userId && empty($password))) {
        $error = '请填写必填项';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        try {
            if ($userId) {
                // 更新用户
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $db->query(
                        "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?",
                        [$username, $email, $hashedPassword, $userId]
                    );
                } else {
                    $db->query(
                        "UPDATE users SET username = ?, email = ? WHERE id = ?",
                        [$username, $email, $userId]
                    );
                }
                $message = '用户已更新';
            } else {
                // 添加新用户
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)",
                    [$username, $email, $hashedPassword]
                );
                $message = '用户已添加';
            }
        } catch (PDOException $e) {
            $error = '用户名或邮箱已存在';
        }
    }
}

// 获取要编辑的用户
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editUser = $db->query("SELECT * FROM users WHERE id = ?", [$editId])->fetch();
}

// 获取所有用户
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="theme-switch">
            <button onclick="toggleTheme()"></button>
        </div>
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-container">
                <h1>用户管理</h1>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo e($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>用户名 *</label>
                            <input type="text" name="username" value="<?php echo $editUser ? e($editUser['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>邮箱 *</label>
                            <input type="email" name="email" value="<?php echo $editUser ? e($editUser['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>密码 <?php echo $editUser ? '(留空保持不变)' : '*'; ?></label>
                            <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?>>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit" class="button">
                        <?php echo $editUser ? '更新用户' : '添加用户'; ?>
                    </button>
                    
                    <?php if ($editUser): ?>
                        <a href="users.php" class="button secondary">取消编辑</a>
                    <?php endif; ?>
                </form>

                <h2>用户列表</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>邮箱</th>
                            <th>最后登录</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo e($user['username']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td><?php echo $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : '-'; ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="get" action="" style="display:inline">
                                        <input type="hidden" name="edit" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="button">编辑</button>
                                    </form>
                                    <?php if (count($users) > 1): ?>
                                        <form method="post" action="" style="display:inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete" class="button" onclick="return confirm('确定要删除这个用户吗？')">删除</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>