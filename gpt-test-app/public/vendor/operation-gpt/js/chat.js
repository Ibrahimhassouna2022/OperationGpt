document.addEventListener('DOMContentLoaded', () => {
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const chatBox = document.getElementById('chat-box');
    const loader = document.getElementById('loader');
    const btnText = document.getElementById('btn-text');

    function appendMessage(role, content, type = 'normal') {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${role} ${type === 'error' ? 'error' : ''}`;
        
        if (typeof content === 'string') {
            msgDiv.innerHTML = content.replace(/\n/g, '<br>');
        } else {
            msgDiv.appendChild(content);
        }
        
        chatBox.appendChild(msgDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function createTable(data) {
        if (!data || data.length === 0) return 'لا توجد بيانات لعرضها.';
        
        const table = document.createElement('table');
        table.className = 'report-table';
        
        // Header
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
                td.textContent = val;
                row.appendChild(td);
            });
            tbody.appendChild(row);
        });
        table.appendChild(tbody);
        
        return table;
    }

    async function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;

        // UI Reset
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
                appendMessage('bot', `⚠️ خطأ: ${result.message}`, 'error');
            } else if (result.type === 'report') {
                const table = createTable(result.data);
                appendMessage('bot', result.message);
                appendMessage('bot', table);
            } else {
                appendMessage('bot', `✅ ${result.message}`);
            }

        } catch (error) {
            appendMessage('bot', 'حدث خطأ تقني أثناء الاتصال بالخادم.', 'error');
            console.error(error);
        } finally {
            messageInput.disabled = false;
            sendBtn.disabled = false;
            loader.style.display = 'none';
            btnText.style.display = 'block';
            messageInput.focus();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});