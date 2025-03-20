<?php
/**
 * Admin Settings Class
 *
 * @package WooCommerce_Estimated_Delivery_Date
 */

defined('ABSPATH') || exit;

/**
 * WC_EDD_Admin_Settings Class
 */
class WC_EDD_Admin_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_item']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_wc_edd_get_shipping_methods', [$this, 'get_shipping_methods_ajax']);
    }

    /**
     * Add menu item
     */
    public function add_menu_item() {
        add_menu_page(
            __('Estimate DD', 'wc-estimated-delivery-date'),
            __('Estimate DD', 'wc-estimated-delivery-date'),
            'manage_woocommerce',
            'wc-estimated-delivery-date',
            [$this, 'settings_page'],
            'dashicons-calendar-alt',
            56
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wc_edd_settings', 'wc_edd_general_settings');
        register_setting('wc_edd_settings', 'wc_edd_shipping_methods');

        // General Settings section
        add_settings_section(
            'wc_edd_general_section',
            __('General Settings', 'wc-estimated-delivery-date'),
            [$this, 'general_section_callback'],
            'wc_edd_settings'
        );

        // Add settings fields
        add_settings_field(
            'default_lead_time_min',
            __('Default Lead Time (Min)', 'wc-estimated-delivery-date'),
            [$this, 'number_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'default_lead_time_min',
                'description' => __('Minimum default lead time (days).', 'wc-estimated-delivery-date'),
                'min' => 0,
                'max' => 365,
            ]
        );

        add_settings_field(
            'default_lead_time_max',
            __('Default Lead Time (Max)', 'wc-estimated-delivery-date'),
            [$this, 'number_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'default_lead_time_max',
                'description' => __('Maximum default lead time (days).', 'wc-estimated-delivery-date'),
                'min' => 0,
                'max' => 365,
            ]
        );

        add_settings_field(
            'display_format',
            __('Display Format', 'wc-estimated-delivery-date'),
            [$this, 'select_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'display_format',
                'description' => __('Select how the estimated delivery date is displayed.', 'wc-estimated-delivery-date'),
                'options' => [
                    'range' => __('Estimated Delivery Date (Min) - (Max)', 'wc-estimated-delivery-date'),
                    'max' => __('Estimated Delivery by (Max)', 'wc-estimated-delivery-date'),
                ],
            ]
        );

        add_settings_field(
            'days_to_add',
            __('Days to Add', 'wc-estimated-delivery-date'),
            [$this, 'number_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'days_to_add',
                'description' => __('Additional days to add to the estimated delivery date.', 'wc-estimated-delivery-date'),
                'min' => 0,
                'max' => 365,
            ]
        );

        add_settings_field(
            'date_format',
            __('Date Format', 'wc-estimated-delivery-date'),
            [$this, 'select_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'date_format',
                'description' => __('Select the format of the displayed date.', 'wc-estimated-delivery-date'),
                'options' => [
                    'F j, Y' => date('F j, Y'),
                    'Y-m-d' => date('Y-m-d'),
                    'm/d/Y' => date('m/d/Y'),
                    'd/m/Y' => date('d/m/Y'),
                    'l, F j, Y' => date('l, F j, Y'),
                ],
            ]
        );

        add_settings_field(
            'store_closed_days',
            __('Store Closed Days', 'wc-estimated-delivery-date'),
            [$this, 'calendar_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'store_closed_days',
                'description' => __('Select days when the store is closed.', 'wc-estimated-delivery-date'),
            ]
        );

        add_settings_field(
            'postage_closed_days',
            __('Postage Closed Days', 'wc-estimated-delivery-date'),
            [$this, 'calendar_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'postage_closed_days',
                'description' => __('Select days when postage is closed.', 'wc-estimated-delivery-date'),
            ]
        );

        add_settings_field(
            'cart_checkout_display',
            __('Cart/Checkout Display', 'wc-estimated-delivery-date'),
            [$this, 'checkbox_field_callback'],
            'wc_edd_settings',
            'wc_edd_general_section',
            [
                'label_for' => 'cart_checkout_display',
                'description' => __('Display the estimated delivery date on cart and checkout pages.', 'wc-estimated-delivery-date'),
            ]
        );
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure general settings for estimated delivery dates.', 'wc-estimated-delivery-date') . '</p>';
    }

    /**
     * Number field callback
     *
     * @param array $args Field arguments.
     */
    public function number_field_callback($args) {
        $options = get_option('wc_edd_general_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        ?>
        <input 
            type="number" 
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="wc_edd_general_settings[<?php echo esc_attr($args['label_for']); ?>]"
            value="<?php echo esc_attr($value); ?>"
            min="<?php echo esc_attr($args['min']); ?>"
            max="<?php echo esc_attr($args['max']); ?>"
            class="regular-text"
        >
        <p class="description">
            <?php echo esc_html($args['description']); ?>
        </p>
        <?php
    }

    /**
     * Select field callback
     *
     * @param array $args Field arguments.
     */
    public function select_field_callback($args) {
        $options = get_option('wc_edd_general_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        ?>
        <select
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="wc_edd_general_settings[<?php echo esc_attr($args['label_for']); ?>]"
            class="regular-text"
        >
            <?php foreach ($args['options'] as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php echo esc_html($args['description']); ?>
        </p>
        <?php
    }

    /**
     * Calendar field callback
     *
     * @param array $args Field arguments.
     */
    public function calendar_field_callback($args) {
        $options = get_option('wc_edd_general_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        ?>
        <div class="wc-edd-calendar-field">
            <input 
                type="text" 
                id="<?php echo esc_attr($args['label_for']); ?>"
                name="wc_edd_general_settings[<?php echo esc_attr($args['label_for']); ?>]"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text wc-edd-datepicker"
            >
            <p class="description">
                <?php echo esc_html($args['description']); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Checkbox field callback
     *
     * @param array $args Field arguments.
     */
    public function checkbox_field_callback($args) {
        $options = get_option('wc_edd_general_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        ?>
        <input 
            type="checkbox" 
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="wc_edd_general_settings[<?php echo esc_attr($args['label_for']); ?>]"
            value="1"
            <?php checked($value, 1); ?>
        >
        <p class="description">
            <?php echo esc_html($args['description']); ?>
        </p>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=wc-estimated-delivery-date&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General Settings', 'wc-estimated-delivery-date'); ?>
                </a>
                <a href="?page=wc-estimated-delivery-date&tab=shipping" class="nav-tab <?php echo $active_tab === 'shipping' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Shipping Methods', 'wc-estimated-delivery-date'); ?>
                </a>
            </h2>

            <?php if ($active_tab === 'general') : ?>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('wc_edd_settings');
                    do_settings_sections('wc_edd_settings');
                    submit_button();
                    ?>
                </form>
            <?php else : ?>
                <div id="wc-edd-shipping-methods">
                    <div class="loading">
                        <?php esc_html_e('Loading shipping methods...', 'wc-estimated-delivery-date'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get shipping methods AJAX callback
     */
    public function get_shipping_methods_ajax() {
        check_ajax_referer('wc_edd_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'wc-estimated-delivery-date'));
        }

        $shipping_methods = WC()->shipping()->get_shipping_methods();
        $saved_methods = get_option('wc_edd_shipping_methods', []);

        ob_start();
        ?>
        <form action="options.php" method="post">
            <?php settings_fields('wc_edd_settings'); ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shipping Method', 'wc-estimated-delivery-date'); ?></th>
                        <th><?php esc_html_e('Transit Time (Min)', 'wc-estimated-delivery-date'); ?></th>
                        <th><?php esc_html_e('Transit Time (Max)', 'wc-estimated-delivery-date'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shipping_methods as $method) : ?>
                        <tr>
                            <td><?php echo esc_html($method->get_method_title()); ?></td>
                            <td>
                                <input
                                    type="number"
                                    name="wc_edd_shipping_methods[<?php echo esc_attr($method->id); ?>][min]"
                                    value="<?php echo esc_attr(isset($saved_methods[$method->id]['min']) ? $saved_methods[$method->id]['min'] : ''); ?>"
                                    min="0"
                                    max="365"
                                    class="small-text"
                                >
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="wc_edd_shipping_methods[<?php echo esc_attr($method->id); ?>][max]"
                                    value="<?php echo esc_attr(isset($saved_methods[$method->id]['max']) ? $saved_methods[$method->id]['max'] : ''); ?>"
                                    min="0"
                                    max="365"
                                    class="small-text"
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
}