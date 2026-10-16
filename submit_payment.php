<?php
// submit_payment.php

header('Content-Type: application/json');

// TODO: আপনার টেলিগ্রাম বট টোকেন এবং চ্যাট আইডি এখানে বসিয়ে দিন
$botToken = "8522645523:AAGKKxWJOGjxneVzhrRw-gd1feQP6KgF150"; // যেমন: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
$chatId   = "6690005625";     // অ্যাডমিনের টেলিগ্রাম চ্যাট আইডি

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gateway =$_POST['gateway'] ?? 'Unknown';
    $trxId   =$_POST['trx_id'] ?? '';
    
    if (empty($trxId) \vert{}\vert{} !isset($_FILES['screenshot'])) {
        echo json_encode(['status' => 'error', 'message' => 'প্রয়োজনীয় তথ্য অনুপস্থিত।']);
        exit;
    }

    $fileTmpPath =$_FILES['screenshot']['tmp_name'];
    $fileName    =$_FILES['screenshot']['name'];

    // টেলিগ্রামে ডাটা ও ছবি পাঠানোর জন্য Telegram Bot API URL
    $website = "https://api.telegram.org/bot" . $botToken;
    $url     =$website . "/sendPhoto";

    $caption  = "🚨 **নতুন পেমেন্ট প্রুফ জমা পড়েছে!**\n\n";
    $caption .= "💳 **মেথড:** " . strtoupper($gateway) . "\n";
    $caption .= "🔢 **TrxID:** `" . $trxId . "`\n";

    $postFields = [
        'chat_id' => $chatId,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
        'photo'   => new CURLFile($fileTmpPath, $_FILES['screenshot']['type'],$fileName)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $serverOutput = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($serverOutput, true);

    if ($result && isset($result['ok']) &&$result['ok'] == true) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'টেলিগ্রামে বার্তা পাঠাতে ব্যর্থ হয়েছে। বট টোকেন চেক করুন।']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'অবৈধ রিকোয়েস্ট।']);
}
?>
