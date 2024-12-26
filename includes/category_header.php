<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($category['parent_id'] === null): ?>
        <title><?php echo e($category['name']); ?> - <?php echo e($siteTitle); ?></title>
    <?php else: ?>
        <title><?php echo e($category['name']); ?> - <?php echo e($parentCategory['name']); ?> - <?php echo e($siteTitle); ?></title>
    <?php endif; ?>
    <meta name="description" content="<?php echo e($siteDescription); ?>">
    <meta name="keywords" content="<?php echo e($siteKeywords); ?>">
    <meta name="author" content="<?php echo e($siteAuthor); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* 面包屑导航样式 */
        .breadcrumb-container {
            margin: 20px 0;
            font-size: 14px;
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
            margin-right: 5px;
        }
        .breadcrumb .separator {
            margin-right: 5px;
            color: #6c757d;
        }
        .breadcrumb .current {
            color: #6c757d;
            font-weight: bold;
        }

        /* 分类名称样式 */
        .category-title {
            font-size: 32px;
            font-weight: bold;
            margin: 20px 0;
            color: #333;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-left">
                <h1 class="site-title">
                    <a href="/"><?php echo e($siteTitle); ?></a>
                </h1>
                <nav class="categories-menu">
                    <a href="/" class="nav-item">首页</a>
                    <?php foreach ($categories as $nav_category): ?>
                        <?php if ($nav_category['parent_id'] === null): ?>
                            <div class="category-item">
                                <a href="category.php?category=<?php echo e($nav_category['id']); ?>" 
                                   class="nav-item <?php echo ($categoryId == $nav_category['id']) ? 'active' : ''; ?>">
                                    <?php echo e($nav_category['name']); ?>
                                </a>
                                <?php
                                // 获取该分类的子分类
                                $subNavCategories = array_filter($categories, function($c) use ($nav_category) {
                                    return $c['parent_id'] == $nav_category['id'];
                                });
                                if (!empty($subNavCategories)): ?>
                                    <div class="sub-categories">
                                        <?php foreach ($subNavCategories as $subNav): ?>
                                            <a href="category.php?category=<?php echo e($subNav['id']); ?>"
                                               class="<?php echo ($categoryId == $subNav['id']) ? 'active' : ''; ?>">
                                                <?php echo e($subNav['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
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

    <div class="container">
        <div class="content-wrapper">
            <!-- 面包屑导航 -->
            <div class="breadcrumb-container">
                <nav class="breadcrumb">
                    <a href="/">首页</a>
                    <span class="separator">/</span>
                    <?php if ($category['parent_id'] === null): ?>
                        <span class="current"><?php echo e($category['name']); ?></span>
                    <?php else: ?>
                        <a href="category.php?category=<?php echo e($parentCategory['id']); ?>"><?php echo e($parentCategory['name']); ?></a>
                        <span class="separator">/</span>
                        <span class="current"><?php echo e($category['name']); ?></span>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>

    <script>
        // 页面加载时设置主题
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.querySelector('.theme-text').textContent = '浅色模式';
            } else {
                document.documentElement.removeAttribute('data-theme');
                document.querySelector('.theme-text').textContent = '深色模式';
            }
        });

        // 简单的主题切换函数
        function toggleTheme() {
            const html = document.documentElement;
            const themeText = document.querySelector('.theme-text');
            
            if (html.hasAttribute('data-theme')) {
                html.removeAttribute('data-theme');
                themeText.textContent = '深色模式';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                themeText.textContent = '浅色模式';
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
</body>
</html>
