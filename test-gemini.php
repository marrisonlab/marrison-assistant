<?php
/**
 * Script di test per Google Gemini API
 * Esegui questo script direttamente per testare la tua API Key
 */

// Inserisci qui la tua API Key di Gemini
$api_key = 'INSERISCI_QUI_LA_TUA_API_KEY';

if ($api_key === 'INSERISCI_QUI_LA_TUA_API_KEY') {
    die("Per favore, inserisci la tua API Key in questo script\n");
}

$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent';

$data = array(
    'contents' => array(
        array(
            'parts' => array(
                array(
                    'text' => 'Rispondi semplicemente con "ok"'
                )
            )
        )
    )
);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $api_url . '?key=' . $api_key);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Testando Google Gemini API...\n";
echo "API Key: " . substr($api_key, 0, 10) . "...\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "ERRORE cURL: $error\n";
    exit(1);
}

echo "HTTP Code: $http_code\n";
echo "Response: $response\n\n";

if ($http_code === 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response = $data['candidates'][0]['content']['parts'][0]['text'];
        echo "✅ SUCCESSO! Risposta AI: $ai_response\n";
        
        if (strpos(strtolower($ai_response), 'ok') !== false) {
            echo "✅ Test passato: L'AI ha risposto correttamente\n";
        } else {
            echo "⚠️  Attenzione: Risposta non attesa\n";
        }
    } else {
        echo "❌ Errore: Formato risposta non valido\n";
    }
} else {
    echo "❌ Errore HTTP $http_code\n";
    
    $data = json_decode($response, true);
    if (isset($data['error']['message'])) {
        echo "Errore API: " . $data['error']['message'] . "\n";
    }
    
    echo "\nPossibili soluzioni:\n";
    echo "- Verifica che l'API Key sia corretta\n";
    echo "- Verifica che l'API Gemini sia abilitata nel tuo progetto Google\n";
    echo "- Controlla la quota API disponibile\n";
    echo "- Verifica la connessione internet\n";
}
?>
