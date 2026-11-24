<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$msgFile = __DIR__ . '/messages.json';

if (!file_exists($msgFile)) {
    @file_put_contents($msgFile, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
    echo json_encode([]);
    exit;
}

$decoded = json_decode(file_get_contents($msgFile), true);
echo json_encode(is_array($decoded) ? $decoded : [], JSON_UNESCAPED_UNICODE);
?>
