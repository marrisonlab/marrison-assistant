<?php
/**
 * Pannello di controllo principale del plugin (singola pagina, no tab)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Main_Page {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registra tutte le impostazioni sotto un unico gruppo
     */
    public function register_settings() {
        $all = array(
            'marrison_assistant_gemini_api_key',
            'marrison_assistant_custom_prompt',
            'marrison_assistant_enable_site_agent',
            'marrison_assistant_site_agent_logged_only',
            'marrison_assistant_site_agent_position',
            'marrison_assistant_site_agent_title',
            'marrison_assistant_site_agent_name',
            'marrison_assistant_site_agent_welcome',
            'marrison_assistant_site_agent_placeholder',
            'marrison_assistant_site_agent_icon_color',
            'marrison_assistant_site_agent_header_color',
            'marrison_assistant_site_agent_button_color',
            'marrison_assistant_site_agent_response_products',
            'marrison_assistant_site_agent_response_orders',
            'marrison_assistant_site_agent_response_info',
            'marrison_assistant_site_agent_response_events',
            'marrison_assistant_enable_custom_prompt',
            // NOTA: marrison_assistant_gemini_api_key rimosso — API key gestita dal Commander
        );
        foreach ($all as $opt) {
            register_setting('marrison_assistant_panel', $opt);
        }
    }
    
    /**
     * Renderizza il pannello di controllo unico
     */
    public function render() {
        // ── Stato Servizio AI ──────────────────────────────────────────
        $cmd_transient = get_transient('marrison_commander_online');
        if ($cmd_transient === false) {
            $resp = wp_remote_get('https://marrisonlab.com/wp-json/marrison-commander/v1/', array(
                'timeout' => 4, 'sslverify' => false,
            ));
            $cmd_transient = (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) < 500) ? 'yes' : 'no';
            set_transient('marrison_commander_online', $cmd_transient, 5 * MINUTE_IN_SECONDS);
        }
        $cmd_ok = ($cmd_transient === 'yes');

        // ── Stato collegamento sito (aggiornato dai response reali di call_commander) ──
        $site_connected = get_transient('marrison_site_connected'); // 'yes' | 'no' | false
        // false = mai testato (nessuna chiamata AI ancora effettuata)

        $agent_on  = (bool) get_option('marrison_assistant_enable_site_agent');
        $last_scan = get_option('marrison_assistant_last_content_scan');

        // Colori LED
        $led_cmd  = $cmd_ok  ? 'led-green' : 'led-red';
        $led_site = ($site_connected === 'yes') ? 'led-green' : (($site_connected === 'no') ? 'led-red' : 'led-yellow');
        $led_wid  = $agent_on ? 'led-green' : 'led-grey';

        $txt_cmd  = $cmd_ok  ? 'Online' : 'Non raggiungibile';
        $txt_site = ($site_connected === 'yes') ? 'Collegato' : (($site_connected === 'no') ? 'Non collegato' : 'Non verificato');
        $txt_wid  = $agent_on ? 'Attivo' : 'Disattivo';

        $prompt_enabled = (bool) get_option('marrison_assistant_enable_custom_prompt', 0);
        ?>
        <div class="wrap" id="ma-panel">

        <style>
        #ma-panel { max-width:980px; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }

        /* ── Top bar ── */
        #ma-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 0 20px; border-bottom:1px solid #dcdcde; margin-bottom:24px; }
        #ma-topbar h1 { margin:0; padding:0; font-size:20px; font-weight:700; color:#1d2327; }
        #ma-status-group { display:flex; gap:8px; flex-wrap:wrap; }
        .ma-pill { display:inline-flex; align-items:center; gap:7px; padding:6px 14px; border-radius:100px; background:#f6f7f7; border:1px solid #dcdcde; font-size:12px; font-weight:600; color:#3c434a; }
        .ma-pill .led { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
        .led-green  { background:#00a32a; box-shadow:0 0 0 3px #00a32a22; }
        .led-red    { background:#d63638; box-shadow:0 0 0 3px #d6363822; }
        .led-yellow { background:#dba617; box-shadow:0 0 0 3px #dba61722; }
        .led-grey   { background:#8c8f94; }

        /* ── Cards ── */
        .ma-section { margin-bottom:20px; }
        .ma-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .ma-row.thirds { grid-template-columns:1fr 1fr 1fr; }
        @media(max-width:780px){ .ma-row,.ma-row.thirds { grid-template-columns:1fr; } }
        .ma-card { background:#fff; border:1px solid #dcdcde; border-radius:8px; overflow:hidden; }
        .ma-card-title { display:flex; align-items:center; gap:9px; padding:13px 18px; background:#f6f7f7; border-bottom:1px solid #dcdcde; }
        .ma-card-title .dashicons { font-size:16px; width:16px; height:16px; color:#50575e; flex-shrink:0; }
        .ma-card-title strong { font-size:13px; color:#1d2327; }
        .ma-card-body { padding:18px; }

        /* ── Form fields ── */
        .maf { margin-bottom:14px; }
        .maf:last-child { margin-bottom:0; }
        .maf > label { display:block; font-size:12px; font-weight:600; color:#50575e; margin-bottom:5px; letter-spacing:.02em; }
        .maf input[type=text],.maf input[type=url],.maf input[type=password],.maf textarea,.maf select { width:100%; box-sizing:border-box; }
        .maf .hint { font-size:11px; color:#8c8f94; margin-top:4px; line-height:1.5; }

        /* ── Toggles ── */
        .ma-toggle { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid #f0f0f1; }
        .ma-toggle:last-of-type { border-bottom:none; }
        .ma-toggle label { font-size:13px; color:#1d2327; flex:1; cursor:pointer; margin:0; }

        /* ── Color strip ── */
        .ma-colors { display:flex; gap:0; }
        .ma-color-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; padding:12px 8px; border-right:1px solid #f0f0f1; }
        .ma-color-item:last-child { border-right:none; }
        .ma-color-item span { font-size:11px; color:#50575e; text-align:center; }
        .ma-color-item input[type=color] { width:44px; height:36px; padding:2px; border:1px solid #c3c4c7; border-radius:6px; cursor:pointer; }

        /* ── Category responses ── */
        .ma-cat-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media(max-width:600px){ .ma-cat-grid { grid-template-columns:1fr; } }
        .ma-cat-item label { font-size:12px; font-weight:600; color:#50575e; display:block; margin-bottom:4px; }
        .ma-cat-item input { width:100%; box-sizing:border-box; }

        /* ── Scan bar ── */
        .ma-scan-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .ma-scan-meta { font-size:12px; color:#50575e; line-height:1.7; }
        .ma-scan-meta strong { color:#1d2327; }

        /* ── Save ── */
        .ma-save { padding:16px 0 4px; }
        </style>

        <!-- Top bar -->
        <div id="ma-topbar">
            <h1><?php echo esc_html(Marrison_Assistant_White_Label::plugin_name()); ?></h1>
            <div id="ma-status-group">
                <span class="ma-pill"><span class="led <?php echo $led_cmd; ?>"></span>Servizio: <?php echo $txt_cmd; ?></span>
                <span class="ma-pill"><span class="led <?php echo $led_site; ?>"></span>Sito: <?php echo $txt_site; ?></span>
                <span class="ma-pill"><span class="led <?php echo $led_wid; ?>"></span>Widget: <?php echo $txt_wid; ?></span>
            </div>
        </div>

        <?php if ($site_connected === false && $cmd_ok): ?>
        <div class="notice notice-info inline" style="margin-bottom:20px;"><p>
            <strong>Connessione non ancora verificata.</strong>
            Lo stato si aggiornerà automaticamente dopo la prima conversazione dell'assistente con un utente.
        </p></div>
        <?php elseif ($site_connected === 'no'): ?>
        <div class="notice notice-error inline" style="margin-bottom:20px;"><p>
            <strong>Sito non collegato al servizio AI.</strong>
            Contatta il supporto per verificare che questo sito sia autorizzato.
        </p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('marrison_assistant_panel'); ?>

            <!-- Riga 1: Comportamento AI + Impostazioni Widget -->
            <div class="ma-row">

                <!-- Comportamento AI -->
                <div class="ma-card">
                    <div class="ma-card-title">
                        <span class="dashicons dashicons-admin-customizer"></span>
                        <strong>Comportamento Assistente</strong>
                    </div>
                    <div class="ma-card-body">
                        <div class="ma-toggle" style="margin-bottom:10px;">
                            <input type="checkbox"
                                   id="marrison_assistant_enable_custom_prompt"
                                   name="marrison_assistant_enable_custom_prompt"
                                   value="1"
                                   <?php checked($prompt_enabled, true); ?>>
                            <label for="marrison_assistant_enable_custom_prompt">Abilita prompt personalizzato</label>
                        </div>
                        <div class="maf">
                            <textarea id="marrison_assistant_custom_prompt"
                                      name="marrison_assistant_custom_prompt"
                                      rows="7"
                                      <?php echo $prompt_enabled ? '' : 'disabled'; ?>
                                      style="opacity:<?php echo $prompt_enabled ? '1' : '.4'; ?>;transition:opacity .2s;"><?php echo esc_textarea(get_option('marrison_assistant_custom_prompt', 'Sei un assistente AI per questo sito web. Rispondi in modo professionale e utile basandoti sui contenuti del sito.')); ?></textarea>
                            <div class="hint">Se disabilitato, l'assistente usa le istruzioni predefinite del servizio.</div>
                        </div>
                    </div>
                </div>

                <!-- Widget -->
                <div class="ma-card">
                    <div class="ma-card-title">
                        <span class="dashicons dashicons-format-chat"></span>
                        <strong>Impostazioni Widget</strong>
                    </div>
                    <div class="ma-card-body">
                        <div class="ma-toggle">
                            <input type="checkbox" id="marrison_assistant_enable_site_agent"
                                   name="marrison_assistant_enable_site_agent" value="1"
                                   <?php checked(get_option('marrison_assistant_enable_site_agent'), 1); ?>>
                            <label for="marrison_assistant_enable_site_agent">Mostra widget chat sul sito</label>
                        </div>
                        <div class="ma-toggle" style="margin-bottom:14px;">
                            <input type="checkbox" id="marrison_assistant_site_agent_logged_only"
                                   name="marrison_assistant_site_agent_logged_only" value="1"
                                   <?php checked(get_option('marrison_assistant_site_agent_logged_only'), 1); ?>>
                            <label for="marrison_assistant_site_agent_logged_only">Solo utenti registrati</label>
                        </div>
                        <div class="maf">
                            <label for="marrison_assistant_site_agent_position">Posizione</label>
                            <select id="marrison_assistant_site_agent_position" name="marrison_assistant_site_agent_position">
                                <option value="bottom-right" <?php selected(get_option('marrison_assistant_site_agent_position','bottom-right'),'bottom-right'); ?>>Basso destra</option>
                                <option value="bottom-left"  <?php selected(get_option('marrison_assistant_site_agent_position','bottom-right'),'bottom-left'); ?>>Basso sinistra</option>
                                <option value="top-right"    <?php selected(get_option('marrison_assistant_site_agent_position','bottom-right'),'top-right'); ?>>Alto destra</option>
                                <option value="top-left"     <?php selected(get_option('marrison_assistant_site_agent_position','bottom-right'),'top-left'); ?>>Alto sinistra</option>
                            </select>
                        </div>
                        <div class="maf">
                            <label for="marrison_assistant_site_agent_title">Titolo finestra</label>
                            <input type="text" id="marrison_assistant_site_agent_title"
                                   name="marrison_assistant_site_agent_title"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_title','Assistente AI')); ?>">
                        </div>
                        <div class="maf">
                            <label for="marrison_assistant_site_agent_name">Nome assistente</label>
                            <input type="text" id="marrison_assistant_site_agent_name"
                                   name="marrison_assistant_site_agent_name"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_name','Marry')); ?>">
                        </div>
                        <div class="maf">
                            <label for="marrison_assistant_site_agent_welcome">Messaggio benvenuto</label>
                            <textarea id="marrison_assistant_site_agent_welcome"
                                      name="marrison_assistant_site_agent_welcome"
                                      rows="2"><?php echo esc_textarea(get_option('marrison_assistant_site_agent_welcome','Ciao! Come posso aiutarti oggi?')); ?></textarea>
                        </div>
                        <div class="maf">
                            <label for="marrison_assistant_site_agent_placeholder">Placeholder input</label>
                            <input type="text" id="marrison_assistant_site_agent_placeholder"
                                   name="marrison_assistant_site_agent_placeholder"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_placeholder','Scrivi un messaggio...')); ?>">
                        </div>
                    </div>
                </div>

            </div><!-- /ma-row -->

            <!-- Riga 2: Colori + Risposte Categorie -->
            <div class="ma-row">

                <!-- Colori -->
                <div class="ma-card">
                    <div class="ma-card-title">
                        <span class="dashicons dashicons-art"></span>
                        <strong>Colori Widget</strong>
                    </div>
                    <div class="ma-colors">
                        <div class="ma-color-item">
                            <span>Icona<br>fluttuante</span>
                            <input type="color" name="marrison_assistant_site_agent_icon_color"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_icon_color','#667eea')); ?>">
                        </div>
                        <div class="ma-color-item">
                            <span>Testata<br>chat</span>
                            <input type="color" name="marrison_assistant_site_agent_header_color"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_header_color','#667eea')); ?>">
                        </div>
                        <div class="ma-color-item">
                            <span>Pulsante<br>invio</span>
                            <input type="color" name="marrison_assistant_site_agent_button_color"
                                   value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_button_color','#667eea')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Risposte Categorie -->
                <div class="ma-card">
                    <div class="ma-card-title">
                        <span class="dashicons dashicons-category"></span>
                        <strong>Risposte Bottoni Categoria</strong>
                    </div>
                    <div class="ma-card-body">
                        <div class="ma-cat-grid">
                            <div class="ma-cat-item">
                                <label>🛍 Prodotti</label>
                                <input type="text" name="marrison_assistant_site_agent_response_products"
                                       value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_products','Perfetto! Dimmi cosa stai cercando tra i nostri prodotti.')); ?>">
                            </div>
                            <div class="ma-cat-item">
                                <label>📦 Ordini</label>
                                <input type="text" name="marrison_assistant_site_agent_response_orders"
                                       value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_orders','Certo! Dimmi il numero ordine o cosa vorresti sapere sul tuo acquisto.')); ?>">
                            </div>
                            <div class="ma-cat-item">
                                <label>ℹ️ Info</label>
                                <input type="text" name="marrison_assistant_site_agent_response_info"
                                       value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_info','Con piacere! Su cosa vorresti informazioni? Azienda, contatti, servizi?')); ?>">
                            </div>
                            <div class="ma-cat-item">
                                <label>📅 Eventi</label>
                                <input type="text" name="marrison_assistant_site_agent_response_events"
                                       value="<?php echo esc_attr(get_option('marrison_assistant_site_agent_response_events','Ottimo! Stai cercando un evento specifico o vuoi vedere il calendario?')); ?>">
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /ma-row -->

            <div class="ma-save">
                <?php submit_button('Salva impostazioni', 'primary', 'submit', false); ?>
            </div>

        </form>

        <!-- Scansione contenuti — fuori dal form -->
        <div class="ma-card ma-section">
            <div class="ma-card-title">
                <span class="dashicons dashicons-search"></span>
                <strong>Scansione Contenuti Sito</strong>
            </div>
            <div class="ma-card-body">
                <div class="ma-scan-row">
                    <button type="button" id="scan-content-btn" class="button button-primary">
                        <span class="dashicons dashicons-update" style="vertical-align:text-bottom;margin-right:4px;"></span>Scansiona ora
                    </button>
                    <span id="scan-status"></span>
                    <div class="ma-scan-meta">
                        Ultima scansione: <strong><?php echo $last_scan ? date_i18n('d/m/Y H:i', $last_scan) : '—'; ?></strong>
                        &nbsp;·&nbsp;
                        Prossima automatica: <strong><?php
                            $next = wp_next_scheduled('marrison_assistant_auto_scan');
                            echo $next ? 'tra ' . human_time_diff(time(), $next) : 'N/D';
                        ?></strong>
                    </div>
                </div>
                <div id="scan-results" style="display:none;margin-top:14px;">
                    <div id="scan-details"></div>
                </div>
            </div>
        </div>

        </div><!-- /wrap #ma-panel -->
        <?php
    }

}

// JavaScript per la scansione contenuti
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'marrison-assistant') === false) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Toggle prompt personalizzato
        var $promptToggle   = $('#marrison_assistant_enable_custom_prompt');
        var $promptTextarea = $('#marrison_assistant_custom_prompt');

        $promptToggle.on('change', function() {
            if (this.checked) {
                var confirmed = window.confirm(
                    '⚠️ Attenzione\n\n' +
                    'Un prompt personalizzato aggiunge testo extra ad ogni richiesta, ' +
                    'aumentando il consumo di token.\n\n' +
                    'Questo può ridurre il numero di conversazioni disponibili nel tuo piano.\n\n' +
                    'Vuoi abilitarlo comunque?'
                );
                if (!confirmed) {
                    this.checked = false;
                    return;
                }
            }
            var isEnabled = this.checked;
            $promptTextarea.prop('disabled', !isEnabled).css('opacity', isEnabled ? '1' : '.45');
        });

        $('#scan-content-btn').on('click', function() {
            var $btn    = $(this);
            var $status = $('#scan-status');
            var $res    = $('#scan-results');
            $btn.prop('disabled', true);
            $status.html('<span style="color:#f0b429;">⏳ Scansione in corso…</span>');
            $res.hide();
            $.ajax({
                url: ajaxurl, method: 'POST',
                data: { action: 'marrison_scan_site_content', nonce: '<?php echo wp_create_nonce('marrison_nonce'); ?>' },
                success: function(r) {
                    if (r.success) {
                        $status.html('<span style="color:#00a32a;">✔ Completata</span>');
                        $('#scan-details').html(r.data);
                        $res.show();
                        setTimeout(function(){ location.reload(); }, 2000);
                    } else {
                        $status.html('<span style="color:#d63638;">✘ ' + r.data + '</span>');
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    $status.html('<span style="color:#d63638;">✘ Errore di connessione</span>');
                    $btn.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
});

/* end of file */
