<?php
/**
 * Plugin Name: Marrison Assistant
 * Plugin URI: https://github.com/marrisonlab/marrison-assistant
 * Description: Asssistente professionale AI per i tuoi clienti
 * Version: 1.3.4.5
 * Author: Marrisonlab
 * Author URI: https://marrisonlab.com
 * Text Domain: marrison-assistant
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Previeni caricamento multiplo (solo controllo versione)
if (defined('MARRISON_ASSISTANT_VERSION')) {
    error_log('Marrison Assistant: Plugin already loaded, skipping');
    return;
}

// Definisci costanti del plugin
define('MARRISON_ASSISTANT_VERSION', '1.3.4.5');
define('MARRISON_ASSISTANT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MARRISON_ASSISTANT_PLUGIN_URL', plugin_dir_url(__FILE__));

error_log('Marrison Assistant: Loading plugin v' . MARRISON_ASSISTANT_VERSION . ' from ' . MARRISON_ASSISTANT_PLUGIN_DIR);

// Carica i file necessari
require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-white-label.php';
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

        // White-label: sovrascrive nome e autore nella lista plugin WP (solo se configurato)
        add_filter('all_plugins', array($this, 'apply_white_label_plugin_info'));
    }

    /**
     * Sovrascrive le info del plugin nella lista WP admin in base a white-label.json.
     * Versione e aggiornamenti restano invariati.
     */
    public function apply_white_label_plugin_info($plugins) {
        if (!Marrison_Assistant_White_Label::is_active()) {
            return $plugins;
        }
        $key = plugin_basename(__FILE__);
        if (!isset($plugins[$key])) {
            return $plugins;
        }
        $name       = Marrison_Assistant_White_Label::plugin_name();
        $author     = Marrison_Assistant_White_Label::author();
        $author_url = Marrison_Assistant_White_Label::author_url();

        $plugins[$key]['Name']       = $name;
        $plugins[$key]['Title']      = $name;
        $plugins[$key]['Author']     = '<a href="' . esc_url($author_url) . '">' . esc_html($author) . '</a>';
        $plugins[$key]['AuthorName'] = $author;
        $plugins[$key]['AuthorURI']  = $author_url;
        $plugins[$key]['PluginURI']  = $author_url;

        return $plugins;
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
            'site_agent_button_color' => '#667eea',    // Colore pulsante invio
            // Risposte ai bottoni di categoria (personalizzabili per tipo di sito)
            'site_agent_response_products' => 'Perfetto! Dimmi cosa stai cercando tra i nostri prodotti.',
            'site_agent_response_orders'   => 'Certo! Dimmi il numero ordine o cosa vorresti sapere sul tuo acquisto.',
            'site_agent_response_info'     => 'Con piacere! Su cosa vorresti informazioni? Azienda, contatti, servizi?',
            'site_agent_response_events'   => 'Ottimo! Stai cercando un evento specifico o vuoi vedere il calendario?'
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
        $this->set_default_options();
        flush_rewrite_rules();

        // Prima scansione immediata all'installazione
        $scanner = new Marrison_Assistant_Content_Scanner();
        $scanner->scan_all_content();

        // Schedula la scansione automatica ogni 24 ore
        if (!wp_next_scheduled('marrison_assistant_auto_scan')) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'marrison_assistant_auto_scan');
        }
    }
    
    /**
     * Disattivazione del plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
        wp_clear_scheduled_hook('marrison_assistant_auto_scan');
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
error_log('Marrison Assistant: Initializing main plugin class');
new Marrison_Assistant();

// Cron: scansione automatica contenuti ogni 24 ore
add_action('marrison_assistant_auto_scan', function() {
    if (!class_exists('Marrison_Assistant_Content_Scanner')) {
        require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-content-scanner.php';
    }
    $scanner = new Marrison_Assistant_Content_Scanner();
    $scanner->scan_all_content();
    error_log('Marrison Assistant: auto-scan completato via cron - ' . current_time('mysql'));
});

// Assicura che il cron sia pianificato (utile dopo update plugin)
add_action('plugins_loaded', function() {
    if (!wp_next_scheduled('marrison_assistant_auto_scan')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'marrison_assistant_auto_scan');
    }
});
