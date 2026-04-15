<?php
/**
 * Test webhook per verificare che l'endpoint sia raggiungibile
 */

// Carica WordPress
$wp_load = dirname(__FILE__) . '/../../wp-load.php';
if (file_exists($wp_load)) {
    require_once $wp_load;
} else {
    die("WordPress non trovato");
}

echo "<h2>🔍 Test Webhook Marrison Assistant</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}</style>";

// Test 1: Verifica endpoint REST
echo "<h3>Test 1: Endpoint REST</h3>";

$webhook_url = home_url('/wp-json/wa-ai/v1/incoming');
echo "<div class='info'>URL Webhook: " . esc_html($webhook_url) . "</div>";

// Test GET request
$response = wp_remote_get($webhook_url, array('timeout' => 10));

if (is_wp_error($response)) {
    echo "<div class='error'>❌ Errore GET: " . $response->get_error_message() . "</div>";
} else {
    $http_code = wp_remote_retrieve_response_code($response);
    echo "<div>HTTP Code (GET): $http_code</div>";
    
    if ($http_code === 404) {
        echo "<div class='error'>❌ Endpoint non trovato (404) - REST API non registrata</div>";
        echo "<div>Possibili cause:</div>";
        echo "<ul>";
        echo "<li>Plugin non attivato</li>";
        echo "<li>Permalinks non configurati (Impostazioni > Permalinks > Salva)</li>";
        echo "<li>REST API disabilitata</li>";
        echo "</ul>";
    } elseif ($http_code === 405) {
        echo "<div class='success'>✅ Endpoint trovato (405 = Method Not Allowed, normale per GET)</div>";
    } else {
        echo "<div class='info'>Response: " . esc_html(wp_remote_retrieve_body($response)) . "</div>";
    }
}

// Test 2: Simula webhook Twilio
echo "<h3>Test 2: Simula Webhook Twilio</h3>";

$webhook_data = array(
    'Body' => 'Test messaggio da webhook',
    'From' => 'whatsapp:+393331234567'
);

$response = wp_remote_post($webhook_url, array(
    'method' => 'POST',
    'body' => $webhook_data,
    'timeout' => 30
));

if (is_wp_error($response)) {
    echo "<div class='error'>❌ Errore POST: " . $response->get_error_message() . "</div>";
} else {
    $http_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<div>HTTP Code (POST): $http_code</div>";
    echo "<div>Response: <pre>" . esc_html($body) . "</pre></div>";
    
    if ($http_code === 200) {
        echo "<div class='success'>✅ Webhook funzionante!</div>";
    } else {
        echo "<div class='error'>❌ Errore webhook HTTP $http_code</div>";
    }
}

// Test 3: Verifica impostazioni plugin
echo "<h3>Test 3: Impostazioni Plugin</h3>";

$enable_webhook = get_option('marrison_assistant_enable_webhook');
$twilio_sid = get_option('marrison_assistant_twilio_sid');
$twilio_token = get_option('marrison_assistant_twilio_auth_token');
$twilio_number = get_option('marrison_assistant_twilio_whatsapp_number');

echo "<div>Webhook abilitato: " . ($enable_webhook ? '✅ Sì' : '❌ No') . "</div>";
echo "<div>Twilio SID: " . (!empty($twilio_sid) ? '✅ Configurato' : '❌ Mancante') . "</div>";
echo "<div>Twilio Token: " . (!empty($twilio_token) ? '✅ Configurato' : '❌ Mancante') . "</div>";
echo "<div>Twilio Number: " . (!empty($twilio_number) ? '✅ Configurato' : '❌ Mancante') . "</div>";

if (!$enable_webhook) {
    echo "<div class='error'>❌ Webhook non abilitato! Vai in Impostazioni > Marrison Assistant e spunta 'Abilita Webhook'</div>";
}

// Test 4: Debug log
echo "<h3>Test 4: Debug Log</h3>";

$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $marrison_logs = array();
    
    foreach (explode("\n", $log_content) as $line) {
        if (strpos($line, 'Marrison Assistant') !== false) {
            $marrison_logs[] = $line;
        }
    }
    
    if (!empty($marrison_logs)) {
        echo "<div class='success'>✅ Trovati " . count($marrison_logs) . " log Marrison Assistant:</div>";
        echo "<pre>";
        foreach (array_slice($marrison_logs, -10) as $log) {
            echo esc_html($log) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='info'>ℹ️ Nessun log Marrison Assistant trovato</div>";
    }
} else {
    echo "<div class='info'>ℹ️ File debug.log non esiste</div>";
    echo "<div>Abilita debug in wp-config.php:</div>";
    echo "<pre>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);</pre>";
}

echo "<h3>🔧 Soluzioni Rapide</h3>";
echo "<ol>";
echo "<li>Salva i Permalinks: Impostazioni > Permalinks > Salva</li>";
echo "<li>Attiva webhook: Impostazioni > Marrison Assistant > Spunta 'Abilita Webhook'</li>";
echo "<li>Abilita debug: Aggiungi in wp-config.php le costanti WP_DEBUG</li>";
echo "<li>Verifica URL in Twilio: deve essere esattamente " . esc_html($webhook_url) . "</li>";
echo "</ol>";

?>
