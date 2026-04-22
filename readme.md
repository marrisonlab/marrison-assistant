# Marrison Assistant Plugin WordPress

Plugin WordPress per assistente AI con Google Gemini integrato nel sito.

**Versione:** 1.3.1  
**Autore:** [Marrisonlab](https://marrisonlab.com)

## 📝 Changelog

### v1.3.1 (2025-04-22)
- **Fix:** Conflitto plugin Marrison - rimosso controllo classe troppo restrittivo
- **Fix:** Compatibilità ripristinata con altri plugin Marrison (Commander, etc.)
- **Fix:** Pannello admin ripristinato e caricamento corretto
- **Fix:** Debug migliorato per tracciare problemi di caricamento

### v1.3.0 (2025-04-22) - Major Release
- **Nuovo:** Supporto completo Custom Post Types (CPT) e Custom Taxonomies (CCT)
- **Nuovo:** Estrazione automatica meta campi e featured images per CPT
- **Nuovo:** Contesto CPT integrato nelle ricerche generali dell'AI
- **Nuovo:** Link cliccabili telefono e WhatsApp con styling differenziato
- **Nuovo:** Risposte mirate per contatti - numeri telefono e email diretti
- **Nuovo:** Sistema debug avanzato per tracciare flusso aggiornamenti
- **Nuovo:** Fix automatico cartelle GitHub con nomi casuali
- **Nuovo:** Controllo globale anti-caricamento multiplo
- **Migliorato:** Keyword extraction accetta numeri (es. taglie "45")
- **Migliorato:** Link prodotti sempre inclusi con [Nome](URL)
- **Migliorato:** Inviti registrazione solo su siti con WooCommerce
- **Fix:** Risposte mirate contatti e link target behavior
- **Fix:** Processo aggiornamenti GitHub e download URL issues

### v1.2.4 (2025-04-22)
- **Fix:** Debug avanzato per tracciare flusso aggiornamenti
- **Fix:** Hook package options per identificare problemi download
- **Fix:** Logging completo per verificare dove il download URL diventa nullo

### v1.2.3 (2025-04-22)
- **Fix:** Download URL nullo - errore aggiornamento ZipArchive filename vuoto
- **Fix:** Sistema gerarchico URL con priorità zipball_url > assets.zip > URL costruito
- **Fix:** Logging migliorato e validazione assets per release GitHub

### v1.2.2 (2025-04-22)
- **Nuovo:** Link cliccabili telefono e WhatsApp con styling differenziato
- **Fix:** Caricamento multiplo plugin con controllo globale

### v1.2.1 (2025-04-22)
- **Fix:** Risolto problema aggiornamenti GitHub con cartelle a nome casuale
- **Fix:** WordPress ora riconosce correttamente il plugin dopo aggiornamento
- **Fix:** Aggiunto sistema automatico per rinominare cartelle GitHub

### v1.2.0 (2025-04-22)
- **Nuovo:** Scansione completa Custom Post Types (CPT) e Custom Taxonomies (CCT)
- **Nuovo:** Estrazione automatica meta campi e featured images per CPT
- **Nuovo:** Contesto CPT integrato nelle ricerche generali dell'AI
- **Migliorato:** Risposte mirate per contatti - numeri telefono e email diretti
- **Migliorato:** Keyword extraction accetta numeri (es. taglie "45")
- **Migliorato:** Link prodotti sempre inclusi con [Nome](URL)
- **Fix:** Rimossi target="_blank" - link aprono nella stessa finestra
- **Fix:** Inviti registrazione solo su siti con WooCommerce
- **Fix:** Corretta fraseologia "sito negozio" in "negozio"

### v1.1.0 (2025-04-22)
- **UI/UX:** Pannello di controllo unico senza tab — interfaccia moderna a card
- **UI/UX:** Header con badge di stato in tempo reale (Servizio AI, API, Widget)
- **Privacy:** Rimossi tutti i riferimenti visibili a Commander URL e Gemini AI
- **Nuovo:** Toggle "Abilita prompt personalizzato" con alert consumo token
- **Nuovo:** Cron WP per scansione automatica contenuti ogni 24 ore
- **Nuovo:** Prima scansione automatica all'attivazione del plugin
- **Nuovo:** File `uninstall.php` — pulizia completa dati all'eliminazione
- **Migliorato:** LED API Connections mostra stato reale raggiungibilità servizio
- **Fix:** Icona dashicons corretta su pulsante scansione
- **Fix:** Rimosso pulsante Debug Eventi e anteprima widget
- **Fix:** Commander URL hardcoded (non più configurabile)
- **Fix:** Validazione chiave API con regex AIza...

### v1.0.2 (2025-04-21)
- **Migliorato:** Scansione contenuti con supporto Elementor e fallback meta
- **Migliorato:** Intent "info" ora include sempre le pagine del sito (fallback_all)
- **Migliorato:** Aumentato snippet contesto pagine da 200 a 400 caratteri
- **Fix:** Pulsanti routing nascosti quando c'è solo "Info" (no ecommerce)
- **Fix:** Messaggio tip login nascosto su siti senza WooCommerce
- **Fix:** Pulsante "Ordini" visibile solo con WooCommerce attivo
- **Nuovo:** Dashboard Commander con filtri per sito nei contatori
- **Nuovo:** Pulsante Reset log con conferma modale in Commander
- **Nuovo:** Supporto white-label tramite `white-label.json`

### v1.0.1 (2025-04)
- Fix token analytics dashboard filtering
- Migrazione log token su Commander
- Aggiunto calcolo costi API Gemini

### v1.0.0 (2025-04)
- Release iniziale

## 🎯 Funzionalità

- **Widget Chat** integrato nel frontend del sito
- **Integrazione AI** tramite Google Gemini API
- **Scansione contenuti** del sito (pagine, articoli, prodotti WooCommerce, ordini, CPT, eventi)
- **Knowledge base** automatica da tutti i contenuti del sito inclusi Custom Post Types
- **Scansione CPT/CCT**: Supporto completo per Custom Post Types e tassonomie personalizzate
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
2. Il plugin analizzerà pagine, articoli, prodotti WooCommerce, ordini, eventi e tutti i Custom Post Types
3. I contenuti verranno usati come knowledge base per l'AI
4. I CPT e le loro tassonomie vengono inclusi automaticamente nelle ricerche

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
