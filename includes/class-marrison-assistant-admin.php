<?php
/**
 * Classe per l'amministrazione del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once MARRISON_ASSISTANT_PLUGIN_DIR . 'includes/class-marrison-assistant-main-page.php';

class Marrison_Assistant_Admin {
    
    private $main_page;
    
    public function __construct() {
        $this->main_page = new Marrison_Assistant_Main_Page();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Aggiunge la pagina menu del plugin
     */
    public function add_admin_menu() {
        $menu_label = Marrison_Assistant_White_Label::plugin_name();
        add_menu_page(
            $menu_label,
            $menu_label,
            'manage_options',
            'marrison-assistant',
            array($this, 'main_page'),
            'dashicons-format-chat',
            30
        );
        
        // Sottopagine
        add_submenu_page(
            'marrison-assistant',
            'Impostazioni Generali',
            'Generale',
            'manage_options',
            'marrison-assistant',
            array($this, 'main_page')
        );
        
    }
    
    /**
     * Pagina principale (con tab)
     */
    public function main_page() {
        $this->main_page->render();
    }
    
    /**
     * Registra le impostazioni del plugin
     */
    public function register_settings() {
        // NOTA: marrison_assistant_gemini_api_key rimosso — API key gestita dal Commander
        register_setting('marrison_assistant_settings', 'marrison_assistant_custom_prompt');
        register_setting('marrison_assistant_settings', 'marrison_assistant_logged_only');
        
        // Impostazioni agente sito
        register_setting('marrison_assistant_settings', 'marrison_assistant_enable_site_agent');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_position');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_color');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_title');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_name');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_welcome');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_placeholder');
        // Colori personalizzabili
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_icon_color');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_header_color');
        register_setting('marrison_assistant_settings', 'marrison_assistant_site_agent_button_color');
    }
    
    /**
     * Carica script e stili admin
     */
    public function enqueue_admin_scripts($hook) {
        // Carica nelle pagine del plugin
        if (strpos($hook, 'marrison-assistant') !== false) {
            wp_enqueue_script(
                'marrison-admin',
                plugins_url('assets/js/admin.js', MARRISON_ASSISTANT_PLUGIN_DIR . 'marrison-assistant.php'),
                array('jquery'),
                MARRISON_ASSISTANT_VERSION,
                true
            );
            
            wp_localize_script('marrison-admin', 'marrisonAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('marrison_admin_nonce')
            ));
        }
    }
    
    /**
     * Renderizza la pagina admin
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(); ?>
            
            <div class="marrison-assistant-container">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('marrison_assistant_settings');
                    do_settings_sections('marrison_assistant_settings');
                    ?>
                                        <div class="marrison-assistant-section">
                        <h2>⚙️ Configurazione API</h2>
                    
                    <div class="marrison-assistant-section">
                        <h2>🤖 Comportamento AI</h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="marrison_assistant_custom_prompt">Prompt Personalizzato</label>
                                </th>
                                <td>
                                    <textarea id="marrison_assistant_custom_prompt" 
                                              name="marrison_assistant_custom_prompt" 
                                              rows="8" 
                                              class="large-text"><?php echo esc_textarea(get_option('marrison_assistant_custom_prompt', 'Sei un assistente AI per questo sito web. Rispondi in modo professionale e utile basandoti sui contenuti del sito.')); ?></textarea>
                                    <p class="description">Definisci come l'AI dovrebbe comportarsi. Questo prompt verrà combinato con i contenuti del sito.</p>
                                </td>
                            </tr>
                            
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
                                    <p class="description">Limita l'accesso all'assistente solo agli utenti loggati. Utile per aree riservate o assistenza premium.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="marrison-assistant-section">
                        <h2>🧪 Test Connessioni</h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">Debug Gemini API</th>
                                <td>
                                    <button type="button" id="debug-gemini" class="button button-secondary">
                                        Debug Completo API
                                    </button>
                                    <div id="gemini-debug-result" class="debug-result" style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; display: none;"></div>
                                    <p class="description">Test dettagliato per identificare problemi di connessione API</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Test API Gemini</th>
                                <td>
                                    <button type="button" id="test-gemini" class="button button-secondary">
                                        Testa Connessione Gemini
                                    </button>
                                    <span id="gemini-test-result" class="test-result"></span>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Scansione Contenuti</th>
                                <td>
                                    <button type="button" id="scan-content" class="button button-secondary">
                                        Scansiona Contenuti Sito
                                    </button>
                                    <span id="scan-result" class="test-result"></span>
                                    <p class="description">Scansiona pagine, articoli e prodotti WooCommerce per creare la knowledge base</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php submit_button('Salva Impostazioni'); ?>
                </form>
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
        
        .test-result {
            margin-left: 10px;
            font-weight: bold;
        }
        
        .test-success {
            color: #46b450;
        }
        
        .test-error {
            color: #dc3232;
        }
        
        .test-loading {
            color: #666;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#debug-gemini').click(function() {
                var $button = $(this);
                var $result = $('#gemini-debug-result');
                
                $button.prop('disabled', true);
                $result.show().html('<div style="color:blue;">Debug in corso...</div>');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_debug_gemini',
                        nonce: '<?php echo wp_create_nonce("marrison_test_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html(response.data);
                        } else {
                            $result.html('<div style="color:red;">Errore: ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $result.html('<div style="color:red;">Errore di connessione</div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
            
            $('#test-gemini').click(function() {
                var $button = $(this);
                var $result = $('#gemini-test-result');
                
                $button.prop('disabled', true);
                $result.removeClass('test-success test-error').addClass('test-loading').text('Test in corso...');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_test_gemini',
                        nonce: '<?php echo wp_create_nonce("marrison_test_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.removeClass('test-loading').addClass('test-success').text('✓ Connessione riuscita');
                        } else {
                            $result.removeClass('test-loading').addClass('test-error').text('✗ Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        $result.removeClass('test-loading').addClass('test-error').text('✗ Errore di connessione');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
            
            $('#scan-content').click(function() {
                var $button = $(this);
                var $result = $('#scan-result');
                
                $button.prop('disabled', true);
                $result.removeClass('test-success test-error').addClass('test-loading').text('Scansione in corso...');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_scan_content',
                        nonce: '<?php echo wp_create_nonce("marrison_scan_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.removeClass('test-loading').addClass('test-success').text('✓ ' + response.data);
                        } else {
                            $result.removeClass('test-loading').addClass('test-error').text('✗ Errore: ' + response.data);
                        }
                    },
                    error: function() {
                        $result.removeClass('test-loading').addClass('test-error').text('✗ Errore durante la scansione');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
