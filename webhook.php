<?php
// সার্ভারে ক্র্যাশ বা আউটপুট প্রবলেম এড়াতে এরর রিপোর্টিং অফ রাখা হলো
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

$botToken = getenv('BOT_TOKEN'); 
$targetChannelId = getenv('TARGET_CHANNEL_ID'); // আপনার নির্দিষ্ট টেলিগ্রাম চ্যানেল আইডি (-100xxxxxxxxx)

$msgObject = null;
$isChannelPost = false;

// মেসেজ কোন সোর্স থেকে আসছে তা চেক করা (বট চ্যাট নাকি চ্যানেল পোস্ট)
if (isset($update['message'])) {
    $msgObject = $update['message'];
    $isChannelPost = false;
} elseif (isset($update['channel_post'])) {
    $msgObject = $update['channel_post'];
    $isChannelPost = true; // এটি চ্যানেল থেকে আসা পোস্ট
} elseif (isset($update['edited_message'])) {
    $msgObject = $update['edited_message'];
    $isChannelPost = false;
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
        $from = $isChannelPost ? 'Channel Broadcast' : 'System / Source';
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
        
        // ১. ড্যাশবোর্ডের জন্য messages.json ফাইলে নিরাপদে সেভ করার লজিক (File Locking সহ)
        $messages = [];
        if (file_exists($msgFile)) {
            $fileContent = file_get_contents($msgFile);
            $decoded = json_decode($fileContent, true);
            if (is_array($decoded)) {
                $messages = $decoded;
            }
        }

        // নতুন মেসেজ সবার ওপরে যোগ করা (যাতে ড্যাশবোর্ডে রিয়েল-টাইমে দেখা যায়)
        array_unshift($messages, [
            'from' => $from,
            'username' => $username,
            'text' => $text,
            'time' => $time
        ]);

        file_put_contents($msgFile, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        // ২. ফ্রি জাংশন বোর্ড স্টাইল লুপ-প্রিভেন্টেড অটো-ফরওয়ার্ডিং লজিক
        if (!empty($botToken) && !empty($targetChannelId)) {
            
            // শর্ত: মেসেজটি যদি সরাসরি বট বা অন্য সোর্স থেকে আসে, তবেই কেবল তা চ্যানেলে পাঠাবে। 
            // আর যদি মেসেজটি ইতিমধ্যে চ্যানেল থেকে আসে (channel_post), তবে সেটি আর চ্যানেলে রি-সেন্ড হবে না। 
            // এর ফলে ইনফিনিট লুপ বা বারবার ফরওয়ার্ড হওয়া চিরতরে বন্ধ থাকবে!
            if (!$isChannelPost) {
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
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // ৫ সেকেন্ড টাইমআউট
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
}

echo json_encode(['status' => 'success']);
?>
