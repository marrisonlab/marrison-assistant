<?php
/**
 * Test rapido per identificare il problema Gemini
 * Esegui questo script direttamente nel browser o CLI
 */

// Inserisci la tua API Key qui
$api_key = ''; // Lascia vuoto per leggerla da WordPress se disponibile

// Se siamo in ambiente WordPress, prova a leggere l'API Key
if (function_exists('get_option') && empty($api_key)) {
    $api_key = get_option('marrison_assistant_gemini_api_key');
}

echo "=== TEST RAPIDO GEMINI API ===\n\n";

if (empty($api_key)) {
    echo "❌ ERRORE: API Key non fornita\n";
    echo "Modifica questo script e inserisci la tua API Key\n";
    echo "Oppure assicurati che sia salvata in WordPress\n\n";
    
    echo "Formato API Key: AIzaSyCxxxxxxxxxxxxxxxxxxxxxxx\n";
    echo "Ottienila da: https://aistudio.google.com/app/apikey\n";
    exit(1);
}

echo "✅ API Key: " . substr($api_key, 0, 15) . "...\n";
echo "Lunghezza: " . strlen($api_key) . " caratteri\n\n";

// Test 1: Verifica formato
if (!preg_match('/^AIza[A-Za-z0-9_-]{35}$/', $api_key)) {
    echo "⚠️  ATTENZIONE: L'API Key non sembra avere il formato corretto\n";
    echo "Dovrebbe iniziare con 'AIza' e avere 39 caratteri totali\n\n";
}

// Test 2: Connessione base
echo "Test 1: Connessione base a Google AI...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "❌ ERRORE cURL: $error\n\n";
    echo "Possibili cause:\n";
    echo "- Internet non funzionante\n";
    echo "- Firewall che blocca le chiamate\n";
    echo "- cURL non abilitato\n";
    exit(1);
}

echo "HTTP Code: $http_code\n";

if ($http_code === 200) {
    echo "✅ Connessione base riuscita\n";
    
    $data = json_decode($response, true);
    if (isset($data['models'])) {
        echo "Modelli trovati: " . count($data['models']) . "\n";
        foreach ($data['models'] as $model) {
            if (strpos($model['name'], 'gemini') !== false) {
                echo "- " . basename($model['name']) . "\n";
            }
        }
    }
} elseif ($http_code === 403) {
    echo "❌ ERRORE 403: Accesso negato\n";
    echo "Response: $response\n\n";
    echo "Soluzioni:\n";
    echo "- Verifica che l'API Key sia corretta\n";
    echo "- Assicurati che Gemini API sia abilitata nel progetto Google\n";
    echo "- Controlla la billing configuration\n";
} elseif ($http_code === 429) {
    echo "❌ ERRORE 429: Troppe richieste\n";
    echo "Quota API probabilmente esaurita\n";
} else {
    echo "❌ ERRORE HTTP $http_code\n";
    echo "Response: $response\n";
}

echo "\n";

// Test 3: Test generazione
echo "Test 2: Test generazione con gemini-1.5-flash...\n";

$generate_data = json_encode(array(
    'contents' => array(
        array(
            'parts' => array(
                array('text' => 'Rispondi solo con "ok"')
            )
        )
    )
));

$generate_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $api_key;

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $generate_url);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $generate_data);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

$gen_response = curl_exec($ch2);
$gen_http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$gen_error = curl_error($ch2);

curl_close($ch2);

if ($gen_error) {
    echo "❌ ERRORE generazione: $gen_error\n";
} else {
    echo "HTTP Code: $gen_http_code\n";
    
    if ($gen_http_code === 200) {
        $gen_data = json_decode($gen_response, true);
        if (isset($gen_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $gen_data['candidates'][0]['content']['parts'][0]['text'];
            echo "✅ Risposta AI: '$ai_response'\n";
            
            if (strpos(strtolower($ai_response), 'ok') !== false) {
                echo "🎉 TEST COMPLETATO CON SUCCESSO!\n";
                echo "L'API Key funziona correttamente\n";
            }
        }
    } else {
        echo "❌ Errore generazione: $gen_response\n";
        
        // Prova con il vecchio endpoint
        echo "\nTentativo con vecchio endpoint gemini-pro...\n";
        
        $ch3 = curl_init();
        curl_setopt($ch3, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key);
        curl_setopt($ch3, CURLOPT_POST, true);
        curl_setopt($ch3, CURLOPT_POSTFIELDS, $generate_data);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch3, CURLOPT_TIMEOUT, 30);

        $old_response = curl_exec($ch3);
        $old_http_code = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
        $old_error = curl_error($ch3);

        curl_close($ch3);

        if (!$old_error && $old_http_code === 200) {
            echo "✅ Vecchio endpoint funziona!\n";
            $old_data = json_decode($old_response, true);
            if (isset($old_data['candidates'][0]['content']['parts'][0]['text'])) {
                echo "Risposta: " . $old_data['candidates'][0]['content']['parts'][0]['text'] . "\n";
            }
        } else {
            echo "❌ Anche vecchio endpoint fallisce\n";
        }
    }
}

echo "\n=== RIEPILOGO ===\n";
echo "Se vedi 'TEST COMPLETATO CON SUCCESSO', l'API Key è valida\n";
echo "Altrimenti, controlla i messaggi di errore sopra\n";
echo "Per problemi di 403, verifica il progetto Google AI Studio\n";

?>
