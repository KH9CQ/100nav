<?php
require_once 'db.php';  // 恢复这行

// 获取网站设置
function getSetting($key) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("获取设置失败: " . $e->getMessage());
        return null;
    }
}

// 更新网站设置
function updateSetting($key, $value) {
    global $db;  // 恢复使用全局 $db
    try {
        // 验证输入参数
        $key = trim(strip_tags($key));
        $value = trim(strip_tags($value));
        
        if (empty($key)) {
            error_log("Invalid setting key for update");
            return false;
        }
        
        // 检查设置是否存在
        $stmt = $db->prepare("SELECT * FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
            $result = $stmt->execute([$value, $key]);
        } else {
            $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
            $result = $stmt->execute([$key, $value]);
        }
        return $result;
    } catch(PDOException $e) {
        error_log("Setting update failed: " . $e->getMessage());
        return false;
    }
}

// 获取所有分类
function getCategories($parentId = null) {
    global $db;  // 恢复使用全局 $db
    try {
        // 获取所有分类，不限制 parent_id
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

// 获取分类下的链接
function getLinks($categoryId = null, $level = null, $isPrivate = false) {
    global $db;  // 恢复使用全局 $db
    try {
        // 参数验证和类型转换
        $categoryId = $categoryId !== null ? (int)$categoryId : null;
        $level = $level !== null ? (int)$level : null;
        $isPrivate = (bool)$isPrivate;
        
        // 如果没有指定 level，则默认获取非推荐链接
        if ($level === null) {
            $sql = "SELECT * FROM links WHERE (level IS NULL OR level < 9)";
        } else {
            $sql = "SELECT * FROM links WHERE 1=1";
        }
        $params = [];
        
        if ($categoryId !== null) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($level !== null) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        if (!$isPrivate) {
            $sql .= " AND (is_private = 0 OR is_private IS NULL)";
        }
        
        $sql .= " ORDER BY sort_order ASC, id ASC";
        
        error_log("DEBUG: getLinks SQL: " . $sql);
        error_log("DEBUG: getLinks params: " . json_encode($params));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        error_log("DEBUG: getLinks found " . count($results) . " results");
        if (count($results) > 0) {
            error_log("DEBUG: First result: " . json_encode($results[0]));
        }
        
        return $results;
    } catch(PDOException $e) {
        error_log("ERROR: getLinks failed: " . $e->getMessage());
        return [];
    }
}

// 获取推荐链接
function getFeaturedLinks($isLoggedIn = false) {
    global $db;  // 恢复使用全局 $db
    try {
        $sql = "SELECT * FROM links WHERE level = 9";
        if (!$isLoggedIn) {
            $sql .= " AND (is_private = 0 OR is_private IS NULL)";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        
        error_log("DEBUG: getFeaturedLinks SQL: " . $sql);
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        error_log("DEBUG: getFeaturedLinks found " . count($results) . " results");
        if (count($results) > 0) {
            error_log("DEBUG: First featured result: " . json_encode($results[0]));
        }
        
        return $results;
    } catch(PDOException $e) {
        error_log("ERROR: getFeaturedLinks failed: " . $e->getMessage());
        return [];
    }
}

// 获取搜索引擎列表
function getSearchEngines() {
    global $db;  // 恢复使用全局 $db
    return $db->query("SELECT * FROM search_engines WHERE is_visible = 1 ORDER BY sort_order")->fetchAll();
}

// 获取默认搜索引擎
function getDefaultSearchEngine() {
    global $db;
    return $db->query("SELECT * FROM search_engines WHERE is_default = 1 LIMIT 1")->fetch();
}

// XSS防护
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// CSRF Token生成
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token验证
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    $result = hash_equals($_SESSION['csrf_token'], $token);
    // 使用后立即刷新 token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $result;
}

// 导入CSV文件
function importCSV($file, $categoryId) {
    global $db;
    // 文件类型验证
    $mimeType = mime_content_type($file);
    if (!in_array($mimeType, ['text/csv', 'text/plain'])) {
        error_log("Invalid file type: " . $mimeType);
        return false;
    }
    
    // 文件大小限制
    if (filesize($file) > 5242880) { // 5MB
        error_log("File too large");
        return false;
    }
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (isset($data[0]) && isset($data[1])) {
                $db->query(
                    "INSERT INTO links (name, url, category_id) VALUES (?, ?, ?)",
                    [$data[0], $data[1], $categoryId]
                );
            }
        }
        fclose($handle);
        return true;
    }
    return false;
}

// 检查用户是否登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前登录用户信息
function getCurrentUser() {
    global $db;
    if (isLoggedIn()) {
        return $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
    }
    return null;
}

// 验证密码强度
function validatePassword($password) {
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match("/[A-Z]/", $password) || 
        !preg_match("/[a-z]/", $password) || 
        !preg_match("/[0-9]/", $password)) {
        return false;
    }
    return true;
}
?>