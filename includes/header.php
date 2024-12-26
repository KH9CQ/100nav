<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查必要的变量是否已经设置
if (!isset($title)) {
    $title = getSetting('site_title');
}
if (!isset($desc)) {
    $desc = getSetting('site_description');
}
if (!isset($keywords)) {
    $keywords = getSetting('site_keywords');
}
if (!isset($author)) {
    $author = getSetting('site_author');
}

// 调试输出
error_log("Title: " . $title);
error_log("Description: " . $desc);
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?><?php echo isset($desc) ? ' - ' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') : ''; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- 添加网站图标支持 -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <header class="fade-in">
        <div class="header-container">
            <div class="header-left">
                <h1 class="site-title">
                    <a href="/"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
                </h1>
                <nav>
                    <ul class="categories-menu">
                        <div class="category-item">
                            <a href="index.php">首页</a>
                        </div>
                        <?php foreach ($categories as $category): ?>
                            <?php if (!$category['parent_id']): ?>
                                <li class="category-item">
                                    <a href="category.php?category=<?php echo e($category['id']); ?>"><?php echo e($category['name']); ?></a>
                                    <?php
                                    $subs = array_filter($categories, function($sub) use ($category) {
                                        return $sub['parent_id'] == $category['id'];
                                    });
                                    if (!empty($subs)):
                                    ?>
                                    <div class="sub-categories">
                                        <?php foreach ($subs as $sub): ?>
                                            <a href="category.php?category=<?php echo e($sub['id']); ?>"><?php echo e($sub['name']); ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
            <div class="header-right">
                <div class="theme-switch">
                    <button onclick="toggleTheme()" aria-label="切换主题">
                        <span class="theme-icon">🌓</span>
                        <span class="theme-text">深色模式</span>
                    </button>
                </div>
                <div class="user-buttons">
                    <?php if (isLoggedIn()): ?>
                        <a href="admin/index.php" class="admin-btn">后台管理</a>
                        <a href="logout.php" class="logout-btn">退出登录</a>
                    <?php else: ?>
                        <a href="login.php" class="login-btn">登录</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
</body>
</html>