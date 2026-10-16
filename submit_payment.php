<?php
header('Content-Type: application/json');

$botToken = '8522645523:AAGKKxWJOGjxneVzhrRw-gd1feQP6KgF150'; // আপনার টেলিগ্রাম বট টোকেন বসান
$chatId = '6690005625';     // আপনার টেলিগ্রাম চ্যাট আইডি বসান

$gateway = isset($_POST['gateway']) ? $_POST['gateway'] : 'bkash';
$trxId = isset($_POST['trx_id']) ? $_POST['trx_id'] : '';

if (empty($trxId) || !isset($_FILES['screenshot'])) {
    echo json_encode(['status' => 'error', 'message' => 'TrxID অথবা স্ক্রিনশট পাওয়া যায়নি!']);
    exit;
}

$file = $_FILES['screenshot'];
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadFile = $uploadDir . basename($file['name']);
if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
    $message = "🚨 নতুন পেমেন্ট প্রুফ জমা হয়েছে!\n\n💳 গেটওয়ে: " . strtoupper($gateway) . "\n📌 TrxID: " . $trxId;
    
    // টেলিগ্রামে মেসেজ ও ছবি পাঠানোর cURL রিকোয়েস্ট
    $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";
    $postFields = [
        'chat_id' => $chatId,
        'caption' => $message,
        'photo' => new CURLFile(realpath($uploadFile))
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ফাইল আপলোড ব্যর্থ হয়েছে।']);
}
?>
