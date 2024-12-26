<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态
requireLogin();

$message = '';
$error = '';

// 处理链接删除
if (isset($_POST['delete']) && isset($_POST['link_id'])) {
    $linkId = (int)$_POST['link_id'];
    $stmt = $db->prepare("DELETE FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $message = '链接已删除';
}

// 处理链接添加/编辑
if (isset($_POST['submit'])) {
    $linkId = isset($_POST['link_id']) ? (int)$_POST['link_id'] : null;
    $name = $_POST['name'];
    $url = $_POST['url'];
    $description = $_POST['description'];
    $categoryId = (int)$_POST['category_id'];
    $level = (int)$_POST['level'];
    $isPrivate = isset($_POST['is_private']) ? 1 : 0;

    if (empty($name) || empty($url) || empty($categoryId)) {
        $error = '请填写必填项';
    } else {
        if ($linkId) {
            // 更新链接
            $stmt = $db->prepare("UPDATE links SET name = ?, url = ?, description = ?, category_id = ?, level = ?, is_private = ? WHERE id = ?");
            $stmt->execute([$name, $url, $description, $categoryId, $level, $isPrivate, $linkId]);
            $message = '链接已更新';
        } else {
            // 添加新链接
            $stmt = $db->prepare("INSERT INTO links (name, url, description, category_id, level, is_private) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $url, $description, $categoryId, $level, $isPrivate]);
            $message = '链接已添加';
        }
    }
}

// 处理CSV导入
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $categoryId = (int)$_POST['import_category_id'];
    if ($categoryId) {
        if (($handle = fopen($_FILES['csv_file']['tmp_name'], "r")) !== FALSE) {
            $isFirstRow = true;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($isFirstRow) {
                    $isFirstRow = false;
                    continue;
                }
                if (isset($data[0]) && isset($data[1])) {
                    $db->query(
                        "INSERT INTO links (name, url, category_id) VALUES (?, ?, ?)",
                        [$data[0], $data[1], $categoryId]
                    );
                }
            }
            fclose($handle);
            $message = 'CSV文件已成功导入';
        } else {
            $error = 'CSV文件读取失败';
        }
    } else {
        $error = '请选择导入分类';
    }
}

// 获取所有分类（包括一级和二级分类）
$categories = $db->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY COALESCE(p.sort_order, c.sort_order), p.name, c.sort_order, c.name
")->fetchAll();

// 整理分类列表，按父子关系组织
$categoriesForSelect = [];
foreach ($categories as $category) {
    if (empty($category['parent_id'])) {
        // 一级分类
        $categoriesForSelect[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'level' => 0
        ];
        // 查找其下的二级分类
        foreach ($categories as $subCategory) {
            if ($subCategory['parent_id'] == $category['id']) {
                $categoriesForSelect[] = [
                    'id' => $subCategory['id'],
                    'name' => "∟ " . $subCategory['name'],
                    'level' => 1
                ];
            }
        }
    }
}

// 获取所有链接 - 修改这部分查询
$stmt = $db->prepare("
    SELECT l.*, 
           c.name as category_name,
           p.name as parent_category_name
    FROM links l 
    LEFT JOIN categories c ON l.category_id = c.id 
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY l.created_at DESC");
$stmt->execute();
$links = $stmt->fetchAll();

// 获取要编辑的链接
$editLink = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$editId]);
    $editLink = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>链接管理 - 后台管理</title>
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
                <h1>链接管理</h1>
                
                <?php if ($message): ?>
                    <div class="message success"><?php echo e($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php if ($editLink): ?>
                        <input type="hidden" name="link_id" value="<?php echo $editLink['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>链接名称 *</label>
                            <input type="text" name="name" value="<?php echo $editLink ? e($editLink['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>链接地址 *</label>
                            <input type="text" name="url" value="<?php echo $editLink ? e($editLink['url']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>链接说明</label>
                            <textarea name="description"><?php echo $editLink ? e($editLink['description']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>分类 *</label>
                            <select name="category_id" class="category-select" required>
                                <option value="">请选择分类</option>
                                <?php foreach ($categoriesForSelect as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            class="level-<?php echo $category['level']; ?>"
                                            <?php echo ($editLink && $editLink['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['level'] == 0 ? $category['name'] : '-- ' . $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="level-group">
                            <label>链接等级（1-9）</label>
                            <select name="level" class="level-select">
                                <?php for($i = 1; $i <= 9; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($editLink && $editLink['level'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="is_private" id="is_private" <?php echo ($editLink && $editLink['is_private']) ? 'checked' : ''; ?>>
                            <label for="is_private">私密链接</label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" name="submit" class="button"><?php echo $editLink ? '更新链接' : '添加链接'; ?></button>
                        <?php if ($editLink): ?>
                            <a href="links.php" class="button secondary">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- 修改导入链接部分的分类选择 -->
                <div class="csv-import">
                    <h2>导入链接</h2>
                    <form method="post" action="" enctype="multipart/form-data" class="import-row">
                        <div class="form-group">
                            <select name="import_category_id" required>
                                <option value="">选择分类</option>
                                <?php foreach ($categoriesForSelect as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            class="level-<?php echo $category['level']; ?>">
                                        <?php echo $category['level'] == 0 ? $category['name'] : '-- ' . $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="file-upload">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            <label for="csv_file">选择文件</label>
                        </div>
                        
                        <button type="submit" name="import" class="button">导入CSV</button>
                    </form>
                </div>

                <h2>链接列表</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>地址</th>
                            <th>分类</th>
                            <th>等级</th>
                            <th>私密</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td><?php echo $link['id']; ?></td>
                                <td><?php echo e($link['name']); ?></td>
                                <td><a href="<?php echo e($link['url']); ?>" target="_blank"><?php echo e($link['url']); ?></a></td>
                                <td><?php 
                                    if ($link['parent_category_name']) {
                                        echo e($link['parent_category_name']) . ' > ' . e($link['category_name']);
                                    } else {
                                        echo e($link['category_name']); 
                                    }
                                ?></td>
                                <td><?php echo $link['level']; ?></td>
                                <td><?php echo $link['is_private'] ? '是' : '否'; ?></td>
                                <td><?php echo $link['created_at']; ?></td>
                                <td>
                                    <form method="get" action="" style="display:inline">
                                        <input type="hidden" name="edit" value="<?php echo $link['id']; ?>">
                                        <button type="submit" class="button">编辑</button>
                                    </form>
                                    <form method="post" action="" style="display:inline">
                                        <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                        <button type="submit" name="delete" class="button" onclick="return confirm('确定要删除这个链接吗？')">删除</button>
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
</body>
</html>