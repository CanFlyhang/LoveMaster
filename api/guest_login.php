<?php
// api/guest_login.php
require_once 'cors.php';
require_once 'config.php';

// 生成 UUID (简单版)
function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$conn = getDbConnection();

$uuid = uuidv4();
$openid = 'guest_' . substr($uuid, 0, 8);
$nickname = '游客' . rand(1000, 9999);
$gender = 0;
$credits = 10;

$sql = "INSERT INTO users (openid, nickname, gender, credits) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $openid, $nickname, $gender, $credits);

if ($stmt->execute()) {
    echo json_encode([
        'user_id' => $stmt->insert_id,
        'nickname' => $nickname,
        'credits' => $credits
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Guest login failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
