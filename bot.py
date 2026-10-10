import os
import logging
from telegram import Update
from telegram.ext import (
    ApplicationBuilder,
    ContextTypes,
    MessageHandler,
    ChannelPostHandler,
    EditedMessageHandler,
    filters
)

# লগের জন্য সেটিংস
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)

BOT_TOKEN = os.getenv("BOT_TOKEN")
CHANNEL_ID = os.getenv("CHANNEL_ID")

async def send_to_channel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    try:
        # update.message এর বদলে effective_message ব্যবহার করা হয়েছে 
        # যাতে সব ধরনের সোর্স বা বট থেকে আসা মেসেজ কাভার করে
        message = update.effective_message
        if message:
            await context.bot.copy_message(
                chat_id=CHANNEL_ID,
                from_chat_id=message.chat_id,
                message_id=message.message_id
            )
    except Exception as e:
        print(f"Failed to send message: {e}")

if __name__ == '__main__':
    if not BOT_TOKEN or not CHANNEL_ID:
        print("Error: BOT_TOKEN or CHANNEL_ID is missing!")
    else:
        app = ApplicationBuilder().token(BOT_TOKEN).build()
        
        # বিভিন্ন সোর্স থেকে মেসেজ রিসিভ করার জন্য হ্যান্ডলারগুলো যোগ করা হলো
        app.add_handler(MessageHandler(filters.ALL, send_to_channel))
        app.add_handler(ChannelPostHandler(send_to_channel))
        app.add_handler(EditedMessageHandler(filters.ALL, send_to_channel))
        
        print("Bot is running...")
        app.run_polling()
