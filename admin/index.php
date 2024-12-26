<?php
require_once '../includes/config.php';
require_once '../includes/db.php';  // 继续使用 db.php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态
requireLogin();

// 获取统计数据
$stats = [
    'links' => $db->query("SELECT COUNT(*) as count FROM links")->fetchColumn(),
    'categories' => $db->query("SELECT COUNT(*) as count FROM categories")->fetchColumn(),
    'private_links' => $db->query("SELECT COUNT(*) as count FROM links WHERE is_private = 1")->fetchColumn(),
    'featured_links' => $db->query("SELECT COUNT(*) as count FROM links WHERE level = 9")->fetchColumn()
];

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?php echo e(getSetting('site_title')); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-container">
                <h1>管理后台</h1>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>总链接数</h3>
                        <div class="stat-number"><?php echo (int)$stats['links']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>分类数量</h3>
                        <div class="stat-number"><?php echo (int)$stats['categories']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>私密链接</h3>
                        <div class="stat-number"><?php echo (int)$stats['private_links']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>推荐链接</h3>
                        <div class="stat-number"><?php echo (int)$stats['featured_links']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>