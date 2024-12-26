<?php
require_once 'includes/init.php';

// 获取网站设置
$siteTitle = getSetting('site_title');
$siteDescription = getSetting('site_description');
$siteKeywords = getSetting('site_keywords');
$siteAuthor = getSetting('site_author');

// 获取所有分类用于导航
$categories = Database::getInstance()->getConnection()->query("SELECT * FROM categories ORDER BY sort_order, id ASC")->fetchAll();

// 获取分类ID并确保为整数
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
error_log("Processing category ID: " . $categoryId);

// 初始化变量
$category = null;
$parentCategory = null;
$subCategories = [];
$links = [];

if ($categoryId > 0) {
    $db = Database::getInstance()->getConnection();

    // 获取当前分类信息
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Category data: " . print_r($category, true));

    if ($category) {
        if ($category['parent_id'] === null) {
            // 一级分类：获取子分类
            error_log("Processing parent category: {$category['name']} (ID: {$category['id']})");

            // 获取子分类
            $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY id ASC");
            $stmt->execute([$category['id']]);
            $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($subCategories) . " subcategories");

            // 获取当前分类的链接
            $stmt = $db->prepare("SELECT * FROM links WHERE category_id = ? AND (is_private = 0 OR ? = 1) ORDER BY id ASC");
            $stmt->execute([$category['id'], isLoggedIn()]);
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($links) . " links for parent category");

            // 获取所有子分类的链接
            $subCategoryLinks = [];
            foreach ($subCategories as $sub) {
                $stmt = $db->prepare("SELECT l.*, c.name as category_name FROM links l 
                                    JOIN categories c ON l.category_id = c.id 
                                    WHERE l.category_id = ? AND (l.is_private = 0 OR ? = 1) 
                                    ORDER BY l.sort_order, l.id ASC");
                $stmt->execute([$sub['id'], isLoggedIn()]);
                $subCategoryLinks[$sub['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

        } else {
            // 二级分类：获取父分类信息
            error_log("Processing child category: {$category['name']} (ID: {$category['id']}, Parent ID: {$category['parent_id']})");

            $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
            $stmt->execute([$category['parent_id']]);
            $parentCategory = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Parent category data: " . print_r($parentCategory, true));

            // 获取当前分类的链接
            $stmt = $db->prepare("SELECT * FROM links WHERE category_id = ? AND (is_private = 0 OR ? = 1) ORDER BY id ASC");
            $stmt->execute([$category['id'], isLoggedIn()]);
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($links) . " links for child category");
        }
    }
}

// 使用分类专用的头部
include 'includes/category_header.php';
?>

<div class="category-content fade-in">
    <?php if ($category): ?>
        <div class="category-header">
            <h1><?php echo e($category['name']); ?></h1>
        </div>

        <?php if ($category['parent_id'] === null): ?>
            <!-- 显示当前一级分类的链接 -->
            <?php if (!empty($links)): ?>
                <div class="category-section">
                    <h2>分类链接</h2>
                    <div class="links-grid">
                        <?php foreach ($links as $link): ?>
                            <div class="link-card">
                                <div class="link-title">
                                    <a href="<?php echo e($link['url']); ?>" target="_blank">
                                        <?php echo e($link['name']); ?>
                                    </a>
                                </div>
                                <?php if (!empty($link['description'])): ?>
                                    <p class="link-description"><?php echo e($link['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 显示子分类和它们的链接 -->
            <?php if (!empty($subCategories)): ?>
                <?php foreach ($subCategories as $sub): ?>
                    <?php if (!empty($subCategoryLinks[$sub['id']])): ?>
                        <div class="category-section">
                            <h2><?php echo e($sub['name']); ?></h2>
                            <div class="links-grid">
                                <?php foreach ($subCategoryLinks[$sub['id']] as $link): ?>
                                    <div class="link-card">
                                        <div class="link-title">
                                            <a href="<?php echo e($link['url']); ?>" target="_blank">
                                                <?php echo e($link['name']); ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($link['description'])): ?>
                                            <p class="link-description"><?php echo e($link['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php else: ?>
            <!-- 显示二级分类的链接 -->
            <?php if (!empty($links)): ?>
                <div class="category-section">
                    <div class="links-grid">
                        <?php foreach ($links as $link): ?>
                            <div class="link-card">
                                <div class="link-title">
                                    <a href="<?php echo e($link['url']); ?>" target="_blank">
                                        <?php echo e($link['name']); ?>
                                    </a>
                                </div>
                                <?php if (!empty($link['description'])): ?>
                                    <p class="link-description"><?php echo e($link['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-links">该分类下暂无链接</div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="error">分类不存在</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
