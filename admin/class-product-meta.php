<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_EDD_Product_Meta
 *
 * Handles the product-specific lead time meta fields in the WooCommerce product editor.
 */
class WC_EDD_Product_Meta {
    /**
     * @var WC_EDD_Product_Meta The single instance of the class
     */
    protected static ?WC_EDD_Product_Meta $_instance = null;

    /**
     * Main WC_EDD_Product_Meta Instance
     */
    public static function get_instance(): WC_EDD_Product_Meta {
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
    private function init_hooks(): void {
        // Add fields to the 'Shipping' product data tab
        add_action('woocommerce_product_options_shipping', [$this, 'add_lead_time_fields']);

        // Save product data (using appropriate hooks for different product types)
        add_action('woocommerce_process_product_meta_simple', [$this, 'save_product_data']);
        add_action('woocommerce_process_product_meta_variable', [$this, 'save_product_data']);
        // Add hooks for other product types if necessary (grouped, external, etc.)

        // TODO: Add support for variations if required by future specs
    }

    /**
     * Add lead time fields to the Shipping tab in Product Data meta box.
     */
    public function add_lead_time_fields(): void {
        global $post, $product_object;

        // Ensure we have a product object
        $product = $product_object instanceof WC_Product ? $product_object : wc_get_product($post->ID);
        if (!$product) {
            return;
        }

        echo '<div class="options_group">'; // Group fields visually

        woocommerce_wp_text_input([
            'id' => '_wc_edd_min_lead_time', // Updated meta key
            'label' => __('Lead Time (Min)', 'wc-estimated-delivery-date'), // Spec Label
            'description' => __('Minimum lead time for this product (days). Overrides default.', 'wc-estimated-delivery-date'), // Spec Description (adjusted)
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => [
                'min' => '0',
                'step' => '1',
            ],
            // Use $product->get_meta() for HPOS compatibility
            'value' => $product->get_meta('_wc_edd_min_lead_time', true) ?: '', // Updated meta key
        ]);

        woocommerce_wp_text_input([
            'id' => '_wc_edd_max_lead_time', // Updated meta key
            'label' => __('Lead Time (Max)', 'wc-estimated-delivery-date'), // Spec Label
            'description' => __('Maximum lead time for this product (days). Overrides default.', 'wc-estimated-delivery-date'), // Spec Description (adjusted)
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => [
                'min' => '0',
                'step' => '1',
            ],
            // Use $product->get_meta() for HPOS compatibility
            'value' => $product->get_meta('_wc_edd_max_lead_time', true) ?: '', // Updated meta key
        ]);

        echo '</div>';
    }

    /**
     * Save product data (lead times)
     *
     * @param int $post_id The ID of the product being saved.
     */
    public function save_product_data(int $post_id): void {
        $product = wc_get_product($post_id);
        if (!$product) {
            return;
        }

        
                // Sanitize and save Min Lead Time
                $min_lead_time = isset($_POST['_wc_edd_min_lead_time']) && $_POST['_wc_edd_min_lead_time'] !== '' // Updated key
                    ? absint($_POST['_wc_edd_min_lead_time']) // Updated key
                    : ''; // Save empty string if cleared
                $product->update_meta_data('_wc_edd_min_lead_time', $min_lead_time); // Updated key
        
                // Sanitize and save Max Lead Time
                $max_lead_time = isset($_POST['_wc_edd_max_lead_time']) && $_POST['_wc_edd_max_lead_time'] !== '' // Updated key
                    ? absint($_POST['_wc_edd_max_lead_time']) // Updated key
                    : ''; // Save empty string if cleared
                $product->update_meta_data('_wc_edd_max_lead_time', $max_lead_time); // Updated key
        // Validate that min <= max if both are set
        if ($min_lead_time !== '' && $max_lead_time !== '' && $min_lead_time > $max_lead_time) {
            // Optionally add an admin notice about the invalid range
            // WC_Admin_Meta_Boxes::add_error( __( 'Lead Time (Min) cannot be greater than Lead Time (Max).', 'wc-estimated-delivery-date' ) );
            // For simplicity, we might just save the values as entered or adjust them (e.g., set max = min).
            // Let's save as entered for now.
        }

        $product->save_meta_data(); // Persist changes
    }

} // End class WC_EDD_Product_Meta