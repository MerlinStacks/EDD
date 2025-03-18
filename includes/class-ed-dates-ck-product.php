<?php
if (!defined('ABSPATH')) {
    exit;
}

class ED_Dates_CK_Product {
    /**
     * @var ED_Dates_CK_Product The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main ED_Dates_CK_Product Instance
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
        // Add product data tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        
        // Save product data
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        
        // Add AJAX endpoint for dynamic delivery date updates
        add_action('wp_ajax_get_estimated_delivery', array($this, 'ajax_get_estimated_delivery'));
        add_action('wp_ajax_nopriv_get_estimated_delivery', array($this, 'ajax_get_estimated_delivery'));
    }

    /**
     * Add product data tab
     */
    public function add_product_data_tab($tabs) {
        try {
            if (!is_array($tabs)) {
                error_log('ED Dates CK - Error: Invalid tabs parameter');
                return $tabs;
            }

            $tabs['ed_dates_ck'] = array(
                'label' => __('Delivery Lead Time', 'ed-dates-ck'),
                'target' => 'ed_dates_ck_product_data',
                'class' => array('show_if_simple', 'show_if_variable'),
                'priority' => 70
            );
            return $tabs;
        } catch (Exception $e) {
            error_log('ED Dates CK - Error adding product data tab: ' . $e->getMessage());
            return $tabs;
        }
    }

    /**
     * Add product data panel
     */
    public function add_product_data_panel() {
        try {
            global $post;
            
            if (!$post || !isset($post->ID)) {
                error_log('ED Dates CK - Error: Invalid post object');
                return;
            }

            $min_lead_time = get_post_meta($post->ID, '_ed_dates_ck_min_lead_time', true);
            $max_lead_time = get_post_meta($post->ID, '_ed_dates_ck_max_lead_time', true);

            // Ensure values are numeric
            $min_lead_time = is_numeric($min_lead_time) ? $min_lead_time : '';
            $max_lead_time = is_numeric($max_lead_time) ? $max_lead_time : '';
            ?>
            <div id="ed_dates_ck_product_data" class="panel woocommerce_options_panel">
                <?php
                woocommerce_wp_text_input(array(
                    'id' => '_ed_dates_ck_min_lead_time',
                    'label' => __('Minimum Lead Time (days)', 'ed-dates-ck'),
                    'type' => 'number',
                    'custom_attributes' => array(
                        'min' => '0',
                        'step' => '1'
                    ),
                    'value' => $min_lead_time,
                    'desc_tip' => true,
                    'description' => __('Minimum number of days needed for delivery', 'ed-dates-ck')
                ));

                woocommerce_wp_text_input(array(
                    'id' => '_ed_dates_ck_max_lead_time',
                    'label' => __('Maximum Lead Time (days)', 'ed-dates-ck'),
                    'type' => 'number',
                    'custom_attributes' => array(
                        'min' => '0',
                        'step' => '1'
                    ),
                    'value' => $max_lead_time,
                    'desc_tip' => true,
                    'description' => __('Maximum number of days needed for delivery', 'ed-dates-ck')
                ));
                ?>
            </div>
            <?php
        } catch (Exception $e) {
            error_log('ED Dates CK - Error adding product data panel: ' . $e->getMessage());
        }
    }

    /**
     * Save product data
     */
    public function save_product_data($post_id) {
        try {
            if (!$post_id) {
                error_log('ED Dates CK - Error: Invalid post ID');
                return;
            }

            // Verify nonce if it exists
            if (isset($_POST['_wpnonce'])) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {
                    error_log('ED Dates CK - Error: Nonce verification failed');
                    return;
                }
            }

            $min_lead_time = isset($_POST['_ed_dates_ck_min_lead_time']) ? absint($_POST['_ed_dates_ck_min_lead_time']) : 0;
            $max_lead_time = isset($_POST['_ed_dates_ck_max_lead_time']) ? absint($_POST['_ed_dates_ck_max_lead_time']) : 0;

            // Validate lead times
            if ($max_lead_time < $min_lead_time) {
                $max_lead_time = $min_lead_time;
            }

            update_post_meta($post_id, '_ed_dates_ck_min_lead_time', $min_lead_time);
            update_post_meta($post_id, '_ed_dates_ck_max_lead_time', $max_lead_time);
        } catch (Exception $e) {
            error_log('ED Dates CK - Error saving product data: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for getting estimated delivery date
     */
    public function ajax_get_estimated_delivery() {
        try {
            // Verify nonce
            if (!check_ajax_referer('ed_dates_ck_nonce', 'nonce', false)) {
                wp_send_json_error(array(
                    'message' => __('Security check failed', 'ed-dates-ck')
                ));
                return;
            }
            
            $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
            if (!$product_id) {
                wp_send_json_error(array(
                    'message' => __('Invalid product ID', 'ed-dates-ck')
                ));
                return;
            }

            // Verify product exists
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(array(
                    'message' => __('Product not found', 'ed-dates-ck')
                ));
                return;
            }

            $calculator = ED_Dates_CK_Calculator::get_instance();
            if (!$calculator) {
                wp_send_json_error(array(
                    'message' => __('Calculator initialization failed', 'ed-dates-ck')
                ));
                return;
            }

            $delivery_date = $calculator->calculate_estimated_delivery($product_id);
            if (!$delivery_date) {
                wp_send_json_error(array(
                    'message' => __('Could not calculate delivery date', 'ed-dates-ck')
                ));
                return;
            }
            
            wp_send_json_success(array(
                'delivery_date' => $delivery_date
            ));
        } catch (Exception $e) {
            error_log('ED Dates CK - Error in AJAX handler: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while processing your request', 'ed-dates-ck')
            ));
        }
    }
} 