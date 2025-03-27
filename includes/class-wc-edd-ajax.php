<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_EDD_Ajax { // Renamed class
    /**
     * @var WC_EDD_Ajax The single instance of the class // Updated type hint
     */
    protected static $_instance = null;

    /**
     * Main WC_EDD_Ajax Instance // Updated class name
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
        // Meta box hooks removed - Handled by WC_EDD_Product_Meta class
        // add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        // add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));

        // Add AJAX endpoint for dynamic delivery date updates
        // TODO: Review and potentially move this AJAX logic (e.g., to a dedicated AJAX handler class or frontend display class)
        add_action('wp_ajax_wc_edd_get_estimated_delivery', array($this, 'ajax_get_estimated_delivery')); // Updated action name
        add_action('wp_ajax_nopriv_wc_edd_get_estimated_delivery', array($this, 'ajax_get_estimated_delivery')); // Updated action name
    }

    /**
     * AJAX handler for getting estimated delivery date.
     * TODO: Implement this function based on requirements.
     */
    public function ajax_get_estimated_delivery() {
        // Check nonce, get parameters (product ID, variation ID, shipping method?), calculate date, return JSON
        wp_send_json_error('Not implemented yet.'); // Placeholder
    }

    // Meta box functions removed - Handled by WC_EDD_Product_Meta class
    // public function add_product_data_panel() { ... }
    // public function save_product_data($post_id) { ... }

} // End class ED_Dates_CK_Product