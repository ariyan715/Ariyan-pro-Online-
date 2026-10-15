<?php
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) { exit; }

$chat_id  = $update['message']['chat']['id'] ?? ($update['callback_query']['message']['chat']['id'] ?? '');
$text     = $update['message']['text'] ?? ($update['callback_query']['data'] ?? '');
$from     = $update['message']['from']['first_name'] ?? 'Unknown User';
$username = $update['message']['from']['username'] ?? 'no_username';
$date     = date('Y-m-d H:i:s', $update['message']['date'] ?? time());

// আপনার টেলিগ্রাম বট টোকেন
$bot_token = '8536895168:AAEreaM2NyDpyTaAIf0xLbKcJXoA3LZTKKI';

if (!empty($text)) {
    // ১. ড্যাশবোর্ডের জন্য messages.json এ ডাটা সেভ করার অংশ
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

    // ২. বটে ইনস্ট্যান্ট অটো-রিপ্লাই বা রিপোস্ট পাঠানোর অংশ
    $reply_text = "User Data Server\nUpdate Now";
    $api_url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($reply_text);
    
    // টেলিগ্রাম এপিআইতে রিকোয়েস্ট পাঠানো
    @file_get_contents($api_url);
}

http_response_code(200);
?>
