import os
from telethon import TelegramClient, events

API_ID = int(os.getenv("API_ID", 0))
API_HASH = os.getenv("API_HASH", "")
CHANNEL_ID = os.getenv("CHANNEL_ID")

client = TelegramClient('userbot_session', API_ID, API_HASH)

@client.on(events.NewMessage(incoming=True))
async def forward_incoming_messages(event):
    try:
        await client.forward_messages(CHANNEL_ID, event.message)
    except Exception as e:
        print(f"Failed to forward message: {e}")

if __name__ == '__main__':
    if not API_ID or not API_HASH or not CHANNEL_ID:
        print("Error: API_ID, API_HASH, or CHANNEL_ID is missing!")
    else:
        print("Userbot is running and listening for messages...")
        client.start()
        client.run_until_disconnected()
