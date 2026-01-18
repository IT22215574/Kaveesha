<?php
require_once __DIR__ . '/config.php';
require_login();

$pageTitle = 'Messages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - mctronicservice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-container {
            height: 500px;
        }
        .message-bubble {
            max-width: 70%;
            word-wrap: break-word;
        }
        .message-bubble.sent {
            background-color: #3B82F6;
            color: white;
            margin-left: auto;
        }
        .message-bubble.received {
            background-color: #F3F4F6;
            color: #1F2937;
        }
        .notification-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 8px;
            height: 8px;
            background-color: #EF4444;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/includes/user_nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="text-white p-6" style="background: linear-gradient(to right, #692f69, #8b4f8b);">
                    <h1 class="text-2xl font-bold">Messages</h1>
                    <p class="mt-1" style="color: #e8d4e8;">Chat with MC YOMA electronic support</p>
                </div>
                
                <!-- Chat Container -->
                <div class="chat-container flex flex-col">
                    <!-- Messages Area -->
                    <div id="messagesArea" class="flex-1 p-4 overflow-y-auto bg-gray-50">
                        <div id="messagesList" class="space-y-3">
                            <div class="text-center text-gray-500 py-4">
                                Loading messages...
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Input -->
                    <div class="border-t bg-white p-4">
                        <form id="messageForm" class="flex gap-2">
                            <input 
                                type="text" 
                                id="messageInput" 
                                placeholder="Type your message..." 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                            <button 
                                type="submit" 
                                id="sendButton"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                            >
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/chat_client.js"></script>
    <script>
        const currentUserId = <?= json_encode($_SESSION['user_id']) ?>;
        const messagesList = document.getElementById('messagesList');
        const form = document.getElementById('messageForm');
        const input = document.getElementById('messageInput');
        const button = document.getElementById('sendButton');

        function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        function formatDateTime(dateTimeString) { const date = new Date(dateTimeString); return date.toLocaleString(); }
        function scrollToBottom() { const area = document.getElementById('messagesArea'); area.scrollTop = area.scrollHeight; }

        function renderInitialPlaceholder() {
            messagesList.innerHTML = `<div class="text-center text-gray-500 py-8"><div class="text-4xl mb-2">💬</div><p>Loading conversation...</p></div>`;
        }

        function appendMessage(message) {
            const isOwn = message.sender_type === 'user';
            const messageClass = isOwn ? 'sent' : 'received';
            const alignClass = isOwn ? 'justify-end' : 'justify-start';
            const placeholder = messagesList.querySelector('.text-center');
            if (placeholder && /Loading|No messages/.test(placeholder.textContent)) {
                messagesList.innerHTML = '';
            }
            const html = `<div class="flex ${alignClass}"><div class="message-bubble ${messageClass} px-4 py-2 rounded-lg"><div class="text-sm">${escapeHtml(message.message)}</div><div class="text-xs mt-1 ${isOwn ? 'text-blue-100' : 'text-gray-500'}">${formatDateTime(message.created_at)}</div></div></div>`;
            messagesList.insertAdjacentHTML('beforeend', html);
        }

        function markMessagesAsRead() {
            fetch('messages_api.php?action=mark_read', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversation_id: currentUserId })
            }).catch(err => console.warn('Mark read failed', err));
        }

        // Chat client instance
        const chat = new ChatClient({ conversationId: currentUserId });
        chat.on('batch', batch => {
            if (batch.length === 0 && messagesList.children.length === 0) {
                messagesList.innerHTML = `<div class="text-center text-gray-500 py-8"><div class=\"text-4xl mb-2\">💬</div><p>No messages yet. Start a conversation!</p></div>`;
                return;
            }
            const frag = document.createDocumentFragment();
            batch.forEach(m => {
                const isOwn = m.sender_type === 'user';
                const messageClass = isOwn ? 'sent' : 'received';
                const alignClass = isOwn ? 'justify-end' : 'justify-start';
                const wrap = document.createElement('div');
                wrap.className = 'flex ' + alignClass;
                wrap.innerHTML = `<div class="message-bubble ${messageClass} px-4 py-2 rounded-lg"><div class="text-sm">${escapeHtml(m.message)}</div><div class="text-xs mt-1 ${isOwn ? 'text-blue-100' : 'text-gray-500'}">${formatDateTime(m.created_at)}</div></div>`;
                frag.appendChild(wrap);
            });
            const placeholder = messagesList.querySelector('.text-center');
            if (placeholder && /Loading/.test(placeholder.textContent)) messagesList.innerHTML = '';
            messagesList.appendChild(frag);
            scrollToBottom();
            markMessagesAsRead();
        }).on('error', err => {
            if (!messagesList.querySelector('.error-banner')) {
                messagesList.insertAdjacentHTML('beforeend', `<div class="error-banner text-center text-red-500 py-2">Connection issue. Retrying...</div>`);
            }
        });

        form.addEventListener('submit', function(e){
            e.preventDefault();
            const message = input.value.trim();
            if (!message) return;
            button.disabled = true; button.textContent = 'Sending...';
            fetch('messages_api.php?action=send', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    input.value = '';
                    appendMessage(data.message);
                    scrollToBottom();
                    markMessagesAsRead();
                } else {
                    alert('Failed to send message');
                }
            }).catch(err => {
                console.error('Send failed', err);
                alert('Error sending message');
            }).finally(() => {
                button.disabled = false; button.textContent = 'Send'; input.focus();
            });
        });

        document.addEventListener('DOMContentLoaded', function(){
            renderInitialPlaceholder();
            chat.start();
        });
    </script>
</body>
</html>