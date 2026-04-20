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
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_name');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_welcome');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_placeholder');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_logged_only');
        // Colori personalizzabili
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_icon_color');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_header_color');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_button_color');
        // Risposte ai bottoni di categoria
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_response_products');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_response_orders');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_response_info');
        register_setting('marrison_assistant_site_agent', 'marrison_assistant_site_agent_response_events');
    }
    
    /**
     * Renderizza la pagina principale
     */
    public function render() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap marrison-assistant-wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html( Marrison_Assistant_White_Label::plugin_name() ); ?></h1>
            <hr class="wp-header-end">
            
            <nav class="nav-tab-wrapper">
                <a href="?page=marrison-assistant&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> Generale
                </a>
                <a href="?page=marrison-assistant&tab=site-agent" class="nav-tab <?php echo $active_tab === 'site-agent' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-format-chat"></span> Agente Sito
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
                    <span class="status-indicator <?php echo get_option('marrison_assistant_commander_url') ? 'active' : 'inactive'; ?>"></span>
                    Commander: <?php echo get_option('marrison_assistant_commander_url', 'https://marrisonlab.com'); ?>
                </p>
            </div>
            
            <div class="marrison-card">
                <h3>Moduli Attivi</h3>
                <p>
                    <span class="status-indicator <?php echo get_option('marrison_assistant_enable_site_agent') ? 'active' : 'inactive'; ?>"></span>
                    Agente Sito: <?php echo get_option('marrison_assistant_enable_site_agent') ? 'Attivo' : 'Disattivo'; ?>
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
                            <label for="marrison_assistant_commander_url">Commander URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="marrison_assistant_commander_url" 
                                   name="marrison_assistant_commander_url" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_commander_url', 'https://marrisonlab.com')); ?>" 
                                   class="regular-text"
                                   placeholder="https://marrisonlab.com">
                            <p class="description">URL del sito con Marrison Commander installato. Gestisce le API e le quote.</p>
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
                &nbsp;
                <button type="button" id="debug-events-btn" class="button button-secondary">
                    📅 Debug Eventi
                </button>
                <span id="scan-status"></span>
            </p>
            
            <div id="scan-results" style="display:none;">
                <h4>Risultati Scansione:</h4>
                <div id="scan-details"></div>
            </div>
            <div id="debug-events-result" style="display:none; margin-top:10px; padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px;"></div>
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
                            <label for="marrison_assistant_site_agent_name">Nome Assistente</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="marrison_assistant_site_agent_name" 
                                   name="marrison_assistant_site_agent_name" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_name', 'Marry')); ?>" 
                                   class="regular-text">
                            <p class="description">Nome che l'assistente userà per presentarsi (es: "Ciao, sono Marry, il tuo assistente...")</p>
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

                    <tr><td colspan="2"><hr><h4>Risposte ai Bottoni di Categoria</h4>
                        <p class="description">Personalizza il messaggio che l'assistente invia dopo che l'utente clicca su una categoria. Adatta il testo al tipo di sito (es. per beauty, moda, eventi...).</p>
                    </td></tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_response_products">🛍️ Risposta "Prodotti"</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="marrison_assistant_site_agent_response_products"
                                   name="marrison_assistant_site_agent_response_products"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_products', 'Perfetto! Dimmi cosa stai cercando tra i nostri prodotti.')); ?>"
                                   class="large-text">
                            <p class="description">Es. per beauty: "Perfetto! Cerchi una crema, un siero o un profumo?"</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_response_orders">📦 Risposta "Ordini"</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="marrison_assistant_site_agent_response_orders"
                                   name="marrison_assistant_site_agent_response_orders"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_orders', 'Certo! Dimmi il numero ordine o cosa vorresti sapere sul tuo acquisto.')); ?>"
                                   class="large-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_response_info">ℹ️ Risposta "Info"</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="marrison_assistant_site_agent_response_info"
                                   name="marrison_assistant_site_agent_response_info"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_info', 'Con piacere! Su cosa vorresti informazioni? Azienda, contatti, servizi?')); ?>"
                                   class="large-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_response_events">📅 Risposta "Eventi"</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="marrison_assistant_site_agent_response_events"
                                   name="marrison_assistant_site_agent_response_events"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_events', 'Ottimo! Stai cercando un evento specifico o vuoi vedere il calendario?')); ?>"
                                   class="large-text">
                        </td>
                    </tr>

                    <tr><td colspan="2"><hr><h4>Colori Personalizzati</h4></td></tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_icon_color">Colore Icona Fluttuante</label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="marrison_assistant_site_agent_icon_color" 
                                   name="marrison_assistant_site_agent_icon_color" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_icon_color', '#667eea')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_header_color">Colore Testata Chat</label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="marrison_assistant_site_agent_header_color" 
                                   name="marrison_assistant_site_agent_header_color" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_header_color', '#667eea')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="marrison_assistant_site_agent_button_color">Colore Pulsante Invio</label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="marrison_assistant_site_agent_button_color" 
                                   name="marrison_assistant_site_agent_button_color" 
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_button_color', '#667eea')); ?>">
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
     * Tab analytics — Monitoraggio consumo token Gemini
     */
    private function render_analytics_tab() {
        $log = get_option('marrison_assistant_token_log', array());

        // Calcola totali
        $tot_messages   = count($log);
        $tot_bytes      = 0;
        $tot_est        = 0;
        $tot_real_in    = 0;
        $tot_real_out   = 0;
        $tot_real_total = 0;
        $has_real       = false;
        foreach ($log as $entry) {
            $tot_bytes   += (int) ($entry['prompt_bytes']       ?? 0);
            $tot_est     += (int) ($entry['prompt_tokens_est']  ?? 0);
            if (!is_null($entry['prompt_tokens_real'] ?? null)) {
                $tot_real_in    += (int) $entry['prompt_tokens_real'];
                $tot_real_out   += (int) ($entry['output_tokens']  ?? 0);
                $tot_real_total += (int) ($entry['total_tokens']   ?? 0);
                $has_real = true;
            }
        }

        $intent_labels = array(
            'products' => '🛍️ Prodotti',
            'orders'   => '📦 Ordini',
            'info'     => 'ℹ️ Info',
            'events'   => '📅 Eventi',
            'general'  => '🔍 Generale',
        );
        ?>
        <div class="marrison-assistant-section">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <h2 style="margin:0;">📊 Monitoraggio Token Gemini</h2>
                <button type="button" id="marrison-reset-token-log" class="button button-secondary">
                    🗑️ Reset log
                </button>
            </div>
            <p style="color:#666; margin-top:8px;">
                Ogni riga corrisponde a un messaggio inviato al Commander. 
                <?php if (!$has_real): ?>
                    <em>I token reali non sono disponibili (usageMetadata non restituito dal Commander). Vengono mostrati i token stimati.</em>
                <?php endif; ?>
            </p>

            <?php if (empty($log)): ?>
                <p><em>Nessuna conversazione registrata. Inizia una chat per vedere i dati.</em></p>
            <?php else: ?>

            <!-- Tabella riepilogo -->
            <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
                <div style="background:#f0f6fc; border:1px solid #b3d4f7; border-radius:6px; padding:14px 20px; min-width:140px; text-align:center;">
                    <div style="font-size:24px; font-weight:700; color:#0073aa;"><?php echo $tot_messages; ?></div>
                    <div style="font-size:12px; color:#555;">Messaggi totali</div>
                </div>
                <div style="background:#f0f6fc; border:1px solid #b3d4f7; border-radius:6px; padding:14px 20px; min-width:140px; text-align:center;">
                    <div style="font-size:24px; font-weight:700; color:#0073aa;"><?php echo number_format($tot_est); ?></div>
                    <div style="font-size:12px; color:#555;">Token stimati totali</div>
                </div>
                <?php if ($has_real): ?>
                <div style="background:#edfaed; border:1px solid #84c484; border-radius:6px; padding:14px 20px; min-width:140px; text-align:center;">
                    <div style="font-size:24px; font-weight:700; color:#2a7a2a;"><?php echo number_format($tot_real_total); ?></div>
                    <div style="font-size:12px; color:#555;">Token reali totali</div>
                </div>
                <div style="background:#edfaed; border:1px solid #84c484; border-radius:6px; padding:14px 20px; min-width:140px; text-align:center;">
                    <div style="font-size:24px; font-weight:700; color:#2a7a2a;"><?php echo number_format($tot_real_in); ?> / <?php echo number_format($tot_real_out); ?></div>
                    <div style="font-size:12px; color:#555;">Token reali in / out</div>
                </div>
                <?php endif; ?>
                <div style="background:#f9f0ff; border:1px solid #c9a0f7; border-radius:6px; padding:14px 20px; min-width:140px; text-align:center;">
                    <div style="font-size:24px; font-weight:700; color:#6a0dad;"><?php echo number_format(round($tot_bytes / 1024, 1)); ?> KB</div>
                    <div style="font-size:12px; color:#555;">Bytes prompt totali</div>
                </div>
            </div>

            <!-- Tabella dettaglio -->
            <div style="overflow-x:auto;">
                <table class="widefat fixed striped" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th style="width:130px;">#&nbsp;Ora</th>
                            <th style="width:110px;">Intent</th>
                            <th style="width:90px; text-align:right;">Bytes prompt</th>
                            <th style="width:110px; text-align:right;">Token stimati</th>
                            <?php if ($has_real): ?>
                            <th style="width:110px; text-align:right;">Token reali in</th>
                            <th style="width:110px; text-align:right;">Token reali out</th>
                            <th style="width:100px; text-align:right;">Totale reale</th>
                            <?php endif; ?>
                            <th style="width:80px; text-align:center;">Qualità</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $rows = array_reverse($log); // più recenti prima
                    foreach ($rows as $i => $e) {
                        $intent_lbl = isset($intent_labels[$e['intent']]) ? $intent_labels[$e['intent']] : esc_html($e['intent']);
                        $est        = (int) ($e['prompt_tokens_est'] ?? 0);
                        $real_in    = $e['prompt_tokens_real'] ?? null;
                        $real_out   = $e['output_tokens']      ?? null;
                        $real_tot   = $e['total_tokens']       ?? null;

                        // Semaforo qualità basato su token stimati
                        if ($est < 2000)       { $quality = '🟢'; $quality_title = 'Ottimale (<2K)'; }
                        elseif ($est < 5000)   { $quality = '🟡'; $quality_title = 'Accettabile (2-5K)'; }
                        else                   { $quality = '🔴'; $quality_title = 'Alto (>5K)'; }
                        ?>
                        <tr>
                            <td style="font-size:11px;"><?php echo date('d/m H:i:s', (int)$e['time']); ?></td>
                            <td><?php echo $intent_lbl; ?></td>
                            <td style="text-align:right;"><?php echo number_format((int)($e['prompt_bytes'] ?? 0)); ?></td>
                            <td style="text-align:right; font-weight:600;"><?php echo number_format($est); ?></td>
                            <?php if ($has_real): ?>
                            <td style="text-align:right;"><?php echo !is_null($real_in)  ? number_format((int)$real_in)  : '<span style="color:#aaa">—</span>'; ?></td>
                            <td style="text-align:right;"><?php echo !is_null($real_out) ? number_format((int)$real_out) : '<span style="color:#aaa">—</span>'; ?></td>
                            <td style="text-align:right;"><?php echo !is_null($real_tot) ? number_format((int)$real_tot) : '<span style="color:#aaa">—</span>'; ?></td>
                            <?php endif; ?>
                            <td style="text-align:center;" title="<?php echo esc_attr($quality_title); ?>"><?php echo $quality; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f0f0f0; font-weight:bold;">
                            <td colspan="2">TOTALE (<?php echo $tot_messages; ?> msg)</td>
                            <td style="text-align:right;"><?php echo number_format($tot_bytes); ?></td>
                            <td style="text-align:right;"><?php echo number_format($tot_est); ?></td>
                            <?php if ($has_real): ?>
                            <td style="text-align:right;"><?php echo number_format($tot_real_in); ?></td>
                            <td style="text-align:right;"><?php echo number_format($tot_real_out); ?></td>
                            <td style="text-align:right;"><?php echo number_format($tot_real_total); ?></td>
                            <?php endif; ?>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#marrison-reset-token-log').on('click', function() {
                if (!confirm('Azzerare il log token? I dati non saranno recuperabili.')) return;
                var $btn = $(this).prop('disabled', true).text('Resetting…');
                $.post(ajaxurl, {
                    action: 'marrison_reset_token_log',
                    nonce:  marrisonAdmin.nonce
                })
                .done(function(r) {
                    if (r.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + (r.data || 'sconosciuto'));
                        $btn.prop('disabled', false).html('🗑️ Reset log');
                    }
                })
                .fail(function() {
                    alert('Errore di rete.');
                    $btn.prop('disabled', false).html('🗑️ Reset log');
                });
            });
        });
        </script>
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
            
            // Debug eventi
            $('#debug-events-btn').click(function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $result = $('#debug-events-result');
                $btn.prop('disabled', true);
                $result.show().html('<em>Analisi in corso...</em>');
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'marrison_debug_events',
                        nonce: '<?php echo wp_create_nonce('marrison_test_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html(response.data);
                        } else {
                            $result.html('<span style="color:red;">Errore: ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        $result.html('<span style="color:red;">Errore di connessione.</span>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
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
