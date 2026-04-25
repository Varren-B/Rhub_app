// Messages JavaScript - Peer to Peer Communication

let currentConversationId = null;
let messagePollingInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    initConversationList();
    initMessageForm();
    checkForConversationParam();
    startMessagePolling();
});

function initConversationList() {
    const conversations = document.querySelectorAll('.conversation-item');
    
    conversations.forEach(conv => {
        conv.addEventListener('click', function() {
            const conversationId = this.dataset.conversationId;
            selectConversation(conversationId);
            
            // Update active state
            conversations.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // Remove unread indicator
            const unreadBadge = this.querySelector('.unread-badge');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        });
    });
}

function checkForConversationParam() {
    const urlParams = new URLSearchParams(window.location.search);
    const conversationId = urlParams.get('conversation');
    
    if (conversationId) {
        selectConversation(conversationId);
        
        // Highlight the conversation in the list
        const convItem = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        if (convItem) {
            document.querySelectorAll('.conversation-item').forEach(c => c.classList.remove('active'));
            convItem.classList.add('active');
        }
    }
}

function selectConversation(conversationId) {
    currentConversationId = conversationId;
    
    // Show loading state
    const chatArea = document.getElementById('chat-messages');
    if (chatArea) {
        chatArea.innerHTML = '<div class="loading-messages"><span class="spinner"></span> Loading messages...</div>';
    }
    
    // Show chat area on mobile
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        chatContainer.classList.add('show-chat');
    }
    
    // Fetch messages
    loadMessages(conversationId);
    
    // Enable message form
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    if (messageForm && messageInput) {
        messageInput.disabled = false;
        messageInput.focus();
    }
}

function loadMessages(conversationId) {
    fetch(`api/messages.php?action=get_messages&conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMessages(data.messages, data.current_user_id);
            } else {
                showChatError(data.message);
            }
        })
        .catch(error => {
            showChatError('Failed to load messages');
        });
}

function renderMessages(messages, currentUserId) {
    const chatArea = document.getElementById('chat-messages');
    if (!chatArea) return;
    
    if (messages.length === 0) {
        chatArea.innerHTML = '<div class="no-messages">No messages yet. Start the conversation!</div>';
        return;
    }
    
    let html = '';
    let lastDate = '';
    
    messages.forEach(msg => {
        const msgDate = new Date(msg.created_at).toLocaleDateString();
        
        // Add date separator
        if (msgDate !== lastDate) {
            html += `<div class="date-separator"><span>${formatDate(msg.created_at)}</span></div>`;
            lastDate = msgDate;
        }
        
        const isMine = parseInt(msg.sender_id) === parseInt(currentUserId);
        const messageClass = isMine ? 'message sent' : 'message received';
        
        html += `
            <div class="${messageClass}">
                <div class="message-content">
                    <p>${escapeHtml(msg.message)}</p>
                    <span class="message-time">${formatTime(msg.created_at)}</span>
                </div>
            </div>
        `;
    });
    
    chatArea.innerHTML = html;
    
    // Scroll to bottom
    chatArea.scrollTop = chatArea.scrollHeight;
}

function showChatError(message) {
    const chatArea = document.getElementById('chat-messages');
    if (chatArea) {
        chatArea.innerHTML = `<div class="chat-error">${message}</div>`;
    }
}

function initMessageForm() {
    const messageForm = document.getElementById('message-form');
    
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
        
        // Enter to send (Shift+Enter for new line)
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    }
}

function sendMessage() {
    if (!currentConversationId) {
        showNotification('Please select a conversation first', 'error');
        return;
    }
    
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('conversation_id', currentConversationId);
    formData.append('message', message);
    
    // Disable input while sending
    messageInput.disabled = true;
    
    // Optimistically add message to chat
    addMessageToChat(message, true);
    messageInput.value = '';
    
    fetch('api/messages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showNotification(data.message, 'error');
            // Remove optimistic message on failure
            removeLastMessage();
        }
    })
    .catch(error => {
        showNotification('Failed to send message', 'error');
        removeLastMessage();
    })
    .finally(() => {
        messageInput.disabled = false;
        messageInput.focus();
    });
}

function addMessageToChat(message, isMine) {
    const chatArea = document.getElementById('chat-messages');
    if (!chatArea) return;
    
    // Remove "no messages" placeholder if present
    const noMessages = chatArea.querySelector('.no-messages');
    if (noMessages) {
        noMessages.remove();
    }
    
    const messageClass = isMine ? 'message sent' : 'message received';
    const messageHtml = `
        <div class="${messageClass} new-message">
            <div class="message-content">
                <p>${escapeHtml(message)}</p>
                <span class="message-time">${formatTime(new Date())}</span>
            </div>
        </div>
    `;
    
    chatArea.insertAdjacentHTML('beforeend', messageHtml);
    chatArea.scrollTop = chatArea.scrollHeight;
}

function removeLastMessage() {
    const chatArea = document.getElementById('chat-messages');
    if (chatArea) {
        const lastMessage = chatArea.querySelector('.message:last-child');
        if (lastMessage) {
            lastMessage.remove();
        }
    }
}

function startMessagePolling() {
    // Poll for new messages every 5 seconds
    messagePollingInterval = setInterval(() => {
        if (currentConversationId) {
            checkNewMessages();
        }
        updateUnreadCount();
    }, 5000);
}

function checkNewMessages() {
    fetch(`api/messages.php?action=get_messages&conversation_id=${currentConversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const chatArea = document.getElementById('chat-messages');
                const currentCount = chatArea.querySelectorAll('.message').length;
                
                if (data.messages.length > currentCount) {
                    renderMessages(data.messages, data.current_user_id);
                }
            }
        })
        .catch(error => {
            // Silently fail polling
        });
}

function updateUnreadCount() {
    fetch('api/messages.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.nav-unread-badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            // Silently fail
        });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined
        });
    }
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Back button for mobile
document.addEventListener('click', function(e) {
    if (e.target.closest('.back-to-list')) {
        const chatContainer = document.querySelector('.chat-container');
        if (chatContainer) {
            chatContainer.classList.remove('show-chat');
        }
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
});
