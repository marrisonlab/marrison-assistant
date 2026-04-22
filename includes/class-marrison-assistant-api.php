<?php
/**
 * Classe per la gestione delle API REST del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_API {
    
    public function __construct() {
        add_action('wp_ajax_marrison_test_gemini', array($this, 'ajax_test_gemini'));
        add_action('wp_ajax_marrison_scan_content', array($this, 'ajax_scan_content'));
        add_action('wp_ajax_marrison_debug_gemini', array($this, 'ajax_debug_gemini'));
        add_action('wp_ajax_marrison_scan_site_content', array($this, 'ajax_scan_site_content'));
        add_action('wp_ajax_marrison_reset_token_log', array($this, 'ajax_reset_token_log'));
    }
    
    /**
     * AJAX: Debug completo Gemini
     */
    public function ajax_debug_gemini() {
        check_ajax_referer('marrison_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi necessari');
        }
        
        $api_key = get_option('marrison_assistant_gemini_api_key');
        
        ob_start();
        echo "<h3>🔍 Debug Completo Gemini API</h3>";
        
        if (empty($api_key)) {
            echo "<div style='color:red;'>❌ API Key non configurata</div>";
            echo "<p>Inserisci l'API Key nelle impostazioni</p>";
            wp_send_json_success(ob_get_clean());
        }
        
        echo "<div style='color:green;'>✅ API Key: " . substr($api_key, 0, 15) . "...</div>";
        echo "<div>Lunghezza: " . strlen($api_key) . " caratteri</div>";
        
        if (!preg_match('/^AIza[A-Za-z0-9_-]{35}$/', $api_key)) {
            echo "<div style='color:orange;'>⚠️ Formato API Key non standard</div>";
        }
        
        // Test 1: Connessione base e lista modelli
        echo "<h4>Test 1: Connessione base e modelli disponibili</h4>";
        
        $test_url = 'https://generativelanguage.googleapis.com/v1/models';
        $response = wp_remote_get($test_url . '?key=' . $api_key, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            echo "<div style='color:red;'>❌ Errore connessione: " . $response->get_error_message() . "</div>";
            echo "<div>Dettagli: <pre>" . print_r($response, true) . "</pre></div>";
        } else {
            $http_code = wp_remote_retrieve_response_code($response);
            echo "<div>HTTP Code: $http_code</div>";
            
            if ($http_code === 200) {
                echo "<div style='color:green;'>✅ Connessione base riuscita</div>";
                
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (isset($data['models'])) {
                    echo "<h5>Modelli disponibili:</h5>";
                    $gemini_models = array();
                    foreach ($data['models'] as $model) {
                        if (strpos($model['name'], 'gemini') !== false) {
                            $model_name = basename($model['name']);
                            $gemini_models[] = $model_name;
                            echo "<div>- " . $model_name . " (supports: " . implode(', ', $model['supportedGenerationMethods'] ?? array()) . ")</div>";
                        }
                    }
                    
                    // Trova il primo modello che supporta generateContent
                    $working_model = null;
                    foreach ($gemini_models as $model) {
                        if (strpos($model, 'gemini') !== false) {
                            $working_model = $model;
                            break;
                        }
                    }
                    
                    if ($working_model) {
                        echo "<div style='color:green;'><strong>Modello da usare: " . $working_model . "</strong></div>";
                        
                        // Test con il modello trovato
                        echo "<h4>Test 2: Generazione con " . $working_model . "</h4>";
                        
                        $api_url = 'https://generativelanguage.googleapis.com/v1/models/' . $working_model . ':generateContent';
                        $url = add_query_arg('key', $api_key, $api_url);
                        
                        $body = array(
                            'contents' => array(
                                array(
                                    'parts' => array(
                                        array('text' => 'Rispondi con "ok"')
                                    )
                                )
                            )
                        );
                        
                        $args = array(
                            'method' => 'POST',
                            'headers' => array('Content-Type' => 'application/json'),
                            'body' => json_encode($body),
                            'timeout' => 30,
                            'sslverify' => true
                        );
                        
                        $response = wp_remote_post($url, $args);
                        
                        if (is_wp_error($response)) {
                            echo "<div style='color:red;'>❌ Errore generazione: " . $response->get_error_message() . "</div>";
                        } else {
                            $http_code = wp_remote_retrieve_response_code($response);
                            $body_response = wp_remote_retrieve_body($response);
                            
                            echo "<div>HTTP Code: $http_code</div>";
                            echo "<div>Response: <pre>" . esc_html($body_response) . "</pre></div>";
                            
                            if ($http_code === 200) {
                                $data = json_decode($body_response, true);
                                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                                    $ai_response = $data['candidates'][0]['content']['parts'][0]['text'];
                                    echo "<div style='color:green;'>✅ Risposta AI: " . esc_html($ai_response) . "</div>";
                                    echo "<div style='color:green;font-weight:bold;'>🎉 API FUNZIONANTE con modello: " . $working_model . "!</div>";
                                    
                                    // Salva il modello funzionante
                                    update_option('marrison_assistant_working_model', $working_model);
                                }
                            }
                        }
                    } else {
                        echo "<div style='color:red;'>❌ Nessun modello Gemini trovato</div>";
                    }
                }
            } elseif ($http_code === 403) {
                echo "<div style='color:red;'>❌ Accesso negato (403)</div>";
                echo "<p>Verifica API Key e abilitazione Gemini</p>";
            }
        }
        
        // Info server
        echo "<h4>Info Server</h4>";
        echo "<div>PHP: " . PHP_VERSION . "</div>";
        echo "<div>cURL: " . (extension_loaded('curl') ? 'Yes' : 'No') . "</div>";
        echo "<div>allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "</div>";
        
        wp_send_json_success(ob_get_clean());
    }
    
    /**
     * AJAX: Scansiona contenuti del sito
     */
    public function ajax_scan_site_content() {
        // Debug log
        error_log('Marrison Assistant: ajax_scan_site_content called');
        
        // Verifica nonce con fallback
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'marrison_nonce')) {
            error_log('Marrison Assistant: Invalid nonce in scan content');
            wp_send_json_error('Nonce non valido');
        }
        
        // Verifica permessi
        if (!current_user_can('manage_options')) {
            error_log('Marrison Assistant: Insufficient permissions for scan content');
            wp_send_json_error('Permessi insufficienti');
        }
        
        ob_start();
        echo "<div style='padding: 10px;'>";
        echo "<h4>Scansione Contenuti in Corso...</h4>";
        
        try {
            // Inizializza scanner
            if (!class_exists('Marrison_Assistant_Content_Scanner')) {
                echo "<p style='color: red;'>Errore: Classe Content Scanner non trovata</p>";
                echo "</div>";
                wp_send_json_success(ob_get_clean());
                return;
            }
            
            $scanner = new Marrison_Assistant_Content_Scanner();
            
            // Prepara directory e mostra path diagnostico
            $data_dir = $scanner->get_data_directory();
            $dir_ok = is_writable($data_dir);
            echo "<p style='color:#888; font-size:11px;'>Directory dati: " . esc_html($data_dir) . " — " . ($dir_ok ? "<span style='color:green'>scrivibile</span>" : "<span style='color:red'>NON scrivibile</span>") . "</p>";

            // Esegui scansione pagine
            echo "<p>Scansione pagine...</p>";
            $pages = $scanner->scan_pages();
            echo "<p>Trovate " . count($pages) . " pagine</p>";
            if (!empty($pages)) {
                $r = $scanner->save_content_file_public('pages', $pages);
                echo "<p style='color:#888; font-size:11px;'>pages.json: " . ($r !== false ? esc_html($r) . " bytes" : "<span style='color:red'>ERRORE scrittura</span>") . "</p>";
            }

            // Esegui scansione articoli
            echo "<p>Scansione articoli...</p>";
            $posts = $scanner->scan_posts();
            echo "<p>Trovati " . count($posts) . " articoli</p>";
            if (!empty($posts)) {
                $r = $scanner->save_content_file_public('posts', $posts);
                echo "<p style='color:#888; font-size:11px;'>posts.json: " . ($r !== false ? esc_html($r) . " bytes" : "<span style='color:red'>ERRORE scrittura</span>") . "</p>";
            }

            // Esegui scansione prodotti (se WooCommerce è attivo)
            echo "<p>Scansione prodotti...</p>";
            $products = array();
            if (class_exists('WooCommerce')) {
                $products = $scanner->scan_products();
                echo "<p>Trovati " . count($products) . " prodotti</p>";
                if (!empty($products)) {
                    $r = $scanner->save_content_file_public('products', $products);
                    echo "<p style='color:#888; font-size:11px;'>products.json: " . ($r !== false ? esc_html($r) . " bytes" : "<span style='color:red'>ERRORE scrittura</span>") . "</p>";
                }

                // Scansione ordini
                echo "<p>Scansione ordini...</p>";
                $orders = $scanner->scan_orders();
                echo "<p>Trovati " . count($orders) . " ordini</p>";
                if (!empty($orders)) {
                    $r = $scanner->save_content_file_public('orders', $orders);
                    echo "<p style='color:#888; font-size:11px;'>orders.json: " . ($r !== false ? esc_html($r) . " bytes" : "<span style='color:red'>ERRORE scrittura</span>") . "</p>";
                }
            } else {
                echo "<p>WooCommerce non installato - saltato prodotti e ordini</p>";
            }

            // Scansione eventi
            echo "<p>Scansione eventi...</p>";
            $events = $scanner->scan_events();
            echo "<p>Trovati " . count($events) . " eventi</p>";
            if (!empty($events)) {
                $r = $scanner->save_content_file_public('events', $events);
                echo "<p style='color:#888; font-size:11px;'>events.json: " . ($r !== false ? esc_html($r) . " bytes" : "<span style='color:red'>ERRORE scrittura</span>") . "</p>";
            }

            // Salva anche in wp_options come fallback legacy
            $content = array(
                'pages'       => $pages,
                'posts'       => $posts,
                'products'    => $products,
                'scanned_at'  => current_time('mysql'),
                'total_items' => count($pages) + count($posts) + count($products)
            );

            update_option('marrison_assistant_site_content', $content);
            update_option('marrison_assistant_last_content_scan', time());

            echo "<h4 style='color: green;'>Scansione Completata!</h4>";
            echo "<p><strong>Totale:</strong> " . $content['total_items'] . " elementi scansionati</p>";
            echo "<p><strong>Data:</strong> " . $content['scanned_at'] . "</p>";
            echo "<p><strong>Knowledge base aggiornata!</strong></p>";
            
            error_log('Marrison Assistant: Content scan completed successfully');
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Errore durante scansione: " . esc_html($e->getMessage()) . "</p>";
            error_log('Marrison Assistant: Scan error: ' . $e->getMessage());
        }
        
        echo "</div>";
        wp_send_json_success(ob_get_clean());
    }
    
    /**
     * AJAX: Test connessione Gemini
     */
    public function ajax_test_gemini() {
        check_ajax_referer('marrison_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi necessari');
        }
        
        $gemini = new Marrison_Assistant_Gemini();
        $result = $gemini->test_connection();
        
        if ($result === true) {
            wp_send_json_success('Connessione Gemini riuscita');
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Scansione contenuti
     */
    public function ajax_scan_content() {
        check_ajax_referer('marrison_scan_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi necessari');
        }

        // === DIAGNOSTICA DIRECTORY ===
        $upload_dir   = wp_upload_dir();
        $upload_base  = $upload_dir['basedir'];
        $target_dir   = $upload_base . '/marrison-assistant';
        $plugin_dir   = MARRISON_ASSISTANT_PLUGIN_DIR . 'data';

        $debug = array();
        $debug[] = 'uploads basedir: ' . $upload_base;
        $debug[] = 'uploads scrivibile: ' . (is_writable($upload_base) ? 'SI' : 'NO');
        $debug[] = 'target dir: ' . $target_dir;
        $debug[] = 'target dir esiste: ' . (file_exists($target_dir) ? 'SI' : 'NO');
        if (isset($upload_dir['error']) && $upload_dir['error']) {
            $debug[] = 'ERRORE upload_dir: ' . $upload_dir['error'];
        }

        // Tentativo creazione manuale della directory
        if (!file_exists($target_dir)) {
            $mkdir_result = wp_mkdir_p($target_dir);
            $debug[] = 'mkdir result: ' . ($mkdir_result ? 'RIUSCITO' : 'FALLITO');
        } else {
            $debug[] = 'target dir scrivibile: ' . (is_writable($target_dir) ? 'SI' : 'NO');
        }

        // Test scrittura diretta
        $test_file = $target_dir . '/test.txt';
        $write_test = @file_put_contents($test_file, 'test');
        $debug[] = 'test scrittura: ' . ($write_test !== false ? 'OK (' . $write_test . ' bytes)' : 'FALLITO');
        if ($write_test !== false) {
            @unlink($test_file);
        }

        // Prova fallback nella directory plugin
        if (!file_exists($plugin_dir)) {
            $plugin_mkdir = wp_mkdir_p($plugin_dir);
            $debug[] = 'plugin data dir mkdir: ' . ($plugin_mkdir ? 'RIUSCITO' : 'FALLITO');
        }
        $plugin_test_file = $plugin_dir . '/test.txt';
        $plugin_write_test = @file_put_contents($plugin_test_file, 'test');
        $debug[] = 'plugin dir scrittura: ' . ($plugin_write_test !== false ? 'OK' : 'FALLITO');
        if ($plugin_write_test !== false) {
            @unlink($plugin_test_file);
        }
        // ===========================

        $scanner = new Marrison_Assistant_Content_Scanner();
        $content = $scanner->scan_all_content();

        $stats = $scanner->get_content_stats();

        // Verifica se i file sono stati creati
        foreach (array('pages', 'posts', 'products', 'orders', 'events') as $type) {
            $primary  = $target_dir . '/' . $type . '.json';
            $fallback = $plugin_dir . '/' . $type . '.json';
            if (file_exists($primary)) {
                $debug[] = $type . '.json: creato in uploads (' . filesize($primary) . ' bytes)';
            } elseif (file_exists($fallback)) {
                $debug[] = $type . '.json: creato in plugin/data (' . filesize($fallback) . ' bytes)';
            } else {
                $debug[] = $type . '.json: NON CREATO (forse contenuto vuoto?)';
            }
        }

        $message = sprintf(
            'Scansione completata: %d pagine, %d articoli, %d prodotti — DEBUG: %s',
            $stats['total_pages'],
            $stats['total_posts'],
            $stats['total_products'],
            implode(' | ', $debug)
        );

        wp_send_json_success($message);
    }

    /**
     * AJAX: Azzera il log dei token
     */
    public function ajax_reset_token_log() {
        check_ajax_referer('marrison_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permesso negato');
        }
        update_option('marrison_assistant_token_log', array());
        wp_send_json_success('Log token azzerato.');
    }
}
