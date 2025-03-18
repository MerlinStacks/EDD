<?php
/**
 * Plugin Name: ED Dates CK
 * Plugin URI: https://customkings.com.au
 * Description: A WooCommerce plugin that displays estimated delivery dates on product, cart, and checkout pages.
 * Version: 1.0.2
 * Author: CustomKings Personalised Gifts
 * Author URI: https://customkings.com.au
 * Text Domain: ed-dates-ck
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ED_DATES_CK_VERSION', '1.0.2');
define('ED_DATES_CK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ED_DATES_CK_PLUGIN_URL', plugin_dir_url(__FILE__));

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

/**
 * Register blocks
 */
function ed_dates_ck_register_blocks() {
    // Automatically load dependencies and version
    $asset_file = include(ED_DATES_CK_PLUGIN_DIR . 'blocks/build/index.asset.php');

    wp_register_script(
        'ed-dates-ck-blocks-editor',
        ED_DATES_CK_PLUGIN_URL . 'blocks/build/index.js',
        $asset_file['dependencies'],
        $asset_file['version']
    );

    register_block_type(ED_DATES_CK_PLUGIN_DIR . 'blocks/build', array(
        'render_callback' => 'ed_dates_ck_render_delivery_block'
    ));
}
add_action('init', 'ed_dates_ck_register_blocks');

/**
 * Render delivery block
 */
function ed_dates_ck_render_delivery_block($attributes) {
    if (!class_exists('ED_Dates_CK_Calculator')) {
        return '';
    }

    $calculator = ED_Dates_CK_Calculator::get_instance();
    $delivery_date = $calculator->calculate_estimated_delivery(get_the_ID());
    
    ob_start();
    ?>
    <div class="ed-dates-ck-block">
        <h3><?php echo esc_html__('Estimated Delivery Date', 'ed-dates-ck'); ?></h3>
        <p><?php echo esc_html($delivery_date); ?></p>
    </div>
    <?php
    return ob_get_clean();
}

// Include required files
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-admin.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-product.php';
require_once ED_DATES_CK_PLUGIN_DIR . 'includes/class-ed-dates-ck-calculator.php';

/**
 * Initialize the plugin
 */
function ed_dates_ck_init() {
    // Initialize main plugin class
    ED_Dates_CK::get_instance();
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