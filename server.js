const express = require('express');
const cors = require('cors');
const multer = require('multer');
const fetch = require('node-fetch');
const FormData = require('form-data');
require('dotenv').config();

const app = express();
const upload = multer({ storage: multer.memoryStorage() });

app.use(cors());
app.use(express.json());

const BOT_TOKEN = process.env.BOT_TOKEN;
const CHAT_ID = process.env.CHAT_ID;
const PORT = process.env.PORT || 3000;

// SSE (Server-Sent Events) কানেকশন স্টোরেজ
let clients = [];

// ১. ফ্রন্টএন্ডের জন্য Real-time SSE ইভেন্ট স্ট্রিম
app.get('/api/events', (req, res) => {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.flushHeaders();

  const clientId = Date.now();
  const newClient = { id: clientId, res };
  clients.push(newClient);

  req.on('close', () => {
    clients = clients.filter(client => client.id !== clientId);
  });
});

// সমস্ত ফ্রন্টএন্ড ক্লায়েন্টকে রিয়েল-টাইম আপডেট পাঠানোর ফাংশন
function sendSSEEvent(data) {
  clients.forEach(client => {
    client.res.write(`data: ${JSON.stringify(data)}\n\n`);
  });
}

// ২. ফটো আপলোড API (ফ্রন্টএন্ড থেকে স্ক্রিনশট রিসিভ ও টেলিগ্রামে সেন্ড)
app.post('/api/upload', upload.single('photo'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ success: false, message: 'No image uploaded' });
    }

    const userId = req.body.userId || Date.now().toString();

    const formData = new FormData();
    formData.append('chat_id', CHAT_ID);
    formData.append('photo', req.file.buffer, { filename: 'screenshot.jpg' });
    formData.append('caption', `📷 নতুন পেমেন্ট স্ক্রিনশট\nUser ID: ${userId}\nঅনুমোদন বা বাতিল করুন:`);
    
    // Telegram Inline Approve / Reject Buttons
    const inlineKeyboard = {
      inline_keyboard: [
        [
          { text: '✅ Approve', callback_data: `approve_${userId}` },
          { text: '❌ Reject', callback_data: `reject_${userId}` }
        ]
      ]
    };
    formData.append('reply_markup', JSON.stringify(inlineKeyboard));

    const telegramRes = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendPhoto`, {
      method: 'POST',
      body: formData
    });

    const telegramData = await telegramRes.json();

    if (telegramData.ok) {
      // ৩০ মিনিটের অটো-রিজেক্ট টাইমার
      setTimeout(() => {
        sendSSEEvent({ status: 'rejected', userId, reason: 'Timeout (30 mins)' });
      }, 30 * 60 * 1000);

      return res.json({ success: true, message: 'Photo sent to Telegram', userId });
    } else {
      return res.status(500).json({ success: false, message: 'Telegram API error' });
    }
  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ success: false, message: 'Server error' });
  }
});

// ৩. Telegram Webhook (এডমিন যখন Approve/Reject বাটনে ক্লিক করবে)
app.post('/api/telegram-webhook', async (req, res) => {
  const update = req.body;

  if (update && update.callback_query) {
    const callbackQuery = update.callback_query;
    const data = callbackQuery.data;
    const callbackId = callbackQuery.id;

    const [action, userId] = data.split('_');

    if (action === 'approve') {
      sendSSEEvent({ status: 'approved', userId });

      await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/answerCallbackQuery`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ callback_query_id: callbackId, text: 'অনুমোদিত হয়েছে!' })
      });
    } else if (action === 'reject') {
      sendSSEEvent({ status: 'rejected', userId });

      await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/answerCallbackQuery`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ callback_query_id: callbackId, text: 'বাতিল করা হয়েছে!' })
      });
    }
  }

  res.sendStatus(200);
});

app.get('/', (req, res) => {
  res.send('Railway Telegram Approval Backend is Running!');
});

app.listen(PORT, () => {
  console.log(`Server active on port ${PORT}`);
});

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
