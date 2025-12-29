<?php
// api/user_history.php
require_once 'cors.php';
require_once 'config.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
if ($limit > 50) $limit = 50;
if ($limit < 1) $limit = 20;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit();
}

$conn = getDbConnection();
// 查询该用户所有会话中的有效回答日志
$sql = "
    SELECT 
        l.npc_message, 
        l.user_message, 
        l.score, 
        l.created_at 
    FROM chat_logs l
    JOIN game_sessions s ON l.session_id = s.id
    WHERE s.user_id = ? 
      AND l.user_message IS NOT NULL
    ORDER BY l.created_at DESC
    LIMIT $limit
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode($history);

$stmt->close();
$conn->close();
?>
