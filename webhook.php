<?php
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// যদি কোনো মেসেজ না আসে, তবে স্ক্রিপ্ট থেমে যাবে
if (!$update || !isset($update['message'])) { 
    exit; 
}

$chat_id  = $update['message']['chat']['id'] ?? '';
$text     = $update['message']['text'] ?? ($update['message']['caption'] ?? '');
$from     = $update['message']['from']['first_name'] ?? 'Unknown User';
$username = $update['message']['from']['username'] ?? 'no_username';
$date     = date('Y-m-d H:i:s', $update['message']['date'] ?? time());
$msg_id   = $update['message']['message_id'] ?? '';

// আপনার টেলিগ্রাম বট টোকেন এখানে বসাবেন
$bot_token = '8536895168:AAEreaM2NyDpyTaAIf0xLbKcJXoA3LZTKKI'; 

if (!empty($text) && !empty($chat_id)) {
    // ১. ড্যাশবোর্ডের লাইভ ফিডের জন্য messages.json-এ সেভ করা
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

    // ২. বট স্বয়ংক্রিয়ভাবে মেসেজটি রি-পোস্ট বা রিপ্লাই করে দেবে
    $api_url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text) . "&reply_to_message_id=" . $msg_id;
    
    @file_get_contents($api_url);
}

http_response_code(200);
?>
