<?php
declare(strict_types=1);

/**
 * Chatbot Front-end Floating Widget Component for KGF Mens Wear
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!-- Chatbot Floating Action Button & Modal Widget -->
<div id="kgfChatbotContainer" class="kgf-chatbot-wrapper">
    <!-- Floating Launcher Button (Fixed to Right Corner) -->
    <button id="kgfChatbotLauncher" class="kgf-chatbot-launcher" aria-label="Toggle KGF Customer Support Chatbot" title="Chat with KGF Style & Order Assistant">
        <span class="kgf-chatbot-badge" id="kgfChatbotBadge">1</span>
        
        <!-- Open Icon (Chat Bubble) -->
        <svg class="kgf-icon-chat" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <circle cx="9" cy="10" r="1" fill="currentColor"></circle>
            <circle cx="12" cy="10" r="1" fill="currentColor"></circle>
            <circle cx="15" cy="10" r="1" fill="currentColor"></circle>
        </svg>

        <!-- Close Icon (Cross) -->
        <svg class="kgf-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>

    <!-- Chat Modal Window -->
    <div id="kgfChatbotWindow" class="kgf-chatbot-window hidden">
        <!-- Header -->
        <div class="kgf-chat-header">
            <div class="kgf-chat-header-info">
                <div class="kgf-chat-avatar">
                    <img src="<?= url('assets/kgf-logo-shield.png') ?>" alt="KGF Bot Avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/4712/4712035.png'">
                    <span class="kgf-status-dot"></span>
                </div>
                <div>
                    <h3 class="kgf-chat-title">KGF Mens Support</h3>
                    <span class="kgf-chat-subtitle">Online · Instant Answers</span>
                </div>
            </div>
            <div class="kgf-chat-header-actions">
                <button id="kgfChatbotClearBtn" class="kgf-chat-icon-btn" title="Clear Conversation" aria-label="Clear Chat History">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
                <button id="kgfChatbotCloseBtn" class="kgf-chat-icon-btn" title="Close Chat" aria-label="Close Chat Window">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Scroll Area -->
        <div id="kgfChatbotBody" class="kgf-chat-body">
            <div id="kgfChatbotMessages" class="kgf-chat-messages">
                <!-- Dynamic Chat Bubbles Inserted Here -->
            </div>
            
            <!-- Typing Indicator -->
            <div id="kgfChatbotTyping" class="kgf-chat-typing hidden">
                <div class="kgf-chat-avatar-sm">
                    <img src="<?= url('assets/kgf-logo-shield.png') ?>" alt="KGF Bot">
                </div>
                <div class="kgf-typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <!-- Quick Action Prompt Chips -->
        <div id="kgfChatbotQuickReplies" class="kgf-chat-quick-replies">
            <button type="button" class="kgf-chip" data-msg="👕 Search Shirts & Jeans">👕 Shirts & Jeans</button>
            <button type="button" class="kgf-chip" data-msg="📦 Track My Order">📦 Track Order</button>
            <button type="button" class="kgf-chip" data-msg="🚚 Delivery & Returns">🚚 Shipping Policy</button>
            <button type="button" class="kgf-chip" data-msg="🎁 Discounts & Offers">🎁 Offers</button>
            <button type="button" class="kgf-chip" data-msg="📐 Size Guide">📐 Size Guide</button>
        </div>

        <!-- Input Area -->
        <form id="kgfChatbotForm" class="kgf-chat-footer">
            <input type="text" id="kgfChatbotInput" class="kgf-chat-input" placeholder="Type a message or order #..." autocomplete="off">
            <button type="submit" id="kgfChatbotSendBtn" class="kgf-chat-send-btn" aria-label="Send Message" title="Send Message">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </div>
</div>

<!-- Chatbot Modern Styling (Luxury Dark & Accent Gold) -->
<style>
/* Chatbot Container Positioning - Fixed to Bottom Right Corner */
.kgf-chatbot-wrapper {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 99999;
    font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
}

/* Floating Launcher Button */
.kgf-chatbot-launcher {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fbbf24;
    border: 2px solid rgba(251, 191, 36, 0.4);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35), 0 0 0 4px rgba(251, 191, 36, 0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    outline: none;
}

.kgf-chatbot-launcher:hover {
    transform: scale(1.08) translateY(-3px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.45), 0 0 0 6px rgba(251, 191, 36, 0.25);
    border-color: #fbbf24;
}

.kgf-chatbot-launcher:active {
    transform: scale(0.95);
}

.kgf-chatbot-launcher .kgf-icon-close {
    display: none;
    color: #ffffff;
}

.kgf-chatbot-wrapper.active .kgf-chatbot-launcher {
    background: #0f172a;
    border-color: #ef4444;
    box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
}

.kgf-chatbot-wrapper.active .kgf-chatbot-launcher .kgf-icon-chat {
    display: none;
}

.kgf-chatbot-wrapper.active .kgf-chatbot-launcher .kgf-icon-close {
    display: block;
}

/* Unread Badge */
.kgf-chatbot-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #0f172a;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    animation: kgfPulse 2s infinite;
}

.kgf-chatbot-badge.hidden {
    display: none;
}

@keyframes kgfPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { transform: scale(1.15); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Chat Modal Window */
.kgf-chatbot-window {
    position: absolute;
    bottom: 75px;
    right: 0;
    width: 380px;
    max-width: calc(100vw - 30px);
    height: 560px;
    max-height: calc(100vh - 120px);
    background: #0f172a;
    color: #f8fafc;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    opacity: 1;
    transform: scale(1) translateY(0);
}

.kgf-chatbot-window.hidden {
    opacity: 0;
    transform: scale(0.85) translateY(20px);
    pointer-events: none;
}

/* Header */
.kgf-chat-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.kgf-chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.kgf-chat-avatar {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #334155;
    padding: 2px;
    border: 1px solid rgba(251, 191, 36, 0.5);
}

.kgf-chat-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: contain;
}

.kgf-status-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 11px;
    height: 11px;
    background: #10b981;
    border: 2px solid #0f172a;
    border-radius: 50%;
}

.kgf-chat-title {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 2px 0;
    line-height: 1.2;
}

.kgf-chat-subtitle {
    font-size: 12px;
    color: #94a3b8;
}

.kgf-chat-header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.kgf-chat-icon-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 6px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.kgf-chat-icon-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

/* Chat Messages Scroll Container */
.kgf-chat-body {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background: #090d16;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
}

.kgf-chat-body::-webkit-scrollbar {
    width: 5px;
}
.kgf-chat-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
}

/* Chat Message Bubbles */
.kgf-msg-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-width: 85%;
    animation: kgfFadeIn 0.25s ease-out;
}

@keyframes kgfFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.kgf-msg-row.user {
    align-self: flex-end;
    align-items: flex-end;
}

.kgf-msg-row.bot {
    align-self: flex-start;
    align-items: flex-start;
}

.kgf-msg-bubble {
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
}

.kgf-msg-row.user .kgf-msg-bubble {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: #ffffff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
}

.kgf-msg-row.bot .kgf-msg-bubble {
    background: #1e293b;
    color: #f1f5f9;
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.kgf-msg-bubble a {
    color: #fbbf24;
    text-decoration: underline;
}

.kgf-msg-time {
    font-size: 10px;
    color: #64748b;
    padding: 0 4px;
}

/* Product Cards Container */
.kgf-product-cards-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
    width: 100%;
}

.kgf-product-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #0f172a;
    border: 1px solid rgba(251, 191, 36, 0.25);
    border-radius: 12px;
    padding: 8px 12px;
    text-decoration: none !important;
    color: inherit;
    transition: all 0.2s ease;
}

.kgf-product-card:hover {
    background: #1e293b;
    border-color: #fbbf24;
    transform: translateX(3px);
}

.kgf-product-img {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 8px;
    background: #334155;
}

.kgf-product-info {
    flex: 1;
    overflow: hidden;
}

.kgf-product-title {
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 2px 0;
}

.kgf-product-meta {
    font-size: 11px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 8px;
}

.kgf-product-price {
    color: #fbbf24;
    font-weight: 700;
}

.kgf-product-btn {
    font-size: 11px;
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
}

/* Status Badges inside Chat */
.chat-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}
.chat-badge.status-pending { background: #f59e0b; color: #000; }
.chat-badge.status-shipped { background: #3b82f6; color: #fff; }
.chat-badge.status-delivered, .chat-badge.status-completed { background: #10b981; color: #fff; }

.chat-item-list {
    margin: 4px 0 0 16px;
    padding: 0;
    font-size: 12px;
}

/* Typing Indicator Animation */
.kgf-chat-typing {
    display: flex;
    align-items: center;
    gap: 8px;
    align-self: flex-start;
}

.kgf-chat-typing.hidden {
    display: none;
}

.kgf-chat-avatar-sm {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #334155;
    padding: 2px;
}
.kgf-chat-avatar-sm img { width: 100%; height: 100%; object-fit: contain; }

.kgf-typing-dots {
    background: #1e293b;
    padding: 10px 14px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.kgf-typing-dots span {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: kgfDotPulse 1.4s infinite ease-in-out both;
}

.kgf-typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.kgf-typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes kgfDotPulse {
    0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; background: #fbbf24; }
}

/* Quick Action Chips */
.kgf-chat-quick-replies {
    display: flex;
    gap: 6px;
    padding: 10px 16px;
    background: #0f172a;
    overflow-x: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    white-space: nowrap;
}

.kgf-chat-quick-replies::-webkit-scrollbar { display: none; }

.kgf-chip {
    background: #1e293b;
    color: #cbd5e1;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.kgf-chip:hover {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border-color: #fbbf24;
}

/* Footer Input Area */
.kgf-chat-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #1e293b;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.kgf-chat-input {
    flex: 1;
    background: #090d16;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 24px;
    padding: 10px 16px;
    color: #ffffff;
    font-size: 13.5px;
    outline: none;
    transition: all 0.2s;
}

.kgf-chat-input:focus {
    border-color: #fbbf24;
    box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.2);
}

.kgf-chat-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #d97706;
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.kgf-chat-send-btn:hover {
    background: #f59e0b;
    transform: scale(1.05);
}

/* Mobile Responsiveness Adjustment */
@media (max-width: 480px) {
    .kgf-chatbot-wrapper {
        bottom: 20px;
        right: 15px;
    }
    .kgf-chatbot-window {
        bottom: 70px;
        right: 0;
        width: calc(100vw - 30px);
        height: 500px;
    }
}
</style>

<!-- Chatbot Client JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('kgfChatbotContainer');
    const launcher = document.getElementById('kgfChatbotLauncher');
    const windowEl = document.getElementById('kgfChatbotWindow');
    const closeBtn = document.getElementById('kgfChatbotCloseBtn');
    const clearBtn = document.getElementById('kgfChatbotClearBtn');
    const badge = document.getElementById('kgfChatbotBadge');
    const messagesEl = document.getElementById('kgfChatbotMessages');
    const typingEl = document.getElementById('kgfChatbotTyping');
    const bodyEl = document.getElementById('kgfChatbotBody');
    const form = document.getElementById('kgfChatbotForm');
    const input = document.getElementById('kgfChatbotInput');
    const quickRepliesContainer = document.getElementById('kgfChatbotQuickReplies');

    const BASE_URL = "<?= url('') ?>";

    // Toggle Chat Window
    function toggleChatWindow() {
        const isHidden = windowEl.classList.contains('hidden');
        if (isHidden) {
            windowEl.classList.remove('hidden');
            wrapper.classList.add('active');
            badge.classList.add('hidden');
            input.focus();
            scrollToBottom();
        } else {
            windowEl.classList.add('hidden');
            wrapper.classList.remove('active');
        }
    }

    launcher.addEventListener('click', toggleChatWindow);
    closeBtn.addEventListener('click', toggleChatWindow);

    // Scroll to latest message
    function scrollToBottom() {
        setTimeout(() => {
            bodyEl.scrollTop = bodyEl.scrollHeight;
        }, 50);
    }

    // Load Chat History from Server
    function loadChatHistory() {
        fetch(BASE_URL + 'chatbot/chatbot_history.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && Array.isArray(data.history)) {
                    renderHistoryMessages(data.history);
                }
            })
            .catch(err => console.error("Chatbot load error:", err));
    }

    // Render messages array
    function renderHistoryMessages(history) {
        messagesEl.innerHTML = '';
        history.forEach(msg => {
            appendMessageUI(msg.role, msg.text, msg.products, msg.timestamp);
            if (msg.quick_replies && Array.isArray(msg.quick_replies)) {
                renderQuickReplies(msg.quick_replies);
            }
        });
        scrollToBottom();
    }

    // Append single message to DOM
    function appendMessageUI(role, text, products = [], timestamp = '') {
        const msgRow = document.createElement('div');
        msgRow.className = `kgf-msg-row ${role}`;

        let contentHtml = `<div class="kgf-msg-bubble">${text}</div>`;

        // Render product cards if available
        if (products && products.length > 0) {
            contentHtml += `<div class="kgf-product-cards-grid">`;
            products.forEach(p => {
                contentHtml += `
                    <a href="${p.url}" class="kgf-product-card">
                        <img src="${p.image_url}" alt="${p.name}" class="kgf-product-img" onerror="this.src='${BASE_URL}assets/kgf-logo-shield.png'">
                        <div class="kgf-product-info">
                            <h4 class="kgf-product-title">${p.name}</h4>
                            <div class="kgf-product-meta">
                                <span>${p.category}</span>
                                <span class="kgf-product-price">₹${p.price}</span>
                            </div>
                        </div>
                        <span class="kgf-product-btn">View</span>
                    </a>
                `;
            });
            contentHtml += `</div>`;
        }

        if (timestamp) {
            contentHtml += `<span class="kgf-msg-time">${timestamp}</span>`;
        }

        msgRow.innerHTML = contentHtml;
        messagesEl.appendChild(msgRow);
        scrollToBottom();
    }

    // Render Quick Reply Chips
    function renderQuickReplies(replies) {
        if (!replies || replies.length === 0) return;
        quickRepliesContainer.innerHTML = '';
        replies.forEach(rText => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'kgf-chip';
            btn.textContent = rText;
            btn.addEventListener('click', () => sendUserMessage(rText));
            quickRepliesContainer.appendChild(btn);
        });
    }

    // Send Message to Server
    function sendUserMessage(msgText) {
        const text = msgText || input.value.trim();
        if (!text) return;

        // Display user message immediately
        const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        appendMessageUI('user', text, [], now);
        input.value = '';

        // Show typing indicator
        typingEl.classList.remove('hidden');
        scrollToBottom();

        // AJAX POST to chatbot_process.php
        fetch(BASE_URL + 'chatbot/chatbot_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            typingEl.classList.add('hidden');
            if (data.status === 'success') {
                appendMessageUI('bot', data.reply, data.products, data.timestamp);
                if (data.quick_replies) {
                    renderQuickReplies(data.quick_replies);
                }
            } else {
                appendMessageUI('bot', "Sorry, I encountered an error processing your request. Please try again.", [], now);
            }
        })
        .catch(err => {
            typingEl.classList.add('hidden');
            appendMessageUI('bot', "Network error. Please check your connection and try again.", [], now);
        });
    }

    // Submit form handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        sendUserMessage();
    });

    // Delegate quick replies click in initial container
    quickRepliesContainer.querySelectorAll('.kgf-chip').forEach(btn => {
        btn.addEventListener('click', function() {
            sendUserMessage(this.dataset.msg || this.textContent);
        });
    });

    // Clear History Button Handler
    clearBtn.addEventListener('click', function() {
        if (confirm("Are you sure you want to clear your chat history?")) {
            fetch(BASE_URL + 'chatbot/chatbot_history.php?action=clear')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.history) {
                        renderHistoryMessages(data.history);
                    }
                });
        }
    });

    // Load initial history on startup
    loadChatHistory();
});
</script>
