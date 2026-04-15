<?php
/**
 * Plugin Name: Marrison Assistant
 * Description: Plugin WordPress per assistente AI con integrazione WhatsApp e Google Gemini
 * Version: 1.0.0
 * Author: Marrison Assistant Team
 * Text Domain: marrison-assistant
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('MARRISON_ASSISTANT_VERSION', '1.0.0');
define('MARRISON_ASSISTANT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MARRISON_ASSISTANT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Carica i file necessari
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-admin.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-api.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-gemini.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-site-agent.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-content-scanner.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-twilio.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-order-scanner.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-auth.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-user-management.php';

/**
 * Classe principale del plugin
 */
class Marrison_Assistant {
    
    private $admin;
    private $api;
    private $gemini;
    private $twilio;
    private $content_scanner;
    private $order_scanner;
    private $auth;
    private $user_management;
    private $site_agent;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Inizializza tutte le classi
        $this->admin = new Marrison_Assistant_Admin();
        $this->api = new Marrison_Assistant_API();
        $this->gemini = new Marrison_Assistant_Gemini();
        $this->site_agent = new Marrison_Assistant_Site_Agent();
        $this->content_scanner = new Marrison_Assistant_Content_Scanner();
        $this->twilio = new Marrison_Assistant_Twilio();
        $this->order_scanner = new Marrison_Assistant_Order_Scanner();
        $this->auth = new Marrison_Assistant_Auth();
        $this->user_management = new Marrison_Assistant_User_Management();
        
        // Aggiungi link impostazioni nella pagina plugin
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        // Carica le opzioni di default
        $this->set_default_options();
    }
    
    /**
     * Imposta le opzioni di default all'attivazione
     */
    private function set_default_options() {
        $default_options = array(
            'gemini_api_key' => '',
            'twilio_sid' => '',
            'twilio_auth_token' => '',
            'twilio_whatsapp_number' => '',
            'custom_prompt' => 'Sei un assistente AI per questo sito web. Rispondi in modo professionale e utile basandoti sui contenuti del sito.',
            'enable_webhook' => false,
            'logged_only' => false,
            'last_content_scan' => 0,
            // Opzioni agente sito
            'enable_site_agent' => false,
            'site_agent_position' => 'bottom-right',
            'site_agent_color' => '#0073aa',
            'site_agent_title' => 'Assistente AI',
            'site_agent_welcome' => 'Ciao! Come posso aiutarti oggi?',
            'site_agent_placeholder' => 'Scrivi un messaggio...',
            'site_agent_logged_only' => false
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option('marrison_assistant_' . $option) === false) {
                update_option('marrison_assistant_' . $option, $value);
            }
        }
    }
    
    /**
     * Attivazione del plugin
     */
    public function activate() {
        // Crea le tabelle necessarie se servono
        // Imposta le opzioni di default
        $this->set_default_options();
        
        // Flush rewrite rules per i custom endpoint
        flush_rewrite_rules();
    }
    
    /**
     * Disattivazione del plugin
     */
    public function deactivate() {
        // Pulizia se necessaria
        flush_rewrite_rules();
    }
    
    /**
     * Aggiunge il link alle impostazioni nella pagina dei plugin
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=marrison-assistant') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Ottiene le impostazioni del plugin
     */
    public static function get_settings() {
        return array(
            'gemini_api_key' => get_option('marrison_assistant_gemini_api_key'),
            'twilio_sid' => get_option('marrison_assistant_twilio_sid'),
            'twilio_auth_token' => get_option('marrison_assistant_twilio_auth_token'),
            'twilio_whatsapp_number' => get_option('marrison_assistant_twilio_whatsapp_number'),
            'custom_prompt' => get_option('marrison_assistant_custom_prompt'),
            'enable_webhook' => get_option('marrison_assistant_enable_webhook'),
            'last_content_scan' => get_option('marrison_assistant_last_content_scan')
        );
    }
}

// Inizializza il plugin
new Marrison_Assistant();
