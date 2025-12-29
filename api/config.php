<?php
// api/config.php

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'xzh060822');
define('DB_NAME', 'high_eq_game');

// LLM 配置
define('LLM_API_URL', 'https://api.deepseek.com/v1/chat/completions');
define('LLM_API_KEY', ''); // 请填入你的 DeepSeek API Key
define('LLM_MODEL', 'deepseek-chat');

// 连接数据库
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
