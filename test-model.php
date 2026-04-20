<?php
// Test rapido modelli Gemini
require_once 'marrison-assistant.php';

$api_key = get_option('marrison_assistant_gemini_api_key');

if (empty($api_key)) {
    die("API Key non trovata\n");
}

echo "Testing modelli Gemini...\n\n";

$models = ['gemini-1.5-flash', 'gemini-2.5-flash', 'gemini-2.5-pro'];

foreach ($models as $model) {
    $url = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent?key=' . $api_key;
    
    $data = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Rispondi solo "OK"']
                ]
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($http_code == 200) {
        echo "✅ $model FUNZIONA\n";
        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            echo "   Risposta: " . trim($data['candidates'][0]['content']['parts'][0]['text']) . "\n";
        }
    } else {
        echo "❌ $model ERRORE: HTTP $http_code\n";
        $error = json_decode($response, true);
        if (isset($error['error']['message'])) {
            echo "   Messaggio: " . $error['error']['message'] . "\n";
        }
    }
    
    curl_close($ch);
    echo "\n";
}
