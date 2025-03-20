<?php
/**
 * Product Meta Class
 *
 * @package WooCommerce_Estimated_Delivery_Date
 */

defined('ABSPATH') || exit;

/**
 * WC_EDD_Product_Meta Class
 */
class WC_EDD_Product_Meta {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('woocommerce_product_options_shipping', [$this, 'add_lead_time_fields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_lead_time_fields']);
    }

    /**
     * Add lead time fields to product shipping tab
     */
    public function add_lead_time_fields() {
        global $post;

        echo '<div class="options_group">';

        woocommerce_wp_number_field([
            'id' => '_wc_edd_lead_time_min',
            'label' => __('Lead Time (Min)', 'wc-estimated-delivery-date'),
            'description' => __('Minimum lead time in days for this product. Leave empty to use default.', 'wc-estimated-delivery-date'),
            'desc_tip' => true,
            'custom_attributes' => [
                'min' => '0',
                'max' => '365',
                'step' => '1',
            ],
        ]);

        woocommerce_wp_number_field([
            'id' => '_wc_edd_lead_time_max',
            'label' => __('Lead Time (Max)', 'wc-estimated-delivery-date'),
            'description' => __('Maximum lead time in days for this product. Leave empty to use default.', 'wc-estimated-delivery-date'),
            'desc_tip' => true,
            'custom_attributes' => [
                'min' => '0',
                'max' => '365',
                'step' => '1',
            ],
        ]);

        echo '</div>';
    }

    /**
     * Save lead time fields
     *
     * @param WC_Product $product Product object.
     */
    public function save_lead_time_fields($product) {
        if (isset($_POST['_wc_edd_lead_time_min'])) {
            $product->update_meta_data(
                '_wc_edd_lead_time_min',
                sanitize_text_field($_POST['_wc_edd_lead_time_min'])
            );
        }

        if (isset($_POST['_wc_edd_lead_time_max'])) {
            $product->update_meta_data(
                '_wc_edd_lead_time_max',
                sanitize_text_field($_POST['_wc_edd_lead_time_max'])
            );
        }
    }

    /**
     * Get product lead time
     *
     * @param WC_Product $product Product object.
     * @return array Lead time array with min and max values.
     */
    public static function get_product_lead_time($product) {
        $lead_time = [
            'min' => null,
            'max' => null,
        ];

        // Get product specific lead times
        $lead_time_min = $product->get_meta('_wc_edd_lead_time_min');
        $lead_time_max = $product->get_meta('_wc_edd_lead_time_max');

        // If product specific lead times are set, use them
        if ('' !== $lead_time_min) {
            $lead_time['min'] = (int) $lead_time_min;
        }

        if ('' !== $lead_time_max) {
            $lead_time['max'] = (int) $lead_time_max;
        }

        // If no product specific lead times, use defaults
        if (null === $lead_time['min'] || null === $lead_time['max']) {
            $general_settings = get_option('wc_edd_general_settings', []);
            
            if (null === $lead_time['min']) {
                $lead_time['min'] = isset($general_settings['default_lead_time_min']) 
                    ? (int) $general_settings['default_lead_time_min'] 
                    : 0;
            }

            if (null === $lead_time['max']) {
                $lead_time['max'] = isset($general_settings['default_lead_time_max']) 
                    ? (int) $general_settings['default_lead_time_max'] 
                    : 0;
            }
        }

        return $lead_time;
    }
}