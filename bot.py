import os
from telegram import Update
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, filters

# রেলওয়ের এনভায়রনমেন্ট ভ্যারিয়েবল থেকে টোকেন এবং চ্যানেল আইডি নেওয়া হবে
BOT_TOKEN = os.getenv("BOT_TOKEN")
CHANNEL_ID = os.getenv("CHANNEL_ID")

async def forward_to_channel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    if update.message:
        # বোটে আসা যেকোনো মেসেজ সরাসরি চ্যানেলে ফরওয়ার্ড করবে
        await update.message.forward(chat_id=CHANNEL_ID)

if __name__ == '__main__':
    if not BOT_TOKEN or not CHANNEL_ID:
        print("Error: BOT_TOKEN or CHANNEL_ID is missing!")
    else:
        app = ApplicationBuilder().token(BOT_TOKEN).build()
        app.add_handler(MessageHandler(filters.ALL, forward_to_channel))
        print("Bot is running...")
        app.run_polling()
