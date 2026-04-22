<?php
/**
 * GitHub Updater per Marrison Assistant
 * Gestisce aggiornamenti automatici dal repository GitHub
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Updater {

    private $plugin_slug = 'marrison-assistant';
    private $plugin_file = 'marrison-assistant/marrison-assistant.php';
    private $github_user = 'marrisonlab';
    private $github_repo = 'marrison-assistant';
    private $github_api_url = 'https://api.github.com/repos/marrisonlab/marrison-assistant';
    public function __construct() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_action('upgrader_pre_download', [$this, 'perform_plugin_update'], 10, 2);
        add_filter('upgrader_package_options', [$this, 'debug_package_options'], 10, 3);
    }

    /**
     * Controlla aggiornamenti da GitHub (chiamato quando WP fa il suo check naturale)
     */
    public function check_update($transient) {
        error_log('Marrison Assistant: check_update called');
        
        if (empty($transient->checked)) {
            error_log('Marrison Assistant: transient checked is empty');
            return $transient;
        }

        // Ottieni versione remota
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            error_log('Marrison Assistant: failed to get remote version');
            return $transient;
        }

        $current_version = MARRISON_ASSISTANT_VERSION;
        error_log('Marrison Assistant: current version ' . $current_version . ', remote version ' . $remote_version['version']);

        // Confronta versioni
        if (version_compare($current_version, $remote_version['version'], '<')) {
            error_log('Marrison Assistant: update available, preparing plugin data');
            
            $plugin_data = new stdClass();
            $plugin_data->slug = $this->plugin_slug;
            $plugin_data->plugin = $this->plugin_file;
            $plugin_data->new_version = $remote_version['version'];
            $plugin_data->url = $remote_version['url'];
            $plugin_data->package = $remote_version['download_url'];
            $plugin_data->icons = [];
            $plugin_data->banners = [];
            $plugin_data->banners_rtl = [];
            $plugin_data->tested = '6.4';
            $plugin_data->requires_php = '7.4';

            error_log('Marrison Assistant: package URL set to: ' . $remote_version['download_url']);
            
            $transient->response[$this->plugin_file] = $plugin_data;
            
            error_log('Marrison Assistant: plugin data added to transient response');
        } else {
            error_log('Marrison Assistant: no update needed');
        }

        return $transient;
    }

    /**
     * Ottiene informazioni sulla versione remota da GitHub
     */
    private function get_remote_version() {
        // Ottieni ultima release da GitHub
        $response = wp_remote_get(
            $this->github_api_url . '/releases/latest',
            [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version')
                ]
            ]
        );

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['tag_name'])) {
            return false;
        }

        // Estrai versione (rimuovi 'v' iniziale se presente)
        $version = ltrim($body['tag_name'], 'v');
        
        // Priorità: zipball_url > assets_url > construct URL
        $download_url = '';
        if (!empty($body['zipball_url'])) {
            $download_url = $body['zipball_url'];
        } elseif (!empty($body['assets']) && is_array($body['assets'])) {
            // Cerca il primo asset che sia un .zip
            foreach ($body['assets'] as $asset) {
                if (!empty($asset['browser_download_url']) && strpos($asset['browser_download_url'], '.zip') !== false) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        // Se ancora non abbiamo URL, costruiscilo
        if (empty($download_url)) {
            $download_url = 'https://github.com/' . $this->github_user . '/' . $this->github_repo . '/archive/refs/tags/' . $body['tag_name'] . '.zip';
        }
        
        error_log('Marrison Assistant: GitHub release download URL: ' . $download_url);
        
        $data = [
            'version' => $version,
            'url' => $body['html_url'] ?? 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'download_url' => $download_url,
            'published_at' => $body['published_at'] ?? '',
            'body' => $body['body'] ?? ''
        ];

        return $data;
    }

    /**
     * Sovrascrive il processo di aggiornamento per gestire cartelle GitHub con nomi casuali
     */
    public function perform_plugin_update($upgrader_object, $options) {
        if (!isset($options['action']) || $options['action'] !== 'update') {
            return;
        }
        if (!isset($options['type']) || $options['type'] !== 'plugin') {
            return;
        }

        $plugin_file = $this->plugin_file;
        if (!isset($options['plugins']) || !is_array($options['plugins'])) {
            return;
        }

        if (!in_array($plugin_file, $options['plugins'])) {
            return;
        }

        // Esegui l'aggiornamento personalizzato dopo quello standard
        add_action('upgrader_process_complete', [$this, 'fix_github_folder_name'], 10, 2);
    }

    /**
     * Corregge il nome della cartella dopo l'aggiornamento GitHub
     */
    public function fix_github_folder_name($upgrader_object, $options) {
        global $wp_filesystem;
        
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();

        if (!$wp_filesystem) {
            return;
        }

        $plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        $expected_plugin_file = $plugin_dir . '/' . basename($this->plugin_file);

        // Se il file del plugin esiste nella posizione corretta, non fare nulla
        if ($wp_filesystem->exists($expected_plugin_file)) {
            return;
        }

        // Cerca cartelle GitHub con nomi casuali
        $plugin_base_files = glob(WP_PLUGIN_DIR . '/' . $this->plugin_slug . '-*');
        if (empty($plugin_base_files)) {
            return;
        }

        foreach ($plugin_base_files as $candidate_dir) {
            if (!is_dir($candidate_dir)) {
                continue;
            }

            $candidate_plugin_file = $candidate_dir . '/' . basename($this->plugin_file);
            if ($wp_filesystem->exists($candidate_plugin_file)) {
                // Rinomina la cartella al nome corretto
                if ($wp_filesystem->move($candidate_dir, $plugin_dir)) {
                    // Pulisci la cache dei plugin
                    wp_clean_plugins_cache(true);
                    delete_site_transient('update_plugins');
                    
                    error_log('Marrison Assistant: GitHub folder renamed from ' . basename($candidate_dir) . ' to ' . $this->plugin_slug);
                }
                break;
            }
        }
    }

    /**
     * Fornisce informazioni plugin per la schermata "Vedi dettagli"
     */
    public function plugin_info($res, $action, $args) {
        if ($action !== 'plugin_information') {
            return $res;
        }

        if ($args->slug !== $this->plugin_slug) {
            return $res;
        }

        $remote = $this->get_remote_version();
        
        if (!$remote) {
            return $res;
        }

        $info = new stdClass();
        $info->name = 'Marrison Assistant';
        $info->slug = $this->plugin_slug;
        $info->version = $remote['version'];
        $info->author = '<a href="https://marrisonlab.com" target="_blank">Marrisonlab</a>';
        $info->author_profile = 'https://marrisonlab.com';
        $info->plugin_url = 'https://github.com/marrisonlab/marrison-assistant';
        $info->download_link = $remote['download_url'];
        $info->requires_php = '7.4';
        $info->requires = '5.0';
        $info->tested = '6.4';
        $info->last_updated = $remote['published_at'];
        $info->homepage = 'https://github.com/marrisonlab/marrison-assistant';
        $info->sections = [
            'description' => 'Assistente AI per WordPress con integrazione Google Gemini. Widget chat frontend, RAG, analytics token e rate limiting integrati.',
            'installation' => '1. Carica il plugin in wp-content/plugins/<br>2. Attiva il plugin<br>3. Configura la Gemini API Key nelle impostazioni',
            'changelog' => $this->parse_changelog($remote['body']),
            'faq' => '<strong>Dove trovo la API Key?</strong><br>Vai su Google AI Studio e crea una nuova API Key.<br><br><strong>Supporta WooCommerce?</strong><br>Sì, scansiona automaticamente prodotti e ordini.'
        ];

        return $info;
    }

    /**
     * Converte markdown changelog in HTML
     */
    private function parse_changelog($body) {
        if (empty($body)) {
            return 'Consulta il repository GitHub per il changelog completo.';
        }

        // Converte markdown base in HTML
        $html = esc_html($body);
        $html = nl2br($html);
        
        // Bold per versioni
        $html = preg_replace('/^(#{1,3}\s*)(.+)$/m', '<strong>$2</strong>', $html);
        
        // Lista puntata
        $html = preg_replace('/^[-*]\s+(.+)$/m', '• $1', $html);

        return $html;
    }

    /**
     * Debug delle opzioni del package durante l'aggiornamento
     */
    public function debug_package_options($options, $package, $upgrader) {
        error_log('Marrison Assistant: debug_package_options called');
        error_log('Marrison Assistant: package = ' . var_export($package, true));
        error_log('Marrison Assistant: options = ' . var_export($options, true));
        
        // Se il package è nullo o vuoto, questo è il nostro problema
        if (empty($package)) {
            error_log('Marrison Assistant: CRITICAL - Package is empty/null!');
        }
        
        return $options;
    }
}

// Inizializza updater
error_log('Marrison Assistant: Initializing updater');
new Marrison_Assistant_Updater();
