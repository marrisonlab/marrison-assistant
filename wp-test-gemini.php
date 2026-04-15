<?php
/**
 * Test Gemini che simula esattamente la chiamata WordPress
 * Carica questo file nel tuo sito WordPress: /wp-content/plugins/wp-test-gemini.php
 * Poi accedi a: https://tuosito.com/wp-content/plugins/wp-test-gemini.php
 */

// Carica WordPress
$wp_load = dirname(__FILE__) . '/../../wp-load.php';
if (file_exists($wp_load)) {
    require_once $wp_load;
} else {
    die("WordPress non trovato. Carica questo file nella cartella plugins.");
}

// Verifica che siamo in admin
if (!current_user_can('manage_options')) {
    wp_die('Permessi insufficienti. Fai login come admin.');
}

// Ottieni API Key dalle impostazioni
$api_key = get_option('marrison_assistant_gemini_api_key');

echo "<h2>🔍 Test Gemini API (WordPress)</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.error{color:red;}.success{color:green;}.code{background:#f5f5f5;padding:10px;margin:10px 0;font-family:monospace;}</style>";

if (empty($api_key)) {
    echo "<div class='error'>❌ API Key non configurata in WordPress</div>";
    echo "<p>Vai in <strong>Impostazioni > Marrison Assistant</strong> e inserisci l'API Key</p>";
    exit;
}

echo "<div class='success'>✅ API Key trovata: " . substr($api_key, 0, 15) . "...</div>";
echo "<div>Lunghezza: " . strlen($api_key) . " caratteri</div>";

// Test esatto come nel plugin
echo "<h3>Test 1: Connessione WordPress wp_remote_post</h3>";

$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
$url = add_query_arg('key', $api_key, $api_url);

$body = array(
    'contents' => array(
        array(
            'parts' => array(
                array(
                    'text' => 'Rispondi semplicemente con "ok"'
                )
            )
        )
    ),
    'generationConfig' => array(
        'temperature' => 0.7,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 1024,
    )
);

$args = array(
    'method' => 'POST',
    'headers' => array(
        'Content-Type' => 'application/json',
    ),
    'body' => json_encode($body),
    'timeout' => 30,
    'sslverify' => true
);

echo "<div class='code'>URL: " . esc_html($url) . "</div>";

$response = wp_remote_post($url, $args);

if (is_wp_error($response)) {
    echo "<div class='error'>❌ Errore WordPress: " . $response->get_error_message() . "</div>";
    echo "<div class='code'>Dettagli errore: <pre>" . print_r($response, true) . "</pre></div>";
    
    echo "<h3>🔧 Possibili soluzioni:</h3>";
    echo "<ul>";
    echo "<li>Verifica che il server permetta connessioni esterne (firewall)</li>";
    echo "<li>Controlla che PHP curl sia abilitato</li>";
    echo "<li>Verifica certificati SSL del server</li>";
    echo "<li>Prova a disabilitare temporaneamente sslverify per test</li>";
    echo "</ul>";
    
    // Test senza SSL verify
    echo "<h3>Test senza SSL verify:</h3>";
    $args_no_ssl = $args;
    $args_no_ssl['sslverify'] = false;
    
    $response_no_ssl = wp_remote_post($url, $args_no_ssl);
    
    if (is_wp_error($response_no_ssl)) {
        echo "<div class='error'>❌ Anche senza SSL verify: " . $response_no_ssl->get_error_message() . "</div>";
    } else {
        echo "<div class='success'>✅ Funziona senza SSL verify - problema certificati SSL</div>";
    }
    
} else {
    $http_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<div>HTTP Code: $http_code</div>";
    echo "<div class='code'>Response: " . esc_html($body) . "</div>";
    
    if ($http_code === 200) {
        $data = json_decode($body, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $data['candidates'][0]['content']['parts'][0]['text'];
            echo "<div class='success'>✅ Risposta AI: " . esc_html($ai_response) . "</div>";
            
            if (strpos(strtolower($ai_response), 'ok') !== false) {
                echo "<div class='success'>🎉 TEST COMPLETATO CON SUCCESSO!</div>";
                echo "<p>L'API funziona correttamente. Il problema potrebbe essere nel plugin stesso.</p>";
            }
        }
    } else {
        echo "<div class='error'>❌ Errore HTTP $http_code</div>";
        
        if ($http_code === 403) {
            echo "<div class='error'>API Key non valida o Gemini non abilitata</div>";
            echo "<p>Controlla su <a href='https://aistudio.google.com/app/apikey' target='_blank'>Google AI Studio</a></p>";
        } elseif ($http_code === 429) {
            echo "<div class='error'>Quota API esaurita</div>";
        }
    }
}

echo "<h3>📋 Informazioni Server</h3>";
echo "<div>PHP Version: " . PHP_VERSION . "</div>";
echo "<div>cURL enabled: " . (extension_loaded('curl') ? 'Yes' : 'No') . "</div>";
echo "<div>allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "</div>";

if (extension_loaded('curl')) {
    $curl_version = curl_version();
    echo "<div>cURL Version: " . $curl_version['version'] . "</div>";
}

echo "<h3>🔍 Debug Log WordPress</h3>";
echo "<p>Controlla il file <code>wp-content/debug.log</code> per messaggi 'Marrison Assistant'</p>";

echo "<p><a href='" . admin_url('options-general.php?page=marrison-assistant') . "'>← Torna alle impostazioni Marrison Assistant</a></p>";

?>
