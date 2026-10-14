import os
from telethon import TelegramClient, events

API_ID = int(os.getenv("API_ID", 0))
API_HASH = os.getenv("API_HASH", "")
CHANNEL_ID = os.getenv("CHANNEL_ID")

# নির্দিষ্ট বটের আইডি (যদি সেট করা থাকে)
specific_bot_env = os.getenv("SPECIFIC_BOT_ID")
SPECIFIC_BOT_ID = int(specific_bot_env) if specific_bot_env else None

client = TelegramClient('userbot_session', API_ID, API_HASH)

@client.on(events.NewMessage(incoming=True))
async def forward_handler(event):
    try:
        sender_id = event.sender_id
        sender = await event.get_sender()
        sender_username = getattr(sender, 'username', 'No Username')
        sender_title = getattr(sender, 'title', None) or getattr(sender, 'first_name', 'Unknown')
        
        # রেলওয়ে লগে কে মেসেজ পাঠাল তার আইডি ও নাম প্রিন্ট হবে
        print(f"[LOG] Message received from -> Name: {sender_title} | Username: @{sender_username} | ID: {sender_id}")

        # যদি SPECIFIC_BOT_ID সেট করা থাকে, তবে কেবল সেই বটের মেসেজ চ্যানেলে যাবে
        if SPECIFIC_BOT_ID:
            if sender_id == SPECIFIC_BOT_ID:
                await client.forward_messages(CHANNEL_ID, event.message)
        else:
            # যদি আইডি সেট করা না থাকে, সাময়িকভাবে সব মেসেজ ফরোয়ার্ড করবে যাতে আপনি আইডি দেখতে পান
            await client.forward_messages(CHANNEL_ID, event.message)
            
    except Exception as e:
        print(f"Error: {e}")

if __name__ == '__main__':
    if not API_ID or not API_HASH or not CHANNEL_ID:
        print("Error: API_ID, API_HASH, or CHANNEL_ID is missing!")
    else:
        print("Userbot is running and listening for messages...")
        client.start()
        client.run_until_disconnected()
