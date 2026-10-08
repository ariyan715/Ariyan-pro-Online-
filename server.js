require('dotenv').config();
const express = require('express');
const TelegramBot = require('node-telegram-bot-api');
const cors = require('cors');

const app = express();

// ১. বড় আকারের ইমেজের জন্য Payload Limit ৫০ মেগাবাইট করা হলো
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));
app.use(cors());

// ডাটাবেজের বদলে সার্ভার মেমোরিতে রিয়েল-টাইম স্ট্যাটাস ধরে রাখার ব্যবস্থা
const approvalsStore = new Map();

// ২. হোম রুট (সার্ভার রানিং চেক)
app.get('/', (req, res) => {
    res.send('Server is running smoothly without DB dependency!');
});

// ৩. টেলিগ্রাম বট কনফিগারেশন
const BOT_TOKEN = process.env.BOT_TOKEN;
const ADMIN_CHAT_ID = process.env.ADMIN_CHAT_ID;
const bot = new TelegramBot(BOT_TOKEN, { polling: true });

// ৪. ছবি আপলোড ও টেলিগ্রামে পাঠানোর এপিআই
app.post('/api/upload-photo', async (req, res) => {
    const { userId, imageUrl } = req.body;

    if (!userId || !imageUrl) {
        return res.status(400).json({ success: false, message: 'userId এবং imageUrl আবশ্যক' });
    }

    try {
        // ডাটাবেজের বদলে মেমোরির জন্য ইউনিক রেকর্ড আইডি
        const recordId = 'REC_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        
        // ইন-মেমোরি স্টোরে রেকর্ড সেভ
        approvalsStore.set(recordId, {
            userId,
            status: 'pending',
            timestamp: Date.now()
        });

        // Base64 ছবিকে টেলিগ্রাম বটের উপযোগী Buffer-এ কনভার্ট করা
        let photoBuffer;
        if (typeof imageUrl === 'string' && imageUrl.startsWith('data:image')) {
            const base64Data = imageUrl.replace(/^data:image\/\w+;base64,/, "");
            photoBuffer = Buffer.from(base64Data, 'base64');
        } else {
            photoBuffer = imageUrl;
        }

        // টেলিগ্রামে ছবি ও Approve/Reject বাটন পাঠানো
        await bot.sendPhoto(ADMIN_CHAT_ID, photoBuffer, {
            caption: `📸 **নতুন ছবি আপলোড**\n\n👤 **User ID:** \`${userId}\`\n🆔 **Record ID:** \`${recordId}\``,
            parse_mode: 'Markdown',
            reply_markup: {
                inline_keyboard: [
                    [
                        { text: '✅ Approve', callback_data: `approve_${recordId}` },
                        { text: '❌ Reject', callback_data: `reject_${recordId}` }
                    ]
                ]
            }
        });

        return res.json({ success: true, message: 'ছবি টেলিগ্রামে পাঠানো হয়েছে', recordId });

    } catch (error) {
        console.error('Upload Error:', error);
        return res.status(500).json({ 
            success: false, 
            message: error.message || 'টেলিগ্রামে ছবি পাঠাতে সমস্যা হয়েছে' 
        });
    }
});

// ৫. টেলিগ্রাম বাটনের রেসপন্স গ্রহণ
bot.on('callback_query', async (query) => {
    const data = query.data;
    if (!data || !data.includes('_')) return;

    const [action, recordId] = data.split('_');
    const newStatus = action === 'approve' ? 'approved' : 'rejected';

    try {
        // মেমোরিতে ইউজারের স্ট্যাটাস আপডেট করা
        if (approvalsStore.has(recordId)) {
            const currentData = approvalsStore.get(recordId);
            currentData.status = newStatus;
            approvalsStore.set(recordId, currentData);
        }

        const statusText = action === 'approve' ? '✅ Approved (অনুমোদিত)' : '❌ Rejected (বাতিল)';
        
        await bot.editMessageCaption(`${query.message.caption}\n\n**স্ট্যাটাস:** ${statusText}`, {
            chat_id: query.message.chat.id,
            message_id: query.message.message_id,
            parse_mode: 'Markdown'
        });

        bot.answerCallbackQuery(query.id, { text: `ছবিটি ${newStatus} করা হয়েছে!` });
    } catch (error) {
        console.error('Callback Error:', error);
        bot.answerCallbackQuery(query.id, { text: 'আপডেট করতে সমস্যা হয়েছে' });
    }
});

// ৬. ফ্রন্টএন্ড থেকে রিয়েল-টাইম স্ট্যাটাস চেক এপিআই (Polling)
app.get('/api/status/:recordId', (req, res) => {
    const recordId = req.params.recordId;
    
    if (approvalsStore.has(recordId)) {
        const data = approvalsStore.get(recordId);
        return res.json({ success: true, data: { status: data.status } });
    }

    return res.status(404).json({ success: false, message: 'রেকর্ড পাওয়া যায়নি' });
});

// মেমোরি পরিষ্কার রাখার লজিক (১ ঘণ্টার পুরানো ডাটা অটো ক্লিনআপ)
setInterval(() => {
    const now = Date.now();
    for (const [key, value] of approvalsStore.entries()) {
        if (now - value.timestamp > 3600000) {
            approvalsStore.delete(key);
        }
    }
}, 600000);

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
