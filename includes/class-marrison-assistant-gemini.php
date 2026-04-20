<?php
/**
 * Classe per l'integrazione con Google Gemini API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Gemini {
    
    private $api_key;
    private $api_url;
    private $current_intent = 'general';
    
    public function __construct() {
        $this->api_key = get_option('marrison_assistant_gemini_api_key');
        
        // Usa il modello funzionante salvato dal debug, o il default
        $working_model = get_option('marrison_assistant_working_model', 'gemini-2.5-flash');
        $this->api_url = 'https://generativelanguage.googleapis.com/v1/models/' . $working_model . ':generateContent';
    }
    
    /**
     * Testa la connessione con l'API Gemini
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return 'API Key non configurata';
        }
        
        // Verifica formato API Key
        if (strlen($this->api_key) < 20) {
            return 'API Key non valida (troppo corta)';
        }
        
        $test_prompt = 'Rispondi semplicemente con "ok"';
        $response = $this->send_to_gemini($test_prompt);
        
        if ($response === false) {
            // Controlla gli errori specifici nei log
            $last_error = error_get_last();
            if ($last_error && strpos($last_error['message'], 'Marrison Assistant') !== false) {
                return 'Errore API: controlla i log per dettagli';
            }
            return 'Errore di connessione all\'API - verifica API Key e connessione';
        }
        
        if (strpos(strtolower($response), 'ok') !== false) {
            return true;
        }
        
        return 'Risposta non valida dall\'API: ' . substr($response, 0, 100);
    }
    
    /**
     * Invia un prompt a Gemini e ottiene la risposta
     */
    public function send_to_gemini($prompt) {
        if (empty($this->api_key)) {
            error_log('Marrison Assistant: API Key Gemini mancante');
            return false;
        }
        
        // Usa direttamente il modello gemini-pro che è garantito funzionare
        $result = $this->try_gemini_endpoint($prompt, $this->api_url);
        
        return $result;
    }
    
    /**
     * Cerca contenuti pertinenti nella knowledge base
     */
    private function search_relevant_content($query, $site_content) {
        $results = array();
        $query_lower = strtolower($query);
        $query_words = array_filter(explode(' ', $query_lower));
        
        // Cerca nelle pagine
        if (!empty($site_content['pages'])) {
            foreach ($site_content['pages'] as $page) {
                $score = $this->calculate_relevance_score($query_words, $page['title'], $page['content']);
                if ($score > 0) {
                    $results[] = array(
                        'type' => 'page',
                        'title' => $page['title'],
                        'url' => $page['url'],
                        'score' => $score,
                        'excerpt' => $this->get_excerpt($page['content'], 150)
                    );
                }
            }
        }
        
        // Cerca negli articoli
        if (!empty($site_content['posts'])) {
            foreach ($site_content['posts'] as $post) {
                $score = $this->calculate_relevance_score($query_words, $post['title'], $post['content']);
                if ($score > 0) {
                    $results[] = array(
                        'type' => 'articolo',
                        'title' => $post['title'],
                        'url' => $post['url'],
                        'score' => $score,
                        'excerpt' => $this->get_excerpt($post['content'], 150)
                    );
                }
            }
        }
        
        // Cerca nei prodotti
        if (!empty($site_content['products'])) {
            foreach ($site_content['products'] as $product) {
                $score = $this->calculate_relevance_score($query_words, $product['title'], $product['description']);
                if ($score > 0) {
                    $results[] = array(
                        'type' => 'prodotto',
                        'title' => $product['title'],
                        'url' => $product['url'],
                        'score' => $score,
                        'price' => $product['price'],
                        'excerpt' => $this->get_excerpt($product['description'], 150)
                    );
                }
            }
        }
        
        // Ordina per rilevanza
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Restituisci i primi 5 risultati più rilevanti
        return array_slice($results, 0, 5);
    }
    
    /**
     * Calcola il punteggio di rilevanza
     */
    private function calculate_relevance_score($query_words, $title, $content) {
        $score = 0;
        $title_lower = strtolower($title);
        $content_lower = strtolower(strip_tags($content));
        
        foreach ($query_words as $word) {
            if (strlen($word) < 3) continue; // Ignora parole corte
            
            // Punteggio maggiore se la parola è nel titolo
            if (strpos($title_lower, $word) !== false) {
                $score += 10;
            }
            
            // Punteggio se la parola è nel contenuto
            $content_count = substr_count($content_lower, $word);
            $score += $content_count * 2;
        }
        
        return $score;
    }
    
    /**
     * Estrae un excerpt dal contenuto
     */
    private function get_excerpt($content, $length = 150) {
        $text = strip_tags($content);
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }
        return $text;
    }

    /**
     * Invia il prompt al Commander (proxy) e ritorna la risposta AI
     */
    private function call_commander($full_prompt) {
        $commander_url = get_option('marrison_assistant_commander_url', 'https://marrisonlab.com');
        $endpoint = trailingslashit($commander_url) . 'wp-json/marrison-commander/v1/chat';
        $site_url = get_site_url();

        // Stima locale dei token (italiano: ~3.5 char/token)
        $prompt_bytes  = strlen($full_prompt);
        $prompt_tokens_est = (int) ceil($prompt_bytes / 3.5);
        error_log('--- MARRISON DEBUG: PROMPT ---');
        error_log('Prompt bytes: ' . $prompt_bytes);
        error_log('Token stimati (prompt): ~' . $prompt_tokens_est);

        $response = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode([
                'site_url' => $site_url,
                'prompt'   => $full_prompt,
            ]),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            error_log('Marrison Assistant: Commander error - ' . $response->get_error_message());
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body      = json_decode(wp_remote_retrieve_body($response), true);

        // Log usageMetadata se il Commander lo restituisce (passthrough da Gemini)
        $real_prompt  = null;
        $real_output  = null;
        $real_total   = null;
        if (!empty($body['usageMetadata'])) {
            $u = $body['usageMetadata'];
            $real_prompt = isset($u['promptTokenCount'])     ? (int) $u['promptTokenCount']     : null;
            $real_output = isset($u['candidatesTokenCount']) ? (int) $u['candidatesTokenCount'] : null;
            $real_total  = isset($u['totalTokenCount'])      ? (int) $u['totalTokenCount']      : null;
            error_log('--- MARRISON DEBUG: PESO CHAT ---');
            error_log('Token Input  (Prompt):   ' . ($real_prompt  ?? 'n/d'));
            error_log('Token Output (Risposta): ' . ($real_output  ?? 'n/d'));
            error_log('Token Totali:            ' . ($real_total   ?? 'n/d'));
        } else {
            error_log('--- MARRISON DEBUG: usageMetadata non presente nella risposta Commander ---');
        }

        // Salva nel log token per la tab Analytics
        $log = get_option('marrison_assistant_token_log', array());
        $log[] = array(
            'time'              => time(),
            'intent'            => $this->current_intent,
            'prompt_bytes'      => $prompt_bytes,
            'prompt_tokens_est' => $prompt_tokens_est,
            'prompt_tokens_real'=> $real_prompt,
            'output_tokens'     => $real_output,
            'total_tokens'      => $real_total,
        );
        // Mantieni solo gli ultimi 200 record
        if (count($log) > 200) {
            $log = array_slice($log, -200);
        }
        update_option('marrison_assistant_token_log', $log);

        if ($http_code === 200 && !empty($body['message'])) {
            return $body['message'];
        }

        if ($http_code === 429) return '⚠️ Quota giornaliera esaurita. Riprova domani.';
        if ($http_code === 403) return '⚠️ Sito non autorizzato. Contatta l\'amministratore.';

        error_log('Marrison Assistant: Commander HTTP ' . $http_code . ' - ' . print_r($body, true));
        return false;
    }

    /**
     * Processa un messaggio con RAG semplificato:
     * filtra i dati lato PHP prima di chiamare Gemini, riducendo il contesto da ~3MB a <10KB.
     */
    public function process_message($message, $intent = 'general', $history = array(), $raw_query = null) {

        if (!class_exists('Marrison_Assistant_Content_Scanner')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-marrison-assistant-content-scanner.php';
        }
        $scanner = new Marrison_Assistant_Content_Scanner();

        // RAG: arricchisce la query con keywords dallo storico per mantenere il contesto
        $rag_query = $this->build_rag_query($raw_query ?? $message, $history);
        $filtered = $scanner->get_context_by_intent($intent, $rag_query);

        // Costruisce un contesto compatto in testo (<10KB)
        $context = $this->build_compact_context($filtered);

        $custom_prompt = get_option(
            'marrison_assistant_custom_prompt',
            'Sei un assistente AI per questo sito. Per prodotti: mantieni la categoria discussa anche se un colore/taglia non è disponibile, proponi alternative nella stessa categoria.'
        );

        $intent_hints = array(
            'products' => 'Domanda su prodotti del negozio.',
            'orders'   => 'Domanda su ordini effettuati.',
            'info'     => 'Domanda informativa sul sito.',
            'events'   => 'Domanda su eventi.',
            'general'  => 'Domanda generale.',
        );
        $hint = isset($intent_hints[$intent]) ? $intent_hints[$intent] : $intent_hints['general'];

        // Storico compatto: max 2 turni, messaggi già troncati lato JS
        $history_text = '';
        if (!empty($history) && is_array($history)) {
            $history_text = "STORICO CONVERSAZIONE (ultimi scambi):\n";
            foreach ($history as $turn) {
                $u = isset($turn['u']) ? sanitize_text_field($turn['u']) : '';
                $b = isset($turn['b']) ? sanitize_text_field($turn['b']) : '';
                if ($u || $b) {
                    $history_text .= "Utente: " . $u . "\nAssistente: " . $b . "\n";
                }
            }
            $history_text .= "---\n";
        }

        $full_prompt =
            $custom_prompt . "\n\n" .
            "CONTESTO (" . $hint . "):\n" . $context . "\n\n" .
            $history_text .
            "REGOLE: rispondi in max 3 frasi, mantieni contesto storico, usa URL esatti [Nome](URL), non inventare URL.\n\n" .
            "DOMANDA: " . $message . "\n\nRispondi in italiano:";

        error_log('Marrison Assistant: prompt size=' . strlen($full_prompt) . ' bytes, intent=' . $intent);
        $this->current_intent = $intent;

        $response = $this->call_commander($full_prompt);

        if ($response) {
            return $response;
        }

        error_log('Marrison Assistant: Commander non disponibile');
        return 'Mi dispiace, il servizio AI non è al momento disponibile. Riprova più tardi.';
    }

    /**
     * Arricchisce la query RAG con i messaggi utente dallo storico.
     * Evita che domande di follow-up (es. "che colori hai?") perdano il contesto del prodotto.
     */
    private function build_rag_query($query, $history) {
        $parts = array($query);
        foreach (array_reverse($history) as $turn) {
            if (!empty($turn['u'])) {
                $parts[] = $turn['u'];
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Costruisce una stringa di contesto compatta dai dati filtrati.
     * Formato pipe-separated per minimizzare i token.
     */
    private function build_compact_context($filtered) {
        $parts = array();

        // Prodotti
        if (!empty($filtered['products'])) {
            $lines = array('[P]');
            foreach ($filtered['products'] as $p) {
                $line = $p['title'] . '|' . $p['url'];
                if (!empty($p['price']))        $line .= '|€' . $p['price'];
                if (!empty($p['stock_status'])) $line .= '|' . ($p['stock_status'] === 'instock' ? '✓' : '✗');
                if (!empty($p['available_options'])) {
                    foreach ($p['available_options'] as $k => $vals) {
                        $val_str = implode(',', (array) $vals);
                        if (strlen($val_str) > 40) $val_str = substr($val_str, 0, 40);
                        $line .= '|' . $k . ':' . $val_str;
                    }
                } else {
                    if (!empty($p['short_description'])) {
                        $line .= '|' . substr(strip_tags($p['short_description']), 0, 60);
                    } elseif (!empty($p['description'])) {
                        $line .= '|' . substr(strip_tags($p['description']), 0, 60);
                    }
                }
                $lines[] = $line;
            }
            // Se abbiamo raggiunto il limite, segnala a Gemini che potrebbero esserci altri prodotti
            if (count($filtered['products']) >= 6) {
                $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
                $note = '[Mostro solo i prodotti più rilevanti. Se ne esistono altri, invita l\'utente a "vedere tutti i prodotti"';
                if ($shop_url) $note .= ': ' . $shop_url;
                $note .= ']';
                $lines[] = $note;
            }
            $parts[] = implode("\n", $lines);
        }

        // Pagine
        if (!empty($filtered['pages'])) {
            $lines = array('[PAGINE]');
            foreach ($filtered['pages'] as $pg) {
                $snippet = substr(strip_tags($pg['content'] ?? ''), 0, 200);
                $lines[] = '"' . $pg['title'] . '" | URL: ' . $pg['url'] . ' | ' . $snippet;
            }
            $parts[] = implode("\n", $lines);
        }

        // Articoli
        if (!empty($filtered['posts'])) {
            $lines = array('[ARTICOLI]');
            foreach ($filtered['posts'] as $po) {
                $snippet = substr(strip_tags($po['content'] ?? ''), 0, 200);
                $lines[] = '"' . $po['title'] . '" | URL: ' . $po['url'] . ' | ' . $snippet;
            }
            $parts[] = implode("\n", $lines);
        }

        // Ordini
        if (!empty($filtered['orders'])) {
            $lines = array('[ORDINI]');
            foreach ($filtered['orders'] as $o) {
                $line = 'Ordine #' . ($o['number'] ?? $o['id'] ?? '');
                $line .= ' | Cliente: ' . ($o['customer_name'] ?? '');
                $line .= ' | Stato: '   . ($o['status_name'] ?? $o['status'] ?? '');
                $line .= ' | Totale: '  . ($o['total'] ?? '') . ' ' . ($o['currency'] ?? '');
                if (!empty($o['items'])) {
                    $names = array_map(function ($i) { return $i['name'] . ' x' . $i['quantity']; }, array_slice($o['items'], 0, 3));
                    $line .= ' | Prodotti: ' . implode(', ', $names);
                }
                if (!empty($o['tracking']['number'])) {
                    $line .= ' | Tracking: ' . $o['tracking']['number'];
                }
                $lines[] = $line;
            }
            $parts[] = implode("\n", $lines);
        }

        // Eventi
        if (!empty($filtered['events'])) {
            $lines = array('[EVENTI]');
            foreach ($filtered['events'] as $ev) {
                $line = '"' . $ev['title'] . '" | URL: ' . $ev['url'];
                if (!empty($ev['start']))   $line .= ' | Data: ' . $ev['start'];
                if (!empty($ev['excerpt'])) $line .= ' | ' . substr(strip_tags($ev['excerpt']), 0, 150);
                $lines[] = $line;
            }
            $parts[] = implode("\n", $lines);
        }

        return empty($parts) ? '[Nessun contenuto pertinente trovato nel database]' : implode("\n\n", $parts);
    }
    
    /**
     * Prova un endpoint Gemini specifico
     */
    private function try_gemini_endpoint($prompt, $api_url) {
        $url = add_query_arg('key', $this->api_key, $api_url);
        
        // Log per debug (rimuovi dopo il test)
        error_log('Marrison Assistant: Testing Gemini URL: ' . $url);
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.5,        // Ridotto per risposte più concise e deterministiche
                'topK' => 20,                // Ridotto per maggiore focus
                'topP' => 0.85,              // Ridotto per risposte più dirette
                'maxOutputTokens' => 512,    // Ridotto per risposte brevi (max ~100-150 parole)
            )
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 60, // Aumentato da 30 a 60 secondi per gestire richieste più lunghe
            'sslverify' => true
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Marrison Assistant: Errore API Gemini - ' . $response->get_error_message());
            error_log('Marrison Assistant: WP Error details: ' . print_r($response, true));
            return false;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Marrison Assistant: Gemini HTTP Code: ' . $http_code);
        error_log('Marrison Assistant: Gemini Response: ' . $body);
        
        if ($http_code !== 200) {
            error_log('Marrison Assistant: Errore HTTP Gemini - ' . $http_code . ' - ' . $body);
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Marrison Assistant: Errore parsing JSON Gemini - ' . json_last_error_msg());
            return false;
        }
        
        // Estrai la risposta dal formato Gemini
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        if (isset($data['error'])) {
            error_log('Marrison Assistant: Errore API Gemini - ' . $data['error']['message']);
            return false;
        }
        
        error_log('Marrison Assistant: Risposta Gemini non valida - ' . $body);
        return false;
    }
    
    /**
     * Costruisce il prompt completo con knowledge base e istruzioni
     */
    public function build_complete_prompt($user_message, $site_knowledge = '') {
        $custom_prompt = get_option('marrison_assistant_custom_prompt', 'Sei un assistente AI per questo sito web. Rispondi in modo professionale e utile basandoti sui contenuti del sito.');
        
        $complete_prompt = "Sei un assistente AI per questo sito web.\n\n";
        
        if (!empty($site_knowledge)) {
            $complete_prompt .= "CONTENUTI DEL SITO:\n" . $site_knowledge . "\n\n";
        }
        
        $complete_prompt .= "ISTRUZIONI ADMIN:\n" . $custom_prompt . "\n\n";
        $complete_prompt .= "UTENTE DICE:\n" . $user_message . "\n\n";
        $complete_prompt .= "Rispondi in modo utile e professionale basandoti sulle informazioni fornite. Se non trovi informazioni rilevanti nei contenuti del sito, rispondi in modo generale ma utile.";
        
        return $complete_prompt;
    }
}
