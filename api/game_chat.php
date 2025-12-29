<?php
// api/game_chat.php
require_once 'cors.php';
require_once 'config.php';
require_once 'llm_service.php';

$input = json_decode(file_get_contents('php://input'), true);
$session_id = isset($input['session_id']) ? intval($input['session_id']) : 0;
$message = isset($input['message']) ? trim($input['message']) : '';

if ($session_id <= 0 || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$conn = getDbConnection();

// 1. 获取 Session
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

// 2. 检查体力
$sql_user = "SELECT credits FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $session['user_id']);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

if (!$user || $user['credits'] <= 0) {
    http_response_code(403);
    echo json_encode(['error' => 'NO_CREDITS', 'message' => '体力不足']);
    exit();
}

// 3. 扣除体力
$sql_deduct = "UPDATE users SET credits = credits - 1 WHERE id = ?";
$stmt_deduct = $conn->prepare($sql_deduct);
$stmt_deduct->bind_param("i", $session['user_id']);
$stmt_deduct->execute();

// 4. 获取历史记录
$sql_history = "SELECT npc_message, user_message FROM chat_logs WHERE session_id = ? ORDER BY round_index ASC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $session_id);
$stmt_history->execute();
$res_history = $stmt_history->get_result();

$history = [];
while ($row = $res_history->fetch_assoc()) {
    if ($row['npc_message']) $history[] = ['role' => 'assistant', 'content' => $row['npc_message']];
    if ($row['user_message']) $history[] = ['role' => 'user', 'content' => $row['user_message']];
}

// 5. 调用 LLM
$llm = new LLMService();
$aiResult = $llm->getResponse($session['scenario_type'], $history, $message);

$score = isset($aiResult['score']) ? intval($aiResult['score']) : 0;
$analysis = json_encode($aiResult, JSON_UNESCAPED_UNICODE);

// 6. 更新 Session
$newTotalScore = $session['total_score'] + $score;
$newRound = $session['round_count'] + 1;

$sql_update_session = "UPDATE game_sessions SET total_score = ?, round_count = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update_session);
$stmt_update->bind_param("iii", $newTotalScore, $newRound, $session_id);
$stmt_update->execute();

// 7. 写入/更新 Chat Log
// 查找是否有 pending 的 log (只有 npc_message 没有 user_message)
$sql_pending = "SELECT id FROM chat_logs WHERE session_id = ? AND user_message IS NULL ORDER BY id DESC LIMIT 1";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $session_id);
$stmt_pending->execute();
$pending = $stmt_pending->get_result()->fetch_assoc();

if ($pending) {
    $sql_log_update = "UPDATE chat_logs SET user_message = ?, score = ?, analysis = ? WHERE id = ?";
    $stmt_log_up = $conn->prepare($sql_log_update);
    $stmt_log_up->bind_param("sisi", $message, $score, $analysis, $pending['id']);
    $stmt_log_up->execute();
} else {
    $sql_log_insert = "INSERT INTO chat_logs (session_id, round_index, npc_message, user_message, score, analysis) VALUES (?, ?, '', ?, ?, ?)";
    $stmt_log_in = $conn->prepare($sql_log_insert);
    $stmt_log_in->bind_param("issis", $session_id, $newRound, $message, $score, $analysis);
    $stmt_log_in->execute();
}

// 8. 更新排行榜
$rankKey = $session['scenario_type'];
$sql_rank_check = "SELECT score FROM leaderboards WHERE user_id = ? AND category = ?";
$stmt_rank_check = $conn->prepare($sql_rank_check);
$stmt_rank_check->bind_param("is", $session['user_id'], $rankKey);
$stmt_rank_check->execute();
$rank = $stmt_rank_check->get_result()->fetch_assoc();

if (!$rank) {
    $sql_rank_insert = "INSERT INTO leaderboards (user_id, category, score) VALUES (?, ?, ?)";
    $stmt_rank_in = $conn->prepare($sql_rank_insert);
    $stmt_rank_in->bind_param("isi", $session['user_id'], $rankKey, $score);
    $stmt_rank_in->execute();
} else if ($score > $rank['score']) {
    $sql_rank_update = "UPDATE leaderboards SET score = ? WHERE user_id = ? AND category = ?";
    $stmt_rank_up = $conn->prepare($sql_rank_update);
    $stmt_rank_up->bind_param("iis", $score, $session['user_id'], $rankKey);
    $stmt_rank_up->execute();
}

echo json_encode([
    'score' => $score,
    'analysis' => $aiResult['analysis'] ?? '',
    'best_reply' => $aiResult['best_reply'] ?? '',
    'remaining_credits' => $user['credits'] - 1
]);

$stmt->close();
$conn->close();
?>
