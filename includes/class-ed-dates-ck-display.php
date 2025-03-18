<?php
if (!defined('ABSPATH')) {
    exit;
}

class ED_Dates_CK_Display {
    /**
     * @var ED_Dates_CK_Display The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main ED_Dates_CK_Display Instance
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
        // Add delivery date display to product page
        add_action('woocommerce_before_add_to_cart_form', array($this, 'display_estimated_delivery'));
        
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
        if (!is_product() && !is_cart() && !is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'ed-dates-ck-style',
            ED_DATES_CK_URL . 'assets/css/ed-dates-ck.css',
            array(),
            ED_DATES_CK_VERSION
        );

        wp_enqueue_script(
            'ed-dates-ck-script',
            ED_DATES_CK_URL . 'assets/js/ed-dates-ck.js',
            array('jquery'),
            ED_DATES_CK_VERSION,
            true
        );

        wp_localize_script('ed-dates-ck-script', 'edDatesCk', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ed_dates_ck_nonce')
        ));
    }

    /**
     * Display estimated delivery date on product page
     */
    public function display_estimated_delivery() {
        global $product;
        
        if (!$product) {
            return;
        }

        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery($product->get_id());

        if (!$delivery_date) {
            return;
        }

        $this->render_delivery_date($delivery_date);
    }

    /**
     * Display estimated delivery date in cart
     */
    public function display_cart_estimated_delivery($cart_item, $cart_item_key) {
        if (!isset($cart_item['product_id'])) {
            return;
        }

        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery($cart_item['product_id']);

        if (!$delivery_date) {
            return;
        }

        $this->render_delivery_date($delivery_date);
    }

    /**
     * Display estimated delivery date in checkout
     */
    public function display_checkout_estimated_delivery($quantity_html, $cart_item, $cart_item_key) {
        if (!isset($cart_item['product_id'])) {
            return $quantity_html;
        }

        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery($cart_item['product_id']);

        if (!$delivery_date) {
            return $quantity_html;
        }

        ob_start();
        $this->render_delivery_date($delivery_date);
        return $quantity_html . ob_get_clean();
    }

    /**
     * Render the delivery date HTML
     */
    private function render_delivery_date($delivery_date) {
        $settings = get_option('ed_dates_ck_settings', array());
        $display_text = !empty($settings['display_text']) 
            ? $settings['display_text'] 
            : __('Estimated Delivery Date:', 'ed-dates-ck');
        ?>
        <div class="ed-dates-ck-delivery-date">
            <span class="ed-dates-ck-label"><?php echo esc_html($display_text); ?></span>
            <span class="ed-dates-ck-date"><?php echo esc_html($delivery_date); ?></span>
        </div>
        <?php
    }
} 