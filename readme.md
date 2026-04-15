# Marrison Assistant Plugin WordPress

Plugin WordPress per assistente AI con integrazione WhatsApp e Google Gemini.

## 🎯 Funzionalità

- **Integrazione WhatsApp** tramite Twilio API
- **Integrazione AI** tramite Google Gemini API
- **Scansione contenuti** del sito (pagine, articoli, prodotti WooCommerce)
- **Knowledge base** automatica dai contenuti del sito
- **Webhook** per ricevere messaggi WhatsApp
- **Pannello admin** per configurazione

## 📋 Requisiti

- WordPress 5.0+
- PHP 7.4+
- Account Twilio con WhatsApp Sandbox
- Account Google AI Studio per API Gemini
- WooCommerce (opzionale, per prodotti)

## 🚀 Installazione

1. Copia la cartella `marrison-assistant` in `wp-content/plugins/`
2. Attiva il plugin dalla pagina dei plugin WordPress
3. Configura le impostazioni in `Impostazioni > Marrison Assistant`

## ⚙️ Configurazione

### 1. Google Gemini API

1. Vai su [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Crea una nuova API Key
3. Inserisci la API Key nel campo "Google Gemini API Key"

### 2. Twilio WhatsApp

1. Crea un account su [Twilio](https://www.twilio.com/)
2. Configura il WhatsApp Sandbox
3. Ottieni Account SID e Auth Token
4. Inserisci i dati nei rispettivi campi
5. Configura il webhook URL in Twilio

### 3. Webhook URL

Il webhook URL da configurare in Twilio è:
```
https://tuosito.com/wp-json/wa-ai/v1/incoming
```

## 🔧 Utilizzo

### Scansione Contenuti

1. Nella pagina admin, clicca su "Scansiona Contenuti Sito"
2. Il plugin analizzerà pagine, articoli e prodotti WooCommerce
3. I contenuti verranno usati come knowledge base per l'AI

### Test Connessioni

1. Usa i pulsanti "Testa Connessione" per verificare API Gemini e Twilio
2. Entrambi i test devono restituire "✓ Connessione riuscita"

### Prompt Personalizzato

Personalizza il comportamento dell'AI modificando il prompt nel campo dedicato. Esempio:

```
Sei un assistente per il nostro e-commerce. 
Sii cordiale, professionale e helpful.
Usa i informazioni sui prodotti per aiutare i clienti.
Se un utente chiede di un prodotto specifico, fornisci prezzo e descrizione.
```

## 📁 Struttura File

```
marrison-assistant/
├── marrison-assistant.php          # File principale
├── includes/
│   ├── class-marrison-assistant-admin.php      # Pagina admin
│   ├── class-marrison-assistant-api.php        # REST API
│   ├── class-marrison-assistant-gemini.php     # Integrazione Gemini
│   ├── class-marrison-assistant-twilio.php     # Integrazione Twilio
│   └── class-marrison-assistant-content-scanner.php  # Scansione contenuti
├── assets/
│   ├── admin.js                    # Script admin
│   └── admin.css                   # Stili admin
└── readme.md                       # Documentazione
```

## 🔐 Sicurezza

- Sanitizzazione input con funzioni WordPress
- Verifica nonce per operazioni admin
- Validazione base webhook Twilio
- SSL verification per chiamate API

## 🐛 Debug

Per abilitare il debug, aggiungi a `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

I log del plugin appariranno in `wp-content/debug.log`.

## 📞 Supporto

Per problemi o domande, controlla i log di WordPress e assicurati che:

1. Le API Keys siano corrette
2. Il webhook URL sia accessibile pubblicamente
3. I permessi del file siano corretti
4. WordPress e PHP siano aggiornati

## 🔄 Flow di Messaggi

1. Utente invia messaggio WhatsApp al numero Twilio
2. Twilio invia webhook al plugin WordPress
3. Plugin recupera knowledge base del sito
4. Costruisce prompt completo con istruzioni admin
5. Invia a Google Gemini API
6. Riceve risposta e la invia a Twilio
7. Twilio consegna messaggio all'utente

## 📝 Note

- Plugin MVP per test e sviluppo
- Richiede connessione internet funzionante
- I costi API dipendono da Twilio e Google
- Consigliato per siti con contenuti strutturati
