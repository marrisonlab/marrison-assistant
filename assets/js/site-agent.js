/**
 * Marrison Assistant Site Agent JavaScript
 */

jQuery(document).ready(function($) {
    // Variabili globali
    let chatWindow = null;
    let chatButton = null;
    let chatMessages = null;
    let chatTextarea = null;
    let sendButton = null;
    let isOpen = false;
    let messageCount = 0;
    
    // Inizializzazione
    function init() {
        chatWindow = $('.marrison-chat-window');
        chatButton = $('.marrison-chat-button');
        chatMessages = $('.marrison-chat-messages');
        chatTextarea = $('#marrison-chat-textarea');
        sendButton = $('#marrison-chat-send');
        
        // Event listeners
        chatButton.on('click', toggleChat);
        $('.marrison-chat-close').on('click', closeChat);
        sendButton.on('click', sendMessage);
        chatTextarea.on('keydown', handleKeyDown);
        chatTextarea.on('input', handleInput);
        
        // Auto-resize textarea
        chatTextarea.on('input', autoResize);
        
        // Nascondi badge dopo primo click
        chatButton.on('click', function() {
            $('.marrison-chat-badge').hide();
        });
    }
    
    // Toggle chat window
    function toggleChat() {
        if (isOpen) {
            closeChat();
        } else {
            openChat();
        }
    }
    
    // Open chat
    function openChat() {
        chatWindow.addClass('open');
        isOpen = true;
        chatTextarea.focus();
        
        // Traccia apertura chat
        $.post(marrisonAgent.ajaxUrl, {
            action: 'marrison_site_agent_track',
            type: 'chat_open',
            nonce: marrisonAgent.nonce
        });
    }
    
    // Close chat
    function closeChat() {
        chatWindow.removeClass('open');
        isOpen = false;
    }
    
    // Send message
    function sendMessage() {
        const message = chatTextarea.val().trim();
        
        if (!message) return;
        
        // Disable send button
        sendButton.prop('disabled', true);
        
        // Add user message
        addMessage(message, 'user');
        chatTextarea.val('');
        autoResize();
        
        // Show typing indicator
        showTyping();
        
        // Send to server
        $.post(marrisonAgent.ajaxUrl, {
            action: 'marrison_site_agent_chat',
            message: message,
            nonce: marrisonAgent.nonce
        })
        .done(function(response) {
            hideTyping();
            
            if (response.success) {
                addMessage(response.data.message, 'bot', response.data.time);
            } else {
                addMessage('Mi dispiace, ho avuto un problema. Riprova più tardi.', 'bot');
            }
        })
        .fail(function() {
            hideTyping();
            addMessage('Errore di connessione. Riprova più tardi.', 'bot');
        })
        .always(function() {
            sendButton.prop('disabled', false);
        });
    }
    
    // Add message to chat
    function addMessage(text, sender, time) {
        const messageClass = sender === 'user' ? 'marrison-user' : 'marrison-bot';
        const messageTime = time || getCurrentTime();
        
        // Formatta il testo: URL in link, bold markdown in HTML
        const formattedText = formatMessageText(text);
        
        const messageHtml = `
            <div class="marrison-message ${messageClass}" style="margin-bottom: 12px; display: flex; flex-direction: column; ${sender === 'user' ? 'align-items: flex-end;' : 'align-items: flex-start;'}">
                <div class="marrison-message-content" style="max-width: 85%; padding: 10px 14px; border-radius: 16px; ${sender === 'user' ? 'background: #667eea; color: white; border-bottom-right-radius: 4px;' : 'background: white; color: #1e293b; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px;'} box-shadow: 0 1px 3px rgba(0,0,0,0.1); word-wrap: break-word; line-height: 1.4; font-size: 14px;">${formattedText}</div>
                <div class="marrison-message-time" style="font-size: 11px; color: #64748b; margin-top: 2px; padding: 0 4px;">${messageTime}</div>
            </div>
        `;
        
        chatMessages.append(messageHtml);
        scrollToBottom();
        
        messageCount++;
        updateBadge();
    }
    
    // Format message text: convert URLs to links and markdown bold to HTML
    function formatMessageText(text) {
        if (!text) return '';
        
        // First escape HTML to prevent XSS
        let formatted = escapeHtml(text);
        
        // Convert markdown bold (**text**) to HTML <strong>
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        
        // Convert markdown italic (*text*) to HTML <em>
        formatted = formatted.replace(/\*([^\*]+)\*/g, '<em>$1</em>');
        
        // Convert URLs to clickable links
        // Match http, https, and www URLs
        const urlRegex = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/g;
        formatted = formatted.replace(urlRegex, function(url) {
            let href = url;
            if (url.startsWith('www.')) {
                href = 'https://' + url;
            }
            return `<a href="${href}" target="_blank" rel="noopener noreferrer" style="color: #667eea; text-decoration: underline; font-weight: 500;">${url}</a>`;
        });
        
        // Convert line breaks to <br>
        formatted = formatted.replace(/\n/g, '<br>');
        
        return formatted;
    }
    
    // Show typing indicator
    function showTyping() {
        const typingHtml = `
            <div class="marrison-message marrison-bot marrison-typing-message">
                <div class="marrison-typing">
                    <div class="marrison-typing-dot"></div>
                    <div class="marrison-typing-dot"></div>
                    <div class="marrison-typing-dot"></div>
                </div>
            </div>
        `;
        
        chatMessages.append(typingHtml);
        scrollToBottom();
    }
    
    // Hide typing indicator
    function hideTyping() {
        $('.marrison-typing-message').remove();
    }
    
    // Handle keyboard events
    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }
    
    // Handle input events
    function handleInput() {
        // Auto-send on certain conditions if needed
        // Could implement smart suggestions here
    }
    
    // Auto-resize textarea
    function autoResize() {
        chatTextarea.css('height', 'auto');
        chatTextarea.css('height', Math.min(chatTextarea[0].scrollHeight, 100) + 'px');
    }
    
    // Scroll to bottom
    function scrollToBottom() {
        const messagesContainer = chatMessages[0];
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Get current time
    function getCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Update badge
    function updateBadge() {
        if (messageCount > 0 && !isOpen) {
            $('.marrison-chat-badge').text(messageCount).show();
        }
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + K to open chat
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (!isOpen) {
                openChat();
            }
        }
        
        // Escape to close chat
        if (e.key === 'Escape' && isOpen) {
            closeChat();
        }
    });
    
    // Initialize when ready
    init();
    
    // Add welcome message if needed
    if (marrisonAgent.welcome && $('.marrison-message').length === 1) {
        // Welcome message already added in HTML
    }
    
    // Focus management
    chatWindow.on('click', function(e) {
        if (!$(e.target).is('textarea, button')) {
            chatTextarea.focus();
        }
    });
    
    // Accessibility
    chatButton.attr('aria-label', 'Apri chat assistente');
    chatButton.attr('role', 'button');
    chatButton.attr('tabindex', '0');
    
    // Keyboard navigation for button
    chatButton.on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleChat();
        }
    });
    
    // Auto-focus when chat opens
    chatWindow.on('transitionend', function() {
        if (isOpen) {
            chatTextarea.focus();
        }
    });
    
    // Handle window resize
    $(window).on('resize', function() {
        if (isOpen) {
            scrollToBottom();
        }
    });
    
    // Add some nice animations
    chatMessages.on('scroll', function() {
        // Could implement scroll-to-bottom button logic here
    });
    
    // Session tracking
    let sessionId = sessionStorage.getItem('marrison_session_id');
    if (!sessionId) {
        sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem('marrison_session_id', sessionId);
    }
    
    // Track session start
    $.post(marrisonAgent.ajaxUrl, {
        action: 'marrison_site_agent_track',
        type: 'session_start',
        session_id: sessionId,
        nonce: marrisonAgent.nonce
    });
});
