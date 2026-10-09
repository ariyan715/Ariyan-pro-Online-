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

// SSE Client Storage
let clients = [];

// 1. SSE Endpoint for Real-time Frontend Events
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

// Function to send SSE events
function sendSSEEvent(data) {
  clients.forEach(client => {
    client.res.write(`data: ${JSON.stringify(data)}\n\n`);
  });
}

// 2. Photo Upload API
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

// 3. Telegram Webhook Endpoint
app.post('/api/telegram-webhook', async (req, res) => {
  try {
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
  } catch (err) {
    console.error('Webhook error:', err);
    res.sendStatus(500);
  }
});

app.get('/', (req, res) => {
  res.send('Railway Telegram Approval Backend is Running!');
});

app.listen(PORT, () => {
  console.log(`Server active on port ${PORT}`);
});
