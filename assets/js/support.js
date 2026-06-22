console.log("Support JS loaded v2!");

document.addEventListener('DOMContentLoaded', () => {
    const adminSelect = document.getElementById('adminSelect');
    const chatDiv = document.getElementById('supportChat');
    const msgInput = document.getElementById('supportMsg');
    const sendBtn = document.getElementById('supportSend');

    if (!adminSelect) {
        console.error("adminSelect element not found!");
        return;
    }

    // Explicitly set fetching text
    adminSelect.innerHTML = '<option value="">Connecting to server...</option>';

    // Load Admins
    fetch('api/support.php?action=fetch_admins')
    .then(r => r.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if(data.admins) {
                adminSelect.innerHTML = '<option value="">-- Choose an Admin/Faculty --</option>' + 
                    data.admins.map(a => `<option value="${a.id}">${a.full_name} (${a.role})</option>`).join('');
                
                // If there's already a value selected, load messages
                if (adminSelect.value) {
                    loadMessages();
                }
            } else {
                adminSelect.innerHTML = '<option value="">Error loading admins</option>';
            }
        } catch(e) {
            console.error("JSON Parse error:", text);
            adminSelect.innerHTML = '<option value="">Network error (JSON)</option>';
            if (chatDiv) chatDiv.innerHTML = '<p style="color:red;font-size:11px;"><b>RAW SERVER OUTPUT:</b><br>' + text.replace(/</g, "&lt;") + '</p>';
        }
    })
    .catch(e => {
        console.error("Fetch error:", e);
        adminSelect.innerHTML = '<option value="">Network error (Fetch)</option>';
    });

    adminSelect.addEventListener('change', loadMessages);

    function loadMessages() {
        const adminId = adminSelect.value;
        if (!adminId) {
            chatDiv.innerHTML = '<p style="color:#888;text-align:center;">Select an admin to view messages.</p>';
            return;
        }

        fetch('api/support.php?action=fetch&admin_id=' + adminId)
        .then(r => r.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                chatDiv.innerHTML = data.messages.map(m => `
                    <div style="margin-bottom:8px;">
                        <strong>${m.sender_name} <span style="font-size:10px;color:#888;">(${m.sender_role})</span>:</strong>
                        <span style="background: ${m.sender_role==='student'?'#e1f5fe':'#fff3e0'}; padding: 4px 8px; border-radius: 4px; display:inline-block; margin-left: 4px;">
                            ${m.message}
                        </span>
                    </div>
                `).join('');
                chatDiv.scrollTop = chatDiv.scrollHeight;
            } else {
                chatDiv.innerHTML = '<p style="color:#888;text-align:center;">No messages yet with this admin.</p>';
            }
        }).catch(e => console.error("Error loading messages:", e));
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', () => {
            const text = msgInput.value.trim();
            const adminId = adminSelect.value;
            if(!text || !adminId) {
                alert("Please select a recipient and type a message.");
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('receiver_id', adminId);
            formData.append('message', text);

            fetch('api/support.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(() => {
                msgInput.value = '';
                loadMessages();
            }).catch(e => console.error("Error sending message:", e));
        });
    }

    setInterval(loadMessages, 5000); // Poll every 5s
});
