<?php
header('Content-Type: application/json');
$log_file = 'messages.json';
if (file_exists($log_file)) {
    echo file_get_contents($log_file);
} else {
    echo json_encode([]);
}
?>
