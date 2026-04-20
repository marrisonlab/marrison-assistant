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
    }

    /**
     * Controlla aggiornamenti da GitHub (chiamato quando WP fa il suo check naturale)
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Ottieni versione remota
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return $transient;
        }

        $current_version = MARRISON_ASSISTANT_VERSION;

        // Confronta versioni
        if (version_compare($current_version, $remote_version['version'], '<')) {
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

            $transient->response[$this->plugin_file] = $plugin_data;
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
        
        $data = [
            'version' => $version,
            'url' => $body['html_url'] ?? 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'download_url' => $body['zipball_url'] ?? '',
            'published_at' => $body['published_at'] ?? '',
            'body' => $body['body'] ?? ''
        ];

        // Se non c'è zipball_url, costruisci URL alternativo
        if (empty($data['download_url'])) {
            $data['download_url'] = 'https://github.com/' . $this->github_user . '/' . $this->github_repo . '/archive/refs/tags/' . $body['tag_name'] . '.zip';
        }

        return $data;
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
}

// Inizializza updater
new Marrison_Assistant_Updater();
