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
        $tabs['ed_dates_ck'] = array(
            'label' => __('Delivery Lead Time', 'ed-dates-ck'),
            'target' => 'ed_dates_ck_product_data',
            'class' => array('show_if_simple', 'show_if_variable'),
            'priority' => 70
        );
        return $tabs;
    }

    /**
     * Add product data panel
     */
    public function add_product_data_panel() {
        global $post;
        ?>
        <div id="ed_dates_ck_product_data" class="panel woocommerce_options_panel">
            <?php
            woocommerce_wp_text_input(array(
                'id' => '_ed_dates_ck_min_lead_time',
                'label' => __('Minimum Lead Time (days)', 'ed-dates-ck'),
                'type' => 'number',
                'custom_attributes' => array(
                    'min' => '0'
                ),
                'value' => get_post_meta($post->ID, '_ed_dates_ck_min_lead_time', true)
            ));

            woocommerce_wp_text_input(array(
                'id' => '_ed_dates_ck_max_lead_time',
                'label' => __('Maximum Lead Time (days)', 'ed-dates-ck'),
                'type' => 'number',
                'custom_attributes' => array(
                    'min' => '0'
                ),
                'value' => get_post_meta($post->ID, '_ed_dates_ck_max_lead_time', true)
            ));
            ?>
        </div>
        <?php
    }

    /**
     * Save product data
     */
    public function save_product_data($post_id) {
        $min_lead_time = isset($_POST['_ed_dates_ck_min_lead_time']) ? absint($_POST['_ed_dates_ck_min_lead_time']) : 0;
        $max_lead_time = isset($_POST['_ed_dates_ck_max_lead_time']) ? absint($_POST['_ed_dates_ck_max_lead_time']) : 0;

        update_post_meta($post_id, '_ed_dates_ck_min_lead_time', $min_lead_time);
        update_post_meta($post_id, '_ed_dates_ck_max_lead_time', $max_lead_time);
    }

    /**
     * AJAX handler for getting estimated delivery date
     */
    public function ajax_get_estimated_delivery() {
        check_ajax_referer('ed_dates_ck_nonce', 'nonce');
        
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $calculator = ED_Dates_CK_Calculator::get_instance();
        $delivery_date = $calculator->calculate_estimated_delivery($product_id);
        
        wp_send_json_success(array(
            'delivery_date' => $delivery_date
        ));
    }
} 