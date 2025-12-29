<?php
// api/game_start.php
require_once 'cors.php';
require_once 'config.php';
require_once 'llm_service.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit();
}

$scenarioType = 'dating_male';
$llm = new LLMService();
$topic = $llm->generateTopic($scenarioType);

$conn = getDbConnection();

// 创建会话
$sql = "INSERT INTO game_sessions (user_id, scenario_type, total_score) VALUES (?, ?, 0)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $scenarioType);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id;
    
    // 记录开场白
    $sql_log = "INSERT INTO chat_logs (session_id, round_index, npc_message, score) VALUES (?, 0, ?, 0)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("is", $session_id, $topic);
    $stmt_log->execute();
    
    echo json_encode([
        'session_id' => $session_id,
        'scenario_type' => $scenarioType,
        'topic' => $topic
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
