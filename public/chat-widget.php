<?php
// Public Chat Widget JavaScript
require_once __DIR__ . '/../config/config.php';

// Generate unique visitor ID from IP + User Agent
$visitorIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$visitorId = substr(hash('sha256', $visitorIp . $_SERVER['HTTP_USER_AGENT']), 0, 16);

?>
(function() {
    const VISITOR_ID = '<?php echo $visitorId; ?>';
    const API_URL = '/api/chat';
    
    // Create chat widget HTML
    const chatHTML = `
        <div id="umojawetu-chat-widget" style="position: fixed; bottom: 20px; right: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; z-index: 9999;">
            <!-- Chat Bubble Button -->
            <button id="chat-toggle" style="
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #d12828 0%, #a01a1a 100%);
                color: white;
                border: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: transform 0.3s, box-shadow 0.3s;
            ">
                💬
            </button>
            
            <!-- Chat Window -->
            <div id="chat-window" style="
                position: absolute;
                bottom: 80px;
                right: 0;
                width: 380px;
                max-height: 500px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 5px 40px rgba(0,0,0,0.16);
                display: none;
                flex-direction: column;
                overflow: hidden;
            ">
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #d12828 0%, #a01a1a 100%);
                    color: white;
                    padding: 16px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                ">
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Live Support</h3>
                        <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">We typically reply within minutes</p>
                    </div>
                    <button id="chat-close" style="
                        background: none;
                        border: none;
                        color: white;
                        font-size: 20px;
                        cursor: pointer;
                        padding: 0;
                    ">✕</button>
                </div>
                
                <!-- Messages -->
                <div id="chat-messages" style="
                    flex: 1;
                    overflow-y: auto;
                    padding: 16px;
                    background: #f9f9f9;
                "></div>
                
                <!-- Input Form -->
                <form id="chat-form" style="
                    padding: 12px;
                    border-top: 1px solid #e5e7eb;
                    background: white;
                ">
                    <div style="display: flex; gap: 8px;">
                        <input 
                            type="text" 
                            id="chat-input" 
                            placeholder="Type your message..." 
                            style="
                                flex: 1;
                                border: 1px solid #e5e7eb;
                                border-radius: 6px;
                                padding: 8px 12px;
                                font-size: 13px;
                                outline: none;
                            "
                        />
                        <button type="submit" style="
                            background: #d12828;
                            color: white;
                            border: none;
                            border-radius: 6px;
                            padding: 8px 16px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 13px;
                        ">Send</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChat);
    } else {
        initChat();
    }
    
    async function initChat() {
        // Add widget to page
        document.body.insertAdjacentHTML('beforeend', chatHTML);
        
        const toggleBtn = document.getElementById('chat-toggle');
        const closeBtn = document.getElementById('chat-close');
        const chatWindow = document.getElementById('chat-window');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        
        // Toggle window
        toggleBtn.addEventListener('click', () => {
            if (chatWindow.style.display === 'none') {
                chatWindow.style.display = 'flex';
                toggleBtn.style.display = 'none';
                input.focus();
                updateVisitorStatus();
                loadMessages();
            }
        });
        
        closeBtn.addEventListener('click', () => {
            chatWindow.style.display = 'none';
            toggleBtn.style.display = 'flex';
        });
        
        // Send message
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = input.value.trim();
            if (!message) return;
            
            try {
                // Create conversation if needed
                let conversationId = localStorage.getItem('umojawetu_conversation_id');
                if (!conversationId) {
                    const convResponse = await fetch('<?php echo $site_url; ?>/api/chat/create-conversation.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            visitor_id: VISITOR_ID,
                            visitor_name: localStorage.getItem('umojawetu_visitor_name') || 'Anonymous',
                            visitor_email: localStorage.getItem('umojawetu_visitor_email') || '',
                        })
                    });
                    const convData = await convResponse.json();
                    conversationId = convData.conversation_id;
                    localStorage.setItem('umojawetu_conversation_id', conversationId);
                }
                
                // Send message
                const response = await fetch(API_URL + '/send-message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        message: message,
                        sender_type: 'visitor',
                        sender_name: localStorage.getItem('umojawetu_visitor_name') || 'Anonymous',
                        sender_email: localStorage.getItem('umojawetu_visitor_email') || ''
                    })
                });
                
                if (response.ok) {
                    input.value = '';
                    loadMessages();
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        });
        
        // Load messages periodically
        loadMessages();
        setInterval(loadMessages, 3000);
    }
    
    async function loadMessages() {
        const conversationId = localStorage.getItem('umojawetu_conversation_id');
        if (!conversationId) return;
        
        try {
            const response = await fetch(API_URL + `/get-messages.php?id=${conversationId}`);
            const data = await response.json();
            
            if (data.success) {
                const container = document.getElementById('chat-messages');
                container.innerHTML = '';
                
                data.messages.forEach(msg => {
                    const isVisitor = msg.SenderType === 'visitor';
                    const msgDiv = document.createElement('div');
                    msgDiv.style.cssText = `
                        margin-bottom: 12px;
                        display: flex;
                        justify-content: ${isVisitor ? 'flex-end' : 'flex-start'};
                    `;
                    
                    msgDiv.innerHTML = `
                        <div style="
                            max-width: 70%;
                            background: ${isVisitor ? '#d12828' : '#e5e7eb'};
                            color: ${isVisitor ? 'white' : '#333'};
                            padding: 8px 12px;
                            border-radius: 8px;
                            font-size: 13px;
                            word-wrap: break-word;
                        ">
                            ${msg.Message.replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                            <div style="font-size: 11px; margin-top: 4px; opacity: 0.7;">
                                ${new Date(msg.CreatedAt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                            </div>
                        </div>
                    `;
                    
                    container.appendChild(msgDiv);
                });
                
                // Scroll to bottom
                container.scrollTop = container.scrollHeight;
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }
    
    function updateVisitorStatus() {
        fetch('<?php echo $site_url; ?>/api/chat/update-visitor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                visitor_id: VISITOR_ID,
                is_online: true,
                current_page: window.location.pathname
            })
        }).catch(e => console.error('Status update failed:', e));
    }
    
    // Hover effects
    window.addEventListener('load', () => {
        const btn = document.getElementById('chat-toggle');
        btn?.addEventListener('mouseenter', () => {
            btn.style.transform = 'scale(1.1)';
            btn.style.boxShadow = '0 6px 20px rgba(0,0,0,0.25)';
        });
        btn?.addEventListener('mouseleave', () => {
            btn.style.transform = 'scale(1)';
            btn.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        });
    });
})();
