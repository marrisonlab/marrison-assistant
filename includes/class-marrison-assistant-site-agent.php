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
        
        wp_localize_script('marrison-site-agent', 'marrisonAgent', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('marrison_agent_nonce'),
            'welcome' => get_option('marrison_assistant_site_agent_welcome', 'Ciao! Come posso aiutarti oggi?'),
            'placeholder' => get_option('marrison_assistant_site_agent_placeholder', 'Scrivi un messaggio...'),
            'title' => get_option('marrison_assistant_site_agent_title', 'Assistente AI'),
            'isTyping' => 'Sto scrivendo...'
        ));
    }
    
    /**
     * Renderizza il widget chat
     */
    public function render_chat_widget() {
        // Debug log
        error_log('Marrison Assistant: render_chat_widget called. enable_site_agent: ' . (get_option('marrison_assistant_enable_site_agent', false) ? 'true' : 'false'));
        error_log('Marrison Assistant: is_user_logged_in: ' . (is_user_logged_in() ? 'true' : 'false'));
        error_log('Marrison Assistant: logged_only: ' . (get_option('marrison_assistant_site_agent_logged_only', false) ? 'true' : 'false'));
        
        if (!get_option('marrison_assistant_enable_site_agent', false)) {
            error_log('Marrison Assistant: Site agent disabled, skipping widget');
            return;
        }
        
        // Verifica se l'utente deve essere loggato
        $logged_only = get_option('marrison_assistant_site_agent_logged_only', false);
        if ($logged_only && !is_user_logged_in()) {
            error_log('Marrison Assistant: User not logged in and logged_only enabled, skipping widget');
            return;
        }
        
        error_log('Marrison Assistant: Rendering widget...');
        
        $position = get_option('marrison_assistant_site_agent_position', 'bottom-right');
        $color = get_option('marrison_assistant_site_agent_color', '#0073aa');
        $title = get_option('marrison_assistant_site_agent_title', 'Assistente AI');
        $welcome = get_option('marrison_assistant_site_agent_welcome', 'Ciao! Come posso aiutarti oggi?');
        $placeholder = get_option('marrison_assistant_site_agent_placeholder', 'Scrivi un messaggio...');
        
        // Messaggio personalizzato per utenti non loggati (se il check è disabilitato ma l'utente non è loggato)
        if (!is_user_logged_in()) {
            $welcome = 'Ciao! Per accedere all\'assistente completo, effettua il login. Posso comunque aiutarti con informazioni generali.';
        }
        
        // Assicurati che il messaggio di benvenuto non sia mai vuoto
        if (empty($welcome)) {
            $welcome = 'Ciao! Come posso aiutarti oggi?';
        }
        
        ?>
        <div id="marrison-chat-widget" class="marrison-chat-widget marrison-<?php echo esc_attr($position); ?>">
            <!-- Chat Button -->
            <div class="marrison-chat-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                </svg>
                <span class="marrison-chat-badge">1</span>
            </div>
            
            <!-- Chat Window -->
            <div class="marrison-chat-window">
                <!-- Header -->
                <div class="marrison-chat-header">
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
                            <?php 
                            // Debug: log del messaggio
                            error_log('Marrison Assistant - Welcome message: ' . var_export($welcome, true));
                            echo !empty($welcome) ? esc_html($welcome) : 'Ciao! Come posso aiutarti?'; 
                            ?>
                        </div>
                        <div class="marrison-message-time" style="font-size: 11px; color: #64748b; margin-top: 4px; padding: 0 4px;">Ora</div>
                    </div>
                    
                    <?php if (!is_user_logged_in() && !$logged_only): ?>
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
                    <button id="marrison-chat-send" class="marrison-send-button" style="width: 42px; height: 42px; border-radius: 50%; border: none; background: linear-gradient(135deg, #667eea, #764ba2); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
        error_log('Marrison Assistant: Widget rendered successfully');
    }
    
    /**
     * Gestisce le richieste AJAX dal widget
     */
    public function handle_chat_request() {
        check_ajax_referer('marrison_agent_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message']);
        
        if (empty($message)) {
            wp_send_json_error('Messaggio vuoto');
        }
        
        // Verifica se l'utente è loggato e se è richiesto
        $logged_only = get_option('marrison_assistant_site_agent_logged_only', false);
        if ($logged_only && !is_user_logged_in()) {
            wp_send_json_error('Accesso negato. Effettua il login per utilizzare l\'assistente.');
        }
        
        // Processa il messaggio con Gemini
        $gemini = new Marrison_Assistant_Gemini();
        
        // Se l'utente non è loggato, limita le funzionalità
        if (!is_user_logged_in()) {
            // Aggiungi context per utenti guest
            $guest_prompt = "Rispondi come assistente per visitatori del sito. Non fornire informazioni su ordini specifici o dati personali. Invita l'utente a registrarsi per servizi completi. ";
            $response = $gemini->process_message($guest_prompt . $message);
        } else {
            // Utente loggato - accesso completo
            $current_user = wp_get_current_user();
            $user_context = "Utente loggato: {$current_user->display_name} (ID: {$current_user->ID}). ";
            $response = $gemini->process_message($user_context . $message);
        }
        
        if ($response) {
            wp_send_json_success(array(
                'message' => $response,
                'time' => current_time('H:i'),
                'user_logged_in' => is_user_logged_in()
            ));
        } else {
            wp_send_json_error('Errore elaborazione messaggio');
        }
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
