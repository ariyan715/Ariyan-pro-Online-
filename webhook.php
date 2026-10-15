<?php
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) { 
    exit; 
}

// বটের নিজের পাঠানো মেসেজ যাতে লুপে না পড়ে, সেজন্য চেক করা
if (isset($update['message']['from']['is_bot']) && $update['message']['from']['is_bot'] === true) {
    exit;
}

$chat_id  = $update['message']['chat']['id'] ?? '';
$text     = $update['message']['text'] ?? ($update['message']['caption'] ?? '');
$from     = $update['message']['from']['first_name'] ?? 'Unknown User';
$username = $update['message']['from']['username'] ?? 'no_username';
$date     = date('Y-m-d H:i:s', $update['message']['date'] ?? time());

// আপনার টেলিগ্রাম বট টোকেন
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

    // ২. সোর্স থেকে আসা মেসেজটি হুবহু বট দিয়ে অটো-রিপ্লাই বা রি-পোস্ট করানোর অংশ (কপি-পেস্ট অটোমেশন)
    $api_url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text);
    
    // টেলিগ্রাম এপিআইতে রিকোয়েস্ট পাঠানো
    @file_get_contents($api_url);
}

http_response_code(200);
?>
