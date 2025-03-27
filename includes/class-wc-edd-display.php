<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_EDD_Display { // Renamed class
    /**
     * @var WC_EDD_Display The single instance of the class // Updated type hint
     */
    protected static $_instance = null;

    /**
     * Main WC_EDD_Display Instance // Updated class name
     */
    public static function get_instance() { // Updated class name
        if (is_null(self::$_instance)) {
            self::$_instance = new self(); // Updated class name
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Remove duplicate hook for product page
        
        // Add delivery date display to cart and checkout
        add_action('woocommerce_after_cart_item_name', array($this, 'display_cart_estimated_delivery'), 10, 2);
        add_action('woocommerce_checkout_cart_item_quantity', array($this, 'display_checkout_estimated_delivery'), 10, 3);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        try {
            if (!is_product() && !is_cart() && !is_checkout()) {
                return;
            }

            // Use WC_EDD_PLUGIN_URL constant defined in main file
            if (!defined('WC_EDD_PLUGIN_URL') || !defined('WC_EDD_VERSION')) { // Updated constants
                error_log('WC EDD - Error: Plugin constants not defined'); // Updated text domain
                return;
            }

            // Enqueue frontend.css (already handled by WC_EDD_Blocks? Review needed)
            // If WC_EDD_Blocks doesn't enqueue it globally, uncomment this.
            /*
            wp_enqueue_style(
                'wc-edd-frontend-style', // Use handle consistent with WC_EDD_Blocks
                ED_DATES_CK_PLUGIN_URL . 'assets/css/frontend.css', // Updated path
                array(),
                ED_DATES_CK_VERSION
            );
            */

           // Enqueue frontend JS (if needed)
           wp_enqueue_script(
               'wc-edd-frontend-js', // Updated handle
               WC_EDD_PLUGIN_URL . 'assets/js/frontend.js', // Updated constant and path
               array('jquery'), // Add other dependencies if needed
               WC_EDD_VERSION, // Updated constant
               true
           );

            // Localize script if AJAX is used by frontend JS
            // TODO: Update nonce action name
            wp_localize_script('wc-edd-frontend-js', 'wcEddFrontend', array( // Updated object name
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_edd_frontend_nonce') // Updated nonce action
            ));
        } catch (Exception $e) {
            error_log('WC EDD - Error enqueueing scripts: ' . $e->getMessage()); // Updated text domain
        }
    }

    // Removed unused display_estimated_delivery method - Block render callback handles product page display.
    /*
    public function display_estimated_delivery() {
        // ... (original code) ...
    }
    */

    /**
     * Display estimated delivery date in cart (if enabled).
     */
    public function display_cart_estimated_delivery($cart_item, $cart_item_key) {
        // Check if display is enabled in settings
        $settings = get_option('wc_edd_settings', []);
        $general_settings = $settings['general'] ?? [];
        if (empty($general_settings['cart_checkout_display'])) {
            return;
        }

        try {
            if (!is_array($cart_item) || !isset($cart_item['product_id'])) {
                return;
            }
            $product_id = $cart_item['variation_id'] ?: $cart_item['product_id']; // Use variation ID if available

            $calculator = WC_EDD_Calculator::get_instance();
            if (!$calculator) {
                error_log('WC EDD - Error: Could not initialize calculator');
                return;
            }

            // TODO: Determine the relevant shipping method ID if needed for more accuracy?
            // For cart, maybe use the default range or range based on available methods?
            $date_range = $calculator->calculate_delivery_range($product_id);

            if (empty($date_range)) {
                return;
            }

            // Format the latest date according to settings
            $date_format_php = $general_settings['date_format'] ?? 'F j, Y';
            $end_date_obj = date_create($date_range['end']);
            if (!$end_date_obj) return; // Invalid date
            $formatted_date = date_i18n($date_format_php, $end_date_obj->getTimestamp());

            // Render using the helper
            $this->render_delivery_date($formatted_date, true); // Pass true to indicate cart/checkout context

        } catch (\Exception $e) {
            error_log('WC EDD - Error displaying cart delivery date: ' . $e->getMessage());
        }
    }

    /**
     * Display estimated delivery date in checkout (if enabled).
     */
    public function display_checkout_estimated_delivery($quantity_html, $cart_item, $cart_item_key) {
        // Check if display is enabled in settings
        $settings = get_option('wc_edd_settings', []);
        $general_settings = $settings['general'] ?? [];
        if (empty($general_settings['cart_checkout_display'])) {
            return $quantity_html;
        }

        try {
            if (!is_array($cart_item) || !isset($cart_item['product_id'])) {
                return $quantity_html;
            }
            $product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];

            $calculator = WC_EDD_Calculator::get_instance();
            if (!$calculator) {
                error_log('WC EDD - Error: Could not initialize calculator');
                return $quantity_html;
            }

            // TODO: Determine relevant shipping method?
            $date_range = $calculator->calculate_delivery_range($product_id);

            if (empty($date_range)) {
                return $quantity_html;
            }

            // Format the latest date according to settings
            $date_format_php = $general_settings['date_format'] ?? 'F j, Y';
            $end_date_obj = date_create($date_range['end']);
            if (!$end_date_obj) return $quantity_html; // Invalid date
            $formatted_date = date_i18n($date_format_php, $end_date_obj->getTimestamp());

            ob_start();
            $this->render_delivery_date($formatted_date, true); // Pass true for cart/checkout context
            $output = ob_get_clean();
            return $quantity_html . $output; // Append date HTML to quantity HTML

        } catch (\Exception $e) {
            error_log('WC EDD - Error displaying checkout delivery date: ' . $e->getMessage());
            return $quantity_html;
        }
    }

    /**
     * Render the delivery date HTML for cart/checkout.
     *
     * @param string $formatted_date The pre-formatted date string (latest date).
     * @param bool   $is_cart_checkout Context flag (currently always true when called).
     */
    private function render_delivery_date(string $formatted_date, bool $is_cart_checkout = false): void {
        try {
            // No need to fetch settings again if already done in calling methods
            // $settings = get_option('wc_edd_settings', []);
            // $general_settings = $settings['general'] ?? [];

            // Use 'Est. Delivery by %s' format for cart/checkout as per spec
            $display_text = sprintf(
                __('Est. Delivery by %s', 'wc-estimated-delivery-date'), // Using 'Est.' for brevity
                $formatted_date
            );

            // Simple output for cart/checkout
            echo '<div class="wc-edd-delivery-date wc-edd-cart-checkout-date" style="font-size: 0.9em; clear: both;">'; // Add context class
            echo esc_html($display_text);
            echo '</div>';

        } catch (\Exception $e) {
            error_log('WC EDD - Error rendering cart/checkout delivery date: ' . $e->getMessage());
        }
    }
} 