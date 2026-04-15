<?php
/**
 * Classe per l'autenticazione utenti WhatsApp
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Auth {
    
    /**
     * Verifica se un numero WhatsApp è autenticato
     */
    public function is_whatsapp_authenticated($phone_number) {
        // Rimuovi prefisso whatsapp: se presente
        $clean_phone = str_replace('whatsapp:', '', $phone_number);
        
        // Cerca utente WordPress con questo numero
        $user = $this->find_user_by_phone($clean_phone);
        
        if (!$user) {
            return false;
        }
        
        // Verifica che l'utente sia attivo
        if ($user->user_status !== 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Trova utente WordPress per numero di telefono
     */
    private function find_user_by_phone($phone_number) {
        // Normalizza numero di telefono
        $normalized_phone = $this->normalize_phone($phone_number);
        
        // Cerca in meta fields utente
        $users = get_users(array(
            'meta_key' => 'billing_phone',
            'meta_value' => $normalized_phone,
            'number' => 1
        ));
        
        if (!empty($users)) {
            return $users[0];
        }
        
        // Prova altri campi telefono comuni
        $phone_fields = array('phone', 'mobile_phone', 'whatsapp_phone');
        
        foreach ($phone_fields as $field) {
            $users = get_users(array(
                'meta_key' => $field,
                'meta_value' => $normalized_phone,
                'number' => 1
            ));
            
            if (!empty($users)) {
                return $users[0];
            }
        }
        
        return false;
    }
    
    /**
     * Normalizza numero di telefono per confronto
     */
    private function normalize_phone($phone) {
        // Rimuovi tutti i caratteri non numerici
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        
        // Rimuovi prefissi internazionali comuni se presenti
        $prefixes = array('39', '1', '44', '33', '49');
        foreach ($prefixes as $prefix) {
            if (strpos($normalized, $prefix) === 0 && strlen($normalized) > 10) {
                $normalized = substr($normalized, strlen($prefix));
                break;
            }
        }
        
        return $normalized;
    }
    
    /**
     * Genera codice di autenticazione
     */
    public function generate_auth_code() {
        return sprintf('%06d', mt_rand(100000, 999999));
    }
    
    /**
     * Invia codice di autenticazione via WhatsApp
     */
    public function send_auth_code($phone_number, $code) {
        $twilio = new Marrison_Assistant_Twilio();
        
        $message = "Il tuo codice di autenticazione per Marrison Assistant è: *{$code}*\n\n";
        $message .= "Questo codice scadrà tra 10 minuti.";
        
        // Rimuovi prefisso whatsapp: se presente
        $clean_phone = str_replace('whatsapp:', '', $phone_number);
        
        return $twilio->send_whatsapp_message($clean_phone, $message);
    }
    
    /**
     * Verifica codice di autenticazione
     */
    public function verify_auth_code($phone_number, $code) {
        $stored_code = get_transient('marrison_auth_' . md5($phone_number));
        
        if (!$stored_code || $stored_code !== $code) {
            return false;
        }
        
        // Codice valido, rimuovi transient
        delete_transient('marrison_auth_' . md5($phone_number));
        
        return true;
    }
    
    /**
     * Salva codice di autenticazione
     */
    public function save_auth_code($phone_number, $code) {
        // Salva per 10 minuti (600 secondi)
        set_transient('marrison_auth_' . md5($phone_number), $code, 600);
    }
    
    /**
     * Associa numero WhatsApp a utente WordPress
     */
    public function link_whatsapp_to_user($user_id, $phone_number) {
        $clean_phone = str_replace('whatsapp:', '', $phone_number);
        
        // Salva in user meta
        update_user_meta($user_id, 'whatsapp_number', $clean_phone);
        
        // Log dell'associazione
        error_log("Marrison Assistant: WhatsApp {$clean_phone} associato all'utente {$user_id}");
        
        return true;
    }
    
    /**
     * Processa richiesta di autenticazione
     */
    public function process_auth_request($phone_number, $message) {
        $message = strtolower(trim($message));
        
        // Se il messaggio è una richiesta di autenticazione
        if (in_array($message, array('login', 'accedi', 'autentica', 'authenticate'))) {
            $user = $this->find_user_by_phone($phone_number);
            
            if (!$user) {
                return "Non troviamo un account associato a questo numero WhatsApp. Per favore, contatta l'assistenza.";
            }
            
            // Genera e invia codice
            $code = $this->generate_auth_code();
            $this->save_auth_code($phone_number, $code);
            
            if ($this->send_auth_code($phone_number, $code)) {
                return "Codice di autenticazione inviato! Rispondi con il codice a 6 cifre per completare il login.";
            } else {
                return "Errore nell'invio del codice. Riprova più tardi.";
            }
        }
        
        // Se il messaggio sembra un codice numerico
        if (preg_match('/^\d{6}$/', $message)) {
            if ($this->verify_auth_code($phone_number, $message)) {
                $user = $this->find_user_by_phone($phone_number);
                if ($user) {
                    $this->link_whatsapp_to_user($user->ID, $phone_number);
                    return "Autenticazione completata! Ora puoi usare l'assistente. Ciao {$user->display_name}!";
                }
            } else {
                return "Codice non valido o scaduto. Richiedine uno nuovo scrivendo 'login'.";
            }
        }
        
        return false;
    }
    
    /**
     * Messaggio di benvenuto per non autenticati
     */
    public function get_welcome_message() {
        return "Benvenuto in Marrison Assistant! Per accedere all'assistente, devi essere autenticato.\n\n" .
               "Scrivi *login* per ricevere il tuo codice di autenticazione.\n\n" .
               "Se hai già un codice, scrivilo direttamente.";
    }
    
    /**
     * Messaggio di aiuto autenticazione
     */
    public function get_help_message() {
        return "Per usare l'assistente:\n\n" .
               "1. Scrivi *login* per ricevere il codice\n" .
               "2. Inserisci il codice a 6 cifre ricevuto\n" .
               "3. Potrai usare tutte le funzioni dell'assistente\n\n" .
               "Il codice scade dopo 10 minuti per sicurezza.";
    }
}
