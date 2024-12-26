<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态
requireLogin();

$message = '';
$error = '';

// 处理分类删除
if (isset($_POST['delete']) && isset($_POST['category_id'])) {
    $categoryId = (int)$_POST['category_id'];
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $message = '分类已删除';
    } catch (PDOException $e) {
        $error = '无法删除包含子分类或链接的分类';
    }
}

// 处理分类添加/编辑
if (isset($_POST['submit'])) {
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $name = $_POST['name'];
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sortOrder = (int)$_POST['sort_order'];

    if (empty($name)) {
        $error = '请填写分类名称';
    } else {
        if ($categoryId) {
            // 更新分类
            $stmt = $db->prepare("UPDATE categories SET name = ?, parent_id = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$name, $parentId, $sortOrder, $categoryId]);
            $message = '分类已更新';
        } else {
            // 添加新分类
            $stmt = $db->prepare("INSERT INTO categories (name, parent_id, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $parentId, $sortOrder]);
            $message = '分类已添加';
        }
    }
}

// 获取要编辑的分类
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// 获取所有分类
$stmt = $db->prepare("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY c.parent_id, c.sort_order");
$stmt->execute();
$allCategories = $stmt->fetchAll();

// 获取一级分类（用于父级分类选择）
$stmt = $db->prepare("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order");
$stmt->execute();
$parentCategories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="admin-container">
                <h1>分类管理</h1>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo e($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>分类名称 *</label>
                            <input type="text" name="name" value="<?php echo $editCategory ? e($editCategory['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>父级分类</label>
                            <select name="parent_id" class="category-select">
                                <option value="">无（作为一级分类）</option>
                                <?php foreach ($parentCategories as $category): ?>
                                    <?php if (!$editCategory || $editCategory['id'] != $category['id']): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($editCategory && $editCategory['parent_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($category['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>排序顺序</label>
                            <input type="number" name="sort_order" value="<?php echo $editCategory ? $editCategory['sort_order'] : 0; ?>">
                        </div>
                    </div>
                    
                    <button type="submit" name="submit" class="button"><?php echo $editCategory ? '更新分类' : '添加分类'; ?></button>
                    <?php if ($editCategory): ?>
                        <a href="categories.php" class="button secondary">取消编辑</a>
                    <?php endif; ?>
                </form>

                <h2>分类列表</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>父级分类</th>
                            <th>排序顺序</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allCategories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo e($category['name']); ?></td>
                                <td><?php echo $category['parent_name'] ? e($category['parent_name']) : '-'; ?></td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td><?php echo $category['created_at']; ?></td>
                                <td>
                                    <form method="get" action="" style="display:inline">
                                        <input type="hidden" name="edit" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="button">编辑</button>
                                    </form>
                                    <form method="post" action="" style="display:inline">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" name="delete" class="button" onclick="return confirm('确定要删除这个分类吗？这将同时删除该分类下的所有链接！')">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>