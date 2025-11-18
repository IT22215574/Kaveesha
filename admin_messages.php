<?php
require_once __DIR__ . '/config.php';
require_admin();

$pageTitle = 'Messages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-container {
            height: 600px;
        }
        .conversation-item {
            cursor: pointer;
            transition: all 0.2s;
        }
        .conversation-item:hover {
            background-color: #F3F4F6;
        }
        .conversation-item.active {
            background-color: #EBF4FF;
            border-left: 4px solid #3B82F6;
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
        .unread-badge {
            background-color: #EF4444;
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Customer Messages</h1>
                    <p class="text-blue-100 mt-1">Manage customer conversations</p>
                </div>
                
                <!-- Main Content -->
                <div class="chat-container flex">
                    <!-- Conversations Sidebar -->
                    <div class="w-1/3 border-r bg-white">
                        <div class="p-4 border-b bg-gray-50">
                            <h3 class="font-semibold text-gray-800">Conversations</h3>
                        </div>
                        <div id="conversationsList" class="overflow-y-auto" style="height: calc(100% - 73px);">
                            <div class="p-4 text-center text-gray-500">
                                Loading conversations...
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Area -->
                    <div class="flex-1 flex flex-col">
                        <!-- Chat Header -->
                        <div id="chatHeader" class="p-4 border-b bg-gray-50">
                            <div class="text-gray-500 text-center">
                                Select a conversation to start messaging
                            </div>
                        </div>
                        
                        <!-- Messages Area -->
                        <div id="messagesArea" class="flex-1 p-4 overflow-y-auto bg-gray-50" style="display: none;">
                            <div id="messagesList" class="space-y-3">
                            </div>
                        </div>
                        
                        <!-- Message Input -->
                        <div id="messageInput" class="border-t bg-white p-4" style="display: none;">
                            <form id="messageForm" class="flex gap-2">
                                <input 
                                    type="text" 
                                    id="messageText" 
                                    placeholder="Type your reply..." 
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
                        
                        <!-- Empty State -->
                        <div id="emptyState" class="flex-1 flex items-center justify-center bg-gray-50">
                            <div class="text-center text-gray-500">
                                <div class="text-4xl mb-2">💬</div>
                                <p>Select a conversation to view messages</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let conversations = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadConversations();
            setupMessageForm();
        });
        
        function loadConversations() {
            fetch('messages_api.php?action=conversations')
                .then(response => response.json())
                .then(data => {
                    if (data.conversations) {
                        conversations = data.conversations;
                        renderConversations(data.conversations);
                    }
                })
                .catch(error => {
                    console.error('Error loading conversations:', error);
                    document.getElementById('conversationsList').innerHTML = 
                        '<div class="p-4 text-center text-red-500">Error loading conversations</div>';
                });
        }
        
        function renderConversations(conversations) {
            const conversationsList = document.getElementById('conversationsList');
            
            if (conversations.length === 0) {
                conversationsList.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <div class="text-2xl mb-2">📭</div>
                        <p>No conversations yet</p>
                    </div>
                `;
                return;
            }
            
            const conversationsHTML = conversations.map(conversation => {
                const unreadBadge = conversation.unread_count > 0 
                    ? `<span class="unread-badge">${conversation.unread_count}</span>`
                    : '';
                
                const activeClass = currentConversationId == conversation.conversation_id ? 'active' : '';
                
                return `
                    <div class="conversation-item p-4 border-b ${activeClass}" 
                         onclick="selectConversation('${conversation.conversation_id}', '${escapeHtml(conversation.username)}')">
                        <div class="flex justify-between items-start">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-medium text-gray-900 truncate">${escapeHtml(conversation.username)}</h4>
                                    ${unreadBadge}
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${escapeHtml(conversation.mobile_number)}</p>
                                <p class="text-sm text-gray-500 truncate mt-1">${escapeHtml(conversation.last_message || 'No messages')}</p>
                                <p class="text-xs text-gray-400 mt-1">${formatDateTime(conversation.last_message_at)}</p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            conversationsList.innerHTML = conversationsHTML;
        }
        
        function selectConversation(conversationId, username) {
            currentConversationId = conversationId;
            
            // Update UI
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('messagesArea').style.display = 'block';
            document.getElementById('messageInput').style.display = 'block';
            
            // Update header
            document.getElementById('chatHeader').innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center mr-3">
                        ${username.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">${escapeHtml(username)}</h3>
                        <p class="text-sm text-gray-600">Customer</p>
                    </div>
                </div>
            `;
            
            // Update active conversation styling
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.conversation-item').classList.add('active');
            
            // Load messages for this conversation
            loadMessages(conversationId);
        }
        
        function loadMessages(conversationId) {
            fetch(`messages_api.php?action=messages&conversation_id=${conversationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        renderMessages(data.messages);
                        markMessagesAsRead(conversationId);
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }
        
        function renderMessages(messages) {
            const messagesList = document.getElementById('messagesList');
            
            if (messages.length === 0) {
                messagesList.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <p>No messages in this conversation yet.</p>
                    </div>
                `;
                return;
            }
            
            const messagesHTML = messages.map(message => {
                const isOwn = message.sender_type === 'admin';
                const messageClass = isOwn ? 'sent' : 'received';
                const alignClass = isOwn ? 'justify-end' : 'justify-start';
                
                return `
                    <div class="flex ${alignClass}">
                        <div class="message-bubble ${messageClass} px-4 py-2 rounded-lg">
                            <div class="text-sm">${escapeHtml(message.message)}</div>
                            <div class="text-xs mt-1 ${isOwn ? 'text-blue-100' : 'text-gray-500'}">
                                ${escapeHtml(message.sender_name)} • ${formatDateTime(message.created_at)}
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
            const input = document.getElementById('messageText');
            const button = document.getElementById('sendButton');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!currentConversationId) {
                    alert('Please select a conversation first');
                    return;
                }
                
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
                        conversation_id: currentConversationId,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        // Add the new message without full reload
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
        
        function markMessagesAsRead(conversationId) {
            fetch('messages_api.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: conversationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh conversations to update unread counts
                    loadConversations();
                }
            })
            .catch(error => console.error('Error marking messages as read:', error));
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
            if (!dateTimeString) return '';
            const date = new Date(dateTimeString);
            return date.toLocaleString();
        }
        
        function appendNewMessage(message) {
            const messagesList = document.getElementById('messagesList');
            const isOwn = message.sender_type === 'admin';
            const messageClass = isOwn ? 'sent' : 'received';
            const alignClass = isOwn ? 'justify-end' : 'justify-start';
            
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