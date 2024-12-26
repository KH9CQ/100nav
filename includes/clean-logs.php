<?php
$log_file = __DIR__ . '/error.log';
$max_age = 24 * 60 * 60; // 1天的秒数

if (file_exists($log_file)) {
    if (time() - filemtime($log_file) > $max_age) {
        // 直接清空日志文件
        file_put_contents($log_file, '');
    }
}
?>
