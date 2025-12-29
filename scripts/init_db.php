<?php
// scripts/init_db.php

require_once __DIR__ . '/../api/config.php';

echo "Connecting to MySQL server...\n";

// 1. Connect without DB selected
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

// 2. Create DB if not exists
$dbName = DB_NAME;
$sql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbName' checked/created successfully.\n";
} else {
    die("Error creating database: " . $conn->error . "\n");
}

// 3. Select DB
$conn->select_db($dbName);

// 4. Read schema.sql
$schemaPath = __DIR__ . '/../sql/schema.sql';
if (!file_exists($schemaPath)) {
    die("Schema file not found at $schemaPath\n");
}
$sql = file_get_contents($schemaPath);

// 5. Execute multi query
echo "Importing schema...\n";
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check for error
        if ($conn->errno) {
            echo "Error in query: " . $conn->error . "\n";
            break;
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Schema import completed.\n";
} else {
    echo "Error executing schema: " . $conn->error . "\n";
}

$conn->close();
?>
