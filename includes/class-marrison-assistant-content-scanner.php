<?php
/**
 * Classe per la scansione dei contenuti del sito
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Content_Scanner {
    
    /**
     * Scansiona tutti i contenuti del sito
     * Ora salva JSON separati per tipo per ottimizzare le performance
     */
    public function scan_all_content() {
        $content = array();

        // Scansiona pagine
        $pages = $this->scan_pages();
        if (!empty($pages)) {
            $content['pages'] = $pages;
            $this->save_content_file('pages', $pages);
        }

        // Scansiona articoli
        $posts = $this->scan_posts();
        if (!empty($posts)) {
            $content['posts'] = $posts;
            $this->save_content_file('posts', $posts);
        }

        // Scansiona prodotti WooCommerce se attivo
        if (class_exists('WooCommerce')) {
            $products = $this->scan_products();
            if (!empty($products)) {
                $content['products'] = $products;
                $this->save_content_file('products', $products);
            }

            // Scansiona ordini
            $orders = $this->scan_orders();
            if (!empty($orders)) {
                $content['orders'] = $orders;
                $this->save_content_file('orders', $orders);
            }

            // Scansiona impostazioni spedizione
            $shipping = $this->scan_shipping();
            if (!empty($shipping)) {
                $content['shipping'] = $shipping;
                $this->save_content_file('shipping', $shipping);
            }
        }

        // Scansiona eventi (The Events Calendar, MEC, ecc.)
        $events = $this->scan_events();
        if (!empty($events)) {
            $content['events'] = $events;
            $this->save_content_file('events', $events);
        }

        // Salva timestamp ultima scansione
        update_option('marrison_assistant_last_content_scan', time());

        return $content;
    }

    /**
     * Ottiene il percorso base per i file JSON (con fallback)
     */
    public function get_data_directory() {
        // Tentativo 1: Directory uploads di WordPress
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/marrison-assistant';

        // Se non scrivibile, usa fallback nella directory plugin
        if (!is_dir($base_dir) && !wp_mkdir_p($base_dir)) {
            // Tentativo 2: Directory del plugin (wp-content/plugins/marrison-assistant/data/)
            $plugin_dir = MARRISON_ASSISTANT_PLUGIN_DIR . 'data';
            if (!file_exists($plugin_dir)) {
                wp_mkdir_p($plugin_dir);
            }
            if (is_writable($plugin_dir)) {
                $base_dir = $plugin_dir;
            }
        }

        return $base_dir;
    }

    /**
     * Wrapper pubblico per save_content_file (usato da ajax_scan_site_content)
     */
    public function save_content_file_public($type, $data) {
        return $this->save_content_file($type, $data);
    }

    /**
     * Salva contenuti in file JSON separati
     */
    private function save_content_file($type, $data) {
        $base_dir = $this->get_data_directory();

        error_log('Marrison Assistant: Tentativo salvataggio ' . $type . ' in ' . $base_dir);

        // Crea directory se non esiste
        if (!file_exists($base_dir)) {
            $created = wp_mkdir_p($base_dir);
            error_log('Marrison Assistant: Creazione directory ' . ($created ? 'RIUSCITA' : 'FALLITA') . ' - ' . $base_dir);

            if (!$created) {
                error_log('Marrison Assistant: IMPOSSIBILE creare directory: ' . $base_dir);
                return false;
            }

            // Proteggi directory con .htaccess
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            @file_put_contents($base_dir . '/.htaccess', $htaccess_content);
        }

        // Verifica scrivibilità
        if (!is_writable($base_dir)) {
            error_log('Marrison Assistant: ERRORE - Directory non scrivibile: ' . $base_dir);
            @chmod($base_dir, 0755);
            if (!is_writable($base_dir)) {
                error_log('Marrison Assistant: IMPOSSIBILE scrivere nella directory: ' . $base_dir);
                return false;
            }
        }

        $file_path = $base_dir . '/' . $type . '.json';
        $json = wp_json_encode($data, JSON_PRETTY_PRINT);

        // Test: scrivi e verifica immediatamente
        $bytes_written = file_put_contents($file_path, $json, LOCK_EX);

        if ($bytes_written === false) {
            error_log('Marrison Assistant: ERRORE salvataggio ' . $type . '.json - scrittura fallita');
            return false;
        }

        // Verifica che il file esista e sia leggibile
        if (!file_exists($file_path)) {
            error_log('Marrison Assistant: ERRORE - File non trovato dopo scrittura: ' . $file_path);
            return false;
        }

        $file_size = filesize($file_path);
        error_log('Marrison Assistant: Salvato ' . $type . '.json - Scritti: ' . $bytes_written . ' bytes, Filesize: ' . $file_size . ' bytes, Path: ' . $file_path);

        return $bytes_written;
    }

    /**
     * Carica contenuti da file JSON specifico
     */
    public function load_content_file($type) {
        // Prova prima nella directory primaria
        $upload_dir = wp_upload_dir();
        $primary_path = $upload_dir['basedir'] . '/marrison-assistant/' . $type . '.json';

        if (file_exists($primary_path)) {
            $json = file_get_contents($primary_path);
            return json_decode($json, true);
        }

        // Prova nella directory di fallback (plugin data/)
        $fallback_path = MARRISON_ASSISTANT_PLUGIN_DIR . 'data/' . $type . '.json';
        if (file_exists($fallback_path)) {
            $json = file_get_contents($fallback_path);
            return json_decode($json, true);
        }

        return null;
    }

    /**
     * Verifica se un file JSON esiste (in uploads o in fallback)
     */
    public function content_file_exists($type) {
        $upload_dir = wp_upload_dir();
        $primary_path = $upload_dir['basedir'] . '/marrison-assistant/' . $type . '.json';
        $fallback_path = MARRISON_ASSISTANT_PLUGIN_DIR . 'data/' . $type . '.json';

        return file_exists($primary_path) || file_exists($fallback_path);
    }

    /**
     * Verifica se un tipo di contenuto ha effettivamente elementi nel file JSON.
     * Legge solo i primi 256 byte per velocità.
     */
    public function has_content($type) {
        $upload_dir   = wp_upload_dir();
        $primary_path = $upload_dir['basedir'] . '/marrison-assistant/' . $type . '.json';
        $path = file_exists($primary_path) ? $primary_path
              : MARRISON_ASSISTANT_PLUGIN_DIR . 'data/' . $type . '.json';

        if (!file_exists($path) || filesize($path) < 5) return false;
        $head = file_get_contents($path, false, null, 0, 256);
        // Il file ha elementi se inizia con [{  (array non vuoto)
        return (bool) preg_match('/^\s*\[\s*\{/', $head);
    }

    /**
     * RAG semplificato: restituisce solo i contenuti rilevanti per intent + query.
     * Riduce il contesto da ~3MB a <10KB prima di chiamare Gemini.
     *
     * @param  string $intent       products|orders|info|events|general
     * @param  string $search_query messaggio originale dell'utente
     * @return array  array associativo [ 'products'=>[...], 'pages'=>[...], ... ]
     */
    public function get_context_by_intent($intent, $search_query = '', $user_email = '') {
        $keywords = $this->extract_keywords($search_query);
        $result   = array();

        switch ($intent) {

            case 'products':
                $items = $this->load_content_file('products');
                if ($items === null) {
                    $legacy = get_option('marrison_assistant_site_content', array());
                    $items  = !empty($legacy['products']) ? $legacy['products'] : array();
                }
                // fallback_all=true: se nessuna keyword matcha, torna i primi 10 prodotti
                $result['products'] = $this->filter_items_by_keywords(
                    $items, $keywords, array('title', 'description', 'short_description'), 6, true
                );
                break;

            case 'orders':
                $all_orders = $this->load_content_file('orders') ?? array();
                // SICUREZZA: filtra SOLO gli ordini dell'utente loggato
                if (!empty($user_email)) {
                    $all_orders = array_values( array_filter( $all_orders, function($o) use ($user_email) {
                        return isset($o['customer_email']) && strtolower(trim($o['customer_email'])) === strtolower(trim($user_email));
                    }));
                } else {
                    // Senza email verificata non mostrare nessun ordine
                    $all_orders = array();
                }
                // fallback_all=false: mai restituire ordini a caso se la keyword non matcha
                $result['orders'] = $this->filter_items_by_keywords(
                    $all_orders, $keywords, array('number', 'customer_name', 'customer_email'), 10, false
                );
                break;

            case 'events':
                $events = $this->load_content_file('events') ?? array();
                $result['events'] = $this->filter_items_by_keywords(
                    $events, $keywords, array('title', 'content', 'excerpt'), 10, true
                );
                break;

            case 'info':
                $pages    = $this->load_content_file('pages');
                $posts    = $this->load_content_file('posts');
                $shipping = $this->load_content_file('shipping') ?? array();
                if ($pages === null && $posts === null) {
                    $legacy = get_option('marrison_assistant_site_content', array());
                    $pages  = !empty($legacy['pages']) ? $legacy['pages'] : array();
                    $posts  = !empty($legacy['posts']) ? $legacy['posts'] : array();
                }
                $result['pages']    = $this->filter_items_by_keywords(
                    $pages ?? array(), $keywords, array('title', 'content'), 5
                );
                $result['posts']    = $this->filter_items_by_keywords(
                    $posts ?? array(), $keywords, array('title', 'content'), 5
                );
                $result['shipping'] = $shipping;
                break;

            default: // general
                $pages    = $this->load_content_file('pages') ?? array();
                $posts    = $this->load_content_file('posts') ?? array();
                $products = $this->load_content_file('products') ?? array();
                $events   = $this->load_content_file('events') ?? array();
                $shipping = $this->load_content_file('shipping') ?? array();
                if (empty($pages) && empty($posts) && empty($products)) {
                    $legacy   = get_option('marrison_assistant_site_content', array());
                    $pages    = !empty($legacy['pages'])    ? $legacy['pages']    : array();
                    $posts    = !empty($legacy['posts'])    ? $legacy['posts']    : array();
                    $products = !empty($legacy['products']) ? $legacy['products'] : array();
                }
                $result['pages']    = $this->filter_items_by_keywords($pages,    $keywords, array('title', 'content'),      3, true);
                $result['posts']    = $this->filter_items_by_keywords($posts,    $keywords, array('title', 'content'),      3, true);
                $result['products'] = $this->filter_items_by_keywords($products, $keywords, array('title', 'description'), 5, true);
                $result['events']   = $this->filter_items_by_keywords($events,   $keywords, array('title', 'excerpt'),     5, false);
                $result['shipping'] = $shipping;
                break;
        }

        return $result;
    }

    /**
     * Estrae keyword significative dalla query (rimuove stopwords IT/EN e parole corte)
     */
    private function extract_keywords($query) {
        if (empty($query)) return array();

        $query = strtolower($query);
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);

        $stopwords = array(
            'il','lo','la','i','gli','le','un','uno','una','di','da','in','con','su','per',
            'tra','fra','e','o','ma','che','se','non','ho','hai','ha','mi','ti','si','ci',
            'vi','ne','del','della','dello','dei','degli','delle','al','allo','alla','ai',
            'agli','alle','nel','nello','nella','nei','negli','nelle','dal','dallo','dalla',
            'dai','dagli','dalle','sul','sullo','sulla','sui','sugli','sulle','come','sono',
            'cosa','questo','questa','questi','queste','quello','quella','quelli','quelle',
            'molto','poco','quale','quali','voglio','vorrei','cerco','dove','quando','quanto',
            'qui','già','ancora','sempre','anche','sì','no','the','and','for','with','this',
            'that','have','from','all','hai','stai','puoi','può','può','devo','fare',
        );

        $words = array_filter(explode(' ', $query), function ($w) use ($stopwords) {
            return strlen($w) >= 3 && !in_array($w, $stopwords);
        });

        return array_values(array_unique($words));
    }

    /**
     * Filtra array di item per keyword con scoring. Restituisce max $limit risultati.
     * Supporta stem matching italiano e cerca in attributi/varianti WooCommerce.
     * Se nessun match e $fallback_all=true, restituisce i primi $limit item senza filtro.
     *
     * @param  array   $items        array di item da filtrare
     * @param  array   $keywords     parole chiave estratte
     * @param  array   $fields       campi dell'item su cui cercare (il primo ha peso 3x)
     * @param  int     $limit        numero massimo di risultati
     * @param  bool    $fallback_all se true, torna i primi $limit item se nessun match
     */
    private function filter_items_by_keywords($items, $keywords, $fields, $limit, $fallback_all = false) {
        if (empty($items)) return array();

        // Senza keyword: restituisce i primi $limit senza filtrare
        if (empty($keywords)) {
            return array_slice($items, 0, $limit);
        }

        $scored = array();
        foreach ($items as $item) {
            $score = 0;

            // Cerca nei campi testo specificati
            foreach ($fields as $idx => $field) {
                if (empty($item[$field])) continue;
                $text   = strtolower(strip_tags($item[$field]));
                $weight = ($idx === 0) ? 3 : 1;
                foreach ($keywords as $kw) {
                    if ($this->keyword_matches($text, $kw)) {
                        $score += $weight;
                    }
                }
            }

            // Cerca anche in attributi e varianti WooCommerce (colore, taglia, ecc.)
            $attrs_text = $this->serialize_item_attributes($item);
            if (!empty($attrs_text)) {
                foreach ($keywords as $kw) {
                    if ($this->keyword_matches($attrs_text, $kw)) {
                        $score += 2;
                    }
                }
            }

            if ($score > 0) {
                $scored[] = array('item' => $item, 'score' => $score);
            }
        }

        if (empty($scored)) {
            return $fallback_all ? array_slice($items, 0, $limit) : array();
        }

        usort($scored, function ($a, $b) { return $b['score'] - $a['score']; });
        return array_column(array_slice($scored, 0, $limit), 'item');
    }

    /**
     * Verifica se una keyword corrisponde al testo.
     * Supporta stem matching per morfologia italiana:
     * es. "felpe" matcha "felpa", "rosse" matcha "rossa"/"rosso"
     */
    private function keyword_matches($text, $kw) {
        if (strpos($text, $kw) !== false) return true;
        // Stem: rimuove l'ultima lettera per gestire singolare/plurale/genere
        if (strlen($kw) > 4 && strpos($text, substr($kw, 0, -1)) !== false) return true;
        return false;
    }

    /**
     * Serializza attributi e varianti di un item WooCommerce in stringa ricercabile.
     * Gestisce sia attributi semplici (string) che multipli (array).
     */
    private function serialize_item_attributes($item) {
        $parts = array();
        foreach (array('attributes', 'available_options') as $key) {
            if (!empty($item[$key]) && is_array($item[$key])) {
                foreach ($item[$key] as $v) {
                    $parts[] = is_array($v) ? implode(' ', $v) : (string) $v;
                }
            }
        }
        return strtolower(implode(' ', $parts));
    }

    /**
     * Scansiona le pagine
     */
    public function scan_pages() {
        $pages = array();
        
        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $page_data = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'content' => wp_strip_all_tags(get_the_content()),
                    'excerpt' => get_the_excerpt(),
                    'url' => get_permalink(),
                    'type' => 'page'
                );
                
                $pages[] = $page_data;
            }
        }
        
        wp_reset_postdata();
        
        return $pages;
    }
    
    /**
     * Scansiona gli articoli
     */
    public function scan_posts() {
        $posts = array();
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $post_data = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'content' => wp_strip_all_tags(get_the_content()),
                    'excerpt' => get_the_excerpt(),
                    'url' => get_permalink(),
                    'date' => get_the_date('Y-m-d'),
                    'categories' => wp_get_post_categories(get_the_ID(), array('fields' => 'names')),
                    'type' => 'post'
                );
                
                $posts[] = $post_data;
            }
        }
        
        wp_reset_postdata();
        
        return $posts;
    }
    
    /**
     * Scansiona i prodotti WooCommerce
     */
    public function scan_products() {
        $products = array();
        
        if (!class_exists('WooCommerce')) {
            return $products;
        }
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $product = wc_get_product(get_the_ID());

                if (!$product) {
                    continue;
                }

                $product_data = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'description' => wp_strip_all_tags(get_the_content()),
                    'short_description' => $product->get_short_description(),
                    'url' => get_permalink(),
                    'price' => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'categories' => wp_get_post_terms(get_the_ID(), 'product_cat', array('fields' => 'names')),
                    'type' => $product->get_type(),
                    'stock_status' => $product->get_stock_status(),
                    'stock_quantity' => $product->get_stock_quantity(),
                    'sku' => $product->get_sku()
                );

                // Aggiungi immagine prodotto
                $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                if ($image_url) {
                    $product_data['image'] = $image_url;
                }

                // Attributi del prodotto (con nomi leggibili)
                $attributes = array();
                foreach ($product->get_attributes() as $attribute) {
                    $attr_name = wc_attribute_label($attribute->get_name());
                    if ($attribute->is_taxonomy()) {
                        $terms = wp_get_post_terms(get_the_ID(), $attribute->get_name(), array('fields' => 'names'));
                        $attributes[$attr_name] = implode(', ', $terms);
                    } else {
                        $values = $attribute->get_options();
                        $attributes[$attr_name] = implode(', ', $values);
                    }
                }
                if (!empty($attributes)) {
                    $product_data['attributes'] = $attributes;
                }

                // Per prodotti variabili, aggiungi variazioni complete
                if ($product->is_type('variable') && method_exists($product, 'get_available_variations')) {
                    $variations = array();
                    $variation_ids = $product->get_children();

                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if (!$variation) continue;

                        $var_data = array(
                            'id' => $variation_id,
                            'price' => $variation->get_price(),
                            'regular_price' => $variation->get_regular_price(),
                            'sale_price' => $variation->get_sale_price(),
                            'stock_status' => $variation->get_stock_status(),
                            'stock_quantity' => $variation->get_stock_quantity(),
                            'sku' => $variation->get_sku(),
                            'attributes' => array()
                        );

                        // Attributi specifici della variazione
                        $var_attrs = $variation->get_attributes();
                        foreach ($var_attrs as $attr_name => $attr_value) {
                            $label = wc_attribute_label($attr_name);
                            $var_data['attributes'][$label] = $attr_value;
                        }

                        $variations[] = $var_data;
                    }

                    if (!empty($variations)) {
                        $product_data['variations'] = $variations;
                        // Riepilogo disponibilità per attributo
                        $availability_summary = array();
                        foreach ($variations as $var) {
                            if ($var['stock_status'] === 'instock') {
                                foreach ($var['attributes'] as $attr_name => $attr_value) {
                                    if (!isset($availability_summary[$attr_name])) {
                                        $availability_summary[$attr_name] = array();
                                    }
                                    $availability_summary[$attr_name][] = $attr_value;
                                }
                            }
                        }
                        // Rimuovi duplicati
                        foreach ($availability_summary as $attr => $values) {
                            $availability_summary[$attr] = array_unique($values);
                        }
                        $product_data['available_options'] = $availability_summary;
                    }
                }

                $products[] = $product_data;
            }
        }
        
        wp_reset_postdata();
        
        return $products;
    }
    
    /**
     * Scansiona gli eventi (supporta The Events Calendar, MEC, FooEvents for WooCommerce, post generici con date)
     */
    public function scan_events() {
        $events    = array();
        $today     = date('Y-m-d');
        $now       = date('Y-m-d H:i:s');
        $found_any = false;

        // ── The Events Calendar (tribe_events) ──────────────────────
        if ( post_type_exists('tribe_events') ) {
            $found_any = true;
            $args = array(
                'post_type'      => 'tribe_events',
                'post_status'    => 'publish',
                'posts_per_page' => 50,
                'meta_key'       => '_EventStartDate',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'     => '_EventStartDate',
                        'value'   => $now,
                        'compare' => '>=',
                        'type'    => 'DATETIME',
                    ),
                ),
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $id         = get_the_ID();
                    $start      = get_post_meta($id, '_EventStartDate', true);
                    $end        = get_post_meta($id, '_EventEndDate', true);
                    $venue_id   = get_post_meta($id, '_EventVenueID', true);
                    $venue_name = $venue_id ? get_the_title($venue_id) : '';
                    $events[] = array(
                        'id'       => $id,
                        'title'    => get_the_title(),
                        'url'      => get_permalink(),
                        'start'    => $start,
                        'end'      => $end,
                        'venue'    => $venue_name,
                        'excerpt'  => get_the_excerpt(),
                        'type'     => 'event',
                        'source'   => 'tribe_events',
                    );
                }
            }
            wp_reset_postdata();
        }

        // ── Modern Events Calendar (mec-events) ─────────────────────
        if ( post_type_exists('mec-events') ) {
            $found_any = true;
            $args = array(
                'post_type'      => 'mec-events',
                'post_status'    => 'publish',
                'posts_per_page' => 50,
                'meta_key'       => 'mec_start_date',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'     => 'mec_start_date',
                        'value'   => $today,
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $id    = get_the_ID();
                    $start = get_post_meta($id, 'mec_start_date', true);
                    $end   = get_post_meta($id, 'mec_end_date', true);
                    $events[] = array(
                        'id'      => $id,
                        'title'   => get_the_title(),
                        'url'     => get_permalink(),
                        'start'   => $start,
                        'end'     => $end,
                        'excerpt' => get_the_excerpt(),
                        'type'    => 'event',
                        'source'  => 'mec-events',
                    );
                }
            }
            wp_reset_postdata();
        }

        // ── FooEvents for WooCommerce ────────────────────────────────
        // FooEvents salva gli eventi come prodotti WooCommerce con meta WooCommerceEventsEvent != ''
        // NON usiamo filtro data in SQL (il formato varia per versione): filtriamo in PHP con strtotime()
        if ( class_exists('WooCommerce') ) {
            $args = array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 100,
                'meta_query'     => array(
                    array(
                        'key'     => 'WooCommerceEventsEvent',
                        'value'   => '',
                        'compare' => '!=',
                    ),
                ),
            );
            $query = new WP_Query($args);
            error_log('Marrison Assistant [FooEvents]: query trovati ' . $query->found_posts . ' prodotti evento');

            if ($query->have_posts()) {
                $yesterday_ts = strtotime('-1 day');
                $foo_results  = array();

                while ($query->have_posts()) {
                    $query->the_post();
                    $id       = get_the_ID();
                    $date     = get_post_meta($id, 'WooCommerceEventsDate', true);
                    $end_date = get_post_meta($id, 'WooCommerceEventsEndDate', true);
                    $hour     = get_post_meta($id, 'WooCommerceEventsHour', true);
                    $minutes  = get_post_meta($id, 'WooCommerceEventsMinutes', true);
                    $ampm     = get_post_meta($id, 'WooCommerceEventsAmPm', true);
                    $location = get_post_meta($id, 'WooCommerceEventsLocation', true);

                    error_log('Marrison Assistant [FooEvents]: ID=' . $id . ' title="' . get_the_title() . '" date="' . $date . '"');

                    // Filtra eventi passati in PHP — flessibile con qualsiasi formato data
                    if ( !empty($date) ) {
                        $event_ts = strtotime($date);
                        if ( $event_ts !== false && $event_ts < $yesterday_ts ) {
                            continue; // evento passato, salta
                        }
                    }

                    $start = $date;
                    if ($hour) {
                        $start .= ' ' . $hour . ':' . str_pad($minutes ?: '0', 2, '0', STR_PAD_LEFT) . ' ' . $ampm;
                    }

                    // Prezzi: FooEvents è un prodotto WooCommerce, usa i meta standard
                    $price         = get_post_meta($id, '_price', true);
                    $regular_price = get_post_meta($id, '_regular_price', true);
                    $sale_price    = get_post_meta($id, '_sale_price', true);
                    $stock_status  = get_post_meta($id, '_stock_status', true);

                    // Formatta prezzo leggibile
                    $price_display = '';
                    if ( $price !== '' && $price !== false ) {
                        $price_display = number_format((float) $price, 2, ',', '.') . ' ' . get_woocommerce_currency_symbol();
                        if ( $sale_price !== '' && $sale_price !== false && $sale_price != $regular_price ) {
                            $price_display .= ' (scontato da ' . number_format((float) $regular_price, 2, ',', '.') . ' ' . get_woocommerce_currency_symbol() . ')';
                        }
                    }

                    // Posti: capacità e disponibilità FooEvents
                    $capacity      = get_post_meta($id, 'WooCommerceEventsCapacity', true);
                    $capacity_type = get_post_meta($id, 'WooCommerceEventsCapacityType', true);

                    $foo_results[] = array(
                        'id'           => $id,
                        'title'        => get_the_title(),
                        'url'          => get_permalink(),
                        'start'        => $start,
                        'end'          => $end_date ?: '',
                        'venue'        => $location,
                        'excerpt'      => get_the_excerpt(),
                        'price'        => $price_display,
                        'stock_status' => $stock_status === 'instock' ? 'disponibile' : ( $stock_status === 'outofstock' ? 'esaurito' : $stock_status ),
                        'capacity'     => $capacity ?: '',
                        'type'         => 'event',
                        'source'       => 'fooevents',
                    );
                }

                // Ordina per data crescente
                usort($foo_results, function($a, $b) {
                    return strtotime($a['start'] ?: '9999-12-31') - strtotime($b['start'] ?: '9999-12-31');
                });

                if (!empty($foo_results)) {
                    $found_any = true;
                    $events    = array_merge($events, $foo_results);
                    error_log('Marrison Assistant [FooEvents]: ' . count($foo_results) . ' eventi futuri aggiunti');
                }
            }
            wp_reset_postdata();
        }

        // ── Fallback: cerca post_type 'event' generico ───────────────
        if ( !$found_any && post_type_exists('event') ) {
            $args = array(
                'post_type'      => 'event',
                'post_status'    => 'publish',
                'posts_per_page' => 50,
                'orderby'        => 'date',
                'order'          => 'ASC',
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $id = get_the_ID();
                    $events[] = array(
                        'id'      => $id,
                        'title'   => get_the_title(),
                        'url'     => get_permalink(),
                        'start'   => get_the_date('Y-m-d'),
                        'excerpt' => get_the_excerpt(),
                        'type'    => 'event',
                        'source'  => 'event',
                    );
                }
            }
            wp_reset_postdata();
        }

        return $events;
    }

    /**
     * Ottiene la knowledge base formattata per Gemini
     */
    public function get_site_knowledge() {
        $content = get_option('marrison_assistant_site_content', array());
        
        if (empty($content)) {
            return '';
        }
        
        $knowledge = '';

        // Data corrente — fondamentale per distinguere eventi passati/futuri
        $knowledge .= "DATA E ORA CORRENTE: " . date_i18n('l j F Y, H:i', current_time('timestamp')) . "\n";
        $knowledge .= "Usa questa data come riferimento per distinguere eventi futuri da quelli passati.\n\n";
        
        // Aggiungi informazioni sulle pagine
        if (isset($content['pages']) && !empty($content['pages'])) {
            $knowledge .= "Pagine del sito:\n";
            foreach ($content['pages'] as $page) {
                $knowledge .= "- " . $page['title'] . "\n";
                $knowledge .= "  URL: " . $page['url'] . "\n";
                if (!empty($page['content'])) {
                    $content_preview = substr($page['content'], 0, 500);
                    $knowledge .= "  Contenuto: " . $content_preview . "...\n";
                }
                $knowledge .= "\n";
            }
        }
        
        // Aggiungi informazioni sugli articoli
        if (isset($content['posts']) && !empty($content['posts'])) {
            $knowledge .= "Articoli del blog:\n";
            foreach ($content['posts'] as $post) {
                $knowledge .= "- " . $post['title'] . "\n";
                $knowledge .= "  Data: " . $post['date'] . "\n";
                $knowledge .= "  URL: " . $post['url'] . "\n";
                if (!empty($post['categories'])) {
                    $knowledge .= "  Categorie: " . implode(', ', $post['categories']) . "\n";
                }
                if (!empty($post['excerpt'])) {
                    $knowledge .= "  Riassunto: " . $post['excerpt'] . "\n";
                }
                $knowledge .= "\n";
            }
        }
        
        // Aggiungi informazioni sui prodotti
        if (isset($content['products']) && !empty($content['products'])) {
            $knowledge .= "Prodotti disponibili:\n";
            foreach ($content['products'] as $product) {
                $knowledge .= "- " . $product['title'] . "\n";
                $knowledge .= "  URL: " . $product['url'] . "\n";
                if (!empty($product['price'])) {
                    $knowledge .= "  Prezzo: €" . $product['price'] . "\n";
                }
                if (!empty($product['categories'])) {
                    $knowledge .= "  Categorie: " . implode(', ', $product['categories']) . "\n";
                }
                if (!empty($product['short_description'])) {
                    $knowledge .= "  Descrizione: " . $product['short_description'] . "\n";
                }
                $knowledge .= "\n";
            }
        }
        
        // Aggiungi informazioni sugli eventi futuri
        if (isset($content['events']) && !empty($content['events'])) {
            $knowledge .= "Prossimi eventi (solo futuri, ordinati per data):\n";
            foreach ($content['events'] as $event) {
                $knowledge .= "- " . $event['title'] . "\n";
                if (!empty($event['start'])) {
                    $knowledge .= "  Data inizio: " . $event['start'] . "\n";
                }
                if (!empty($event['end'])) {
                    $knowledge .= "  Data fine: " . $event['end'] . "\n";
                }
                if (!empty($event['venue'])) {
                    $knowledge .= "  Luogo: " . $event['venue'] . "\n";
                }
                if (!empty($event['price'])) {
                    $knowledge .= "  Prezzo biglietto: " . $event['price'] . "\n";
                }
                if (!empty($event['stock_status'])) {
                    $knowledge .= "  Disponibilità: " . $event['stock_status'] . "\n";
                }
                if (!empty($event['capacity'])) {
                    $knowledge .= "  Capacità: " . $event['capacity'] . " posti\n";
                }
                if (!empty($event['excerpt'])) {
                    $knowledge .= "  Descrizione: " . $event['excerpt'] . "\n";
                }
                $knowledge .= "  URL: " . $event['url'] . "\n\n";
            }
        }

        // Aggiungi informazioni sulla spedizione
        $shipping_data = $this->load_content_file('shipping');
        if (!empty($shipping_data)) {
            $knowledge .= "Informazioni spedizione:\n";
            foreach ($shipping_data as $zone) {
                $knowledge .= "- Zona: " . $zone['zone'];
                if (!empty($zone['locations'])) {
                    $knowledge .= " (" . implode(', ', $zone['locations']) . ")";
                }
                $knowledge .= "\n";
                foreach ($zone['methods'] as $m) {
                    $knowledge .= "  * " . $m['method'];
                    if (!empty($m['cost']))       $knowledge .= " — Costo: " . $m['cost'];
                    if (!empty($m['min_amount'])) $knowledge .= " — Gratuita da: " . $m['min_amount'];
                    if (!empty($m['class_costs'])) $knowledge .= " — Classi: " . implode(', ', $m['class_costs']);
                    $knowledge .= "\n";
                }
            }
            $knowledge .= "\n";
        }

        return $knowledge;
    }
    
    /**
     * Ottiene statistiche sui contenuti scansionati
     */
    public function get_content_stats() {
        $content = get_option('marrison_assistant_site_content', array());
        
        $stats = array(
            'total_pages' => 0,
            'total_posts' => 0,
            'total_products' => 0,
            'last_scan' => get_option('marrison_assistant_last_content_scan', 0)
        );
        
        if (isset($content['pages'])) {
            $stats['total_pages'] = count($content['pages']);
        }
        
        if (isset($content['posts'])) {
            $stats['total_posts'] = count($content['posts']);
        }
        
        if (isset($content['products'])) {
            $stats['total_products'] = count($content['products']);
        }
        
        if (isset($content['orders'])) {
            $stats['total_orders'] = count($content['orders']);
        }

        return $stats;
    }

    /**
     * Scansiona le impostazioni di spedizione WooCommerce:
     * zone, metodi, tariffe, soglia spedizione gratuita.
     */
    public function scan_shipping() {
        $shipping_data = array();

        if (!class_exists('WooCommerce')) {
            return $shipping_data;
        }

        // ── Zone di spedizione ────────────────────────────────────────
        $zones = WC_Shipping_Zones::get_zones();

        // Aggiungi anche la zona "resto del mondo" (id=0)
        $rest_of_world = new WC_Shipping_Zone(0);
        $zones[0] = array(
            'zone_id'       => 0,
            'zone_name'     => $rest_of_world->get_zone_name(),
            'zone_order'    => 0,
            'zone_locations'=> $rest_of_world->get_zone_locations(),
            'shipping_methods' => $rest_of_world->get_shipping_methods(),
        );

        foreach ($zones as $zone_data) {
            $zone_id   = $zone_data['zone_id'];
            $zone      = new WC_Shipping_Zone($zone_id);
            $zone_name = $zone->get_zone_name();

            // Regioni coperte (paesi, stati, CAP)
            $locations = array();
            foreach ($zone->get_zone_locations() as $loc) {
                $locations[] = $loc->code . ($loc->type === 'country' ? '' : ' (' . $loc->type . ')');
            }

            $methods_data = array();
            foreach ($zone->get_shipping_methods(true) as $method) {
                $method_title = $method->get_title();
                $method_id    = $method->id;
                $entry        = array(
                    'method'      => $method_title,
                    'method_type' => $method_id,
                );

                // Flat rate — costo
                if ($method_id === 'flat_rate') {
                    $cost = $method->get_option('cost');
                    if ($cost !== '' && $cost !== null) {
                        $entry['cost'] = $cost . ' ' . get_woocommerce_currency_symbol();
                    }

                    // Costi per classi di spedizione
                    $classes = WC()->shipping()->get_shipping_classes();
                    $class_costs = array();
                    foreach ($classes as $class) {
                        $class_cost = $method->get_option('class_cost_' . $class->term_id);
                        if ($class_cost !== '' && $class_cost !== null) {
                            $class_costs[] = $class->name . ': ' . $class_cost . ' ' . get_woocommerce_currency_symbol();
                        }
                    }
                    if (!empty($class_costs)) {
                        $entry['class_costs'] = $class_costs;
                    }
                }

                // Free shipping — soglia minima
                if ($method_id === 'free_shipping') {
                    $requires     = $method->get_option('requires');
                    $min_amount   = $method->get_option('min_amount');
                    $entry['requires'] = $requires;
                    if ($min_amount !== '' && $min_amount !== null) {
                        $entry['min_amount'] = $min_amount . ' ' . get_woocommerce_currency_symbol();
                    }
                }

                // Local pickup — eventuale costo
                if ($method_id === 'local_pickup') {
                    $cost = $method->get_option('cost');
                    if ($cost !== '' && $cost !== null) {
                        $entry['cost'] = $cost . ' ' . get_woocommerce_currency_symbol();
                    }
                }

                $methods_data[] = $entry;
            }

            if (!empty($methods_data)) {
                $shipping_data[] = array(
                    'zone'      => $zone_name,
                    'locations' => $locations,
                    'methods'   => $methods_data,
                );
            }
        }

        error_log('Marrison Assistant [Shipping]: ' . count($shipping_data) . ' zone di spedizione scansionate');
        return $shipping_data;
    }

    /**
     * Scansiona gli ordini WooCommerce
     */
    public function scan_orders() {
        $orders = array();

        if (!class_exists('WooCommerce')) {
            return $orders;
        }

        // Prendi gli ultimi 100 ordini reali (esclude draft, checkout-draft, auto-draft)
        $args = array(
            'limit'   => 100,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'ids',
            'status'  => array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ),
        );

        $order_ids = wc_get_orders($args);

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            $order_data = array(
                'id' => $order_id,
                'number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'status_name' => wc_get_order_status_name($order->get_status()),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'customer_id' => $order->get_customer_id(),
                'customer_name' => $order->get_formatted_billing_full_name(),
                'customer_email' => $order->get_billing_email(),
                'payment_method' => $order->get_payment_method_title(),
                'shipping_method' => $order->get_shipping_method(),
                'view_url' => $order->get_view_order_url(),
                'items' => array()
            );

            // Prodotti dell'ordine
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $order_data['items'][] = array(
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total(),
                    'product_id' => $product ? $product->get_id() : 0,
                    'sku' => $product ? $product->get_sku() : ''
                );
            }

            // Tracking info se disponibile
            $tracking = array();
            if (method_exists($order, 'get_meta')) {
                $tracking_number = $order->get_meta('_tracking_number');
                if ($tracking_number) {
                    $tracking['number'] = $tracking_number;
                    $tracking['provider'] = $order->get_meta('_tracking_provider');
                }
            }
            if (!empty($tracking)) {
                $order_data['tracking'] = $tracking;
            }

            $orders[] = $order_data;
        }

        return $orders;
    }
}
