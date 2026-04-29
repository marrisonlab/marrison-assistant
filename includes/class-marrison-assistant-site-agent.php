<?php
/**
 * Classe per l'agente AI sul sito (chat widget)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Site_Agent {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_chat_widget'));
        add_action('wp_ajax_marrison_site_agent_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_marrison_site_agent_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_marrison_site_agent_ping', array($this, 'handle_ping'));
        add_action('wp_ajax_nopriv_marrison_site_agent_ping', array($this, 'handle_ping'));
        add_action('wp_ajax_marrison_site_agent_track', array($this, 'handle_track'));
        add_action('wp_ajax_nopriv_marrison_site_agent_track', array($this, 'handle_track'));
    }
    
    /**
     * Carica script e stili per il widget
     */
    public function enqueue_scripts() {
        if (!get_option('marrison_assistant_enable_site_agent', false)) {
            return;
        }
        
        // Path corretto per gli assets
        $plugin_url = plugin_dir_url(dirname(__FILE__) . '/../marrison-assistant.php');
        
        wp_enqueue_style(
            'marrison-site-agent',
            $plugin_url . 'assets/css/site-agent.css',
            array(),
            MARRISON_ASSISTANT_VERSION
        );
        
        wp_enqueue_script(
            'marrison-site-agent',
            $plugin_url . 'assets/js/site-agent.js',
            array('jquery'),
            MARRISON_ASSISTANT_VERSION,
            true
        );
        
        $assistant_name = get_option('marrison_assistant_site_agent_name', 'Marry');
        $welcome_msg = get_option('marrison_assistant_site_agent_welcome', 'Ciao, sono {name}, il tuo assistente virtuale, come posso aiutarti?');
        $welcome_msg = str_replace('{name}', $assistant_name, $welcome_msg);

        wp_localize_script('marrison-site-agent', 'marrisonAgent', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('marrison_agent_nonce'),
            'welcome' => $welcome_msg,
            'placeholder' => get_option('marrison_assistant_site_agent_placeholder', 'Scrivi un messaggio...'),
            'title' => get_option('marrison_assistant_site_agent_title', 'Assistente AI'),
            'name' => $assistant_name,
            'isTyping' => 'Sto scrivendo...',
            'colors' => array(
                'icon' => get_option('marrison_assistant_site_agent_icon_color', '#667eea'),
                'header' => get_option('marrison_assistant_site_agent_header_color', '#667eea'),
                'button' => get_option('marrison_assistant_site_agent_button_color', '#667eea'),
            ),
            'intentResponses' => array(
                'products' => get_option('marrison_assistant_site_agent_response_products', 'Perfetto! Dimmi cosa stai cercando tra i nostri prodotti.'),
                'orders'   => get_option('marrison_assistant_site_agent_response_orders',   'Certo! Dimmi il numero ordine o cosa vorresti sapere sul tuo acquisto.'),
                'info'     => get_option('marrison_assistant_site_agent_response_info',     'Con piacere! Su cosa vorresti informazioni? Azienda, contatti, servizi?'),
                'events'   => get_option('marrison_assistant_site_agent_response_events',   'Ottimo! Stai cercando un evento specifico o vuoi vedere il calendario?'),
            ),
        ));
    }
    
    /**
     * Renderizza il widget chat
     */
    public function render_chat_widget() {
        if (!get_option('marrison_assistant_enable_site_agent', false)) {
            return;
        }
        
        // Verifica se l'utente deve essere loggato
        $logged_only = get_option('marrison_assistant_site_agent_logged_only', false);
        if ($logged_only && !is_user_logged_in()) {
            return;
        }
        
        $position = get_option('marrison_assistant_site_agent_position', 'bottom-right');
        $color = get_option('marrison_assistant_site_agent_color', '#0073aa');
        $title = get_option('marrison_assistant_site_agent_title', 'Assistente AI');
        $assistant_name = get_option('marrison_assistant_site_agent_name', 'Marry');
        $welcome = get_option('marrison_assistant_site_agent_welcome', 'Ciao, sono {name}, il tuo assistente virtuale, come posso aiutarti?');
        $placeholder = get_option('marrison_assistant_site_agent_placeholder', 'Scrivi un messaggio...');

        // Sostituisci {name} con il nome dell'assistente
        $welcome = str_replace('{name}', $assistant_name, $welcome);

        // Colori personalizzabili
        $icon_color = get_option('marrison_assistant_site_agent_icon_color', '#667eea');
        $header_color = get_option('marrison_assistant_site_agent_header_color', '#667eea');
        $button_color = get_option('marrison_assistant_site_agent_button_color', '#667eea');

        // Assicurati che il messaggio di benvenuto non sia mai vuoto
        if (empty($welcome)) {
            $welcome = 'Ciao! Come posso aiutarti oggi?';
        }
        
        ?>
        <div id="marrison-chat-widget" class="marrison-chat-widget marrison-<?php echo esc_attr($position); ?>" style="--marrison-icon-color: <?php echo esc_attr($icon_color); ?>; --marrison-header-color: <?php echo esc_attr($header_color); ?>; --marrison-button-color: <?php echo esc_attr($button_color); ?>; --marrison-button-color-hover: <?php echo esc_attr($button_color); ?>;">
            <!-- Chat Button -->
            <div class="marrison-chat-button">
                <svg width="32" height="32" viewBox="0 0 510 510" fill="white" xmlns="http://www.w3.org/2000/svg" clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2">
                    <path d="m146.534 64.833c-1.709.358-3.479.547-5.294.547-14.192 0-25.714-11.523-25.714-25.715s11.522-25.714 25.714-25.714c14.193 0 25.715 11.522 25.715 25.714 0 6.592-2.486 12.607-6.57 17.16l49.867 86.372h-18.475zm216.932 0-45.243 78.364h-18.475l49.867-86.372c-4.084-4.553-6.57-10.568-6.57-17.16 0-14.192 11.522-25.714 25.715-25.714 14.192 0 25.714 11.522 25.714 25.714s-11.522 25.715-25.714 25.715c-1.815 0-3.585-.189-5.294-.547zm-44.901 399.044h145.576v-116.387h-14.234v-98.962h29.604c5.462 0 9.896 4.435 9.896 9.897v79.169c0 5.25-4.097 9.551-9.266 9.876v124.407c0 4.418-3.582 8-8 8h-153.576c-3.005 6.361-9.481 10.766-16.978 10.766h-33.982c-10.357 0-18.766-8.409-18.766-18.766s8.409-18.766 18.766-18.766h33.982c7.497 0 13.973 4.405 16.978 10.766zm-258.472-116.387h-29.604c-5.462 0-9.896-4.434-9.896-9.896v-79.169c0-5.462 4.434-9.897 9.896-9.897h29.604zm373.814 42.428c0 21.144-17.251 38.337-38.395 38.337h-173.447l-63.058 65.643c-1.979 2.06-5.012 2.711-7.662 1.645-2.65-1.067-4.386-3.637-4.386-6.494v-60.794h-32.471c-21.144 0-38.395-17.193-38.395-38.337v-192.325c0-21.144 17.251-38.396 38.395-38.396h281.024c21.144 0 38.395 17.252 38.395 38.396zm-124.783-63.836h-112.997c1.214 30.324 26.15 54.282 56.499 54.282 30.348 0 55.284-23.958 56.498-54.282zm-118.407-104.564c-13.421 0-24.317 10.896-24.317 24.317 0 13.422 10.896 24.318 24.317 24.318s24.318-10.896 24.318-24.318c0-13.421-10.897-24.317-24.318-24.317zm128.566 0c-13.421 0-24.318 10.896-24.318 24.317 0 13.422 10.897 24.318 24.318 24.318s24.317-10.896 24.317-24.318c0-13.421-10.896-24.317-24.317-24.317z"/>
                </svg>
                <span class="marrison-chat-badge">1</span>
            </div>
            
            <!-- Chat Window -->
            <div class="marrison-chat-window">
                <!-- Header -->
                <div class="marrison-chat-header">
                    <?php $wl_logo = Marrison_Assistant_White_Label::logo_url(); ?>
                    <?php if ($wl_logo): ?>
                    <img src="<?php echo esc_url($wl_logo); ?>" alt="" style="height:22px; width:auto; margin-right:8px; vertical-align:middle; flex-shrink:0;">
                    <?php endif; ?>
                    <div class="marrison-chat-title"><?php echo esc_html($title); ?></div>
                    <div class="marrison-chat-status">
                        <span class="marrison-status-dot"></span>
                        Online
                        <?php if (!is_user_logged_in()): ?>
                            <span style="margin-left: 8px; font-size: 10px; opacity: 0.7;">Guest</span>
                        <?php endif; ?>
                    </div>
                    <button class="marrison-chat-close">&times;</button>
                </div>
                
                <!-- Messages -->
                <div class="marrison-chat-messages" style="flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc;">
                    <div class="marrison-message marrison-bot" style="margin-bottom: 16px; display: flex; flex-direction: column; align-items: flex-start;">
                        <div class="marrison-message-content" style="max-width: 85%; padding: 12px 16px; border-radius: 18px; background: #ffffff !important; color: #1e293b !important; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); word-wrap: break-word; line-height: 1.4; font-size: 14px; display: block; min-height: 20px;">
                            <?php echo !empty($welcome) ? esc_html($welcome) : 'Ciao! Come posso aiutarti?'; ?>
                        </div>
                        <div class="marrison-message-time" style="font-size: 11px; color: #64748b; margin-top: 4px; padding: 0 4px;">Ora</div>
                    </div>
                    
                    <!-- Bottoni di routing categoria (condizionali) -->
                    <?php
                    $scanner      = new Marrison_Assistant_Content_Scanner();
                    $show_products = $scanner->has_content('products') || class_exists('WooCommerce');
                    $show_events   = $scanner->has_content('events');
                    $show_orders   = is_user_logged_in() && class_exists('WooCommerce');
                    // Mostra i pulsanti solo se ce ne sono almeno due (altrimenti è un passaggio inutile)
                    $show_buttons  = ($show_products || $show_orders || $show_events);
                    ?>
                    <?php if ($show_buttons): ?>
                    <div id="marrison-intent-buttons" class="marrison-intent-buttons">
                        <?php if ($show_products): ?><button type="button" class="marrison-intent-btn" data-intent="products">🛍️ Prodotti</button><?php endif; ?>
                        <?php if ($show_orders):   ?><button type="button" class="marrison-intent-btn" data-intent="orders">📦 Ordini</button><?php endif; ?>
                        <button type="button" class="marrison-intent-btn" data-intent="info">ℹ️ Info</button>
                        <?php if ($show_events):   ?><button type="button" class="marrison-intent-btn" data-intent="events">📅 Eventi</button><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!is_user_logged_in() && !$logged_only && class_exists('WooCommerce')): ?>
                    <div class="marrison-message marrison-bot">
                        <div class="marrison-message-content marrison-tip-message">
                            <strong>Tip:</strong> Effettua il login per accedere a funzionalità avanzate come tracking ordini e supporto personalizzato.
                        </div>
                        <div class="marrison-message-time">Ora</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Input -->
                <div class="marrison-chat-input" style="padding: 12px 16px; background: #fff; border-top: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                    <textarea 
                        id="marrison-chat-textarea" 
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        rows="2"
                        style="flex: 1; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.5; color: #333; background: #f8fafc; resize: none; outline: none; min-height: 42px; box-sizing: border-box; width: 100%;"></textarea>
                    <button id="marrison-chat-send" class="marrison-send-button" style="width: 42px; height: 42px; border-radius: 50%; border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
                <!-- Footer credit -->
                <div style="padding: 4px 0; text-align: center; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <?php
                    $pb_text = Marrison_Assistant_White_Label::powered_by_text();
                    $pb_url  = Marrison_Assistant_White_Label::powered_by_url();
                    ?>
                    <a href="<?php echo esc_url($pb_url); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 10px; color: #94a3b8; text-decoration: none; transition: color 0.2s;"><?php echo esc_html($pb_text); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ping leggero — verifica che il canale AJAX funzioni senza chiamare Gemini
     */
    public function handle_ping() {
        check_ajax_referer('marrison_agent_nonce', 'nonce');
        wp_send_json_success(array('pong' => true, 'time' => current_time('H:i:s')));
    }

    /**
     * Tracking eventi client (apertura chat, sessione avviata) — risponde senza fare nulla
     */
    public function handle_track() {
        check_ajax_referer('marrison_agent_nonce', 'nonce');
        wp_send_json_success(array('ok' => true));
    }

    /**
     * Gestisce le richieste AJAX dal widget
     */
    public function handle_chat_request() {
        check_ajax_referer('marrison_agent_nonce', 'nonce');

        // Rate limiting
        $rl = $this->check_rate_limit();
        if ($rl !== true) {
            wp_send_json_error(array(
                'code'    => 'rate_limited',
                'message' => $rl['message'],
                'wait'    => $rl['wait'],
            ));
        }

        $message = sanitize_textarea_field($_POST['message']);
        $intent  = isset($_POST['intent']) ? sanitize_text_field($_POST['intent']) : 'general';
        $history_raw = isset($_POST['history']) ? stripslashes($_POST['history']) : '[]';
        $history = json_decode($history_raw, true);
        if (!is_array($history)) $history = array();

        if (empty($message)) {
            wp_send_json_error('Messaggio vuoto');
        }

        // Verifica se l'utente è loggato e se è richiesto
        $logged_only = get_option('marrison_assistant_site_agent_logged_only', false);
        if ($logged_only && !is_user_logged_in()) {
            wp_send_json_error('Accesso negato. Effettua il login per utilizzare l\'assistente.');
        }

        // SICUREZZA: gli ordini sono accessibili SOLO agli utenti loggati (solo se WooCommerce è attivo)
        if ($intent === 'orders' && !is_user_logged_in() && class_exists('WooCommerce')) {
            wp_send_json_success(array(
                'message'        => 'Per consultare i tuoi ordini devi prima effettuare il login.',
                'time'           => current_time('H:i'),
                'user_logged_in' => false,
                'intent'         => 'orders',
            ));
        }

        // Processa il messaggio con Gemini passando l'intento
        try {
            $gemini = new Marrison_Assistant_Gemini();

            if (!is_user_logged_in()) {
                $guest_prompt = "Rispondi come assistente per visitatori del sito. Non fornire informazioni su ordini specifici o dati personali. Invita l'utente a registrarsi per servizi completi. ";
                $response = $gemini->process_message($guest_prompt . $message, $intent, $history, $message, '');
            } else {
                $current_user = wp_get_current_user();
                $user_email   = $current_user->user_email;
                $user_context = "Utente loggato: {$current_user->display_name} (email: {$user_email}). ";
                $response = $gemini->process_message($user_context . $message, $intent, $history, $message, $user_email);
            }

            if ($response) {
                wp_send_json_success(array(
                    'message' => $response,
                    'time' => current_time('H:i'),
                    'user_logged_in' => is_user_logged_in(),
                    'intent' => $intent
                ));
            } else {
                error_log('Marrison Assistant: process_message returned false per intent=' . $intent . ' message=' . substr($message, 0, 80));
                wp_send_json_success(array(
                    'message' => 'Mi dispiace, il servizio AI non è disponibile in questo momento. Riprova tra qualche minuto.',
                    'time' => current_time('H:i'),
                    'user_logged_in' => is_user_logged_in(),
                    'intent' => $intent
                ));
            }
        } catch (Exception $e) {
            error_log('Marrison Assistant: eccezione in handle_chat_request — ' . $e->getMessage());
            wp_send_json_error('Errore interno: ' . $e->getMessage());
        }
    }
    
    /**
     * Rate limiting per IP: max 10 req/minuto e 80 req/ora.
     * Usa WordPress transients (compatibile con object cache).
     * @return true|array  true se OK, array con 'wait' e 'message' se bloccato
     */
    private function check_rate_limit() {
        // Estrai IP in modo sicuro, anche dietro proxy/CDN
        $ip = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip    = trim($parts[0]);
        }
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        }
        $h = substr(md5($ip), 0, 16); // hash parziale — non loggare IP in chiaro

        // ── Finestra 1: max 10 richieste al minuto ──
        $key_min   = 'marrison_rl_m_' . $h;
        $count_min = (int) get_transient($key_min);
        if ($count_min >= 10) {
            return array('wait' => '60', 'message' => 'Stai inviando troppi messaggi. Attendi un momento prima di riprovare.');
        }
        // Incrementa; se il transient non esiste ancora, impostalo con TTL 60s
        if ($count_min === 0) {
            set_transient($key_min, 1, 60);
        } else {
            set_transient($key_min, $count_min + 1, 60);
        }

        // ── Finestra 2: max 80 richieste all'ora ──
        $key_hour   = 'marrison_rl_h_' . $h;
        $count_hour = (int) get_transient($key_hour);
        if ($count_hour >= 80) {
            return array('wait' => '3600', 'message' => 'Limite orario raggiunto. Riprova tra qualche minuto.');
        }
        if ($count_hour === 0) {
            set_transient($key_hour, 1, 3600);
        } else {
            set_transient($key_hour, $count_hour + 1, 3600);
        }

        return true;
    }

    /**
     * Ottiene le statistiche di utilizzo
     */
    public function get_usage_stats() {
        $stats = get_option('marrison_assistant_site_agent_stats', array(
            'chats_today' => 0,
            'messages_today' => 0,
            'last_reset' => date('Y-m-d')
        ));
        
        // Reset giornaliero
        if ($stats['last_reset'] !== date('Y-m-d')) {
            $stats = array(
                'chats_today' => 0,
                'messages_today' => 0,
                'last_reset' => date('Y-m-d')
            );
            update_option('marrison_assistant_site_agent_stats', $stats);
        }
        
        return $stats;
    }
    
    /**
     * Incrementa le statistiche
     */
    public function increment_stats($type) {
        $stats = $this->get_usage_stats();
        
        if ($type === 'chat') {
            $stats['chats_today']++;
        } elseif ($type === 'message') {
            $stats['messages_today']++;
        }
        
        update_option('marrison_assistant_site_agent_stats', $stats);
    }
}
