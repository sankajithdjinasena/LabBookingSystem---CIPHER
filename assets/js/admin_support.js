window.onerror = function(message, source, lineno, colno, error) {
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = "position:fixed;top:10px;left:50%;transform:translateX(-50%);background:#ffeedd;color:#d32f2f;border:1px solid #d32f2f;padding:15px;border-radius:6px;z-index:100000;box-shadow:0 4px 10px rgba(0,0,0,0.1);max-width:90%;word-wrap:break-word;font-family:monospace;font-size:13px;";
    errorDiv.innerHTML = `<strong>JavaScript Error:</strong><br>${message}<br>at ${source}:${lineno}:${colno}`;
    document.body.appendChild(errorDiv);
    return false;
};

(function() {
    function initAdminChat() {
        const chatWindow = document.getElementById('chatWindow');
        const msgInput = document.getElementById('msgInput');
        const sendBtn = document.getElementById('sendBtn');
        // activeUserId is set inline in the PHP script before this script loads

        if (typeof activeUserId === 'undefined' || !activeUserId) return;
        if (!chatWindow || !sendBtn) return;

        // Create debug window
        const debugBox = document.createElement('div');
        debugBox.style.cssText = "position:fixed;bottom:0;right:0;background:black;color:lime;font-size:10px;z-index:9999;max-height:200px;overflow-y:auto;width:300px;padding:5px;";
        document.body.appendChild(debugBox);
        function log(msg) {
            debugBox.innerHTML += `<div>${msg}</div>`;
            debugBox.scrollTop = debugBox.scrollHeight;
        }
        
        log("JS Initialized. ActiveUser: " + activeUserId);

        function formatTime(dateStr) {
            if(!dateStr) return '';
            try {
                const isoStr = dateStr.replace(' ', 'T');
                const d = new Date(isoStr);
                if (isNaN(d.getTime())) {
                    const parts = dateStr.split(' ');
                    if (parts[1]) {
                        const timeParts = parts[1].split(':');
                        if (timeParts[0] && timeParts[1]) {
                            return timeParts[0] + ':' + timeParts[1];
                        }
                    }
                    return '';
                }
                return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } catch(e) {
                console.error("formatTime error:", e);
                return '';
            }
        }

        function loadMessages() {
            fetch('../api/support.php?action=fetch&user_id=' + activeUserId)
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.messages && data.messages.length > 0) {
                        chatWindow.innerHTML = data.messages.map(m => {
                            const isAdmin = m.sender_role !== 'student';
                            const time = formatTime(m.created_at);
                            return `
                            <div class="msg-bubble ${isAdmin ? 'msg-outgoing' : 'msg-incoming'}">
                                ${!isAdmin ? `<div class="msg-sender">${m.sender_name}</div>` : ''}
                                <span>${m.message}</span>
                                <span class="msg-time">${time}</span>
                            </div>
                            `;
                        }).join('');
                        chatWindow.scrollTop = chatWindow.scrollHeight;
                    } else if (data.messages && data.messages.length === 0) {
                        chatWindow.innerHTML = '<p style="color:#888;text-align:center;">No messages yet.</p>';
                    } else {
                        chatWindow.innerHTML = '<p style="color:#888;text-align:center;">Error: ' + (data.error || 'Unknown error') + '</p>';
                    }
                } catch(e) {
                    console.error("JSON Parse error:", text);
                    chatWindow.innerHTML = '<p style="color:red;font-size:11px;"><b>RAW SERVER OUTPUT:</b><br>' + text.replace(/</g, "&lt;") + '</p>';
                }
            }).catch(e => {
                console.error("Fetch error:", e);
                chatWindow.innerHTML = '<p style="color:red;text-align:center;">Network error.</p>';
            });
        }

        sendBtn.addEventListener('click', () => {
            const text = msgInput.value.trim();
            log("SendBtn clicked. text: " + text);
            if(!text) { log("Text empty, aborting"); return; }
            
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('receiver_id', activeUserId);
            formData.append('message', text);

            log("Fetching ../api/support.php...");
            fetch('../api/support.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(responseText => {
                log("Received response: " + responseText.substring(0, 50));
                try {
                    const data = JSON.parse(responseText);
                    if(data.error) {
                        alert("Error: " + data.error);
                        log("Error in JSON: " + data.error);
                    } else {
                        log("Success, clearing input.");
                        msgInput.value = '';
                        loadMessages();
                    }
                } catch(e) {
                    console.error("Send JSON error:", responseText);
                    alert("Failed to send message. Server returned non-JSON data.");
                    log("Parse exception: " + e.message);
                }
            }).catch(e => {
                console.error("Send fetch error:", e);
                log("Fetch exception: " + e.message);
            });
        });

        loadMessages();
        setInterval(loadMessages, 5000);

        msgInput.addEventListener('keypress', (e) => {
            if(e.key === 'Enter') sendBtn.click();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminChat);
    } else {
        initAdminChat();
    }
})();
