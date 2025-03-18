<?php
/**
 * Plugin Name: ED Dates CK
 * Plugin URI: https://customkings.com/
 * Description: Display estimated delivery dates on product, cart, and checkout pages.
 * Version: 1.0.12
 * Author: CustomKings
 * Author URI: https://customkings.com/
 * Text Domain: ed-dates-ck
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.6
 *
 * @package ED_Dates_CK
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ED_DATES_CK_VERSION', '1.0.12');
define('ED_DATES_CK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ED_DATES_CK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ED_DATES_CK_PLUGIN_PATH', __DIR__);

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'ed_dates_ck_woocommerce_missing_notice');
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
function ed_dates_ck_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('ED Dates CK requires WooCommerce to be installed and active.', 'ed-dates-ck'); ?></p>
    </div>
    <?php
}

// Include required files
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-admin.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-product.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-calculator.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-blocks.php';

/**
 * Initialize the plugin
 */
function ed_dates_ck_init() {
    // Initialize main plugin class
    ED_Dates_CK::get_instance();
    
    // Initialize admin class if in admin area
    if (is_admin()) {
        ED_Dates_CK_Admin::get_instance();
    }

    // Initialize product class
    ED_Dates_CK_Product::get_instance();
}
add_action('plugins_loaded', 'ed_dates_ck_init');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'ed_dates_ck_activate');
function ed_dates_ck_activate() {
    // Create necessary database tables and options
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
register_deactivation_hook(__FILE__, 'ed_dates_ck_deactivate');
function ed_dates_ck_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
} 