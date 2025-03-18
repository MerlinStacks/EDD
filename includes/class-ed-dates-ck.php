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
        add_action('woocommerce_before_add_to_cart_form', array($this, 'display_product_delivery_date'), 15);
        
        // Add estimated delivery date to cart
        add_action('woocommerce_after_cart_item_name', array($this, 'display_cart_delivery_date'), 10, 2);
        
        // Add estimated delivery date to checkout
        add_action('woocommerce_after_checkout_form', array($this, 'display_checkout_delivery_date'));
        
        // Add estimated delivery date to order review
        add_action('woocommerce_review_order_after_cart_contents', array($this, 'display_checkout_delivery_date'));
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
        try {
            global $product;
            
            if (!$product || !($product instanceof WC_Product)) {
                return;
            }

            $calculator = ED_Dates_CK_Calculator::get_instance();
            if (!$calculator) {
                return;
            }

            $delivery_date = $calculator->calculate_estimated_delivery($product->get_id());
            if (!$delivery_date) {
                return;
            }

            ?>
            <div class="ed-dates-ck-product-delivery">
                <h4><?php echo esc_html__('Estimated Delivery', 'ed-dates-ck'); ?></h4>
                <p class="delivery-date"><?php echo esc_html($delivery_date); ?></p>
            </div>
            <?php
        } catch (Exception $e) {
            error_log('ED Dates CK - Error displaying product delivery date: ' . $e->getMessage());
        }
    }

    /**
     * Display delivery date in cart
     */
    public function display_cart_delivery_date($cart_item_name, $cart_item) {
        try {
            if (!isset($cart_item['product_id'])) {
                return $cart_item_name;
            }

            $calculator = ED_Dates_CK_Calculator::get_instance();
            if (!$calculator) {
                return $cart_item_name;
            }

            $delivery_date = $calculator->calculate_estimated_delivery($cart_item['product_id']);
            if (!$delivery_date) {
                return $cart_item_name;
            }

            ob_start();
            ?>
            <div class="ed-dates-ck-cart-delivery">
                <span class="label"><?php echo esc_html__('Estimated Delivery:', 'ed-dates-ck'); ?></span>
                <span class="date"><?php echo esc_html($delivery_date); ?></span>
            </div>
            <?php
            return $cart_item_name . ob_get_clean();
        } catch (Exception $e) {
            error_log('ED Dates CK - Error displaying cart delivery date: ' . $e->getMessage());
            return $cart_item_name;
        }
    }

    /**
     * Display delivery date on checkout
     */
    public function display_checkout_delivery_date() {
        try {
            if (!WC()->cart || WC()->cart->is_empty()) {
                return;
            }

            $calculator = ED_Dates_CK_Calculator::get_instance();
            if (!$calculator) {
                return;
            }

            // Get the latest delivery date from all cart items
            $latest_date = null;
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!isset($cart_item['product_id'])) {
                    continue;
                }

                $delivery_date = $calculator->calculate_estimated_delivery($cart_item['product_id']);
                if (!$delivery_date) {
                    continue;
                }

                $date_obj = DateTime::createFromFormat('l, F j, Y', $delivery_date);
                if (!$date_obj) {
                    continue;
                }

                if (!$latest_date || $date_obj > $latest_date) {
                    $latest_date = $date_obj;
                }
            }

            if (!$latest_date) {
                return;
            }

            ?>
            <div class="ed-dates-ck-checkout-delivery">
                <h3><?php echo esc_html__('Estimated Delivery Date', 'ed-dates-ck'); ?></h3>
                <p><?php echo esc_html($latest_date->format('l, F j, Y')); ?></p>
                <small><?php echo esc_html__('This is the estimated delivery date for your entire order.', 'ed-dates-ck'); ?></small>
            </div>
            <?php
        } catch (Exception $e) {
            error_log('ED Dates CK - Error displaying checkout delivery date: ' . $e->getMessage());
        }
    }
} 