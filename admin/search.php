<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态
requireLogin();

$message = '';
$error = '';

// 处理搜索引擎删除
if (isset($_POST['delete']) && isset($_POST['engine_id'])) {
    $engineId = (int)$_POST['engine_id'];
    $stmt = $db->prepare("DELETE FROM search_engines WHERE id = ?");
    $stmt->execute([$engineId]);
    $message = '搜索引擎已删除';
}

// 处理搜索引擎添加/编辑
if (isset($_POST['submit'])) {
    $engineId = isset($_POST['engine_id']) ? (int)$_POST['engine_id'] : null;
    $name = $_POST['name'];
    $url = $_POST['url'];
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    $sortOrder = (int)$_POST['sort_order'];
    $isVisible = isset($_POST['is_visible']) ? 1 : 0;

    if (empty($name) || empty($url)) {
        $error = '请填写必填项';
    } else {
        if ($isDefault) {
            // 如果设置为默认，先取消其他默认搜索引擎
            $stmt = $db->prepare("UPDATE search_engines SET is_default = 0");
            $stmt->execute();
        }

        if ($engineId) {
            // 更新搜索引擎
            $stmt = $db->prepare("UPDATE search_engines SET name = ?, url = ?, is_default = ?, sort_order = ?, is_visible = ? WHERE id = ?");
            $stmt->execute([$name, $url, $isDefault, $sortOrder, $isVisible, $engineId]);
            $message = '搜索引擎已更新';
        } else {
            // 添加新搜索引擎
            $stmt = $db->prepare("INSERT INTO search_engines (name, url, is_default, sort_order, is_visible) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $url, $isDefault, $sortOrder, $isVisible]);
            $message = '搜索引擎已添加';
        }
    }
}

// 获取要编辑的搜索引擎
$editEngine = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM search_engines WHERE id = ?");
    $stmt->execute([$editId]);
    $editEngine = $stmt->fetch();
}

// 获取所有搜索引擎
$stmt = $db->prepare("SELECT * FROM search_engines ORDER BY sort_order");
$stmt->execute();
$engines = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>搜索引擎管理 - 后台管理</title>
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
                <h1>搜索引擎管理</h1>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo e($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php if ($editEngine): ?>
                        <input type="hidden" name="engine_id" value="<?php echo $editEngine['id']; ?>">
                    <?php endif; ?>
                    
                    <!-- 第一行：标题文字 -->
                    <div class="form-row">
                        <div class="form-group" style="flex: 0 0 150px;">
                            <label>排序顺序</label>
                        </div>
                        <div class="form-group" style="flex: 0 0 300px;">
                            <label>搜索引擎名称 *</label>
                        </div>
                        <div class="form-group">
                            <label>搜索URL（使用{query}作为搜索关键词占位符）*</label>
                        </div>
                    </div>
                    
                    <!-- 第二行：输入控件 -->
                    <div class="form-row">
                        <div class="level-group" style="flex: 0 0 150px;">
                            <label>选择顺序</label>
                            <select name="sort_order" class="level-select">
                                <?php for($i = 0; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($editEngine && $editEngine['sort_order'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 0 0 300px;">
                            <input type="text" class="form-input" name="name" value="<?php echo $editEngine ? e($editEngine['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <input type="text" class="form-input" name="url" value="<?php echo $editEngine ? e($editEngine['url']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="is_default" id="is_default" <?php echo ($editEngine && $editEngine['is_default']) ? 'checked' : ''; ?>>
                            <label for="is_default">设为默认搜索引擎</label>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="is_visible" id="is_visible" <?php echo ($editEngine && $editEngine['is_visible']) ? 'checked' : ''; ?>>
                            <label for="is_visible">前台显示</label>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="button">
                        <?php echo $editEngine ? '更新搜索引擎' : '添加搜索引擎'; ?>
                    </button>
                    <?php if ($editEngine): ?>
                        <a href="search.php" class="button secondary">取消编辑</a>
                    <?php endif; ?>
                </form>

                <h2>搜索引擎列表</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>URL</th>
                            <th>默认</th>
                            <th>排序顺序</th>
                            <th>前台显示</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($engines as $engine): ?>
                            <tr>
                                <td><?php echo $engine['id']; ?></td>
                                <td><?php echo e($engine['name']); ?></td>
                                <td><?php echo e($engine['url']); ?></td>
                                <td><?php echo $engine['is_default'] ? '是' : '否'; ?></td>
                                <td><?php echo $engine['sort_order']; ?></td>
                                <td><?php echo $engine['is_visible'] ? '是' : '否'; ?></td>
                                <td>
                                    <form method="get" action="" style="display:inline">
                                        <input type="hidden" name="edit" value="<?php echo $engine['id']; ?>">
                                        <button type="submit" class="button">编辑</button>
                                    </form>
                                    <form method="post" action="" style="display:inline">
                                        <input type="hidden" name="engine_id" value="<?php echo $engine['id']; ?>">
                                        <button type="submit" name="delete" class="button" onclick="return confirm('确定要删除这个搜索引擎吗？')">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>