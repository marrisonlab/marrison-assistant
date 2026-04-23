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
        // Inietta update quando WP legge la cache update_plugins (più affidabile di pre_set_*).
        add_filter('site_transient_update_plugins', [$this, 'check_update'], 999);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        // Fallback robusto: se WP fallisce il download (path nullo), scarichiamo noi in un tmp file.
        add_filter('upgrader_pre_download', [$this, 'pre_download_package'], 10, 3);
        add_action('upgrader_pre_download', [$this, 'perform_plugin_update'], 10, 2);
        // WP core currently calls this filter with 1 argument ($options). Accepting up to 3
        // keeps compatibility with any older internal usage in our code.
        add_filter('upgrader_package_options', [$this, 'debug_package_options'], 10, 3);
        add_action('upgrader_process_complete', [$this, 'cleanup_maintenance_file'], 10, 2);
    }

    /**
     * Controlla aggiornamenti da GitHub (chiamato quando WP fa il suo check naturale)
     */
    public function check_update($transient) {
        error_log('Marrison Assistant: check_update called');
        if (!is_object($transient)) {
            $transient = new stdClass();
        }
        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = [];
        }
        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = [];
        }
        if (!isset($transient->checked) || !is_array($transient->checked)) {
            $transient->checked = [];
        }

        if (empty($transient->checked)) {
            // Niente da confrontare, non iniettiamo update.
            error_log('Marrison Assistant: transient checked is empty');
            return $transient;
        }

        // Ottieni versione remota
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            error_log('Marrison Assistant: failed to get remote version');
            return $transient;
        }

        // Versione locale: usa checked se presente, fallback alla costante
        $current_version = $transient->checked[$this->plugin_file] ?? MARRISON_ASSISTANT_VERSION;
        error_log('Marrison Assistant: current version ' . $current_version . ', remote version ' . $remote_version['version']);

        // Confronta versioni
        $item = new stdClass();
        $item->slug = $this->plugin_slug;
        $item->plugin = $this->plugin_file;
        $item->new_version = $remote_version['version'];
        $item->url = $remote_version['url'];
        $item->package = $remote_version['download_url'];
        $item->icons = [];
        $item->banners = [];
        $item->banners_rtl = [];
        $item->tested = '6.4';
        $item->requires_php = '7.4';
        $item->compatibility = new stdClass();

        error_log('Marrison Assistant: package URL set to: ' . $remote_version['download_url']);

        if (version_compare($current_version, $remote_version['version'], '<')) {
            error_log('Marrison Assistant: update available, injecting response');
            $transient->response[$this->plugin_file] = $item;
        } else {
            // Popolare no_update aiuta WP a gestire meglio UI e toggle auto-update
            error_log('Marrison Assistant: no update needed, injecting no_update');
            $transient->no_update[$this->plugin_file] = $item;
        }

        $transient->checked[$this->plugin_file] = $current_version;

        return $transient;
    }

    /**
     * Ottiene informazioni sulla versione remota da GitHub
     */
    private function get_remote_version() {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
        ];

        // 1) Prova con /releases/latest (solo release non-prerelease)
        $response = wp_remote_get($this->github_api_url . '/releases/latest', [
            'timeout' => 15,
            'headers' => $headers,
        ]);

        $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
        if (is_wp_error($response) || $code !== 200) {
            error_log('Marrison Assistant: GitHub releases/latest failed (HTTP ' . $code . '): ' . (is_wp_error($response) ? $response->get_error_message() : ''));
            $body = null;
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
        }

        // 2) Fallback: lista release e scegli la versione maggiore (utile se latest non è aggiornato o prerelease)
        if (empty($body['tag_name'])) {
            $fallback = wp_remote_get($this->github_api_url . '/releases?per_page=10', [
                'timeout' => 15,
                'headers' => $headers,
            ]);

            $fcode = is_wp_error($fallback) ? 0 : (int) wp_remote_retrieve_response_code($fallback);
            if (is_wp_error($fallback) || $fcode !== 200) {
                error_log('Marrison Assistant: GitHub releases list failed (HTTP ' . $fcode . '): ' . (is_wp_error($fallback) ? $fallback->get_error_message() : ''));
                return false;
            }

            $releases = json_decode(wp_remote_retrieve_body($fallback), true);
            if (!is_array($releases) || empty($releases)) {
                return false;
            }

            $best = null;
            foreach ($releases as $r) {
                if (!is_array($r) || empty($r['tag_name'])) continue;
                if (!empty($r['draft'])) continue; // ignora bozze
                $tag = $r['tag_name'];
                $ver = ltrim((string) $tag, 'v');
                if ($best === null || version_compare($ver, $best['version'], '>')) {
                    $best = [
                        'tag_name' => $tag,
                        'version' => $ver,
                        'html_url' => $r['html_url'] ?? null,
                        'published_at' => $r['published_at'] ?? '',
                        'body' => $r['body'] ?? '',
                        'assets' => $r['assets'] ?? [],
                    ];
                }
            }

            if ($best === null) {
                return false;
            }

            // Normalizza formato atteso più sotto
            $body = [
                'tag_name' => $best['tag_name'],
                'html_url' => $best['html_url'],
                'published_at' => $best['published_at'],
                'body' => $best['body'],
                'assets' => $best['assets'],
            ];
        }

        // Estrai versione (rimuovi 'v' iniziale se presente)
        $version = ltrim($body['tag_name'], 'v');
        
        // Preferisci URL "github.com/.../archive/refs/tags/..." perché l'endpoint API zipball
        // (`api.github.com/.../zipball/...`) può restituire redirect o rate limit e far fallire
        // il download in WP_Upgrader (ZipArchive filename vuoto).
        $download_url = '';
        if (!empty($body['assets']) && is_array($body['assets'])) {
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
            // codeload è più "diretto" per WP_Upgrader rispetto a github.com/archive (meno redirect strani)
            $download_url = 'https://codeload.github.com/' . $this->github_user . '/' . $this->github_repo . '/zip/refs/tags/' . $body['tag_name'];
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
    public function debug_package_options($options, $hook_extra = null, $result = null) {
        error_log('Marrison Assistant: debug_package_options called');
        error_log('Marrison Assistant: options = ' . var_export($options, true));
        
        // Controlliamo se c'è package nelle opzioni
        if (isset($options['package'])) {
            error_log('Marrison Assistant: package = ' . var_export($options['package'], true));

            // Se WP sta usando l'endpoint API zipball, riscrivilo verso l'archivio tags su github.com
            // per evitare download falliti/filename vuoto in ZipArchive.
            if (is_string($options['package']) && strpos($options['package'], 'https://api.github.com/repos/') === 0 && strpos($options['package'], '/zipball/') !== false) {
                $tag = basename($options['package']);
                if (!empty($tag)) {
                    $options['package'] = 'https://codeload.github.com/' . $this->github_user . '/' . $this->github_repo . '/zip/refs/tags/' . $tag;
                    error_log('Marrison Assistant: rewritten package URL to: ' . $options['package']);
                }
            }

            // Se arriva un URL github.com/archive, riscrivilo a codeload (più affidabile lato WP)
            if (is_string($options['package']) && strpos($options['package'], 'https://github.com/' . $this->github_user . '/' . $this->github_repo . '/archive/refs/tags/') === 0) {
                $tagZip = basename($options['package']); // es: v1.3.4.zip
                $tag = preg_replace('/\.zip$/', '', $tagZip);
                if (!empty($tag)) {
                    $options['package'] = 'https://codeload.github.com/' . $this->github_user . '/' . $this->github_repo . '/zip/refs/tags/' . $tag;
                    error_log('Marrison Assistant: rewritten archive URL to codeload: ' . $options['package']);
                }
            }
            
            // Se il package è nullo o vuoto, questo è il nostro problema
            if (empty($options['package'])) {
                error_log('Marrison Assistant: CRITICAL - Package is empty/null!');
            }
        }
        
        return $options;
    }

    /**
     * Download robusto per WP_Upgrader: restituisce path locale del pacchetto.
     */
    public function pre_download_package($reply, $package, $upgrader) {
        if (!is_string($package) || $package === '') {
            return $reply;
        }

        // Intercetta solo i pacchetti del nostro repo
        $is_ours =
            (strpos($package, 'https://codeload.github.com/' . $this->github_user . '/' . $this->github_repo . '/zip/') === 0) ||
            (strpos($package, 'https://github.com/' . $this->github_user . '/' . $this->github_repo . '/') === 0) ||
            (strpos($package, 'https://api.github.com/repos/' . $this->github_user . '/' . $this->github_repo . '/') === 0);

        if (!$is_ours) {
            return $reply;
        }

        // Se WP ha già un reply valido, non tocchiamo.
        if (!empty($reply) && !is_wp_error($reply)) {
            return $reply;
        }

        // Scarica in un file temporaneo streammando il contenuto (evita filename vuoti in ZipArchive)
        $tmp = wp_tempnam($package);
        if (!$tmp) {
            return new WP_Error('marrison_tmp_failed', 'Impossibile creare file temporaneo per il download.');
        }

        error_log('Marrison Assistant: pre_download_package streaming to tmp: ' . $tmp);

        $response = wp_remote_get($package, [
            'timeout'     => 60,
            'redirection' => 10,
            'user-agent'  => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
            'stream'      => true,
            'filename'    => $tmp,
        ]);

        if (is_wp_error($response)) {
            @unlink($tmp);
            error_log('Marrison Assistant: pre_download_package failed: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            @unlink($tmp);
            error_log('Marrison Assistant: pre_download_package HTTP ' . $code);
            return new WP_Error('marrison_download_http', 'Download pacchetto fallito (HTTP ' . $code . ').');
        }

        if (!file_exists($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            return new WP_Error('marrison_download_empty', 'Download pacchetto vuoto.');
        }

        return $tmp;
    }

    /**
     * Pulisce il file .maintenance anche se l'aggiornamento fallisce
     */
    public function cleanup_maintenance_file($upgrader, $options) {
        error_log('Marrison Assistant: Cleanup maintenance file called');
        
        $maintenance_file = ABSPATH . '.maintenance';
        if (file_exists($maintenance_file)) {
            error_log('Marrison Assistant: Removing maintenance file');
            unlink($maintenance_file);
        }
    }
}

// Inizializza updater
error_log('Marrison Assistant: Initializing updater');
new Marrison_Assistant_Updater();
