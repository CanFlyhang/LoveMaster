<?php
// api/user_share.php
require_once 'cors.php';
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit();
}

$conn = getDbConnection();
$sql = "UPDATE users SET credits = credits + 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Credits added']);
} else {
    http_response_code(500);
    echo json_encode(['error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
