<?php
// api/leaderboard.php
require_once 'cors.php';
require_once 'config.php';

$category = isset($_GET['category']) ? $_GET['category'] : '';

if (empty($category)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category required']);
    exit();
}

$conn = getDbConnection();

$sql = "
    SELECT l.score, u.nickname, l.updated_at 
    FROM leaderboards l
    JOIN users u ON l.user_id = u.id
    WHERE l.category = ?
    ORDER BY l.score DESC
    LIMIT 50
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

$leaderboard = [];
while ($row = $result->fetch_assoc()) {
    $leaderboard[] = $row;
}

echo json_encode($leaderboard);

$stmt->close();
$conn->close();
?>
