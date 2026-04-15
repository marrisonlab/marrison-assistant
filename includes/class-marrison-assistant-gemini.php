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
     * Processa un messaggio e ottiene risposta da Gemini
     */
    public function process_message($message) {
        if (empty($this->api_key)) {
            error_log('Marrison Assistant: API Key Gemini mancante');
            return 'Mi dispiace, non posso rispondere in questo momento. Configura l\'API Key Gemini.';
        }
        
        // Aggiungi knowledge base del sito se disponibile
        $site_content = get_option('marrison_assistant_site_content', array());
        $context = '';
        $relevant_links = '';
        
        if (!empty($site_content)) {
            // Cerca contenuti pertinenti alla domanda
            $relevant_content = $this->search_relevant_content($message, $site_content);
            
            if (!empty($relevant_content)) {
                $relevant_links = "\n\nCONTENUTI PERTINENTI TROVATI:\n";
                foreach ($relevant_content as $item) {
                    $relevant_links .= "\n- [{$item['type']}] **{$item['title']}**\n";
                    $relevant_links .= "  URL: {$item['url']}\n";
                    if (!empty($item['excerpt'])) {
                        $relevant_links .= "  {$item['excerpt']}\n";
                    }
                }
            }
            
            $context .= "\n\nContenuti completi del sito:\n";
            
            // Aggiungi TUTTE le pagine con contenuto completo
            if (!empty($site_content['pages'])) {
                $context .= "\nPAGINE DEL SITO:\n";
                foreach ($site_content['pages'] as $page) {
                    $context .= "\n=== PAGINA: {$page['title']} ===\n";
                    $context .= "URL: {$page['url']}\n";
                    $context .= "Contenuto completo:\n" . strip_tags($page['content']) . "\n";
                    if (!empty($page['excerpt'])) {
                        $context .= "Descrizione: " . strip_tags($page['excerpt']) . "\n";
                    }
                    $context .= "---\n";
                }
            }
            
            // Aggiungi TUTTI gli articoli con contenuto completo
            if (!empty($site_content['posts'])) {
                $context .= "\nARTICOLI DEL BLOG:\n";
                foreach ($site_content['posts'] as $post) {
                    $context .= "\n=== ARTICOLO: {$post['title']} ===\n";
                    $context .= "URL: {$post['url']}\n";
                    $context .= "Data: {$post['date']}\n";
                    $context .= "Contenuto completo:\n" . strip_tags($post['content']) . "\n";
                    if (!empty($post['excerpt'])) {
                        $context .= "Descrizione: " . strip_tags($post['excerpt']) . "\n";
                    }
                    if (!empty($post['categories'])) {
                        $context .= "Categorie: " . implode(', ', $post['categories']) . "\n";
                    }
                    $context .= "---\n";
                }
            }
            
            // Aggiungi TUTTI i prodotti con dettagli completi
            if (!empty($site_content['products'])) {
                $context .= "\nPRODOTTI:\n";
                foreach ($site_content['products'] as $product) {
                    $context .= "\n=== PRODOTTO: {$product['title']} ===\n";
                    $context .= "URL: {$product['url']}\n";
                    $context .= "Prezzo: {$product['price']}\n";
                    $context .= "Descrizione completa:\n" . strip_tags($product['description']) . "\n";
                    if (!empty($product['short_description'])) {
                        $context .= "Breve descrizione: " . strip_tags($product['short_description']) . "\n";
                    }
                    if (!empty($product['categories'])) {
                        $context .= "Categorie: " . implode(', ', $product['categories']) . "\n";
                    }
                    if (!empty($product['attributes'])) {
                        $context .= "Attributi:\n";
                        foreach ($product['attributes'] as $attr_name => $attr_value) {
                            $context .= "- {$attr_name}: {$attr_value}\n";
                        }
                    }
                    $context .= "---\n";
                }
            }
        }
        
        // Costruisci il prompt completo con istruzioni specifiche
        $custom_prompt = get_option('marrison_assistant_custom_prompt', 'Sei un assistente AI esperto per questo sito web.');
        
        $full_prompt = $custom_prompt . "\n\n" . $relevant_links . "\n\n" . $context . "\n\nISTRUZIONI IMPORTANTI:\n" .
            "1. Analizza attentamente i contenuti pertinenti trovati sopra\n" .
            "2. QUANDO menzioni prodotti, articoli o pagine, includi SEMPRE il link completo (URL)\n" .
            "3. Formatta i link così: **Nome Prodotto** - URL completo\n" .
            "4. Se l'utente chiede un prodotto specifico, fornisci il link diretto a quel prodotto\n" .
            "5. Se l'utente chiede un articolo, fornisci il link all'articolo\n" .
            "6. Cita sempre la fonte con il link quando fornisci informazioni\n" .
            "7. Rispondi in modo completo e dettagliato, non interrompere a metà\n\n" .
            "DOMANDA DELL'UTENTE: " . $message . "\n\n" .
            "Rispondi in italiano includendo i link ai contenuti pertinenti.";
        
        // Ottieni risposta da Gemini con timeout più lungo
        $response = $this->send_to_gemini($full_prompt);
        
        if ($response) {
            error_log('Marrison Assistant: Gemini response received successfully');
            return $response;
        } else {
            error_log('Marrison Assistant: Failed to get response from Gemini');
            return 'Mi dispiace, ho avuto problemi a elaborare la tua richiesta. Riprova più tardi.';
        }
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
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 4096, // Aumentato da 1024 a 4096 per risposte più lunghe
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
