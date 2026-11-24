<?php
header('Content-Type: application/json');

$botToken = '8522645523:AAGKKxWJOGjxneVzhrRw-gd1feQP6KgF150';
$chatId = '6690005625';

$gateway = isset($_POST['gateway']) ? trim($_POST['gateway']) : 'bkash';
$trxId = isset($_POST['trx_id']) ? trim($_POST['trx_id']) : '';

if (empty($trxId) || !isset($_FILES['screenshot'])) {
    echo json_encode(['status' => 'error', 'message' => 'TrxID অথবা স্ক্রিনশট পাওয়া যায়নি!']);
    exit;
}

$file = $_FILES['screenshot'];
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
$uploadFile = $uploadDir . $safeName;

if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
    $gatewayUpper = strtoupper($gateway);
    $message = "🚨 নতুন পেমেন্ট প্রুফ জমা হয়েছে!\n\n💳 গেটওয়ে: " . $gatewayUpper . "\n📌 TrxID: " . $trxId;

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
    curl_exec($ch);
    curl_close($ch);

    // ড্যাশবোর্ডে দেখানোর জন্য messages.json-এ সেভ
    $msgFile = __DIR__ . '/messages.json';
    $messages = [];
    if (file_exists($msgFile)) {
        $decoded = json_decode(file_get_contents($msgFile), true);
        if (is_array($decoded)) {
            $messages = $decoded;
        }
    }

    array_unshift($messages, [
        'from' => $gatewayUpper . ' Payment',
        'username' => 'PaymentSystem',
        'text' => "নতুন পেমেন্ট | গেটওয়ে: {$gatewayUpper} | TrxID: {$trxId} | ছবি: uploads/{$safeName}",
        'time' => date('Y-m-d H:i:s'),
        'source' => 'payment'
    ]);

    $messages = array_slice($messages, 0, 200);
    @file_put_contents($msgFile, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    echo json_encode(['status' => 'success', 'file' => 'uploads/' . $safeName]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ফাইল আপলোড ব্যর্থ হয়েছে।']);
}
?>
