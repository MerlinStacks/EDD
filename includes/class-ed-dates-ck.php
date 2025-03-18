<?php
if (!defined('ABSPATH')) {
    exit;
}

class ED_Dates_CK {
    /**
     * @var ED_Dates_CK The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main ED_Dates_CK Instance
     */
    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
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
        // Add frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add estimated delivery date to product page
        add_action('woocommerce_single_product_summary', array($this, 'display_product_delivery_date'), 25);
        
        // Add estimated delivery date to cart
        add_action('woocommerce_after_cart_item_name', array($this, 'display_cart_delivery_date'), 10, 2);
        
        // Add estimated delivery date to checkout
        add_action('woocommerce_review_order_before_submit', array($this, 'display_checkout_delivery_date'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'ed-dates-ck-frontend',
            ED_DATES_CK_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            ED_DATES_CK_VERSION
        );

        wp_enqueue_script(
            'ed-dates-ck-frontend',
            ED_DATES_CK_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ED_DATES_CK_VERSION,
            true
        );

        wp_localize_script('ed-dates-ck-frontend', 'edDatesCK', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ed_dates_ck_nonce'),
            'i18n' => array(
                'estimatedDelivery' => __('Estimated Delivery:', 'ed-dates-ck')
            )
        ));
    }

    /**
     * Display delivery date on product page
     */
    public function display_product_delivery_date() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery($product->get_id());
        
        echo '<div class="ed-dates-ck-product-delivery">';
        echo '<h3>' . esc_html__('Estimated Delivery Date', 'ed-dates-ck') . '</h3>';
        echo '<p>' . esc_html($delivery_date) . '</p>';
        echo '</div>';
    }

    /**
     * Display delivery date in cart
     */
    public function display_cart_delivery_date($name, $cart_item) {
        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery($cart_item['product_id']);
        
        echo '<div class="cart-item-delivery-date">';
        echo esc_html__('Estimated Delivery:', 'ed-dates-ck') . ' ';
        echo esc_html($delivery_date);
        echo '</div>';
    }

    /**
     * Display delivery date on checkout
     */
    public function display_checkout_delivery_date() {
        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery();
        
        echo '<div class="checkout-delivery-date">';
        echo '<h3>' . esc_html__('Estimated Delivery Date', 'ed-dates-ck') . '</h3>';
        echo '<p>' . esc_html($delivery_date) . '</p>';
        echo '</div>';
    }
} 