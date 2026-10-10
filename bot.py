import os
from telegram import Update
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, filters

# রেলওয়ের Environment Variables থেকে নেওয়া হবে
BOT_TOKEN = os.getenv("BOT_TOKEN")
CHANNEL_ID = os.getenv("CHANNEL_ID")

async def send_to_channel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    if update.message:
        # copy_message ব্যবহার করলে ইউজার বা ফরওয়ার্ডারের তথ্য ছাড়াই মেসেজটি চ্যানেলে পোস্ট হবে
        await update.message.copy(chat_id=CHANNEL_ID)

if __name__ == '__main__':
    if not BOT_TOKEN or not CHANNEL_ID:
        print("Error: BOT_TOKEN or CHANNEL_ID is missing!")
    else:
        app = ApplicationBuilder().token(BOT_TOKEN).build()
        app.add_handler(MessageHandler(filters.ALL, send_to_channel))
        print("Bot is running...")
        app.run_polling()
