<?php
/**
 * Eseguito automaticamente da WordPress quando il plugin viene eliminato.
 * Rimuove tutte le opzioni, i file JSON scansionati e il cron schedulato.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// ── Opzioni WordPress ──────────────────────────────────────────────────────
$options = array(
    'marrison_assistant_gemini_api_key',
    'marrison_assistant_custom_prompt',
    'marrison_assistant_logged_only',
    'marrison_assistant_last_content_scan',
    'marrison_assistant_site_content',
    'marrison_assistant_token_log',
    'marrison_assistant_working_model',
    'marrison_assistant_commander_url',
    // Agente sito
    'marrison_assistant_enable_site_agent',
    'marrison_assistant_site_agent_position',
    'marrison_assistant_site_agent_color',
    'marrison_assistant_site_agent_title',
    'marrison_assistant_site_agent_name',
    'marrison_assistant_site_agent_welcome',
    'marrison_assistant_site_agent_placeholder',
    'marrison_assistant_site_agent_logged_only',
    'marrison_assistant_site_agent_icon_color',
    'marrison_assistant_site_agent_header_color',
    'marrison_assistant_site_agent_button_color',
    'marrison_assistant_site_agent_response_products',
    'marrison_assistant_site_agent_response_orders',
    'marrison_assistant_site_agent_response_info',
    'marrison_assistant_site_agent_response_events',
);

foreach ($options as $option) {
    delete_option($option);
}

// ── Cron schedulato ────────────────────────────────────────────────────────
wp_clear_scheduled_hook('marrison_assistant_auto_scan');

// ── File JSON scansionati in uploads/marrison-assistant/ ───────────────────
$upload_dir = wp_upload_dir();

// Procedi solo se wp_upload_dir() non ha riportato errori
if (empty($upload_dir['error'])) {
    $data_dir = realpath($upload_dir['basedir']) . DIRECTORY_SEPARATOR . 'marrison-assistant' . DIRECTORY_SEPARATOR;

    if (is_dir($data_dir)) {
        $files = glob($data_dir . '*.json');
        if (is_array($files)) {
            foreach ($files as $file) {
                // Sicurezza: verifica che il file sia realmente dentro $data_dir
                $real_file = realpath($file);
                if ($real_file !== false && strpos($real_file, realpath($data_dir)) === 0) {
                    unlink($real_file);
                }
            }
        }
        // rmdir rimuove la dir solo se vuota; fallisce silenziosamente altrimenti
        @rmdir($data_dir);
    }
}
