<?php
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    file_put_contents('messages.json', json_encode([]));
    echo json_encode(['status' => 'success']);
    exit;
}
?>
