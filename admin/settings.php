<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态
requireLogin();

$message = '';
$error = '';

// 处理设置保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    foreach (['site_title', 'site_description', 'site_keywords', 'site_author'] as $key) {
        $value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        
        if (empty($value) && $key == 'site_title') {
            $error = '网站标题不能为空';
            break;
        }
        
        if (!updateSetting($key, $value)) {
            $error = '保存设置失败';
            break;
        }
    }

    if (!$error) {
        $message = '设置已保存';
    }
}

// 获取当前设置后立即验证更新是否成功
$siteTitle = getSetting('site_title');
$siteDescription = getSetting('site_description');
$siteKeywords = getSetting('site_keywords');
$siteAuthor = getSetting('site_author');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置 - 后台管理</title>
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
                <h1>网站设置</h1>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo e($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>网站标题 *</label>
                            <input type="text" name="site_title" value="<?php echo e($siteTitle); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>网站关键词（用英文逗号分隔）</label>
                            <input type="text" name="site_keywords" value="<?php echo e($siteKeywords); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>网站描述</label>
                            <textarea name="site_description"><?php echo e($siteDescription); ?></textarea>
                        </div>
                        
                        <div class="form-group" style="flex: 0 0 20%;">
                            <label>网站作者</label>
                            <input type="text" name="site_author" value="<?php echo e($siteAuthor); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="button">保存设置</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>