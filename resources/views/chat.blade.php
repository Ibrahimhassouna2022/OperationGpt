<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation GPT | Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">
 <script>
    // Injects authenticated user data dynamically from Laravel Auth
    window.AppUser = {
        name: "{{ auth()->check() ? auth()->user()->name : 'User Name' }}",
        role: "{{ auth()->check() ? auth()->user()->role : 'User' }}"
    };
</script>
    <link rel="stylesheet" href="{{ asset('../../public/frontend/Chat_App.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/operation-gpt/frontend/index-DLGrjFAV.css') }}">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #10b981;
            --error: #ef4444;
        }

        body {
            font-family: 'Outfit', 'Noto Kufi Arabic', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            width: 90%;
            max-width: 1000px;
            height: 85vh;
            background: var(--card-bg);
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .chat-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header h2 {
            margin: 0;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: var(--accent);
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 10px var(--accent);
        }

        #chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        #chat-box::-webkit-scrollbar {
            width: 6px;
        }

        #chat-box::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .message {
            max-width: 85%;
            padding: 16px 20px;
            border-radius: 18px;
            line-height: 1.6;
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-start;
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.bot {
            align-self: flex-end;
            background: rgba(255, 255, 255, 0.05);
            border-bottom-left-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: #fca5a5;
        }

        .input-area {
            padding: 24px;
            background: rgba(15, 23, 42, 0.5);
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            display: flex;
            gap: 12px;
        }

        #message-input {
            flex: 1;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 14px 20px;
            color: white;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        #message-input:focus {
            border-color: var(--primary);
        }

        #send-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0 24px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #send-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        #send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Table Styling */
        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .report-table th {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px;
            text-align: right;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .report-table td {
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        .loader {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="root"></div>
    <script type="module" src="{{ asset('../../public/frontend/ChatApp.js') }}"></script>
    <script type="module" src="{{ asset('vendor/operation-gpt/frontend/index-BTjBqddF.js') }}"></script>
</body>
</html>