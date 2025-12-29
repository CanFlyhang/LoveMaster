<?php
// scripts/test_llm.php
require_once __DIR__ . '/../api/config.php';

echo "Testing LLM connection...\n";
echo "URL: " . LLM_API_URL . "\n";
echo "Model: " . LLM_MODEL . "\n";
// Mask API Key
echo "API Key: " . substr(LLM_API_KEY, 0, 5) . "..." . substr(LLM_API_KEY, -4) . "\n";

$data = [
    'model' => LLM_MODEL,
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, reply with "OK"']
    ],
    'stream' => false
];

$options = [
    'http' => [
        'header'  => [
            "Content-Type: application/json",
            "Authorization: Bearer " . LLM_API_KEY
        ],
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 10,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

echo "\n--- Attempt 1: file_get_contents with SSL verify disabled ---\n";
$context  = stream_context_create($options);
$result = file_get_contents(LLM_API_URL, false, $context);

if ($result === FALSE) {
    $error = error_get_last();
    echo "FAILED. Error: " . ($error['message'] ?? 'Unknown error') . "\n";
} else {
    echo "SUCCESS. Response length: " . strlen($result) . "\n";
    echo "Response preview: " . substr($result, 0, 200) . "\n";
    echo "Full response: " . $result . "\n";
}

echo "\n--- Debug Info ---\n";
print_r($http_response_header ?? 'No headers received');
?>
