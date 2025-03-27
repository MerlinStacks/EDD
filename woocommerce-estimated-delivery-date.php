<?php
/**
 * Plugin Name: WooCommerce Estimated Delivery Date
 * Plugin URI: https://customkings.com/
 * Description: Displays an estimated delivery date on WooCommerce product pages using a Gutenberg block.
 * Version: 1.0.16
 * Author: CustomKings
 * Author URI: https://customkings.com/
 * Text Domain: wc-estimated-delivery-date
 * Domain Path: /languages/
 * Requires at least: 6.0
 * Requires PHP: 7.4 
 * WC requires at least: 7.0
 * WC tested up to: 8.6
 *
 * @package WC_Estimated_Delivery_Date
 */

if (!defined('ABSPATH')) {
    exit;
}


// Define plugin constants
define('WC_EDD_VERSION', '1.0.16'); // Renamed constant
define('WC_EDD_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Renamed constant
define('WC_EDD_PLUGIN_URL', plugin_dir_url(__FILE__)); // Renamed constant
define('WC_EDD_PLUGIN_PATH', __DIR__); // Renamed constant
// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
    add_action('admin_notices', 'wc_edd_woocommerce_missing_notice'); // Renamed function
    return;
}

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Display WooCommerce missing notice
 */
function wc_edd_woocommerce_missing_notice() { // Renamed function
    ?>
    <div class="error">
        <p><?php esc_html_e('WooCommerce Estimated Delivery Date requires WooCommerce to be installed and active.', 'wc-estimated-delivery-date'); ?></p>
    </div>
    <?php
}

// Include required files
require_once WC_EDD_PLUGIN_DIR . 'includes/class-wc-estimated-delivery-date.php'; // Updated constant
require_once WC_EDD_PLUGIN_DIR . 'admin/class-admin-settings.php'; // Updated constant
require_once WC_EDD_PLUGIN_DIR . 'admin/class-product-meta.php';   // Updated constant
// The class 'includes/class-ed-dates-ck-product.php' might still be needed for AJAX, review later.
require_once WC_EDD_PLUGIN_DIR . 'includes/class-wc-edd-calculator.php'; // Updated constant
require_once WC_EDD_PLUGIN_DIR . 'includes/class-wc-edd-blocks.php';     // Updated constant
require_once WC_EDD_PLUGIN_DIR . 'includes/class-wc-edd-display.php';      // Updated constant
require_once WC_EDD_PLUGIN_DIR . 'includes/class-wc-edd-ajax.php';        // Updated constant
// require_once WC_EDD_PLUGIN_DIR . 'includes/class-ed-dates-ck-product.php'; // Updated constant

/**
 * Initialize the plugin
 */
function wc_edd_init() { // Renamed function
    // Initialize main plugin class
    WC_Estimated_Delivery_Date::get_instance(); // Updated class name

    // Initialize admin classes if in admin area
    if (is_admin()) {
        WC_EDD_Admin_Settings::get_instance(); // Use new Admin Settings class
        WC_EDD_Product_Meta::get_instance();   // Use new Product Meta class
    }

    // TODO: Initialize frontend display logic (e.g., block rendering, cart/checkout display)
    // TODO: Review if ED_Dates_CK_Product instance is needed for AJAX hooks

    // Initialize Blocks class
    WC_EDD_Blocks::get_instance(); // Explicitly initialize blocks

    
        // Initialize Display class (for cart/checkout hooks, frontend assets)
        WC_EDD_Display::get_instance();
    
        // Initialize AJAX class
        WC_EDD_Ajax::get_instance();
    }
    add_action('plugins_loaded', 'wc_edd_init'); // Use renamed function
/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'wc_edd_activate'); // Renamed function
function wc_edd_activate() { // Renamed function
    // Create necessary database tables and options - TODO: Review options based on spec
    add_option('ed_dates_ck_order_cutoff_time', '16:00');
    add_option('ed_dates_ck_shop_closed_days', array('sunday'));
    add_option('ed_dates_ck_shop_holidays', array());
    add_option('ed_dates_ck_postage_holidays', array());
    add_option('ed_dates_ck_shipping_methods', array());

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'wc_edd_deactivate'); // Renamed function
function wc_edd_deactivate() { // Renamed function
    // Flush rewrite rules
    flush_rewrite_rules();
}