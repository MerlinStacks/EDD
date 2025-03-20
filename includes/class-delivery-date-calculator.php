<?php
/**
 * Delivery Date Calculator Class
 *
 * @package WooCommerce_Estimated_Delivery_Date
 */

defined('ABSPATH') || exit;

/**
 * WC_EDD_Delivery_Date_Calculator Class
 */
class WC_EDD_Delivery_Date_Calculator {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_wc_edd_get_delivery_date', [$this, 'ajax_get_delivery_date']);
        add_action('wp_ajax_nopriv_wc_edd_get_delivery_date', [$this, 'ajax_get_delivery_date']);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product() && !is_cart() && !is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'wc-edd-frontend',
            WC_EDD_URL . 'assets/css/frontend.css',
            [],
            WC_EDD_VERSION
        );

        wp_enqueue_script(
            'wc-edd-frontend',
            WC_EDD_URL . 'assets/js/frontend.js',
            ['jquery'],
            WC_EDD_VERSION,
            true
        );

        wp_localize_script('wc-edd-frontend', 'wc_edd_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_edd_nonce'),
            'i18n' => [
                'estimated_text' => __('Estimated Delivery Date:', 'wc-estimated-delivery-date'),
            ],
        ]);
    }

    /**
     * AJAX handler for getting delivery date
     */
    public function ajax_get_delivery_date() {
        check_ajax_referer('wc_edd_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $shipping_method = isset($_POST['shipping_method']) ? sanitize_text_field($_POST['shipping_method']) : '';

        if (!$product_id) {
            wp_send_json_error(__('Invalid product ID', 'wc-estimated-delivery-date'));
        }

        $product = wc_get_product($variation_id ?: $product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found', 'wc-estimated-delivery-date'));
        }

        $dates = $this->calculate_delivery_dates($product, $shipping_method);
        
        wp_send_json_success([
            'min_date' => $dates['min'],
            'max_date' => $dates['max'],
            'formatted_date' => $this->format_delivery_date($dates),
        ]);
    }

    /**
     * Calculate delivery dates for a product
     *
     * @param WC_Product $product Product object
     * @param string     $shipping_method Shipping method ID
     * @return array Delivery dates array with min and max dates
     */
    public function calculate_delivery_dates($product, $shipping_method = '') {
        // Get lead times
        $lead_times = WC_EDD_Product_Meta::get_product_lead_time($product);
        
        // Get shipping transit times
        $transit_times = $this->get_shipping_transit_times($shipping_method);

        // Get current date
        $current_date = new DateTime('now', wp_timezone());

        // Calculate min date
        $min_date = clone $current_date;
        $min_date->modify('+' . ($lead_times['min'] + $transit_times['min']) . ' days');
        $min_date = $this->adjust_for_closed_days($min_date);

        // Calculate max date
        $max_date = clone $current_date;
        $max_date->modify('+' . ($lead_times['max'] + $transit_times['max']) . ' days');
        $max_date = $this->adjust_for_closed_days($max_date);

        return [
            'min' => $min_date,
            'max' => $max_date,
        ];
    }

    /**
     * Get shipping method transit times
     *
     * @param string $shipping_method Shipping method ID
     * @return array Transit times array with min and max times
     */
    private function get_shipping_transit_times($shipping_method) {
        $transit_times = [
            'min' => 0,
            'max' => 0,
        ];

        if (!$shipping_method) {
            return $transit_times;
        }

        $shipping_methods = get_option('wc_edd_shipping_methods', []);
        if (isset($shipping_methods[$shipping_method])) {
            $method = $shipping_methods[$shipping_method];
            $transit_times['min'] = isset($method['min']) ? (int) $method['min'] : 0;
            $transit_times['max'] = isset($method['max']) ? (int) $method['max'] : 0;
        }

        return $transit_times;
    }

    /**
     * Adjust date for closed days
     *
     * @param DateTime $date Date object
     * @return DateTime Adjusted date object
     */
    private function adjust_for_closed_days($date) {
        $settings = get_option('wc_edd_general_settings', []);
        
        // Get closed days
        $store_closed_days = isset($settings['store_closed_days']) 
            ? array_map('strtotime', explode(',', $settings['store_closed_days'])) 
            : [];
        
        $postage_closed_days = isset($settings['postage_closed_days'])
            ? array_map('strtotime', explode(',', $settings['postage_closed_days']))
            : [];

        $closed_days = array_merge($store_closed_days, $postage_closed_days);

        // Keep adjusting date until we find an open day
        while (in_array($date->getTimestamp(), $closed_days, true)) {
            $date->modify('+1 day');
        }

        return $date;
    }

    /**
     * Format delivery date range
     *
     * @param array $dates Delivery dates array with min and max dates
     * @return string Formatted date range
     */
    private function format_delivery_date($dates) {
        $settings = get_option('wc_edd_general_settings', []);
        
        $date_format = isset($settings['date_format']) ? $settings['date_format'] : 'F j, Y';
        $display_format = isset($settings['display_format']) ? $settings['display_format'] : 'range';

        if ($display_format === 'range') {
            return sprintf(
                /* translators: 1: Minimum date 2: Maximum date */
                __('%1$s - %2$s', 'wc-estimated-delivery-date'),
                $dates['min']->format($date_format),
                $dates['max']->format($date_format)
            );
        }

        return $dates['max']->format($date_format);
    }
}