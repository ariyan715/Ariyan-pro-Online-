require('dotenv').config();
const express = require('express');
const TelegramBot = require('node-telegram-bot-api');
const cors = require('cors');

const app = express();

// ১. বড় আকারের ইমেজের জন্য Payload Limit ৫০ মেগাবাইট
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));
app.use(cors());

// ডাটাবেজের বদলে ইন-মেমোরি স্টোরেজ
const approvalsStore = new Map();

// ২. হোম রুট
app.get('/', (req, res) => {
    res.send('Server is running smoothly!');
});

// ৩. টেলিগ্রাম বট কনফিগারেশন
const BOT_TOKEN = process.env.BOT_TOKEN;
const ADMIN_CHAT_ID = process.env.ADMIN_CHAT_ID;
const bot = new TelegramBot(BOT_TOKEN, { polling: true });

// ৪. ছবি আপলোড এপিআই
app.post('/api/upload-photo', async (req, res) => {
    const { userId, imageUrl } = req.body;

    if (!userId || !imageUrl) {
        return res.status(400).json({ success: false, message: 'userId এবং imageUrl আবশ্যক' });
    }

    try {
        const recordId = 'REC_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        
        approvalsStore.set(recordId, {
            userId,
            status: 'pending',
            timestamp: Date.now()
        });

        let photoBuffer;
        if (typeof imageUrl === 'string' && imageUrl.startsWith('data:image')) {
            const base64Data = imageUrl.replace(/^data:image\/\w+;base64,/, "");
            photoBuffer = Buffer.from(base64Data, 'base64');
        } else {
            photoBuffer = imageUrl;
        }

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

// ৫. টেলিগ্রাম বাটনের রেসপন্স (FIXED)
bot.on('callback_query', async (query) => {
    const data = query.data;
    if (!data || !data.includes('_')) return;

    const [action, recordId] = data.split('_');
    const newStatus = action === 'approve' ? 'approved' : 'rejected';

    // ১. শুরুতেই টেলিগ্রামকে দ্রুত উত্তর দেওয়া যাতে বাটন লোডিং না আটকে থাকে
    try {
        await bot.answerCallbackQuery(query.id, { 
            text: `ছবিটি ${action === 'approve' ? 'অনুমোদিত' : 'বাতিল'} করা হয়েছে!` 
        });
    } catch (e) {
        console.error('Answer Callback Error:', e);
    }

    try {
        // ২. মেমোরিতে স্ট্যাটাস আপডেট
        if (approvalsStore.has(recordId)) {
            const currentData = approvalsStore.get(recordId);
            currentData.status = newStatus;
            approvalsStore.set(recordId, currentData);
        }

        const statusText = action === 'approve' ? '✅ APPROVED (অনুমোদিত)' : '❌ REJECTED (বাতিল)';
        const oldCaption = (query.message && query.message.caption) ? query.message.caption : '📸 ছবি আপলোড';

        // ৩. নিরাপদে মেসেজের ক্যাপশন আপডেট করা (Markdown এরর এড়াতে)
        await bot.editMessageCaption(`${oldCaption}\n\nস্ট্যাটাস: ${statusText}`, {
            chat_id: query.message.chat.id,
            message_id: query.message.message_id,
            reply_markup: { inline_keyboard: [] } // একবার ক্লিক হলে বাটনগুলো রিমুভ হয়ে যাবে
        });

    } catch (error) {
        console.error('Callback Processing Error:', error);
    }
});

// ৬. স্ট্যাটাস চেক এপিআই (Polling)
app.get('/api/status/:recordId', (req, res) => {
    const recordId = req.params.recordId;
    
    if (approvalsStore.has(recordId)) {
        const data = approvalsStore.get(recordId);
        return res.json({ success: true, data: { status: data.status } });
    }

    return res.status(404).json({ success: false, message: 'রেকর্ড পাওয়া যায়নি' });
});

// ১ ঘণ্টার পুরানো মেমোরি অটো ক্লিনআপ
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
