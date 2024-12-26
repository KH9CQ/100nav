<?php
require_once 'includes/init.php';

// 获取网站设置
$title = trim(strip_tags(getSetting('site_title')));
$desc = trim(strip_tags(getSetting('site_description')));
$keywords = trim(strip_tags(getSetting('site_keywords')));
$author = trim(strip_tags(getSetting('site_author')));

// 获取推荐链接 - 使用 Database 而不是 DatabaseConnection
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM links WHERE level = 9 AND (is_private = 0 OR ? = 1) ORDER BY id ASC");
$stmt->execute([isLoggedIn()]);
$featuredLinks = $stmt->fetchAll();
error_log("DEBUG: Direct query found " . count($featuredLinks) . " featured links");

// 获取分类和对应的链接
$categories = getCategories();
$categoryLinks = [];
foreach ($categories as $category) {
    if ($category['parent_id'] === null) { // 只处理主分类
        $stmt = $db->prepare(
            "SELECT l.* FROM links l 
            LEFT JOIN categories c ON l.category_id = c.id 
            WHERE (c.id = ? OR c.parent_id = ?) 
            AND (l.is_private = 0 OR ? = 1)
            AND (l.level < 9 OR l.level IS NULL)
            ORDER BY l.id ASC"
        );
        $stmt->execute([$category['id'], $category['id'], isLoggedIn()]);
        $categoryLinks[$category['id']] = $stmt->fetchAll();
        error_log("DEBUG: Category {$category['name']} has " . count($categoryLinks[$category['id']]) . " links");
    }
}

// 获取搜索引擎列表，重点获取默认引擎
$stmt = $db->prepare("SELECT * FROM search_engines WHERE is_visible = 1 ORDER BY is_default DESC, sort_order ASC");
$stmt->execute();
$searchEngines = $stmt->fetchAll();

// 获取默认搜索引擎
$stmt = $db->prepare("SELECT * FROM search_engines WHERE is_default = 1 LIMIT 1");
$stmt->execute();
$defaultEngine = $stmt->fetch();

// 如果没有设置默认搜索引擎，使用第一个引擎
if (!$defaultEngine && !empty($searchEngines)) {
    $defaultEngine = $searchEngines[0];
}

// 检查数据库中的数据
$counts = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM links WHERE level = 9) as featured_count,
        (SELECT COUNT(*) FROM links WHERE level < 9 OR level IS NULL) as normal_count,
        (SELECT COUNT(*) FROM categories) as category_count
")->fetch();

error_log("DEBUG: Database counts - Featured: {$counts['featured_count']}, Normal: {$counts['normal_count']}, Categories: {$counts['category_count']}");

// 在包含 header.php 之前检查所有必要的变量
error_log("DEBUG: Checking variables before including header:");
error_log("DEBUG: categories: " . (isset($categories) ? "set" : "not set"));
error_log("DEBUG: featuredLinks: " . (isset($featuredLinks) ? "set" : "not set"));
error_log("DEBUG: categoryLinks: " . (isset($categoryLinks) ? "set" : "not set"));

include 'includes/header.php';
?>

<div class="search-wrapper fade-in">
    <div class="search-engines">
        <?php foreach ($searchEngines as $engine): ?>
            <button type="button" 
                    class="engine-btn <?php echo ($engine['is_default'] ? 'active' : ''); ?>" 
                    data-engine="<?php echo e($engine['name']); ?>"
                    data-url="<?php echo e($engine['url']); ?>">
                <?php echo e($engine['name']); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <!-- 移除 method 和 action，简化表单结构 -->
    <div class="search-container" id="searchForm">
        <input type="text" class="search-input" placeholder="输入关键词搜索...">
        <button type="button" class="search-button">搜索</button>
    </div>
</div>

<?php if (!empty($featuredLinks)): ?>
    <div class="featured-links fade-in">
        <h2>推荐链接</h2>
        <div class="links-grid">
            <?php foreach ($featuredLinks as $link): ?>
                <div class="link-card">
                    <div class="link-title">
                        <a href="<?php echo e($link['url']); ?>" target="_blank"><?php echo e($link['name']); ?></a>
                    </div>
                    <?php if (!empty($link['description'])): ?>
                        <p class="link-description"><?php echo e($link['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php foreach ($categories as $category): ?>
    <?php if (!$category['parent_id'] && !empty($categoryLinks[$category['id']])): ?>
        <div class="category-section fade-in">
            <h2><?php echo e($category['name']); ?></h2>
            <div class="links-grid">
                <?php foreach ($categoryLinks[$category['id']] as $link): ?>
                    <div class="link-card">
                        <div class="link-title">
                            <a href="<?php echo e($link['url']); ?>" target="_blank"><?php echo e($link['name']); ?></a>
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

<?php include 'includes/footer.php'; ?>
<script src="assets/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const defaultEngine = <?php echo json_encode($defaultEngine); ?>;
    const searchForm = document.getElementById('searchForm');
    const searchInput = searchForm.querySelector('.search-input');
    const searchButton = searchForm.querySelector('.search-button');
    let currentEngine = defaultEngine;
    
    // 确保默认引擎按钮处于激活状态
    if (defaultEngine) {
        const defaultButton = document.querySelector(`.engine-btn[data-engine="${defaultEngine.name}"]`);
        if (defaultButton && !document.querySelector('.engine-btn.active')) {
            defaultButton.classList.add('active');
        }
    }
    
    // 搜索引擎切换
    document.querySelectorAll('.engine-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.engine-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            currentEngine = {
                name: this.getAttribute('data-engine'),
                url: this.getAttribute('data-url')
            };
        });
    });

    // 处理搜索按钮点击和回车事件
    function performSearch() {
        const query = searchInput.value.trim();
        if (query && currentEngine) {
            const searchUrl = currentEngine.url.replace('{query}', encodeURIComponent(query));
            // 使用 location.href 替代 window.open
            const searchWindow = window.open('', '_blank');
            if (searchWindow) {
                searchWindow.location.href = searchUrl;
            }
        }
    }

    // 绑定搜索按钮点击事件
    searchButton.addEventListener('click', performSearch);

    // 绑定回车键事件
    searchInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            performSearch();
        }
    });
});
</script>
</body>
</html>