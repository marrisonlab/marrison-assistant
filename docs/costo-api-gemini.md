# Analisi Costi API Gemini - Marrison Assistant

## Prezzi API Gemini (aggiornati al 2024)

### Gemini 2.5 Flash
- **Input**: $0.15 / 1M token
- **Output**: $0.60 / 1M token
- **Latenza**: Veloce (1-2 secondi)
- **Qualità**: Buona per chat e domande generali

### Gemini 2.5 Pro
- **Input**: $1.25 / 1M token  
- **Output**: $5.00 / 1M token
- **Latenza**: Media (3-5 secondi)
- **Qualità**: Eccellente, più contesto

### Gemini 1.5 Flash
- **Input**: $0.075 / 1M token (testo)
- **Output**: $0.30 / 1M token
- **Input**: $0.10 / 1M token (multimodale)
- **Output**: $0.40 / 1M token

## Scenario di Utilizzo - Sito E-commerce Medio

### Ipotesi:
- **Conversazioni al giorno**: 50 chat
- **Messaggi per conversazione**: 6 (3 utente + 3 bot)
- **Token per messaggio**: 
  - Input: 1,500 token (include knowledge base)
  - Output: 500 token
- **Giorni attivi/mese**: 30
- **Modello**: Gemini 2.5 Flash (rapporto qualità/prezzo)

## Calcolo Mensile

### Volume Token
```
Messaggi al giorno: 50 conversazioni × 6 messaggi = 300 messaggi
Token input/giorno: 300 × 1,500 = 450,000 token
Token output/giorno: 300 × 500 = 150,000 token

Token input/mese: 450,000 × 30 = 13,500,000 token (13.5M)
Token output/mese: 150,000 × 30 = 4,500,000 token (4.5M)
```

### Costo Mensile - Gemini 2.5 Flash
```
Input: 13.5M token × $0.15/M = $2.025
Output: 4.5M token × $0.60/M = $2.70

TOTALE MENSILE: $4.73 (€4.35)
```

### Costo Mensile - Gemini 2.5 Pro
```
Input: 13.5M token × $1.25/M = $16.88
Output: 4.5M token × $5.00/M = $22.50

TOTALE MENSILE: $39.38 (€36.20)
```

## Scenario Ottimizzato (Gemini 1.5 Flash)

### Costo Mensile - Gemini 1.5 Flash
```
Input: 13.5M token × $0.075/M = $1.01
Output: 4.5M token × $0.30/M = $1.35

TOTALE MENSILE: $2.36 (€2.17)
```

## Confronto Modelli

| Modello | Costo/Mese | Qualità | Velocità |
|---------|------------|---------|-----------|
| Gemini 1.5 Flash | $2.36 | Buona | Molto veloce |
| Gemini 2.5 Flash | $4.73 | Ottima | Veloce |
| Gemini 2.5 Pro | $39.38 | Eccellente | Media |

## Ottimizzazione Costi

### 1. Caching Knowledge Base
```
Attuale: Ogni messaggio include tutta la KB (1,500 token)
Ottimizzato: KB in cache, solo domanda (300 token)

Risparmio: ~80% sui token input
Nuovo costo Flash: ~$1.50/mese
```

### 2. Risposte Corte per FAQ
```
Configura risposte brevi per domande frequenti
Token output ridotti da 500 a 200

Risparmio: ~60% sui token output
```

### 3. Modello Ibrido
```
Domande semplici → Gemini 1.5 Flash ($0.075/M)
Domande complesse → Gemini 2.5 Flash ($0.15/M)

Risparmio stimato: ~40%
```

## Proiezione Annuale

### Scenario 1: 50 conversazioni/giorno
| Modello | Mese | Anno |
|---------|------|------|
| 1.5 Flash | $2.36 | $28.32 |
| 2.5 Flash | $4.73 | $56.76 |
| 2.5 Pro | $39.38 | $472.56 |

### Scenario 2: 200 conversazioni/giorno (sito grande)
| Modello | Mese | Anno |
|---------|------|------|
| 1.5 Flash | $9.45 | $113.40 |
| 2.5 Flash | $18.90 | $226.80 |
| 2.5 Pro | $157.50 | $1,890.00 |

### Scenario 3: 10 conversazioni/giorno (sito piccolo)
| Modello | Mese | Anno |
|---------|------|------|
| 1.5 Flash | $0.47 | $5.64 |
| 2.5 Flash | $0.95 | $11.40 |
| 2.5 Pro | $7.88 | $94.56 |

## Budget Consigliato

### Per Avvio (Sito Piccolo)
- **Budget**: $10-20/mese
- **Modello**: Gemini 2.5 Flash
- **Cushion**: 200% rispetto alla stima

### Per Business (Sito Medio)
- **Budget**: $50-100/mese
- **Modello**: Gemini 2.5 Flash (con fallback a 1.5 Flash)
- **Cushion**: 150% rispetto alla stima

### Per Enterprise (Sito Grande)
- **Budget**: $200-500/mese
- **Modello**: Gemini 2.5 Pro (domande complesse) + 2.5 Flash (domande semplici)
- **Cushion**: 120% rispetto alla stima

## Suggerimenti per Ridurre i Costi

1. **Abilita caching** della knowledge base
2. **Limita token output** per risposte brevi
3. **Usa modello più leggero** per FAQ comuni
4. **Implementa rate limiting** per prevenire abusi
5. **Monitora utilizzo** e imposta alert

## Note Importanti

- I prezzi possono cambiare, consulta sempre [Google AI Studio Pricing](https://ai.google.dev/pricing)
- Token = circa 4 caratteri in inglese, 1-2 in italiano
- Knowledge base pesante = più token per messaggio
- Messaggi lunghi dell'utente aumentano i costi input
