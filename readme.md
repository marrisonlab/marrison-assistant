# Marrison Assistant Plugin WordPress

Plugin WordPress per assistente AI con Google Gemini integrato nel sito.

**Versione:** 1.0.1  
**Autore:** [Marrisonlab](https://marrisonlab.com)

## 🎯 Funzionalità

- **Widget Chat** integrato nel frontend del sito
- **Integrazione AI** tramite Google Gemini API
- **Scansione contenuti** del sito (pagine, articoli, prodotti WooCommerce, ordini)
- **Knowledge base** automatica dai contenuti del sito
- **RAG (Retrieval-Augmented Generation)** per risposte contestuali
- **Rate limiting** integrato contro abusi
- **Analytics token** per monitorare consumo API
- **Pannello admin** per configurazione completa
- **Aggiornamenti automatici** da GitHub Releases

## 📋 Requisiti

- WordPress 5.0+
- PHP 7.4+
- Account Google AI Studio per API Gemini
- WooCommerce (opzionale, per prodotti e ordini)

## 🚀 Installazione

1. Copia la cartella `marrison-assistant` in `wp-content/plugins/`
2. Attiva il plugin dalla pagina dei plugin WordPress
3. Configura le impostazioni in `Impostazioni > Marrison Assistant`

## ⚙️ Configurazione

### 1. Google Gemini API

1. Vai su [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Crea una nuova API Key
3. Inserisci la API Key nel campo "Google Gemini API Key"

### 2. Commander API (opzionale)

Per maggiore sicurezza, puoi usare il **Marrison Commander** come proxy per le chiamate Gemini:

1. Configura l'URL del Commander nelle impostazioni
2. Il Commander gestirà le chiamate API al posto del sito direttamente

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
├── marrison-assistant.php              # File principale
├── includes/
│   ├── class-marrison-assistant-admin.php      # Admin base
│   ├── class-marrison-assistant-main-page.php  # Pagina admin principale
│   ├── class-marrison-assistant-api.php        # REST API & AJAX
│   ├── class-marrison-assistant-gemini.php     # Integrazione Gemini
│   ├── class-marrison-assistant-site-agent.php # Widget chat frontend
│   ├── class-marrison-assistant-content-scanner.php  # Scansione contenuti
│   └── class-marrison-assistant-order-scanner.php    # Scansione ordini
├── assets/
│   ├── css/
│   │   ├── admin.css               # Stili admin
│   │   └── site-agent.css          # Stili widget chat
│   └── js/
│       ├── admin.js                # Script admin
│       └── site-agent.js           # Script widget chat
├── docs/
│   └── costo-api-gemini.md         # Documentazione costi
└── readme.md                       # Documentazione
```

## 🔐 Sicurezza

- Sanitizzazione input con funzioni WordPress
- Verifica nonce per operazioni admin e AJAX
- Rate limiting (10 req/min, 80 req/ora per IP)
- SSL verification per chiamate API
- IP hash anonimizzato per rate limiting

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

1. Utente scrive nella chat widget sul sito
2. Il frontend invia richiesta AJAX al backend
3. Il plugin applica rate limiting e validazione
4. Recupera knowledge base del sito tramite RAG
5. Costruisce prompt compatto con contesto e storico
6. Invia a Google Gemini API (o al Commander proxy)
7. Riceve risposta e la mostra nella chat

## 📝 Note

- Plugin pronto per produzione
- Richiede connessione internet funzionante
- I costi API dipendono da Google Gemini
- Consigliato per siti con contenuti strutturati
- Aggiornamenti automatici tramite GitHub Releases

## 🔄 Aggiornamenti

Il plugin supporta aggiornamenti automatici da GitHub. Quando pubblichi una nuova release su `https://github.com/marrisonlab/marrison-assistant/releases/`, WordPress rileverà automaticamente l'aggiornamento disponibile.
