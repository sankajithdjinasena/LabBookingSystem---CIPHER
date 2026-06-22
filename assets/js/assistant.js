document.addEventListener('DOMContentLoaded', () => {
    // Create UI Elements
    const widgetHtml = `
        <div class="ai-assistant-btn" id="aiBtn">🤖</div>
        <div class="ai-assistant-window" id="aiWindow">
            <div class="ai-header">
                <div class="ai-header-title"><span>🤖</span> SURAS Assistant</div>
                <div>
                    <button class="ai-close-btn" id="aiClear" title="Restart Chat" style="margin-right: 8px;">🔄</button>
                    <button class="ai-close-btn" id="aiClose">&times;</button>
                </div>
            </div>
            <div class="ai-messages" id="aiMsgs">
                <div class="ai-msg system">Hi! I'm your AI Booking Assistant. Try saying: "I need a lab for 4 people tomorrow."</div>
            </div>
            <div class="ai-input-area">
                <input type="text" class="ai-input" id="aiInput" placeholder="Type a message...">
                <button class="ai-send" id="aiSend">➤</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', widgetHtml);

    const btn = document.getElementById('aiBtn');
    const win = document.getElementById('aiWindow');
    const closeBtn = document.getElementById('aiClose');
    const clearBtn = document.getElementById('aiClear');
    const input = document.getElementById('aiInput');
    const sendBtn = document.getElementById('aiSend');
    const msgsContainer = document.getElementById('aiMsgs');

    btn.addEventListener('click', () => win.classList.toggle('is-open'));
    closeBtn.addEventListener('click', () => win.classList.remove('is-open'));

    clearBtn.addEventListener('click', () => {
        msgsContainer.innerHTML = '<div class="ai-msg system">Chat cleared. Hi! I\'m your AI Booking Assistant. Try saying: "I need a lab for 4 people tomorrow."</div>';
        fetch('api/assistant.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'clear'})
        });
    });

    const addMessage = (text, sender, action = null) => {
        const div = document.createElement('div');
        div.className = `ai-msg ${sender}`;
        
        // Convert basic markdown-like bold to HTML
        let formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        div.innerHTML = formattedText;
        
        if (action) {
            const a = document.createElement('a');
            a.className = 'ai-msg-action';
            a.href = action.url;
            a.textContent = action.label;
            div.appendChild(document.createElement('br'));
            div.appendChild(a);
        }

        msgsContainer.appendChild(div);
        msgsContainer.scrollTop = msgsContainer.scrollHeight;
    };

    const sendMessage = async () => {
        const text = input.value.trim();
        if (!text) return;
        
        addMessage(text, 'user');
        input.value = '';

        try {
            const res = await fetch('api/assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            
            if (data.reply) {
                addMessage(data.reply, 'system', data.action);
            }
        } catch (e) {
            addMessage("Oops, I lost connection to the server.", 'system');
        }
    };

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});
