<?php
/**
 * Plugin Name: WooCommerce Estimated Delivery Date
 * Plugin URI: #
 * Description: Display estimated delivery dates on WooCommerce product pages using a Gutenberg block.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: #
 * Text Domain: wc-estimated-delivery-date
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 8.0
 *
 * @package WooCommerce_Estimated_Delivery_Date
 */

defined('ABSPATH') || exit;

/**
 * Main plugin class
 */
class WC_Estimated_Delivery_Date {
    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Single instance of the plugin
     *
     * @var WC_Estimated_Delivery_Date
     */
    protected static $instance = null;

    /**
     * Main instance
     *
     * @return WC_Estimated_Delivery_Date
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('WC_EDD_VERSION', self::VERSION);
        define('WC_EDD_FILE', __FILE__);
        define('WC_EDD_PATH', plugin_dir_path(WC_EDD_FILE));
        define('WC_EDD_URL', plugin_dir_url(WC_EDD_FILE));
    }

    /**
     * Initialize plugin hooks
     */
    private function init_hooks() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);
        add_action('init', [$this, 'init'], 0);

        // Register activation and deactivation hooks
        register_activation_hook(WC_EDD_FILE, [$this, 'activate']);
        register_deactivation_hook(WC_EDD_FILE, [$this, 'deactivate']);
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load required files
        $this->includes();

        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }

        // Initialize frontend
        if (!is_admin() || defined('DOING_AJAX')) {
            $this->init_frontend();
        }

        // Register block
        add_action('init', [$this, 'register_block']);
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once WC_EDD_PATH . 'admin/class-admin-settings.php';
        require_once WC_EDD_PATH . 'admin/class-product-meta.php';
    }

    /**
     * Initialize admin
     */
    private function init_admin() {
        new WC_EDD_Admin_Settings();
        new WC_EDD_Product_Meta();
    }

    /**
     * Initialize frontend
     */
    private function init_frontend() {
        // Frontend initialization code will be added here
    }

    /**
     * Register Gutenberg block
     */
    public function register_block() {
        register_block_type(WC_EDD_PATH . 'build/block.json');
    }

    /**
     * Display WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce Estimated Delivery Date requires WooCommerce to be installed and active.', 'wc-estimated-delivery-date'); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wc-estimated-delivery-date',
            false,
            dirname(plugin_basename(WC_EDD_FILE)) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Activation code will be added here
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Deactivation code will be added here
    }
}

/**
 * Return main instance of plugin
 *
 * @return WC_Estimated_Delivery_Date
 */
function WC_EDD() {
    return WC_Estimated_Delivery_Date::instance();
}

// Initialize the plugin
WC_EDD();