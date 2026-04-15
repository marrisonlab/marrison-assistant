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
     */
    public function scan_all_content() {
        $content = array();
        
        // Scansiona pagine
        $pages = $this->scan_pages();
        if (!empty($pages)) {
            $content['pages'] = $pages;
        }
        
        // Scansiona articoli
        $posts = $this->scan_posts();
        if (!empty($posts)) {
            $content['posts'] = $posts;
        }
        
        // Scansiona prodotti WooCommerce se attivo
        if (class_exists('WooCommerce')) {
            $products = $this->scan_products();
            if (!empty($products)) {
                $content['products'] = $products;
            }
        }
        
        // Salva i contenuti in opzione
        update_option('marrison_assistant_site_content', $content);
        update_option('marrison_assistant_last_content_scan', time());
        
        return $content;
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
                
                $product_data = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'description' => wp_strip_all_tags(get_the_content()),
                    'short_description' => $product ? $product->get_short_description() : '',
                    'url' => get_permalink(),
                    'price' => $product ? $product->get_price() : '',
                    'categories' => $product ? wp_get_post_terms(get_the_ID(), 'product_cat', array('fields' => 'names')) : array(),
                    'type' => 'product'
                );
                
                // Aggiungi attributi se disponibili
                if ($product && method_exists($product, 'get_attributes')) {
                    $attributes = array();
                    foreach ($product->get_attributes() as $attribute) {
                        if ($attribute->is_taxonomy()) {
                            $terms = wp_get_post_terms(get_the_ID(), $attribute->get_name(), array('fields' => 'names'));
                            $attributes[$attribute->get_name()] = implode(', ', $terms);
                        } else {
                            $attributes[$attribute->get_name()] = $product->get_attribute($attribute->get_name());
                        }
                    }
                    $product_data['attributes'] = $attributes;
                }
                
                $products[] = $product_data;
            }
        }
        
        wp_reset_postdata();
        
        return $products;
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
        
        return $stats;
    }
}
