<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$msgFile = __DIR__ . '/messages.json';
$result = @file_put_contents($msgFile, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not clear messages.json']);
    exit;
}

echo json_encode(['status' => 'cleared', 'messages' => []]);
?>
