<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation GPT | Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Noto+Kufi+Arabic:wght@400;700&display=swap"
        rel="stylesheet">
    <script>
        window.AppUser = {
            name: "{!! auth()->check() ? auth()->user()->name : 'System Admin' !!}",
            role: "{!! auth()->check() ? auth()->user()->role : 'admin' !!}"
        };
    </script>
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

        * { box-sizing: border-box; }

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
            overflow: hidden;
        }

        /* ── Header ── */
        .chat-header {
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-header h2 {
            margin: 0;
            font-size: 1.4rem;
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-dot {
            width: 9px;
            height: 9px;
            background: var(--accent);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px var(--accent);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.5; }
        }

        .status-text {
            font-size: 0.78rem;
            color: var(--accent);
            font-weight: 600;
        }

        /* ── User badge (top-right corner) ── */
        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 50px;
            padding: 6px 14px 6px 8px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #c084fc);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .user-role {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: capitalize;
        }

        /* ── Chat box ── */
        #chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        #chat-box::-webkit-scrollbar { width: 5px; }
        #chat-box::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        /* ── Messages ── */
        .message {
            max-width: 88%;
            padding: 14px 18px;
            border-radius: 18px;
            line-height: 1.65;
            position: relative;
            animation: fadeIn 0.25s ease-out;
            word-break: break-word;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-end;
            background: var(--primary);
            color: white;
            border-bottom-left-radius: 4px;
        }

        .message.bot {
            align-self: flex-start;
            background: rgba(255, 255, 255, 0.06);
            border-bottom-right-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 95%;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: #fca5a5;
            align-self: flex-start;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--accent);
            color: #6ee7b7;
            align-self: flex-start;
        }

        /* ── Table ── */
        .table-wrapper {
            overflow-x: auto;
            margin-top: 10px;
            border-radius: 12px;
        }

        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 0.88rem;
        }

        .report-table th {
            background: rgba(99, 102, 241, 0.2);
            padding: 11px 14px;
            text-align: right;
            color: #a5b4fc;
            font-weight: 600;
            white-space: nowrap;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .report-table td {
            padding: 10px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }

        .report-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .table-count {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        /* ── Input area ── */
        .input-area {
            padding: 18px 24px;
            background: rgba(15, 23, 42, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        #message-input {
            flex: 1;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 13px 18px;
            color: white;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
            font-family: inherit;
        }

        #message-input::placeholder { color: var(--text-muted); }

        #message-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        #send-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0 22px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        #send-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        #send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loader {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .welcome-msg {
            text-align: center;
            color: var(--text-muted);
            padding: 40px 20px;
            font-size: 0.95rem;
        }
        .welcome-msg .icon { font-size: 2.5rem; margin-bottom: 10px; }
    </style>
</head>

<body>
    <div class="chat-container">

        <!-- Header -->
        <div class="chat-header">
            <div class="header-left">
                <h2>⚡ Operation GPT</h2>
                <div class="status-indicator">
                    <span class="status-dot"></span>
                    <span class="status-text">متصل</span>
                </div>
            </div>

            <!-- User Badge -->
            <div class="user-badge" id="user-badge">
                <div class="user-avatar" id="user-avatar">؟</div>
                <div class="user-info">
                    <span class="user-name" id="user-name">—</span>
                    <span class="user-role" id="user-role">—</span>
                </div>
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chat-box">
            <div class="welcome-msg">
                <div class="icon">🗄️</div>
                <p>مرحباً! اسألني عن قاعدة البيانات وسأجيب بنتائج فورية.</p>
            </div>
        </div>

        <!-- Input Area -->
        <div class="input-area">
            <input
                type="text"
                id="message-input"
                placeholder="اكتب طلبك بالعربي أو الإنجليزي…"
                autocomplete="off"
            >
            <button id="send-btn">
                <div class="loader" id="loader"></div>
                <span id="btn-text">إرسال ➤</span>
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // ── 1. Load user info into badge ──────────────────────────────────
        const user     = window.AppUser || {};
        const userName = user.name || 'مستخدم';
        const userRole = user.role || '';

        document.getElementById('user-name').textContent = userName;
        document.getElementById('user-role').textContent = userRole;

        const initials = userName.trim().split(' ')
            .map(w => w[0]).slice(0, 2).join('').toUpperCase();
        document.getElementById('user-avatar').textContent = initials || '؟';

        // ── 2. DOM refs ───────────────────────────────────────────────────
        const messageInput = document.getElementById('message-input');
        const sendBtn      = document.getElementById('send-btn');
        const chatBox      = document.getElementById('chat-box');
        const loader       = document.getElementById('loader');
        const btnText      = document.getElementById('btn-text');

        // ── 3. Helpers ────────────────────────────────────────────────────
        function scrollToBottom() {
            chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
        }

        function appendMessage(role, content, type = 'normal') {
            const msgDiv = document.createElement('div');
            let cls = `message ${role}`;
            if (type === 'error')   cls += ' error';
            if (type === 'success') cls += ' success';
            msgDiv.className = cls;

            if (content instanceof HTMLElement) {
                msgDiv.appendChild(content);
            } else {
                msgDiv.innerHTML = String(content).replace(/\n/g, '<br>');
            }

            chatBox.appendChild(msgDiv);
            scrollToBottom();
            return msgDiv;
        }

        function buildTable(data) {
            if (!data || data.length === 0) {
                const p = document.createElement('p');
                p.style.color = 'var(--text-muted)';
                p.style.margin = '8px 0 0';
                p.textContent = 'لا توجد بيانات مطابقة.';
                return p;
            }

            const wrapper = document.createElement('div');

            const count = document.createElement('div');
            count.className = 'table-count';
            count.textContent = `📊 ${data.length} سجل مُسترجع`;
            wrapper.appendChild(count);

            const tableWrapper = document.createElement('div');
            tableWrapper.className = 'table-wrapper';

            const table = document.createElement('table');
            table.className = 'report-table';

            // Head
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            Object.keys(data[0]).forEach(key => {
                const th = document.createElement('th');
                th.textContent = key;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Body
            const tbody = document.createElement('tbody');
            data.forEach(item => {
                const row = document.createElement('tr');
                Object.values(item).forEach(val => {
                    const td = document.createElement('td');
                    td.textContent = (val === null || val === undefined) ? '—' : String(val);
                    row.appendChild(td);
                });
                tbody.appendChild(row);
            });
            table.appendChild(tbody);

            tableWrapper.appendChild(table);
            wrapper.appendChild(tableWrapper);
            return wrapper;
        }

        // ── 4. Send message ───────────────────────────────────────────────
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            messageInput.value = '';
            messageInput.disabled = true;
            sendBtn.disabled = true;
            loader.style.display = 'block';
            btnText.style.display = 'none';

            appendMessage('user', message);

            try {
                const response = await fetch('/operation-gpt/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message })
                });

                const result = await response.json();

                if (result.type === 'error') {
                    appendMessage('bot', `⚠️ ${result.reply || result.message}`, 'error');

                } else if (result.type === 'report') {
                    const container = document.createElement('div');
                    // Show text label only if it's not HTML
                    if (result.reply && typeof result.reply === 'string' && !result.reply.startsWith('<')) {
                        const label = document.createElement('p');
                        label.style.margin = '0 0 6px';
                        label.style.color = 'var(--text-muted)';
                        label.textContent = result.reply;
                        container.appendChild(label);
                    }
                    container.appendChild(buildTable(result.data));
                    appendMessage('bot', container);

                } else {
                    appendMessage('bot', `✅ ${result.reply || result.message}`, 'success');
                }

            } catch (err) {
                appendMessage('bot', 'حدث خطأ تقني أثناء الاتصال بالخادم.', 'error');
                console.error(err);
            } finally {
                messageInput.disabled = false;
                sendBtn.disabled = false;
                loader.style.display = 'none';
                btnText.style.display = 'block';
                messageInput.focus();
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        messageInput.focus();
    });
    </script>
</body>

</html>