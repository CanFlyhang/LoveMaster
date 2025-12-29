<?php
// api/game_refresh.php
require_once 'cors.php';
require_once 'config.php';
require_once 'llm_service.php';

$input = json_decode(file_get_contents('php://input'), true);
$session_id = isset($input['session_id']) ? intval($input['session_id']) : 0;

if ($session_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID required']);
    exit();
}

$conn = getDbConnection();

// 获取 Session 信息
$sql = "SELECT * FROM game_sessions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    http_response_code(404);
    echo json_encode(['error' => 'Session not found']);
    exit();
}

$llm = new LLMService();
$topic = $llm->generateTopic($session['scenario_type']);

// 更新日志
// 找到该 session 最近的一条日志
$sql_log = "SELECT id FROM chat_logs WHERE session_id = ? ORDER BY round_index DESC LIMIT 1";
$stmt_log = $conn->prepare($sql_log);
$stmt_log->bind_param("i", $session_id);
$stmt_log->execute();
$log = $stmt_log->get_result()->fetch_assoc();

if ($log) {
    $sql_update = "UPDATE chat_logs SET npc_message = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $topic, $log['id']);
    $stmt_update->execute();
} else {
    // 理论上不应该发生，但防守一下
    $sql_insert = "INSERT INTO chat_logs (session_id, round_index, npc_message, score) VALUES (?, 0, ?, 0)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("is", $session_id, $topic);
    $stmt_insert->execute();
}

echo json_encode(['topic' => $topic]);

$stmt->close();
$conn->close();
?>
