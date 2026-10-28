<?php
// রেলওয়ে সার্ভারে ক্র্যাশ বা আউটপুট প্রবলেম এড়াতে এরর রিপোর্টিং অফ রাখা হলো
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// টেলিগ্রাম বট থেকে আসা ইনপুট ডাটা নিরাপদে রিড করা
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// যেকোনো সোর্স থেকে আসা মেসেজ বা পোস্ট অবজেক্ট এক্সট্র্যাক্ট করা
$msgObject = null;
if (isset($update['message'])) {
    $msgObject = $update['message'];
} elseif (isset($update['channel_post'])) {
    $msgObject = $update['channel_post'];
} elseif (isset($update['edited_message'])) {
    $msgObject = $update['edited_message'];
}

if ($msgObject) {
    $text = '';
    if (isset($msgObject['text'])) {
        $text = $msgObject['text'];
    } elseif (isset($msgObject['caption'])) {
        $text = $msgObject['caption'];
    }

    // যদি টেক্সট বা ক্যাপশন থাকে তবেই প্রসেস করবে
    if (!empty($text)) {
        $from = 'System / Source';
        $username = 'Bot';

        if (isset($msgObject['from']['first_name'])) {
            $from = $msgObject['from']['first_name'];
        } elseif (isset($msgObject['chat']['title'])) {
            $from = $msgObject['chat']['title'];
        }

        if (isset($msgObject['from']['username'])) {
            $username = $msgObject['from']['username'];
        } elseif (isset($msgObject['chat']['username'])) {
            $username = $msgObject['chat']['username'];
        }

        $time = date('Y-m-d H:i:s');
        $msgFile = 'messages.json';
        
        // ১. ড্যাশবোর্ডের জন্য messages.json ফাইলে সেভ করার লজিক (File Locking সহ)
        $messages = [];
        if (file_exists($msgFile)) {
            $fileContent = file_get_contents($msgFile);
            $decoded = json_decode($fileContent, true);
            if (is_array($decoded)) {
                $messages = $decoded;
            }
        }

        // নতুন মেসেজ সবার ওপরে যোগ করা
        array_unshift($messages, [
            'from' => $from,
            'username' => $username,
            'text' => $text,
            'time' => $time
        ]);

        // LOCK_EX ব্যবহার করার ফলে ডেটা কখনো করাপ্ট বা নষ্ট হবে না
        file_put_contents($msgFile, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        // ২. ফ্রি জাংশন বট স্টাইল অটো-রিপোট / ফরোয়ার্ড লজিক (রিপ্লাই ছাড়াই সরাসরি সেন্ড)
        // রেলওয়ের Environment Variables থেকে টোকেন এবং টার্গেট চ্যানেল আইডি নেবে
        $botToken = getenv('BOT_TOKEN'); 
        $targetChannelId = getenv('TARGET_CHANNEL_ID'); // উদাহরণ: -100xxxxxxxxxx বা @channelname

        if (!empty($botToken) && !empty($targetChannelId)) {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $postData = [
                'chat_id' => $targetChannelId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // ৫ সেকেন্ড টাইমআউট, যাতে রেলওয়ে সার্ভার হ্যাং না করে
            curl_exec($ch);
            curl_close($ch);
        }
    }
}

echo json_encode(['status' => 'success']);
?>
