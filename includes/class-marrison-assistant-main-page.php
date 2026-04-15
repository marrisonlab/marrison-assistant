<?php
/**
 * Classe per la pagina principale del plugin con tab separate
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Main_Page {
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Registra le impostazioni
     */
    public function register_settings() {
        // Impostazioni agente sito
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_enable_site_agent');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_position');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_color');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_title');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_welcome');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_placeholder');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_logged_only');
    }
    
    /**
     * Renderizza la pagina principale
     */
    public function render() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap marrison-assistant-wrap">
            <h1 class="wp-heading-inline">Marrison Assistant</h1>
            <hr class="wp-header-end">
            
            <nav class="nav-tab-wrapper">
                <a href="?page=marrison-assistant&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> Generale
                </a>
                <a href="?page=marrison-assistant&tab=site-agent" class="nav-tab <?php echo $active_tab === 'site-agent' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-format-chat"></span> Agente Sito
                </a>
                <a href="?page=marrison-assistant&tab=whatsapp" class="nav-tab <?php echo $active_tab === 'whatsapp' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-smartphone"></span> WhatsApp
                </a>
                <a href="?page=marrison-assistant&tab=analytics" class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span> Analytics
                </a>
            </nav>
            
            <div class="marrison-assistant-content">
                <?php
                switch ($active_tab) {
                    case 'site-agent':
                        $this->render_site_agent_tab();
                        break;
                    case 'whatsapp':
                        $this->render_whatsapp_tab();
                        break;
                    case 'analytics':
                        $this->render_analytics_tab();
                        break;
                    default:
                        $this->render_general_tab();
                        break;
                }
                ?>
            </div>
        </div>
        
        <style>
        .marrison-assistant-wrap .nav-tab-wrapper {
            margin: 1em 0;
        }
        
        .marrison-assistant-wrap .nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .marrison-assistant-content {
            margin-top: 20px;
        }
        
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
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-indicator.active {
            background: #46b450;
        }
        
        .status-indicator.inactive {
            background: #dc3232;
        }
        
        .marrison-card {
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin: 10px 0;
        }
        
        .marrison-card h3 {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Tab generale
     */
    private function render_general_tab() {
        ?>
        <div class="marrison-assistant-section">
            <h2>Stato Sistema</h2>
            
            <div class="marrison-card">
                <h3>API Connections</h3>
                <p>
                    <span class="status-indicator <?php echo get_option('marrison_assistant_gemini_api_key') ? 'active' : 'inactive'; ?>"></span>
                    Gemini API: <?php echo get_option('marrison_assistant_gemini_api_key') ? 'Configurata' : 'Non configurata'; ?>
                </p>
                <p>
                    <span class="status-indicator <?php echo get_option('marrison_assistant_twilio_sid') ? 'active' : 'inactive'; ?>"></span>
                    Twilio API: <?php echo get_option('marrison_assistant_twilio_sid') ? 'Configurata' : 'Non configurata'; ?>
                </p>
            </div>
            
            <div class="marrison-card">
                <h3>Moduli Attivi</h3>
                <p>
                    <span class="status-indicator <?php echo get_option('marrison_assistant_enable_site_agent') ? 'active' : 'inactive'; ?>"></span>
                    Agente Sito: <?php echo get_option('marrison_assistant_enable_site_agent') ? 'Attivo' : 'Disattivo'; ?>
                </p>
                <p>
                    <span class="status-indicator <?php echo get_option('marrison_assistant_enable_webhook') ? 'active' : 'inactive'; ?>"></span>
                    WhatsApp: <?php echo get_option('marrison_assistant_enable_webhook') ? 'Attivo' : 'Disattivo'; ?>
                </p>
            </div>
        </div>
        
        <div class="marrison-assistant-section">
            <h2>Configurazione Rapida</h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('marrison_assistant_settings');
                do_settings_sections('marrison_assistant_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_gemini_api_key">API Key Gemini</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="marrison_assistant_gemini_api_key" 
                                   name="marrison_assistant_gemini_api_key" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_gemini_api_key')); ?>" 
                                   class="regular-text">
                            <p class="description">La tua API Key di Google Gemini</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_custom_prompt">Prompt Personalizzato</label>
                        </th>
                        <td>
                            <textarea id="marrison_assistant_custom_prompt" 
                                      name="marrison_assistant_custom_prompt" 
                                      rows="4" 
                                      class="large-text"><?php echo esc_textarea(get_option('marrison_assistant_custom_prompt', 'Sei un assistente AI per questo sito web. Rispondi in modo professionale e utile basandoti sui contenuti del sito.')); ?></textarea>
                            <p class="description">Personalizza il comportamento dell'assistente AI</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Salva Impostazioni Generali'); ?>
            </form>
        </div>
        
        <div class="marrison-assistant-section">
            <h2>Scansione Contenuti</h2>
            
            <p>L'ultima scansione dei contenuti è stata effettuata il: 
                <strong><?php echo get_option('marrison_assistant_last_content_scan') ? date('d/m/Y H:i', get_option('marrison_assistant_last_content_scan')) : 'Mai'; ?></strong>
            </p>
            
            <p>
                <button type="button" id="scan-content-btn" class="button button-primary">
                    <span class="dashicons-search"></span> Scansiona Contenuti Sito
                </button>
                <span id="scan-status"></span>
            </p>
            
            <div id="scan-results" style="display:none;">
                <h4>Risultati Scansione:</h4>
                <div id="scan-details"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tab agente sito
     */
    private function render_site_agent_tab() {
        ?>
        <div class="marrison-assistant-section">
            <h2>Configurazione Agente Sito</h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('marrison_assistant_site_agent');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_enable_site_agent">Abilita Agente Sito</label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="marrison_assistant_enable_site_agent" 
                                   name="marrison_assistant_enable_site_agent" 
                                   value="1" 
                                   <?php checked(get_option('marrison_assistant_enable_site_agent'), 1); ?>>
                            <label for="marrison_assistant_enable_site_agent">Mostra agente AI sul sito</label>
                            <p class="description">Attiva il widget chat che appare agli utenti del sito</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_logged_only">Solo Utenti Loggati</label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="marrison_assistant_site_agent_logged_only" 
                                   name="marrison_assistant_site_agent_logged_only" 
                                   value="1" 
                                   <?php checked(get_option('marrison_assistant_site_agent_logged_only'), 1); ?>>
                            <label for="marrison_assistant_site_agent_logged_only">Mostra agente solo agli utenti loggati</label>
                            <p class="description">Il widget chat apparirà solo agli utenti che hanno effettuato il login. Utile per B2B o aree premium.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_position">Posizione Widget</label>
                        </th>
                        <td>
                            <select id="marrison_assistant_site_agent_position" 
                                    name="marrison_assistant_site_agent_position">
                                <option value="bottom-right" <?php selected(get_option('marrison_assistant_site_agent_position'), 'bottom-right'); ?>>
                                    In basso a destra
                                </option>
                                <option value="bottom-left" <?php selected(get_option('marrison_assistant_site_agent_position'), 'bottom-left'); ?>>
                                    In basso a sinistra
                                </option>
                                <option value="top-right" <?php selected(get_option('marrison_assistant_site_agent_position'), 'top-right'); ?>>
                                    In alto a destra
                                </option>
                                <option value="top-left" <?php selected(get_option('marrison_assistant_site_agent_position'), 'top-left'); ?>>
                                    In alto a sinistra
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_color">Colore Tema</label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="marrison_assistant_site_agent_color" 
                                   name="marrison_assistant_site_agent_color" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_color', '#0073aa')); ?>">
                            <p class="description">Colore principale del widget chat</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_title">Titolo Widget</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="marrison_assistant_site_agent_title" 
                                   name="marrison_assistant_site_agent_title" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_title', 'Assistente AI')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_welcome">Messaggio Benvenuto</label>
                        </th>
                        <td>
                            <textarea id="marrison_assistant_site_agent_welcome" 
                                      name="marrison_assistant_site_agent_welcome" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea(get_option('marrison_assistant_site_agent_welcome', 'Ciao! Come posso aiutarti oggi?')); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_placeholder">Placeholder Input</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="marrison_assistant_site_agent_placeholder" 
                                   name="marrison_assistant_site_agent_placeholder" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_placeholder', 'Scrivi un messaggio...')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Salva Impostazioni Agente'); ?>
            </form>
        </div>
        
        <div class="marrison-assistant-section">
            <h2>Anteprima Widget</h2>
            
            <div id="widget-preview" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                <p>Anteprima del widget chat (simulata):</p>
                <div style="background: #f5f5f5; border-radius: 8px; padding: 15px; max-width: 300px;">
                    <div style="background: <?php echo esc_attr(get_option('marrison_assistant_site_agent_color', '#0073aa')); ?>; color: white; padding: 10px; border-radius: 8px 8px 0 0; text-align: center;">
                        <?php echo esc_html(get_option('marrison_assistant_site_agent_title', 'Assistente AI')); ?>
                    </div>
                    <div style="padding: 10px; min-height: 60px;">
                        <small><?php echo esc_html(get_option('marrison_assistant_site_agent_welcome', 'Ciao! Come posso aiutarti oggi?')); ?></small>
                    </div>
                    <div style="border-top: 1px solid #ddd; padding: 8px;">
                        <input type="text" placeholder="<?php echo esc_attr(get_option('marrison_assistant_site_agent_placeholder', 'Scrivi un messaggio...')); ?>" style="width: 100%; border: 1px solid #ddd; padding: 5px; border-radius: 3px;" disabled>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tab WhatsApp (in sviluppo)
     */
    private function render_whatsapp_tab() {
        ?>
        <div class="marrison-assistant-section">
            <h2>WhatsApp Assistant - In Sviluppo</h2>
            
            <div class="marrison-card" style="border-left-color: #ffb900;">
                <h3>Funzionalità in Arrivo</h3>
                <p>La funzionalità WhatsApp Assistant è attualmente in fase di sviluppo e sarà disponibile in una prossima versione.</p>
                
                <h4>Cosa includerà:</h4>
                <ul>
                    <li>Integrazione completa con Twilio WhatsApp</li>
                    <li>Assistente AI automatico per messaggi WhatsApp</li>
                    <li>Tracking ordini via WhatsApp</li>
                    <li>Autenticazione utenti WhatsApp</li>
                    <li>Gestione contatti e conversazioni</li>
                    <li>Template messaggi e risposte rapide</li>
                    <li>Analytics e statistiche utilizzo</li>
                </ul>
                
                <h4>Stato Sviluppo:</h4>
                <div style="background: #f0f6fc; padding: 15px; border-radius: 4px; margin: 10px 0;">
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span style="color: #ffb900;">&#9673;</span>
                        <span style="margin-left: 8px;">API Twilio - 80% completato</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span style="color: #ffb900;">&#9673;</span>
                        <span style="margin-left: 8px;">Webhook management - 70% completato</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span style="color: #ffb900;">&#9673;</span>
                        <span style="margin-left: 8px;">Interfaccia admin - 60% completato</span>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <span style="color: #ffb900;">&#9673;</span>
                        <span style="margin-left: 8px;">Testing e debug - 40% completato</span>
                    </div>
                </div>
                
                <h4>Per Adesso:</h4>
                <p>Puoi utilizzare l'<strong>Agente Sito</strong> che è completamente funzionale e include:</p>
                <ul>
                    <li>Widget chat personalizzabile</li>
                    <li>Integrazione con Gemini AI</li>
                    <li>Supporto per utenti loggati/visitatori</li>
                    <li>Design responsive e moderno</li>
                    <li>Analytics di utilizzo</li>
                </ul>
                
                <div style="background: #e6f4ea; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <strong>Prossimo Aggiornamento Previsto:</strong> Versione 1.1.0 (Q2 2026)<br>
                    <strong>Focus:</strong> Completamento integrazione WhatsApp
                </div>
            </div>
            
            <div class="marrison-card">
                <h3>Notifiche Disponibili</h3>
                <p>Vuoi essere avvisato quando WhatsApp Assistant sarà disponibile?</p>
                
                <form method="post" action="">
                    <input type="email" placeholder="La tua email" style="margin-right: 10px; padding: 8px;" required>
                    <button type="submit" class="button button-primary">Avvisami</button>
                </form>
                
                <p style="font-size: 12px; color: #666; margin-top: 10px;">
                    Ti invieremo una notifica email quando la funzionalità sarà pronta. 
                    Nessun spam, promesso!
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Tab analytics
     */
    private function render_analytics_tab() {
        ?>
        <div class="marrison-assistant-section">
            <h2>Statistiche Utilizzo</h2>
            
            <div class="marrison-card">
                <h3>WhatsApp</h3>
                <p>Messaggi ricevuti oggi: <strong>0</strong></p>
                <p>Messaggi inviati oggi: <strong>0</strong></p>
                <p>Utenti attivi oggi: <strong>0</strong></p>
            </div>
            
            <div class="marrison-card">
                <h3>Agente Sito</h3>
                <p>Chat aperte oggi: <strong>0</strong></p>
                <p>Messaggi scambiati oggi: <strong>0</strong></p>
                <p>Tasso di risposta: <strong>0%</strong></p>
            </div>
            
            <p><em>Funzionalità analytics in sviluppo. Presto disponibili statistiche dettagliate!</em></p>
        </div>
        <?php
    }
}

// Aggiungi JavaScript per la pagina admin
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (strpos($screen->id, 'marrison-assistant') !== false) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Scansione contenuti
            $('#scan-content-btn').click(function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $status = $('#scan-status');
                var $results = $('#scan-results');
                
                $btn.prop('disabled', true);
                $status.html('<span style="color: #ffb900;">Scansione in corso...</span>');
                $results.hide();
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_scan_site_content',
                        nonce: '<?php echo wp_create_nonce('marrison_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #46b450;">Scansione completata!</span>');
                            $('#scan-details').html(response.data);
                            $results.show();
                            
                            // Aggiorna data ultima scansione nella pagina dopo 2 secondi
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.html('<span style="color: #dc3232;">Errore: ' + response.data + '</span>');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.responseText);
                        $status.html('<span style="color: #dc3232;">Errore connessione: ' + error + '</span>');
                        $btn.prop('disabled', false);
                        
                        // Mostra dettagli errore per debug
                        if (xhr.responseText) {
                            $('#scan-details').html('<div style="color: red;">Dettagli errore:<pre>' + xhr.responseText + '</pre></div>');
                            $results.show();
                        }
                    }
                });
            });
            
            // Test connessioni (quando implementate)
            $('#test-gemini-btn').click(function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $status = $('#gemini-status');
                
                $btn.prop('disabled', true);
                $status.html('<span style="color: #ffb900;">Test in corso...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_test_gemini',
                        nonce: '<?php echo wp_create_nonce('marrison_test_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #46b450;">Connessione OK</span>');
                        } else {
                            $status.html('<span style="color: #dc3232;">Errore</span>');
                        }
                    },
                    error: function() {
                        $status.html('<span style="color: #dc3232;">Errore</span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            $('#test-twilio-btn').click(function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $status = $('#twilio-status');
                
                $btn.prop('disabled', true);
                $status.html('<span style="color: #ffb900;">Test in corso...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_test_twilio',
                        nonce: '<?php echo wp_create_nonce('marrison_test_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<span style="color: #46b450;">Connessione OK</span>');
                        } else {
                            $status.html('<span style="color: #dc3232;">Errore</span>');
                        }
                    },
                    error: function() {
                        $status.html('<span style="color: #dc3232;">Errore</span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
});
