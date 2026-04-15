<?php
/**
 * Classe per la scansione degli stati ordini WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Marrison_Assistant_Order_Scanner {
    
    /**
     * Controlla lo stato di un ordine dato il numero
     */
    public function get_order_status($order_number) {
        if (!class_exists('WooCommerce')) {
            return 'WooCommerce non è attivo';
        }
        
        // Cerca ordine per numero
        $order = $this->find_order_by_number($order_number);
        
        if (!$order) {
            return 'Ordine non trovato';
        }
        
        $status = $order->get_status();
        $status_name = wc_get_order_status_name($status);
        
        $order_info = array(
            'order_id' => $order->get_id(),
            'order_number' => $order_number,
            'status' => $status,
            'status_name' => $status_name,
            'date_created' => $order->get_date_created()->format('d/m/Y H:i'),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'customer_email' => $order->get_billing_email(),
            'payment_method' => $order->get_payment_method_title(),
            'shipping_method' => $order->get_shipping_method(),
            'items' => array(),
            'tracking_info' => $this->get_tracking_info($order)
        );
        
        // Aggiungi prodotti dell'ordine
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $order_info['items'][] = array(
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total(),
                'product_id' => $product ? $product->get_id() : 0
            );
        }
        
        return $order_info;
    }
    
    /**
     * Trova ordine per numero
     */
    private function find_order_by_number($order_number) {
        // Prima prova come ID ordine
        $order = wc_get_order($order_number);
        if ($order) {
            return $order;
        }
        
        // Poi prova come numero ordine (meta key)
        $orders = wc_get_orders(array(
            'limit' => 1,
            'meta_key' => '_order_number',
            'meta_value' => $order_number,
            'return' => 'ids'
        ));
        
        if (!empty($orders)) {
            return wc_get_order($orders[0]);
        }
        
        // Prova cercando in vari meta fields
        $meta_keys = array('_order_number', 'order_number', '_alg_wc_full_custom_order_number');
        
        foreach ($meta_keys as $meta_key) {
            $orders = wc_get_orders(array(
                'limit' => 1,
                'meta_key' => $meta_key,
                'meta_value' => $order_number,
                'return' => 'ids'
            ));
            
            if (!empty($orders)) {
                return wc_get_order($orders[0]);
            }
        }
        
        return false;
    }
    
    /**
     * Ottiene informazioni di tracking
     */
    private function get_tracking_info($order) {
        $tracking = array();
        
        // Check per plugin di tracking comuni
        if (method_exists($order, 'get_meta')) {
            // WooCommerce Shipping Tracking
            $tracking_number = $order->get_meta('_tracking_number');
            $tracking_provider = $order->get_meta('_tracking_provider');
            
            if ($tracking_number) {
                $tracking['number'] = $tracking_number;
                $tracking['provider'] = $tracking_provider;
            }
            
            // YITH WooCommerce Order Tracking
            $yith_tracking = $order->get_meta('_ywot_tracking_code');
            if ($yith_tracking) {
                $tracking['number'] = $yith_tracking;
                $tracking['provider'] = 'YITH Tracking';
            }
            
            // Altro tracking generico
            $generic_tracking = $order->get_meta('tracking_code');
            if ($generic_tracking) {
                $tracking['number'] = $generic_tracking;
                $tracking['provider'] = 'Generic';
            }
        }
        
        return $tracking;
    }
    
    /**
     * Formatta la risposta per l'utente
     */
    public function format_order_response($order_info) {
        if (is_string($order_info)) {
            return $order_info; // Messaggio di errore
        }
        
        $response = "📦 **Ordine #{$order_info['order_number']}**\n\n";
        $response .= "📅 **Data:** {$order_info['date_created']}\n";
        $response .= "📊 **Stato:** {$order_info['status_name']}\n";
        $response .= "💰 **Totale:** €{$order_info['total']}\n";
        $response .= "👤 **Cliente:** {$order_info['customer_name']}\n";
        $response .= "💳 **Pagamento:** {$order_info['payment_method']}\n\n";
        
        if (!empty($order_info['items'])) {
            $response .= "**Prodotti:**\n";
            foreach ($order_info['items'] as $item) {
                $response .= "- {$item['name']} (x{$item['quantity']}) - €{$item['price']}\n";
            }
            $response .= "\n";
        }
        
        if (!empty($order_info['tracking_info'])) {
            $tracking = $order_info['tracking_info'];
            $response .= "🚚 **Tracking:** {$tracking['number']} ({$tracking['provider']})\n\n";
        }
        
        // Aggiungi informazioni specifiche per lo stato
        $status_descriptions = array(
            'pending' => 'Ordine in attesa di pagamento',
            'processing' => 'Ordine in elaborazione',
            'on-hold' => 'Ordine sospeso',
            'completed' => '✅ Ordine completato e spedito',
            'cancelled' => '❌ Ordine annullato',
            'refunded' => 'Ordine rimborsato',
            'failed' => 'Pagamento fallito'
        );
        
        $status_key = $order_info['status'];
        if (isset($status_descriptions[$status_key])) {
            $response .= "**Note:** {$status_descriptions[$status_key]}";
        }
        
        return $response;
    }
    
    /**
     * Verifica se un messaggio contiene un numero ordine
     */
    public function extract_order_number($message) {
        // Pattern per numeri ordine (es. #12345, ordine 12345, etc.)
        $patterns = array(
            '/(?:ordine|order|n[°o]\s*|#)(\d+)/i',
            '/^(\d{3,})$/',
            '/(?:tracking|tracciamento|stato)(?:\s*:?\s*)(\d+)/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
}
