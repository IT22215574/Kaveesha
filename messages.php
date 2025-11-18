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
    <title><?= htmlspecialchars($pageTitle) ?> - Kaveesha</title>
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
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Messages</h1>
                    <p class="text-blue-100 mt-1">Chat with Yoma Electronics support</p>
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

    <script>
        let currentUserId = <?= json_encode($_SESSION['user_id']) ?>;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages();
            setupMessageForm();
        });
        
        function loadMessages() {
            fetch(`messages_api.php?action=messages&conversation_id=${currentUserId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        renderMessages(data.messages);
                        markMessagesAsRead();
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    document.getElementById('messagesList').innerHTML = 
                        '<div class="text-center text-red-500 py-4">Error loading messages</div>';
                });
        }
        
        function renderMessages(messages) {
            const messagesList = document.getElementById('messagesList');
            
            if (messages.length === 0) {
                messagesList.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <div class="text-4xl mb-2">💬</div>
                        <p>No messages yet. Start a conversation with our support team!</p>
                    </div>
                `;
                return;
            }
            
            const messagesHTML = messages.map(message => {
                const isOwn = message.sender_type === 'user';
                const messageClass = isOwn ? 'sent' : 'received';
                const alignClass = isOwn ? 'justify-end' : 'justify-start';
                
                return `
                    <div class="flex ${alignClass}">
                        <div class="message-bubble ${messageClass} px-4 py-2 rounded-lg">
                            <div class="text-sm">${escapeHtml(message.message)}</div>
                            <div class="text-xs mt-1 ${isOwn ? 'text-blue-100' : 'text-gray-500'}">
                                ${formatDateTime(message.created_at)}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            messagesList.innerHTML = messagesHTML;
            scrollToBottom();
        }
        
        function setupMessageForm() {
            const form = document.getElementById('messageForm');
            const input = document.getElementById('messageInput');
            const button = document.getElementById('sendButton');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = input.value.trim();
                if (!message) return;
                
                button.disabled = true;
                button.textContent = 'Sending...';
                
                fetch('messages_api.php?action=send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        // Add the new message to the display without full reload
                        appendNewMessage(data.message);
                    } else {
                        alert('Failed to send message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Error sending message');
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Send';
                    input.focus();
                });
            });
        }
        
        function markMessagesAsRead() {
            fetch('messages_api.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: currentUserId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update navigation notification
                    updateUnreadCount();
                }
            })
            .catch(error => console.error('Error marking messages as read:', error));
        }
        

        
        function updateUnreadCount() {
            fetch('messages_api.php?action=unread_count')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.messages-badge');
                    if (badge) {
                        if (data.unread_count > 0) {
                            badge.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error updating unread count:', error));
        }
        
        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleString();
        }
        
        function appendNewMessage(message) {
            const messagesList = document.getElementById('messagesList');
            const isOwn = message.sender_type === 'user';
            const messageClass = isOwn ? 'sent' : 'received';
            const alignClass = isOwn ? 'justify-end' : 'justify-start';
            
            // Remove "no messages" placeholder if it exists
            const placeholder = messagesList.querySelector('.text-center');
            if (placeholder && placeholder.textContent.includes('No messages yet')) {
                messagesList.innerHTML = '';
            }
            
            const messageHTML = `
                <div class="flex ${alignClass}">
                    <div class="message-bubble ${messageClass} px-4 py-2 rounded-lg">
                        <div class="text-sm">${escapeHtml(message.message)}</div>
                        <div class="text-xs mt-1 ${isOwn ? 'text-blue-100' : 'text-gray-500'}">
                            ${formatDateTime(message.created_at)}
                        </div>
                    </div>
                </div>
            `;
            
            messagesList.insertAdjacentHTML('beforeend', messageHTML);
            scrollToBottom();
        }
    </script>
</body>
</html>