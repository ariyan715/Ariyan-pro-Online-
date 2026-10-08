
require('dotenv').config();
const express = require('express');
const mysql = require('mysql2/promise');
const TelegramBot = require('node-telegram-bot-api');
const cors = require('cors');
const admin = require('firebase-admin');

const app = express();
app.use(express.json());
app.use(cors());

// ১. মাইএসকিউএল ডাটাবেজ কানেকশন
const db = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// ২. টেলিগ্রাম বট কনফিগারেশন
const BOT_TOKEN = process.env.BOT_TOKEN;
const ADMIN_CHAT_ID = process.env.ADMIN_CHAT_ID;
const bot = new TelegramBot(BOT_TOKEN, { polling: true });

// ৩. ফায়ারবেস অ্যাডমিন কনফিগারেশন (firebase-key.json ফাইল ব্যবহার করবে)
const serviceAccount = require('./firebase-key.json');

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount)
});

// ৪. ছবি আপলোড ও টেলিগ্রামে পাঠানোর এপিআই
app.post('/api/upload-photo', async (req, res) => {
    const { userId, imageUrl } = req.body;

    if (!userId || !imageUrl) {
        return res.status(400).json({ success: false, message: 'userId এবং imageUrl আবশ্যক' });
    }

    try {
        const [result] = await db.execute(
            'INSERT INTO photo_approvals (user_id, image_url, status) VALUES (?, ?, ?)',
            [userId, imageUrl, 'pending']
        );
        const recordId = result.insertId;

        const sentMessage = await bot.sendPhoto(ADMIN_CHAT_ID, imageUrl, {
            caption: `📸 **নতুন ছবি আপলোড**\nUser ID: ${userId}\nRecord ID: ${recordId}`,
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

        await db.execute('UPDATE photo_approvals SET telegram_message_id = ? WHERE id = ?', [
            sentMessage.message_id,
            recordId
        ]);

        res.json({ success: true, message: 'ছবি টেলিগ্রামে পাঠানো হয়েছে', recordId });
    } catch (error) {
        console.error('Upload Error:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// ৫. টেলিগ্রাম বাটনের রেসপন্স ও পুশ নোটিফিকেশন
bot.on('callback_query', async (query) => {
    const data = query.data;
    const [action, recordId] = data.split('_');
    const newStatus = action === 'approve' ? 'approved' : 'rejected';

    try {
        await db.execute('UPDATE photo_approvals SET status = ? WHERE id = ?', [newStatus, recordId]);

        const message = {
            notification: {
                title: 'ছবি এপ্রুভাল আপডেট',
                body: `আপনার ছবিটি ${newStatus === 'approved' ? 'অনুমোদিত (Approved)' : 'বাতিল (Rejected)'} হয়েছে।`
            },
            topic: `user_${recordId}`
        };

        await admin.messaging().send(message);

        const statusText = action === 'approve' ? '✅ Approved' : '❌ Rejected';
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

// ৬. স্ট্যাটাস চেক এপিআই
app.get('/api/status/:recordId', async (req, res) => {
    try {
        const [rows] = await db.execute('SELECT * FROM photo_approvals WHERE id = ?', [req.params.recordId]);
        if (rows.length === 0) return res.status(404).json({ success: false, message: 'ডাটা পাওয়া যায়নি' });

        res.json({ success: true, data: rows[0] });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
