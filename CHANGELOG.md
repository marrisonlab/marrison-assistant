# Changelog Marrison Assistant

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.4] - 2026-04-23

### Fixed
- **Updater GitHub (WordPress core)**: Iniezione update via `site_transient_update_plugins` + popolamento `no_update` per stabilità UI/auto-update
- **Download package**: Preferenza per URL `github.com/.../archive/refs/tags/...zip` per evitare zipball API e errori ZipArchive

## [1.3.4.1] - 2026-04-23

### Fixed
- **Download package (fallback)**: Download “stream” in file temporaneo via `upgrader_pre_download` per evitare `ZipArchive::open()` con filename vuoto quando WP non riesce a scaricare lo zip
- **URL download**: Preferenza per `codeload.github.com/.../zip/refs/tags/...` (meno redirect, più affidabile lato WP_Upgrader)

## [1.3.3] - 2026-04-23

### Fixed
- **Aggiornamento da GitHub**: Evitato fatal error durante l’upgrade (firma hook `upgrader_package_options` ora compatibile con WordPress)
- **Maintenance mode**: L’upgrade non si interrompe più, quindi il file `.maintenance` viene rimosso correttamente a fine aggiornamento

## [1.3.1] - 2025-04-22

### Fixed
- **Conflitto plugin Marrison**: Rimosso controllo classe troppo restrittivo
- **Compatibilità ripristinata**: Ora convive con altri plugin Marrison (Commander, etc.)
- **Pannello admin**: Ripristinato caricamento corretto del pannello amministrazione
- **Debug migliorato**: Logging più specifico per tracciare problemi di caricamento

## [1.3.0] - 2025-04-22

### Major Release - Complete Feature Set

### Added
- **Custom Post Types (CPT) Support**: Scansione completa di tutti i CPT pubblici
- **Custom Taxonomies (CCT) Support**: Include categorie e tag personalizzati
- **Meta & Featured Images**: Estrazione automatica campi personalizzati e immagini in evidenza
- **CPT Context Integration**: I CPT sono inclusi nelle ricerche generali dell'AI
- **Clickable Phone Links**: Numeri telefono automaticamente convertiti in link `tel:+39XXXXX`
- **WhatsApp Integration**: Pulsante WhatsApp accanto ai numeri di telefono
- **Smart Contact Responses**: Regola specifica per fornire direttamente contatti quando disponibili
- **Advanced Debug System**: Logging completo per tracciare flusso aggiornamenti
- **GitHub Update Fix**: Sistema automatico per rinominare cartelle GitHub con nomi casuali
- **Anti-Duplicate Loading**: Controllo globale per prevenire conflitti tra copie del plugin

### Improved
- **Keyword Extraction**: Accetta numeri (es. taglie "45") di 2+ caratteri
- **Product Links**: Sempre inclusi con [Nome](URL) nelle risposte
- **Registration Prompts**: Mostrati solo su siti con WooCommerce
- **Update URL Priority**: Sistema gerarchico zipball_url > assets.zip > URL costruito

### Fixed
- **Targeted Contact Responses**: L'AI ora risponde correttamente a domande su contatti
- **Link Target Behavior**: Rimossi target="_blank" - link aprono nella stessa finestra
- **GitHub Update Process**: Risolto problema cartelle con nomi casuali durante aggiornamento
- **Download URL Issues**: Fix per ZipArchive filename vuoto
- **Plugin Loading**: Prevenzione caricamento multiplo con controllo globale

## [1.2.4] - 2025-04-22

### Added
- **Debug avanzato**: Logging dettagliato per tracciare flusso aggiornamenti
- **Hook package options**: Intercettazione download package per identificare problemi
- **Tracciamento URL**: Log completi per verificare download URL e opzioni

### Fixed
- **Debug migliorato**: Sistema completo per identificare dove il download URL diventa nullo

## [1.2.3] - 2025-04-22

### Fixed
- **Download URL nullo**: Risolto errore aggiornamento con ZipArchive filename vuoto
- **Sistema gerarchico URL**: Priorità zipball_url > assets.zip > URL costruito
- **Logging migliorato**: Tracciamento URL di download per debug
- **Validazione assets**: Controllo robusto per file .zip nelle release GitHub

## [1.2.2] - 2025-04-22

### Added
- **Link cliccabili telefono**: Numeri di telefono automaticamente convertiti in link `tel:+39XXXXX`
- **Link cliccabili WhatsApp**: Pulsante WhatsApp accanto ai numeri di telefono
- **Styling link**: Colori differenziati per telefono (blu) e WhatsApp (verde)
- **Pattern riconoscimento**: Supporto vari formati numeri italiani (+39, 0xxx, con/ senza spazi)

### Fixed
- **Caricamento multiplo**: Controllo globale per prevenire conflitti tra copie del plugin

## [1.2.1] - 2025-04-22

### Fixed
- **Aggiornamenti GitHub**: Risolto problema cartelle con nomi casuali durante aggiornamento
- **Riconoscimento plugin**: WordPress ora riconosce correttamente il plugin dopo aggiornamento da GitHub
- **Hook aggiornamenti**: Aggiunto sistema automatico per rinominare cartelle GitHub al nome standard

## [1.2.0] - 2025-04-22

### Added
- **Scansione Custom Post Types (CPT)**: Supporto completo per tutti i CPT pubblici
- **Scansione Custom Taxonomies (CCT)**: Include categorie e tag personalizzati
- **Meta e Featured Images**: Estrazione automatica dei campi personalizzati e immagini in evidenza per i CPT
- **Contesto CPT nell'AI**: I CPT sono ora inclusi nelle ricerche generali dell'assistente
- **Regola specifica per contatti**: L'AI ora fornisce direttamente numeri di telefono e email quando disponibili

### Fixed
- **Risposte mirate per contatti**: L'AI ora risponde correttamente alle domande su numeri di telefono e contatti
- **Keyword extraction**: Corretto per accettare numeri (es. taglie "45") di 2+ caratteri
- **Link prodotti**: Aggiunta regola per includere sempre il link quando si menziona un prodotto
- **Link target**: Rimossi `target="_blank"` per aprire link nella stessa finestra
- **Inviti registrazione**: Mostrati solo su siti con WooCommerce, non su siti generici

### Improved
- **Pulizia prompt**: Corretta fraseologia "sito negozio" in "negozio"
- **Messaggio benvenuto**: Ottimizzato per utenti non loggati
- **Scansione eventi**: Supporto confermato per The Events Calendar, Modern Events Calendar, FooEvents

## [1.1.0] - 2025-03-XX

### Added
- **Supporto WooCommerce**: Scansione prodotti, varianti, attributi
- **Gestione ordini**: Tracking e stato ordini per utenti loggati
- **Scansione eventi**: Supporto per plugin eventi principali
- **Sistema white label**: Personalizzazione completa brand
- **Dashboard analytics**: Monitoraggio utilizzo e token

### Fixed
- **Performance ottimizzazione**: Scansione più veloce e efficiente
- **Compatibilità**: Testato con WordPress 6.4+
- **Sicurezza**: Validazione input migliorata

## [1.0.0] - 2025-02-XX

### Added
- **Release iniziale**: Assistente AI per WordPress
- **Integrazione Gemini**: Connessione con Google Gemini AI
- **Scansione contenuti**: Pagine, post e informazioni sito
- **Chat widget**: Interfaccia utente responsive
- **Pannello admin**: Configurazione completa delle funzionalità
