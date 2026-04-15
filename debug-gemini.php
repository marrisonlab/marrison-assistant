<?php
/**
 * Script di debug semplificato per Google Gemini API
 */

// Imposta debug WordPress
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Simula ambiente WordPress
if (!function_exists('wp_remote_post')) {
    echo "❌ Questo script deve essere eseguito in un ambiente WordPress\n";
    echo "Oppure usa test-gemini.php per test diretto\n";
    exit(1);
}

// Test API Key
$api_key = get_option('marrison_assistant_gemini_api_key');

echo "=== DEBUG GEMINI API ===\n\n";

if (empty($api_key)) {
    echo "❌ API Key non trovata nelle impostazioni\n";
    echo "Vai in Impostazioni > Marrison Assistant e inserisci l'API Key\n";
    exit(1);
}

echo "✅ API Key trovata: " . substr($api_key, 0, 10) . "...\n";
echo "Lunghezza: " . strlen($api_key) . " caratteri\n\n";

if (strlen($api_key) < 20) {
    echo "❌ API Key troppo corta (minimo 20 caratteri)\n";
    exit(1);
}

// Test connessione base
echo "Test connessione di base...\n";

$test_url = 'https://generativelanguage.googleapis.com/v1beta/models';
$url_with_key = $test_url . '?key=' . $api_key;

$response = wp_remote_get($url_with_key, array(
    'timeout' => 10,
    'sslverify' => true
));

if (is_wp_error($response)) {
    echo "❌ Errore connessione: " . $response->get_error_message() . "\n";
    echo "\nPossibili cause:\n";
    echo "- Firewall che blocca le chiamate esterne\n";
    echo "- Problemi SSL/certificati\n";
    echo "- Connessione internet non funzionante\n";
    exit(1);
}

$http_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "HTTP Code: $http_code\n";

if ($http_code === 200) {
    echo "✅ Connessione base riuscita\n";
    
    $data = json_decode($body, true);
    if (isset($data['models'])) {
        echo "Modelli disponibili:\n";
        foreach ($data['models'] as $model) {
            echo "- " . $model['name'] . "\n";
        }
    }
} else {
    echo "❌ Errore HTTP $http_code\n";
    echo "Response: $body\n";
    
    if ($http_code === 403) {
        echo "\n❌ Accesso negato - Possibili cause:\n";
        echo "- API Key non valida\n";
        echo "- Gemini API non abilitata nel progetto Google\n";
        echo "- Quota API esaurita\n";
    }
}

echo "\n=== TEST GENERAZIONE ===\n";

// Test generazione con nuovo endpoint
$generate_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
$test_data = array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Rispondi con "ok"')
            )
        )
    )
);

$generate_response = wp_remote_post($generate_url . '?key=' . $api_key, array(
    'method' => 'POST',
    'headers' => array('Content-Type' => 'application/json'),
    'body' => json_encode($test_data),
    'timeout' => 30,
    'sslverify' => true
));

if (is_wp_error($generate_response)) {
    echo "❌ Errore generazione: " . $generate_response->get_error_message() . "\n";
} else {
    $gen_http_code = wp_remote_retrieve_response_code($generate_response);
    $gen_body = wp_remote_retrieve_body($generate_response);
    
    echo "HTTP Code: $gen_http_code\n";
    
    if ($gen_http_code === 200) {
        $gen_data = json_decode($gen_body, true);
        if (isset($gen_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $gen_data['candidates'][0]['content']['parts'][0]['text'];
            echo "✅ Risposta AI: $ai_response\n";
        }
    } else {
        echo "❌ Errore generazione: $gen_body\n";
    }
}

echo "\n=== LOG WORDPRESS ===\n";
echo "Controlla wp-content/debug.log per messaggi dettagliati\n";
echo "Cerca 'Marrison Assistant' nei log\n";

?>
