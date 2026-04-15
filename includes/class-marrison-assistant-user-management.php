<?php
/**
 * Classe per la gestione utenti WhatsApp
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_User_Management {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_marrison_link_whatsapp', array($this, 'ajax_link_whatsapp'));
        add_action('wp_ajax_marrison_unlink_whatsapp', array($this, 'ajax_unlink_whatsapp'));
    }
    
    /**
     * Aggiunge la pagina di gestione utenti
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'Marrison Assistant - Utenti',
            'Utenti WhatsApp',
            'manage_options',
            'marrison-assistant-users',
            array($this, 'users_page')
        );
    }
    
    /**
     * Registra le impostazioni
     */
    public function register_settings() {
        // Nessuna nuova impostazione necessaria
    }
    
    /**
     * Renderizza la pagina gestione utenti
     */
    public function users_page() {
        ?>
        <div class="wrap">
            <h1>Marrison Assistant - Gestione Utenti WhatsApp</h1>
            
            <div class="marrison-assistant-container">
                <div class="marrison-assistant-section">
                    <h2>Utenti Autenticati</h2>
                    
                    <?php $this->display_authenticated_users(); ?>
                </div>
                
                <div class="marrison-assistant-section">
                    <h2>Associa Utente WhatsApp</h2>
                    
                    <?php $this->display_link_form(); ?>
                </div>
                
                <div class="marrison-assistant-section">
                    <h2>Impostazioni Autenticazione</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="marrison_assistant_logged_only">Solo Utenti Loggati</label>
                            </th>
                            <td>
                                <input type="checkbox" 
                                       id="marrison_assistant_logged_only" 
                                       name="marrison_assistant_logged_only" 
                                       value="1" 
                                       <?php checked(get_option('marrison_assistant_logged_only'), 1); ?>>
                                <label for="marrison_assistant_logged_only">Attiva assistente solo per utenti loggati</label>
                                <p class="description">Limita le risposte dell'assistente solo agli utenti WhatsApp autenticati</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Salva Impostazioni'); ?>
                </div>
            </div>
        </div>
        
        <style>
        .marrison-assistant-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .marrison-assistant-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .user-row {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        
        .user-row.verified {
            border-color: #46b450;
            background: #f7fdf7;
        }
        
        .user-row.unverified {
            border-color: #dc3232;
            background: #fdf7f7;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Link WhatsApp
            $('#link-whatsapp-form').submit(function(e) {
                e.preventDefault();
                
                var userId = $('#user_id').val();
                var phoneNumber = $('#phone_number').val();
                
                if (!userId || !phoneNumber) {
                    alert('Seleziona un utente e inserisci un numero WhatsApp');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_link_whatsapp',
                        user_id: userId,
                        phone_number: phoneNumber,
                        nonce: '<?php echo wp_create_nonce("marrison_user_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('WhatsApp associato con successo!');
                            location.reload();
                        } else {
                            alert('Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                    }
                });
            });
            
            // Unlink WhatsApp
            $('.unlink-whatsapp').click(function() {
                if (!confirm('Sei sicuro di voler rimuovere l\'associazione WhatsApp?')) {
                    return;
                }
                
                var userId = $(this).data('user-id');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_unlink_whatsapp',
                        user_id: userId,
                        nonce: '<?php echo wp_create_nonce("marrison_user_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('WhatsApp dissociato con successo!');
                            location.reload();
                        } else {
                            alert('Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Mostra gli utenti autenticati
     */
    private function display_authenticated_users() {
        $users = get_users(array('meta_key' => 'whatsapp_number'));
        
        if (empty($users)) {
            echo '<p>Nessun utente WhatsApp autenticato.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Utente</th>';
        echo '<th>Email</th>';
        echo '<th>WhatsApp</th>';
        echo '<th>Data Associazione</th>';
        echo '<th>Azioni</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($users as $user) {
            $whatsapp = get_user_meta($user->ID, 'whatsapp_number', true);
            $auth = new Marrison_Assistant_Auth();
            $is_authenticated = $auth->is_whatsapp_authenticated('whatsapp:' . $whatsapp);
            
            echo '<tr class="user-row ' . ($is_authenticated ? 'verified' : 'unverified') . '">';
            echo '<td><strong>' . esc_html($user->display_name) . '</strong></td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td><a href="https://wa.me/' . esc_attr($whatsapp) . '" target="_blank">+' . esc_html($whatsapp) . '</a></td>';
            echo '<td>' . get_user_meta($user->ID, 'whatsapp_linked_date', true) . '</td>';
            echo '<td>';
            echo '<button class="button button-small unlink-whatsapp" data-user-id="' . $user->ID . '">Dissocia</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Mostra il form per associare WhatsApp
     */
    private function display_link_form() {
        ?>
        <form id="link-whatsapp-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="user_id">Seleziona Utente</label>
                    </th>
                    <td>
                        <select id="user_id" name="user_id" required>
                            <option value="">-- Seleziona utente --</option>
                            <?php
                            $users = get_users();
                            foreach ($users as $user) {
                                $has_whatsapp = get_user_meta($user->ID, 'whatsapp_number', true);
                                if (!$has_whatsapp) {
                                    echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="phone_number">Numero WhatsApp</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="phone_number" 
                               name="phone_number" 
                               class="regular-text" 
                               placeholder="+393331234567"
                               required>
                        <p class="description">Formato internazionale: +393331234567</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Associa WhatsApp'); ?>
        </form>
        <?php
    }
    
    /**
     * AJAX: Associa WhatsApp a utente
     */
    public function ajax_link_whatsapp() {
        check_ajax_referer('marrison_user_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }
        
        $user_id = intval($_POST['user_id']);
        $phone_number = sanitize_text_field($_POST['phone_number']);
        
        if (!$user_id || !$phone_number) {
            wp_send_json_error('Dati mancanti');
        }
        
        // Verifica formato numero
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone_number)) {
            wp_send_json_error('Formato numero non valido. Usa formato internazionale: +393331234567');
        }
        
        // Verifica che utente esista
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error('Utente non trovato');
        }
        
        // Associa WhatsApp
        $auth = new Marrison_Assistant_Auth();
        $result = $auth->link_whatsapp_to_user($user_id, $phone_number);
        
        if ($result) {
            // Salva data associazione
            update_user_meta($user_id, 'whatsapp_linked_date', current_time('mysql'));
            
            wp_send_json_success('WhatsApp associato con successo');
        } else {
            wp_send_json_error('Errore durante l\'associazione');
        }
    }
    
    /**
     * AJAX: Dissocia WhatsApp da utente
     */
    public function ajax_unlink_whatsapp() {
        check_ajax_referer('marrison_user_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }
        
        $user_id = intval($_POST['user_id']);
        
        if (!$user_id) {
            wp_send_json_error('ID utente mancante');
        }
        
        // Rimuovi associazione
        delete_user_meta($user_id, 'whatsapp_number');
        delete_user_meta($user_id, 'whatsapp_linked_date');
        
        error_log("Marrison Assistant: WhatsApp dissociato dall'utente {$user_id}");
        
        wp_send_json_success('WhatsApp dissociato con successo');
    }
}
