<?php
/**
 * Plugin Name: Marrison Assistant
 * Plugin URI: https://github.com/marrisonlab/marrison-assistant
 * Description: Plugin WordPress per assistente AI con Google Gemini
 * Version: 1.0.1
 * Author: Marrisonlab
 * Author URI: https://marrisonlab.com
 * Text Domain: marrison-assistant
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('MARRISON_ASSISTANT_VERSION', '1.0.1');
define('MARRISON_ASSISTANT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MARRISON_ASSISTANT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Carica i file necessari
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-admin.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-api.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-gemini.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-site-agent.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-content-scanner.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-order-scanner.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-auth.php';
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-updater.php';

/**
 * Classe principale del plugin
 */
class Marrison_Assistant {
    
    private $admin;
    private $api;
    private $gemini;
    private $content_scanner;
    private $order_scanner;
    private $auth;
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
        $this->order_scanner = new Marrison_Assistant_Order_Scanner();
        $this->auth = new Marrison_Assistant_Auth();
        
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
            'custom_prompt' => 'Sei un assistente AI per questo sito web. Rispondi in modo professionale e utile basandoti sui contenuti del sito.',
            'logged_only' => false,
            'last_content_scan' => 0,
            // Opzioni agente sito
            'enable_site_agent' => false,
            'site_agent_position' => 'bottom-right',
            'site_agent_color' => '#0073aa',
            'site_agent_title' => 'Assistente AI',
            'site_agent_name' => 'Marry',  // Nome dell'assistente per il messaggio di benvenuto
            'site_agent_welcome' => 'Ciao, sono {name}, il tuo assistente virtuale, come posso aiutarti?', // Usa {name} come placeholder
            'site_agent_placeholder' => 'Scrivi un messaggio...',
            'site_agent_logged_only' => false,
            // Colori personalizzabili
            'site_agent_icon_color' => '#667eea',      // Colore icona fluttuante
            'site_agent_header_color' => '#667eea',    // Colore testata chat
            'site_agent_button_color' => '#667eea'     // Colore pulsante invio
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
            'custom_prompt' => get_option('marrison_assistant_custom_prompt'),
            'last_content_scan' => get_option('marrison_assistant_last_content_scan')
        );
    }
}

// Inizializza il plugin
new Marrison_Assistant();
