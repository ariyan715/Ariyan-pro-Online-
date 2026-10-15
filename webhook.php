<?php
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) { exit; }

$chat_id  = $update['message']['chat']['id'] ?? ($update['callback_query']['message']['chat']['id'] ?? '');
$text     = $update['message']['text'] ?? ($update['callback_query']['data'] ?? '');
$from     = $update['message']['from']['first_name'] ?? 'Unknown User';
$username = $update['message']['from']['username'] ?? 'no_username';
$date     = date('Y-m-d H:i:s', $update['message']['date'] ?? time());

if (!empty($text)) {
    $log_file = 'messages.json';
    $messages = [];
    if (file_exists($log_file)) {
        $messages = json_decode(file_get_contents($log_file), true) ?: [];
    }
    array_unshift($messages, [
        'time'     => $date,
        'from'     => $from,
        'username' => $username,
        'text'     => $text
    ]);
    $messages = array_slice($messages, 0, 100);
    file_put_contents($log_file, json_encode($messages, JSON_PRETTY_PRINT));
}
http_response_code(200);
?>
