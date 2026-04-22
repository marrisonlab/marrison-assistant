# Changelog Marrison Assistant

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
