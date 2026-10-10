import os
import logging
from telegram import Update
from telegram.ext import ApplicationBuilder, ContextTypes, MessageHandler, filters

# লগের জন্য সেটিংস
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

BOT_TOKEN = os.getenv("BOT_TOKEN")
CHANNEL_ID = os.getenv("CHANNEL_ID")

async def send_to_channel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    try:
        if update.message:
            # এটি ইউজারের মেসেজ হুবহু কপি করে চ্যানেলে পাঠাবে, কোনো ফরওয়ার্ড ট্যাগ দেখাবে না
            await context.bot.copy_message(
                chat_id=CHANNEL_ID,
                from_chat_id=update.effective_chat.id,
                message_id=update.message.message_id
            )
    except Exception as e:
        print(f"Failed to send message: {e}")

if __name__ == '__main__':
    if not BOT_TOKEN or not CHANNEL_ID:
        print("Error: BOT_TOKEN or CHANNEL_ID is missing!")
    else:
        app = ApplicationBuilder().token(BOT_TOKEN).build()
        app.add_handler(MessageHandler(filters.ALL, send_to_channel))
        print("Bot is running...")
        app.run_polling()
