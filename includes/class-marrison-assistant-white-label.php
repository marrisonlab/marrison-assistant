<?php
/**
 * Gestione branding white-label.
 *
 * Legge white-label.json dalla root del plugin.
 * I campi vuoti ("") usano i valori predefiniti Marrison.
 * La versione, lo slug, il meccanismo di aggiornamento restano invariati.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_White_Label {

    private static $config  = null;
    private static $loaded  = false;

    // ── Lettura config ────────────────────────────────────────────────

    private static function load() {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $file = MARRISON_ASSISTANT_PLUGIN_DIR . 'white-label.json';
        error_log('Marrison Assistant White Label: Looking for file at ' . $file);
        
        if (!file_exists($file)) {
            error_log('Marrison Assistant White Label: File not found');
            self::$config = array();
            return;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            error_log('Marrison Assistant White Label: Failed to read file');
            self::$config = array();
            return;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Marrison Assistant White Label: JSON error - ' . json_last_error_msg());
            self::$config = array();
            return;
        }
        
        self::$config = is_array($data) ? $data : array();
        error_log('Marrison Assistant White Label: Loaded config - ' . json_encode(self::$config));
    }

    /**
     * Restituisce un valore dal file white-label, o $default se assente/vuoto.
     */
    public static function get($key, $default = '') {
        self::load();
        $val = isset(self::$config[$key]) ? trim((string) self::$config[$key]) : '';
        return ($val !== '' && $key !== '_note') ? $val : $default;
    }

    // ── Accessori tipizzati ───────────────────────────────────────────

    public static function plugin_name() {
        return self::get('plugin_name', 'Marrison Assistant');
    }

    public static function author() {
        return self::get('author', 'Marrisonlab');
    }

    public static function author_url() {
        return self::get('author_url', 'https://marrisonlab.com');
    }

    public static function powered_by_text() {
        return self::get('powered_by_text', 'Powered by Marrisonlab');
    }

    public static function powered_by_url() {
        return self::get('powered_by_url', 'https://marrisonlab.com');
    }

    /**
     * URL assoluto del logo white-label, oppure '' se non configurato.
     * Il campo "logo" può essere:
     *   - URL completo (https://...)
     *   - nome file relativo alla cartella white-label/ del plugin (es. "logo.png")
     */
    public static function logo_url() {
        $logo = self::get('logo', '');
        if ($logo === '') {
            return '';
        }
        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            return $logo;
        }
        return plugins_url('white-label/' . ltrim($logo, '/'), MARRISON_ASSISTANT_PLUGIN_DIR . 'marrison-assistant.php');
    }

    /**
     * True se almeno un campo branding è stato personalizzato.
     */
    public static function is_active() {
        self::load();
        foreach (array('plugin_name', 'author', 'powered_by_text', 'logo') as $k) {
            if (self::get($k, '') !== '') {
                return true;
            }
        }
        return false;
    }
}
