<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// --- কনফিগারেশন ভেরিয়েবল ---
$botToken = '8522645523:AAGKKxWJOGjxneVzhrRw-gd1feQP6KgF150';
$targetChannelId = '-1004333879553';
$targetBotChatId = '6690005625';

// --- ফরোয়ার্ড রুলস (Forward Rules) ---
$forwardRules = [
    'bot_to_channel'       => true,  // বট/সোর্স থেকে চ্যানেলে যাবে
    'channel_to_bot'       => true,  // চ্যানেল থেকে নির্ধারিত বট/চ্যাটে যাবে
    'prevent_channel_loop' => true,  // চ্যানেলের পোস্ট আবার চ্যানেলে যাওয়া বন্ধ
    'once_only_window_sec' => 15,    // ১৫ সেকেন্ডের মধ্যে একই মেসেজ দ্বিতীয়বার ফরোয়ার্ড হবে না
];

$msgFile = __DIR__ . '/messages.json';
$seenFile = __DIR__ . '/seen_forwards.json';

function tgRequest($method, $params, $token) {
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function isAlreadyForwardedOnce($seenFile, $ruleKey, $uniqueKey, $windowSec) {
    $now = time();
    $seen = [];

    if (file_exists($seenFile)) {
        $decoded = json_decode(@file_get_contents($seenFile), true);
        if (is_array($decoded)) {
            foreach ($decoded as $k => $ts) {
                if (($now - (int)$ts) <= max(60, $windowSec * 4)) {
                    $seen[$k] = (int)$ts;
                }
            }
        }
    }

    $key = $ruleKey . ':' . $uniqueKey;
    if (isset($seen[$key]) && ($now - $seen[$key]) <= $windowSec) {
        return true;
    }

    $seen[$key] = $now;
    @file_put_contents($seenFile, json_encode($seen), LOCK_EX);
    return false;
}

$msgObject = null;
$isChannelPost = false;

if (isset($update['message'])) {
    $msgObject = $update['message'];
    $isChannelPost = false;
} elseif (isset($update['channel_post'])) {
    $msgObject = $update['channel_post'];
    $isChannelPost = true;
} elseif (isset($update['edited_message'])) {
    $msgObject = $update['edited_message'];
    $isChannelPost = false;
}

if ($msgObject) {
    $text = $msgObject['text'] ?? ($msgObject['caption'] ?? '');
    $incomingChatId = (string)($msgObject['chat']['id'] ?? '');
    $messageId = (string)($msgObject['message_id'] ?? '');

    if ($text !== '') {
        $from = $isChannelPost ? 'Channel Broadcast' : 'Primary Bot / Source';
        $username = 'Bot';

        if (!empty($msgObject['from']['first_name'])) {
            $from = $msgObject['from']['first_name'];
        } elseif (!empty($msgObject['chat']['title'])) {
            $from = $msgObject['chat']['title'];
        }

        if (!empty($msgObject['from']['username'])) {
            $username = $msgObject['from']['username'];
        } elseif (!empty($msgObject['chat']['username'])) {
            $username = $msgObject['chat']['username'];
        }

        $time = date('Y-m-d H:i:s');

        $messages = [];
        if (file_exists($msgFile)) {
            $decoded = json_decode(@file_get_contents($msgFile), true);
            if (is_array($decoded)) {
                $messages = $decoded;
            }
        }

        array_unshift($messages, [
            'from' => $from,
            'username' => $username,
            'text' => $text,
            'time' => $time,
            'source' => $isChannelPost ? 'channel' : 'bot'
        ]);

        $messages = array_slice($messages, 0, 200);
        @file_put_contents($msgFile, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        if (!empty($botToken)) {
            $uniqueMsgKey = $incomingChatId . '_' . $messageId . '_' . md5($text);

            // রুল ১: বট/সোর্স থেকে চ্যানেলে একবার পাঠানো
            if (!$isChannelPost && $forwardRules['bot_to_channel'] && !empty($targetChannelId)) {
                $isFromTargetChannel = ($forwardRules['prevent_channel_loop'] && $incomingChatId === (string)$targetChannelId);

                if (!$isFromTargetChannel && !isAlreadyForwardedOnce($seenFile, 'b2c', $uniqueMsgKey, $forwardRules['once_only_window_sec'])) {
                    tgRequest('sendMessage', [
                        'chat_id' => $targetChannelId,
                        'text' => $text,
                        'parse_mode' => 'HTML'
                    ], $botToken);
                }
            }

            // রুল ২: চ্যানেল থেকে বটে/চ্যাটে একবার পাঠানো (চ্যানেলে আর ফিরে যাবে না)
            if ($isChannelPost && $forwardRules['channel_to_bot'] && !empty($targetBotChatId)) {
                if ($incomingChatId === (string)$targetChannelId && !isAlreadyForwardedOnce($seenFile, 'c2b', $uniqueMsgKey, $forwardRules['once_only_window_sec'])) {
                    tgRequest('sendMessage', [
                        'chat_id' => $targetBotChatId,
                        'text' => $text,
                        'parse_mode' => 'HTML'
                    ], $botToken);
                }
            }
        }
    }
}

echo json_encode(['status' => 'success']);
?>
