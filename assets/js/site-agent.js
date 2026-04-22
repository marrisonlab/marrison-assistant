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

    // Stato conversazione
    let conversationState = {
        step: 'initial', // initial | ready
        intent: null,    // products | orders | info | events | general
        history: []      // ultimi 2 turni {u: userMsg, b: botMsg}
    };

    // Chiavi sessionStorage
    const SS_MSGS  = 'marrison_msgs';
    const SS_STATE = 'marrison_state';
    const SS_OPEN  = 'marrison_open';

    function saveSession() {
        try {
            const $clone = chatMessages.clone();
            $clone.find('#marrison-intent-buttons').remove();
            sessionStorage.setItem(SS_MSGS,  $clone.html());
            sessionStorage.setItem(SS_STATE, JSON.stringify(conversationState));
            sessionStorage.setItem(SS_OPEN,  isOpen ? '1' : '0');
        } catch(e) {}
    }

    function restoreSession() {
        try {
            const savedMsgs  = sessionStorage.getItem(SS_MSGS);
            const savedState = sessionStorage.getItem(SS_STATE);
            const savedOpen  = sessionStorage.getItem(SS_OPEN);

            if (savedMsgs) {
                chatMessages.html(savedMsgs);
                scrollToBottom();
            }
            if (savedState) {
                const st = JSON.parse(savedState);
                conversationState.step    = st.step    || 'initial';
                conversationState.intent  = st.intent  || null;
                conversationState.history = st.history || [];
            }
            if (savedOpen === '1') {
                chatWindow.addClass('open');
                isOpen = true;
            }
        } catch(e) {}
    }
    
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
        saveSession();
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
        saveSession();
    }
    
    // Rileva intento dal messaggio
    function detectIntent(message) {
        const lowerMsg = message.toLowerCase();

        // Pattern per ordini
        if (/\b(ordine|ordini|acquisto|acquisti|tracking|spedizione|pacco|consegna|stato ordine|numero ordine|#\d+)\b/.test(lowerMsg)) {
            return 'orders';
        }

        // Pattern per prodotti
        if (/\b(prodotto|prodotti|maglietta|magliette|felpa|felpe|maglia|maglie|hoodie|giacca|giacche|pantaloni|pantalone|scarpa|scarpe|cappello|cappelli|vestito|vestiti|abbigliamento|accessori|borsa|borse|bermuda|shorts|costume|cappotto|giubbotto|maglione|cardigan|camicia|camicie|gonna|gonne|leggings|calze|cintura|collezione|catalogo|shop|negozio|acquista|compra|prezzo|costo|colore|taglia|disponibile|stock|saldo|offerta|sconto|nuovo|novità)\b/.test(lowerMsg)) {
            return 'products';
        }

        // Pattern per eventi (keyword dirette)
        if (/\b(evento|eventi|calendario|appuntamento|incontro|workshop|seminario|concerto|spettacolo|mostra|fiera|sagra|festa|manifestazione|programma)\b/.test(lowerMsg)) {
            return 'events';
        }
        // Pattern per domande su attività/programma in una data specifica
        // es. "che si fa il 25 aprile", "cosa c'è sabato", "cosa succede questo weekend"
        if (/\b(che si fa|cosa si fa|cosa c[''`']è|cosa c'è|c[''`']è qualcosa|cosa succede|cosa fanno|cosa fare|cosa fai|si svolge|si terrà|si tiene|programma|in programma)\b/.test(lowerMsg)) {
            return 'events';
        }
        // Data + contesto temporale → probabile domanda su eventi
        if (/\b(questo weekend|fine settimana|sabato|domenica|prossima settimana|prossimo|domani)\b/.test(lowerMsg)) {
            return 'events';
        }
        // Mese o data + parola interrogativa → es. "il 25 aprile cosa c'è"
        if (/\b(gennaio|febbraio|marzo|aprile|maggio|giugno|luglio|agosto|settembre|ottobre|novembre|dicembre|\d{1,2}[\/\-]\d{1,2})\b/.test(lowerMsg) &&
            /\b(cosa|che|come|quando|dove|ci sono|c'è|cè)\b/.test(lowerMsg)) {
            return 'events';
        }

        // Pattern per informazioni generali
        if (/\b(chi siete|contatti|dove siete|indirizzo|orari|telefono|email|servizi|chi siamo|about|info)\b/.test(lowerMsg)) {
            return 'info';
        }

        return 'general';
    }

    // Gestisce il routing intent: se l'utente scrive senza aver cliccato un bottone,
    // rimuove i bottoni, rileva l'intent dal testo e procede direttamente.
    function handleQuestionnaire(message) {
        // Rimuovi i bottoni se ancora visibili
        $('#marrison-intent-buttons').fadeOut(200, function() { $(this).remove(); });

        if (conversationState.step === 'ready') {
            // Ri-valuta l'intent ad ogni messaggio: se la domanda riguarda un argomento
            // diverso da quello corrente, aggiorna il contesto inviato al server.
            // Se il rilevamento è ambiguo ('general'), mantieni l'intent esistente.
            const newIntent = detectIntent(message);
            if (newIntent !== 'general') {
                conversationState.intent = newIntent;
            }
            return true;
        }

        // Primo messaggio senza bottone: rileva intent e vai diretto
        const intent = detectIntent(message);
        conversationState.intent = intent;
        conversationState.step   = 'ready';
        return true;
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

        // Gestisci questionario
        const shouldSend = handleQuestionnaire(message);
        if (!shouldSend) {
            sendButton.prop('disabled', false);
            return;
        }

        // Show typing indicator
        showTyping();

        // Send to server con intento e storico
        $.post(marrisonAgent.ajaxUrl, {
            action: 'marrison_site_agent_chat',
            message: message,
            intent:  conversationState.intent || 'general',
            history: JSON.stringify(conversationState.history),
            nonce:   marrisonAgent.nonce
        })
        .done(function(response) {
            hideTyping();

            if (response.success) {
                const botMsg = response.data.message;
                addMessage(botMsg, 'bot', response.data.time);
                // Aggiorna storico: max 2 turni, messaggi troncati
                conversationState.history.push({
                    u: message.substring(0, 120),
                    b: botMsg.substring(0, 100)
                });
                if (conversationState.history.length > 2) {
                    conversationState.history.shift();
                }
            } else if (response.data && response.data.code === 'rate_limited') {
                addMessage('⏳ ' + response.data.message, 'bot');
            } else {
                addMessage('Mi dispiace, ho avuto un problema. Riprova più tardi.', 'bot');
            }
        })
        .fail(function(xhr) {
            hideTyping();
            var statusCode = xhr.status;
            if (statusCode === 403) {
                addMessage('Errore di sessione (403). Ricarica la pagina e riprova.', 'bot');
            } else if (statusCode === 500) {
                addMessage('Errore interno del server (500). Controlla i log PHP.', 'bot');
            } else if (statusCode === 0) {
                addMessage('Impossibile raggiungere il server. Verifica la connessione.', 'bot');
            } else {
                addMessage('Errore di connessione (' + statusCode + '). Riprova più tardi.', 'bot');
            }
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
        saveSession();

        messageCount++;
        updateBadge();
    }
    
    // Format message text: convert markdown to HTML with proper escaping
    function formatMessageText(text) {
        if (!text) return '';

        // Dividi in parti: testo normale vs link markdown
        const parts = [];
        let lastIndex = 0;
        const linkRegex = /\[([^\]]+)\]\((https?:\/\/[^\s<]+)\)/g;
        let match;

        while ((match = linkRegex.exec(text)) !== null) {
            if (match.index > lastIndex) {
                parts.push({ type: 'text', content: text.slice(lastIndex, match.index) });
            }
            parts.push({ type: 'link', linkText: match[1], url: match[2] });
            lastIndex = match.index + match[0].length;
        }

        if (lastIndex < text.length) {
            parts.push({ type: 'text', content: text.slice(lastIndex) });
        }

        if (parts.length === 0) {
            parts.push({ type: 'text', content: text });
        }

        // Processa ogni parte
        let result = '';
        parts.forEach(part => {
            if (part.type === 'text') {
                // PRIMA: Converti markdown bold/italic su testo raw
                let formatted = part.content;
                formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');

                // POI: Escape HTML nel testo (preserva i tag <strong> e <em> creati)
                // Dividi per tag HTML e fai escape solo sul testo
                const segments = formatted.split(/(<\/?(?:strong|em)>)/g);
                let escaped = '';
                segments.forEach(seg => {
                    if (seg === '<strong>' || seg === '</strong>' || seg === '<em>' || seg === '</em>') {
                        escaped += seg; // preserva tag HTML
                    } else {
                        escaped += escapeHtml(seg); // escape testo
                    }
                });

                // Converti URL plain in link
                escaped = escaped.replace(/(https?:\/\/[^\s<]+|www\.[^\s<]+)/g, function(url) {
                    let href = url;
                    if (url.startsWith('www.')) href = 'https://' + url;
                    return `<a href="${href}">${url}</a>`;
                });
                result += escaped;
            } else {
                const safeText = escapeHtml(part.linkText);
                result += `<a href="${part.url}">${safeText}</a>`;
            }
        });

        return result.replace(/\n/g, '<br>');
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
    restoreSession();

    // Bottoni di routing categoria — registrati dopo init() per evitare problemi di scope
    const intentResponses = marrisonAgent.intentResponses || {
        products: 'Perfetto! Dimmi cosa stai cercando tra i nostri prodotti.',
        orders:   'Certo! Dimmi il numero ordine o cosa vorresti sapere sul tuo acquisto.',
        info:     'Con piacere! Su cosa vorresti informazioni? Azienda, contatti, servizi?',
        events:   'Ottimo! Stai cercando un evento specifico o vuoi vedere il calendario?'
    };
    const intentLabels = {
        products: '🛍️ Prodotti',
        orders:   '📦 Ordini',
        info:     'ℹ️ Info',
        events:   '📅 Eventi'
    };
    $(document).on('click', '.marrison-intent-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const intent = $(this).data('intent');
        $('#marrison-intent-buttons').fadeOut(200, function() { $(this).remove(); });
        conversationState.step   = 'ready';
        conversationState.intent = intent;
        addMessage('Ho bisogno di aiuto con: ' + (intentLabels[intent] || intent), 'user');
        setTimeout(function() {
            addMessage(intentResponses[intent] || 'Dimmi pure!', 'bot');
            saveSession();
            chatTextarea.focus();
        }, 300);
    });
    
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
