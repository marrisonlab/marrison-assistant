<?php
/**
 * Classe per l'integrazione con Twilio API per WhatsApp
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Twilio {
    
    private $account_sid;
    private $auth_token;
    private $whatsapp_number;
    
    public function __construct() {
        $this->account_sid = get_option('marrison_assistant_twilio_sid');
        $this->auth_token = get_option('marrison_assistant_twilio_auth_token');
        $this->whatsapp_number = get_option('marrison_assistant_twilio_whatsapp_number');
    }
    
    /**
     * Testa la connessione con l'API Twilio
     */
    public function test_connection() {
        if (empty($this->account_sid) || empty($this->auth_token)) {
            return 'Credenziali Twilio mancanti';
        }
        
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->account_sid . '.json';
        
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->account_sid . ':' . $this->auth_token),
            ),
            'timeout' => 10,
            'sslverify' => true
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return 'Errore di connessione all\'API Twilio';
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        
        if ($http_code === 200) {
            return true;
        }
        
        return 'Errore autenticazione Twilio (HTTP ' . $http_code . ')';
    }
    
    /**
     * Invia un messaggio WhatsApp
     */
    public function send_whatsapp_message($to, $message) {
        if (empty($this->account_sid) || empty($this->auth_token) || empty($this->whatsapp_number)) {
            error_log('Marrison Assistant: Credenziali Twilio non configurate');
            return false;
        }
        
        // Formatta il numero di destinazione
        if (!preg_match('/^whatsapp:\+/', $to)) {
            $to = 'whatsapp:' . $to;
        }
        
        // Formatta il numero mittente
        if (!preg_match('/^whatsapp:\+/', $this->whatsapp_number)) {
            $from = 'whatsapp:' . $this->whatsapp_number;
        } else {
            $from = $this->whatsapp_number;
        }
        
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->account_sid . '/Messages.json';
        
        $body = array(
            'From' => $from,
            'To' => $to,
            'Body' => $message
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->account_sid . ':' . $this->auth_token),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query($body),
            'timeout' => 30,
            'sslverify' => true
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Marrison Assistant: Errore invio messaggio Twilio - ' . $response->get_error_message());
            return false;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($http_code !== 201) {
            error_log('Marrison Assistant: Errore HTTP Twilio - ' . $http_code . ' - ' . $response_body);
            return false;
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Marrison Assistant: Errore parsing JSON Twilio - ' . json_last_error_msg());
            return false;
        }
        
        if (isset($data['sid'])) {
            error_log('Marrison Assistant: Messaggio inviato con SID ' . $data['sid']);
            return $data['sid'];
        }
        
        error_log('Marrison Assistant: Risposta Twilio non valida - ' . $response_body);
        return false;
    }
    
    /**
     * Verifica se il webhook è valido (base)
     */
    public function verify_webhook($request) {
        // Per MVP, verifica base che il webhook sia abilitato
        $enable_webhook = get_option('marrison_assistant_enable_webhook', false);
        
        if (!$enable_webhook) {
            return false;
        }
        
        // Verifica che ci siano i dati necessari
        $body = $request->get_param('Body');
        $from = $request->get_param('From');
        
        if (empty($body) || empty($from)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Processa un messaggio in arrivo da WhatsApp
     */
    public function process_incoming_message($request) {
        if (!$this->verify_webhook($request)) {
            return new WP_Error('invalid_webhook', 'Webhook non valido', array('status' => 400));
        }
        
        $body = $request->get_param('Body');
        $from = $request->get_param('From');
        
        // Rimuovi il prefisso whatsapp: se presente
        if (strpos($from, 'whatsapp:') === 0) {
            $from = substr($from, 9);
        }
        
        // Sanitizza il messaggio
        $user_message = sanitize_text_field($body);
        
        // Log del messaggio ricevuto
        error_log('Marrison Assistant: Messaggio ricevuto da ' . $from . ': ' . $user_message);
        
        // Verifica se è attiva la modalità solo utenti loggati
        $logged_only = get_option('marrison_assistant_logged_only', false);
        
        if ($logged_only) {
            $auth = new Marrison_Assistant_Auth();
            
            // Verifica se l'utente è già autenticato
            if (!$auth->is_whatsapp_authenticated($from)) {
                error_log('Marrison Assistant: Utente non autenticato: ' . $from);
                
                // Processa richiesta di autenticazione
                $auth_response = $auth->process_auth_request($from, $user_message);
                
                if ($auth_response) {
                    $ai_response = $auth_response;
                } else {
                    // Se non è una richiesta di autenticazione, invia messaggio di benvenuto
                    $ai_response = $auth->get_welcome_message();
                }
                
                // Invia la risposta di autenticazione
                $message_sid = $this->send_whatsapp_message($from, $ai_response);
                
                if ($message_sid) {
                    error_log('Marrison Assistant: Risposta autenticazione inviata a ' . $from);
                    return array(
                        'success' => true,
                        'message_sid' => $message_sid,
                        'response' => $ai_response,
                        'auth_required' => true
                    );
                } else {
                    error_log('Marrison Assistant: Errore invio risposta autenticazione a ' . $from);
                    return new WP_Error('send_failed', 'Errore invio risposta', array('status' => 500));
                }
            }
            
            error_log('Marrison Assistant: Utente autenticato: ' . $from);
        }
        
        // Controlla se il messaggio contiene un numero ordine
        $order_scanner = new Marrison_Assistant_Order_Scanner();
        $order_number = $order_scanner->extract_order_number($user_message);
        
        if ($order_number) {
            error_log('Marrison Assistant: Rilevato numero ordine: ' . $order_number);
            
            $order_info = $order_scanner->get_order_status($order_number);
            $ai_response = $order_scanner->format_order_response($order_info);
            
            error_log('Marrison Assistant: Risposta ordine preparata');
        } else {
            // Processa il messaggio normale con Gemini
            $gemini = new Marrison_Assistant_Gemini();
            $ai_response = $gemini->process_message($user_message);
        }
        
        // Invia la risposta
        $message_sid = $this->send_whatsapp_message($from, $ai_response);
        
        if ($message_sid) {
            error_log('Marrison Assistant: Risposta inviata a ' . $from . ' con SID ' . $message_sid);
            return array(
                'success' => true,
                'message_sid' => $message_sid,
                'response' => $ai_response,
                'order_checked' => $order_number ? true : false,
                'auth_required' => false
            );
        } else {
            error_log('Marrison Assistant: Errore invio risposta a ' . $from);
            return new WP_Error('send_failed', 'Errore invio risposta', array('status' => 500));
        }
    }
}
