<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_EDD_Admin_Settings
 *
 * Handles the admin settings page for the WooCommerce Estimated Delivery Date plugin.
 */
class WC_EDD_Admin_Settings {
    /**
     * @var WC_EDD_Admin_Settings The single instance of the class
     */
    protected static ?WC_EDD_Admin_Settings $_instance = null;
    private string $active_tab = 'general';
    private array $settings = []; // Store settings locally
    private string $option_name = 'wc_edd_settings'; // Define option name
    private string $shipping_option_name = 'wc_edd_shipping_methods'; // Separate option for shipping methods

    /**
     * Main WC_EDD_Admin_Settings Instance
     */
    public static function get_instance(): WC_EDD_Admin_Settings {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option($this->option_name, $this->get_default_settings()); // Load settings on init
        $this->init_hooks();
    }

    /**
     * Get default settings values.
     */
    private function get_default_settings(): array {
        // Corresponds to the structure saved in the 'wc_edd_settings' option
        return [
            'general' => [
                'default_lead_time_min' => 1,
                'default_lead_time_max' => 3,
                'display_format' => 'range', // 'range' or 'latest'
                'days_to_add' => 0,
                'date_format' => 'F j, Y',
                'custom_text' => __('Estimated Delivery:', 'wc-estimated-delivery-date'),
                'cart_checkout_display' => false,
                'cutoff_time' => '16:00', // Added default cutoff time
            ],
            'closed_days' => [
                'store_weekly' => ['sunday'],
                'store_specific' => [], // Array of 'Y-m-d' dates
            ],
            'postage_days' => [
                'specific' => [], // Array of 'Y-m-d' dates
            ],
        ];
    }


    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Add settings
        add_action('admin_init', [$this, 'register_settings']);

        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Register AJAX handlers
        add_action('wp_ajax_wc_edd_get_shipping_methods', [$this, 'ajax_get_shipping_methods']); // Updated action
        add_action('wp_ajax_wc_edd_save_shipping_methods', [$this, 'ajax_save_shipping_methods']); // Updated action
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_menu_page(
            __('Estimate DD Settings', 'wc-estimated-delivery-date'),
            __('Estimate DD', 'wc-estimated-delivery-date'),
            'manage_woocommerce',
            'wc-edd-settings',
            [$this, 'render_settings_page'],
            'dashicons-calendar-alt',
            56
        );
    }

    /**
     * Register settings sections and fields.
     */
     public function register_settings(): void {
         // Register one option that holds all settings as an array (excluding shipping methods).
         register_setting(
             'wc_edd_settings_group',      // Option group used in settings_fields()
             $this->option_name,           // Option name
             [$this, 'sanitize_settings'] // Sanitize callback
         );

         // --- General Settings Section ---
         add_settings_section(
             'wc_edd_general_section',     // Section ID
             __('General Settings', 'wc-estimated-delivery-date'),
             null,                         // Section callback (optional description)
             'wc-edd-settings-general'     // Page slug for this section
         );

         add_settings_field(
             'default_lead_time',
             __('Default Lead Time', 'wc-estimated-delivery-date'),
             [$this, 'render_lead_time_field'],
             'wc-edd-settings-general',
             'wc_edd_general_section',
             ['label_for' => 'wc_edd_settings_general_default_lead_time_min'] // Associates label with first input
         );

         add_settings_field(
             'display_format',
             __('Display Format', 'wc-estimated-delivery-date'),
             [$this, 'render_display_format_field'],
             'wc-edd-settings-general',
             'wc_edd_general_section'
         );

         add_settings_field(
             'days_to_add',
             __('Days to Add', 'wc-estimated-delivery-date'),
             [$this, 'render_days_to_add_field'],
             'wc-edd-settings-general',
             'wc_edd_general_section'
         );

         add_settings_field(
             'date_format',
             __('Date Format', 'wc-estimated-delivery-date'),
             [$this, 'render_date_format_field'],
             'wc-edd-settings-general',
             'wc_edd_general_section'
         );

         add_settings_field(
             'custom_text',
             __('Custom Text', 'wc-estimated-delivery-date'),
             [$this, 'render_custom_text_field'],
             'wc-edd-settings-general',
             'wc_edd_general_section'
         );

         add_settings_field(
             'cart_checkout_display',
             __('Cart/Checkout Display', 'wc-estimated-delivery-date'),
             [$this, 'render_cart_checkout_display_field'],
             'wc-edd-settings-general',
             'wc_edd_general_section'
         );

         add_settings_field(
             'cutoff_time', // Field ID
             __('Order Cutoff Time', 'wc-estimated-delivery-date'), // Label
             [$this, 'render_cutoff_time_field'], // Callback to render the input
             'wc-edd-settings-general', // Page slug
             'wc_edd_general_section' // Section ID
         );

         // --- Store Closed Days Section ---
         add_settings_section(
             'wc_edd_store_closed_section',
             __('Store Closed Days', 'wc-estimated-delivery-date'),
             function() { echo '<p>' . esc_html__('Select days or specific dates when the store is closed. Deliveries will not be scheduled for these days.', 'wc-estimated-delivery-date') . '</p>'; },
             'wc-edd-settings-closed-days' // Page slug for this section
         );

         add_settings_field(
             'store_closed_days_weekly',
             __('Weekly Closed Days', 'wc-estimated-delivery-date'),
             [$this, 'render_store_closed_weekly_field'],
             'wc-edd-settings-closed-days',
             'wc_edd_store_closed_section'
         );

         add_settings_field(
             'store_closed_days_specific',
             __('Specific Closed Dates (Holidays)', 'wc-estimated-delivery-date'),
             [$this, 'render_store_closed_specific_field'],
             'wc-edd-settings-closed-days',
             'wc_edd_store_closed_section'
         );

         // --- Postage Closed Days Section ---
         add_settings_section(
             'wc_edd_postage_closed_section',
             __('Postage Closed Days', 'wc-estimated-delivery-date'),
              function() { echo '<p>' . esc_html__('Select specific dates when postage services are unavailable (e.g., public holidays). Shipping will not occur on these days.', 'wc-estimated-delivery-date') . '</p>'; },
             'wc-edd-settings-postage-days' // Page slug for this section
         );

         add_settings_field(
             'postage_closed_days_specific',
             __('Postage Closed Dates', 'wc-estimated-delivery-date'),
             [$this, 'render_postage_closed_specific_field'],
             'wc-edd-settings-postage-days',
             'wc_edd_postage_closed_section'
         );

         // --- Shipping Methods Section ---
         // Note: Shipping methods are saved via AJAX to a separate option 'wc_edd_shipping_methods'
         add_settings_section(
             'wc_edd_shipping_methods_section',
             __('Shipping Methods Transit Times', 'wc-estimated-delivery-date'),
             function() { echo '<p>' . esc_html__('Set the minimum and maximum transit times in days for each enabled shipping method. These times will be added to the lead time.', 'wc-estimated-delivery-date') . '</p>'; },
             'wc-edd-settings-shipping' // Page slug for this section
         );

         add_settings_field(
             'shipping_methods_table',
             '', // No label needed for the table itself
             [$this, 'render_shipping_methods_table'],
             'wc-edd-settings-shipping',
             'wc_edd_shipping_methods_section'
         );
     }

    // --- Sanitize Callback ---

    /**
     * Sanitize the settings array before saving.
     */
    public function sanitize_settings(array $input): array {
        $new_input = [];
        $defaults = $this->get_default_settings();

        // Sanitize General Settings
        $general = $input['general'] ?? [];
        $new_input['general']['default_lead_time_min'] = isset($general['default_lead_time_min']) ? absint($general['default_lead_time_min']) : $defaults['general']['default_lead_time_min'];
        $new_input['general']['default_lead_time_max'] = isset($general['default_lead_time_max']) ? absint($general['default_lead_time_max']) : $defaults['general']['default_lead_time_max'];
        // Ensure min <= max
        if ($new_input['general']['default_lead_time_min'] > $new_input['general']['default_lead_time_max']) {
            $new_input['general']['default_lead_time_max'] = $new_input['general']['default_lead_time_min'];
        }
        $new_input['general']['display_format'] = isset($general['display_format']) && in_array($general['display_format'], ['range', 'latest']) ? sanitize_key($general['display_format']) : $defaults['general']['display_format'];
        $new_input['general']['days_to_add'] = isset($general['days_to_add']) ? absint($general['days_to_add']) : $defaults['general']['days_to_add'];
        // Basic date format validation (can be improved)
        $allowed_date_formats = ['F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y', 'l, F j, Y'];
        $new_input['general']['date_format'] = isset($general['date_format']) && in_array($general['date_format'], $allowed_date_formats) ? sanitize_text_field($general['date_format']) : $defaults['general']['date_format'];
        $new_input['general']['custom_text'] = isset($general['custom_text']) ? sanitize_text_field($general['custom_text']) : $defaults['general']['custom_text'];
        $new_input['general']['cart_checkout_display'] = isset($general['cart_checkout_display']) && $general['cart_checkout_display'] === '1';
        // Sanitize cutoff time (expecting HH:MM format)
        $new_input['general']['cutoff_time'] = isset($general['cutoff_time']) && preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $general['cutoff_time']) ? sanitize_text_field($general['cutoff_time']) : $defaults['general']['cutoff_time'];


        // Sanitize Store Closed Days
        $closed_days = $input['closed_days'] ?? [];
        $valid_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $new_input['closed_days']['store_weekly'] = isset($closed_days['store_weekly']) && is_array($closed_days['store_weekly'])
            ? array_values(array_intersect($closed_days['store_weekly'], $valid_days))
            : $defaults['closed_days']['store_weekly'];
        // Sanitize specific dates (assuming Y-m-d format from JS)
        $new_input['closed_days']['store_specific'] = isset($closed_days['store_specific']) && is_array($closed_days['store_specific'])
            ? array_values(array_filter(array_map([$this, 'sanitize_date_string'], $closed_days['store_specific'])))
            : $defaults['closed_days']['store_specific'];

        // Sanitize Postage Closed Days
        $postage_days = $input['postage_days'] ?? [];
        $new_input['postage_days']['specific'] = isset($postage_days['specific']) && is_array($postage_days['specific'])
            ? array_values(array_filter(array_map([$this, 'sanitize_date_string'], $postage_days['specific'])))
            : $defaults['postage_days']['specific'];

        // Shipping methods are saved via AJAX, not here.

        return $new_input;
    }

    /** Helper function to sanitize date strings (Y-m-d) */
    private function sanitize_date_string($date_string): ?string {
        if (!is_string($date_string)) return null;
        $trimmed = sanitize_text_field($date_string);
        // Basic check for Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            // Further validation could check if it's a valid date
            return $trimmed;
        }
        return null;
    }

    // --- Field Rendering Callbacks ---

    /** Helper to get setting value */
    private function get_setting(string $group, string $key, $default = null) {
        // Ensure group exists
        $group_settings = $this->settings[$group] ?? $this->get_default_settings()[$group] ?? [];
        // Return key value or default
        return $group_settings[$key] ?? $default ?? null;
    }

    public function render_lead_time_field(): void {
        $min = $this->get_setting('general', 'default_lead_time_min');
        $max = $this->get_setting('general', 'default_lead_time_max');
        ?>
        <input type="number" id="wc_edd_settings_general_default_lead_time_min" name="<?php echo esc_attr($this->option_name); ?>[general][default_lead_time_min]" value="<?php echo esc_attr((string)$min); ?>" min="0" step="1" class="small-text" />
        <span>&nbsp;-&nbsp;</span>
        <input type="number" name="<?php echo esc_attr($this->option_name); ?>[general][default_lead_time_max]" value="<?php echo esc_attr((string)$max); ?>" min="0" step="1" class="small-text" />
        <p class="description"><?php esc_html_e('Minimum and maximum default lead time (days). Product-specific lead times override this.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_display_format_field(): void {
        $value = $this->get_setting('general', 'display_format');
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[general][display_format]">
            <option value="range" <?php selected($value, 'range'); ?>><?php esc_html_e('Estimated Delivery Date (Min) - (Max)', 'wc-estimated-delivery-date'); ?></option>
            <option value="latest" <?php selected($value, 'latest'); ?>><?php esc_html_e('Estimated Delivery by (Max)', 'wc-estimated-delivery-date'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Select how the estimated delivery date range is displayed.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_days_to_add_field(): void {
        $value = $this->get_setting('general', 'days_to_add');
        ?>
        <input type="number" name="<?php echo esc_attr($this->option_name); ?>[general][days_to_add]" value="<?php echo esc_attr((string)$value); ?>" min="0" step="1" class="small-text" />
        <p class="description"><?php esc_html_e('Additional days to always add to the calculated delivery date.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_date_format_field(): void {
        $value = $this->get_setting('general', 'date_format');
        $formats = [
            'F j, Y' => date_i18n('F j, Y'),
            'Y-m-d' => date_i18n('Y-m-d'),
            'm/d/Y' => date_i18n('m/d/Y'),
            'd/m/Y' => date_i18n('d/m/Y'),
            'l, F j, Y' => date_i18n('l, F j, Y'),
        ];
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[general][date_format]">
            <?php foreach ($formats as $format => $label) : ?>
                <option value="<?php echo esc_attr($format); ?>" <?php selected($value, $format); ?>>
                    <?php echo esc_html(date_i18n($format)); // Show example using the format itself ?> (<?php echo esc_html($format); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Select the format for displaying dates.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_custom_text_field(): void {
        $value = $this->get_setting('general', 'custom_text');
        ?>
        <input type="text" name="<?php echo esc_attr($this->option_name); ?>[general][custom_text]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter text to display before the delivery date (e.g., "Ships by:", "Get it by:"). Leave blank to use default.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_cart_checkout_display_field(): void {
        $value = $this->get_setting('general', 'cart_checkout_display');
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[general][cart_checkout_display]" value="1" <?php checked($value, true); ?> />
            <?php esc_html_e('Display the estimated delivery date on the cart and checkout pages.', 'wc-estimated-delivery-date'); ?>
        </label>
        <?php
    }

    public function render_cutoff_time_field(): void {
        $value = $this->get_setting('general', 'cutoff_time');
        ?>
        <input type="time" name="<?php echo esc_attr($this->option_name); ?>[general][cutoff_time]" value="<?php echo esc_attr($value); ?>" />
        <p class="description"><?php esc_html_e('Orders placed after this time will use the next available working day as the starting point for calculations.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_store_closed_weekly_field(): void {
        $selected_days = $this->get_setting('closed_days', 'store_weekly', []);
        $days = [
            'monday' => __('Monday', 'wc-estimated-delivery-date'),
            'tuesday' => __('Tuesday', 'wc-estimated-delivery-date'),
            'wednesday' => __('Wednesday', 'wc-estimated-delivery-date'),
            'thursday' => __('Thursday', 'wc-estimated-delivery-date'),
            'friday' => __('Friday', 'wc-estimated-delivery-date'),
            'saturday' => __('Saturday', 'wc-estimated-delivery-date'),
            'sunday' => __('Sunday', 'wc-estimated-delivery-date')
        ];
        echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html__('Weekly Closed Days', 'wc-estimated-delivery-date') . '</span></legend>';
        foreach ($days as $day => $label) {
            ?>
            <label style="margin-right: 15px; display: inline-block;">
                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[closed_days][store_weekly][]"
                       value="<?php echo esc_attr($day); ?>"
                       <?php checked(in_array($day, $selected_days, true)); ?>>
                <?php echo esc_html($label); ?>
            </label>
            <?php
        }
        echo '</fieldset>';
    }

    public function render_store_closed_specific_field(): void {
        $dates = $this->get_setting('closed_days', 'store_specific', []);
        // Basic input for now, JS will enhance this into a multi-date picker
        ?>
        <div class="wc-edd-date-picker-container" id="store-specific-dates">
             <?php // Input field for JS datepicker initialization ?>
            <input type="text" class="wc-edd-date-picker-trigger" placeholder="<?php esc_attr_e('Click to select dates', 'wc-estimated-delivery-date'); ?>" readonly style="width: 25em;">
            <div class="wc-edd-selected-dates" style="margin-top: 5px;">
                <?php foreach ($dates as $date) : if(empty($date)) continue; ?>
                    <span class="wc-edd-selected-date" data-date="<?php echo esc_attr($date); ?>" style="display: inline-block; background: #eee; padding: 2px 5px; margin: 2px; border-radius: 3px;">
                        <?php echo esc_html(date_i18n(get_option('date_format', 'Y-m-d'), strtotime($date))); ?>
                        <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[closed_days][store_specific][]" value="<?php echo esc_attr($date); ?>">
                        <button type="button" class="wc-edd-remove-date button-link delete" style="text-decoration: none; margin-left: 5px;" aria-label="<?php esc_attr_e('Remove date', 'wc-estimated-delivery-date'); ?>">&times;</button>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="description"><?php esc_html_e('Select specific dates when the store is closed.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_postage_closed_specific_field(): void {
        $dates = $this->get_setting('postage_days', 'specific', []);
        // Basic input for now, JS will enhance this
        ?>
         <div class="wc-edd-date-picker-container" id="postage-specific-dates">
            <input type="text" class="wc-edd-date-picker-trigger" placeholder="<?php esc_attr_e('Click to select dates', 'wc-estimated-delivery-date'); ?>" readonly style="width: 25em;">
            <div class="wc-edd-selected-dates" style="margin-top: 5px;">
                <?php foreach ($dates as $date) : if(empty($date)) continue; ?>
                    <span class="wc-edd-selected-date" data-date="<?php echo esc_attr($date); ?>" style="display: inline-block; background: #eee; padding: 2px 5px; margin: 2px; border-radius: 3px;">
                        <?php echo esc_html(date_i18n(get_option('date_format', 'Y-m-d'), strtotime($date))); ?>
                        <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[postage_days][specific][]" value="<?php echo esc_attr($date); ?>">
                        <button type="button" class="wc-edd-remove-date button-link delete" style="text-decoration: none; margin-left: 5px;" aria-label="<?php esc_attr_e('Remove date', 'wc-estimated-delivery-date'); ?>">&times;</button>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="description"><?php esc_html_e('Select specific dates when postage is unavailable.', 'wc-estimated-delivery-date'); ?></p>
        <?php
    }

    public function render_shipping_methods_table(): void {
        // This table will be populated and managed via AJAX by js/admin.js
        ?>
        <div id="wc-edd-shipping-methods-container">
            <p><em><?php esc_html_e('Loading shipping methods...', 'wc-estimated-delivery-date'); ?></em></p>
            <table class="wp-list-table widefat fixed striped wc-edd-shipping-methods-table" style="display: none;">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Enabled', 'wc-estimated-delivery-date'); ?></th>
                        <th scope="col"><?php esc_html_e('Shipping Method', 'wc-estimated-delivery-date'); ?></th>
                        <th scope="col"><?php esc_html_e('Zone', 'wc-estimated-delivery-date'); ?></th>
                        <th scope="col" class="wc-edd-transit-col"><?php esc_html_e('Transit Time (Min Days)', 'wc-estimated-delivery-date'); ?></th>
                        <th scope="col" class="wc-edd-transit-col"><?php esc_html_e('Transit Time (Max Days)', 'wc-estimated-delivery-date'); ?></th>
                    </tr>
                </thead>
                <tbody id="wc-edd-shipping-methods-body">
                    <?php // Rows will be added by JS ?>
                    <tr class="no-items"><td colspan="5"><?php esc_html_e('No shipping methods found or loaded.', 'wc-estimated-delivery-date'); ?></td></tr>
                </tbody>
            </table>
            <p><button type="button" id="wc-edd-save-shipping-methods" class="button button-primary" disabled><?php esc_html_e('Save Shipping Methods', 'wc-estimated-delivery-date'); ?></button></p>
            <span class="spinner" style="float: none; vertical-align: middle;"></span>
            <div id="wc-edd-shipping-methods-notice" style="display: inline-block; margin-left: 10px;"></div>
        </div>
        <?php
    }

    // --- Render Settings Page ---

    /**
     * Render settings page using tabs.
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap wc-edd-settings-wrap">
            <h1><?php echo esc_html__('Estimate DD Settings', 'wc-estimated-delivery-date'); ?></h1>

            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                 <a href="?page=wc-edd-settings&tab=general" class="nav-tab <?php echo $this->active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', 'wc-estimated-delivery-date'); ?>
                </a>
                 <a href="?page=wc-edd-settings&tab=closed_days" class="nav-tab <?php echo $this->active_tab === 'closed_days' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Store Closed Days', 'wc-estimated-delivery-date'); ?>
                </a>
                 <a href="?page=wc-edd-settings&tab=postage_days" class="nav-tab <?php echo $this->active_tab === 'postage_days' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Postage Closed Days', 'wc-estimated-delivery-date'); ?>
                </a>
                <a href="?page=wc-edd-settings&tab=shipping" class="nav-tab <?php echo $this->active_tab === 'shipping' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Shipping Methods', 'wc-estimated-delivery-date'); ?>
                </a>
            </nav>

            <form method="post" action="options.php" id="wc-edd-settings-form">
                <?php
                settings_fields('wc_edd_settings_group'); // Use the correct settings group

                echo '<div class="wc-edd-settings-content">';

                // Display sections based on active tab
                switch ($this->active_tab) {
                    case 'closed_days':
                        do_settings_sections('wc-edd-settings-closed-days');
                        break;
                    case 'postage_days':
                        do_settings_sections('wc-edd-settings-postage-days');
                        break;
                    case 'shipping':
                        do_settings_sections('wc-edd-settings-shipping');
                        // Shipping methods table is rendered, saving is handled via AJAX.
                        break;
                    case 'general':
                    default:
                        do_settings_sections('wc-edd-settings-general');
                        break;
                }

                echo '</div>'; // .wc-edd-settings-content

                // Only show submit button for non-AJAX saved tabs (General, Closed Days, Postage Days)
                if ($this->active_tab !== 'shipping') {
                     submit_button();
                }
                ?>
            </form>
        </div>
        <?php
    }

    // --- AJAX Handlers ---

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook_suffix): void {
        $screen = get_current_screen();
        // Only load on our settings page
        if (!$screen || $screen->id !== 'toplevel_page_wc-edd-settings') {
           return;
        }

        // Styles
        wp_enqueue_style('wp-color-picker'); // For potential future color pickers
        wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css'); // Datepicker style
        wp_enqueue_style(
            'wc-edd-admin-css',
            WC_EDD_PLUGIN_URL . 'assets/css/admin.css', // Path to admin CSS
            [],
            WC_EDD_VERSION
        );

        // Scripts
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('wp-util'); // Needed for AJAX templates
        wp_enqueue_script(
            'wc-edd-admin-js',
            WC_EDD_PLUGIN_URL . 'js/admin.js', // Path to admin JS
            ['jquery', 'wp-util', 'jquery-ui-datepicker', 'wp-color-picker'],
            WC_EDD_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('wc-edd-admin-js', 'wcEddAdminData', [ // Updated object name
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_edd_admin_nonce'), // Updated nonce action
            'i18n' => [
                'loading' => esc_html__('Loading...', 'wc-estimated-delivery-date'),
                'error' => esc_html__('An error occurred. Please try again.', 'wc-estimated-delivery-date'),
                'settingsSaved' => esc_html__('Settings saved successfully.', 'wc-estimated-delivery-date'),
                'removeDateConfirm' => esc_html__('Are you sure you want to remove this date?', 'wc-estimated-delivery-date'),
                'dateFormat' => $this->convert_php_to_jqueryui_date_format(get_option('date_format', 'Y-m-d')), // Pass WP date format converted for JS date picker
            ]
        ]);
    }


    /**
     * AJAX handler for getting shipping methods for the settings table.
     */
    public function ajax_get_shipping_methods(): void {
        check_ajax_referer('wc_edd_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied.', 'wc-estimated-delivery-date')], 403);
        }

        $all_methods = [];
        if (!class_exists('WC_Data_Store') || !class_exists('WC_Shipping_Zone')) {
             wp_send_json_error(['message' => __('WooCommerce classes not found.', 'wc-estimated-delivery-date')], 500);
        }

        $data_store = WC_Data_Store::load('shipping-zone');
        $zones = $data_store->get_zones();

        // Add Rest of the World zone
        $zones[] = new WC_Shipping_Zone(0);

        $saved_method_settings = get_option($this->shipping_option_name, []); // Load saved transit times

        foreach ($zones as $zone) {
            $zone_id = $zone->get_id();
            $zone_name = $zone->get_zone_name();
            if (empty($zone_name)) {
                $zone_name = __('Rest of World', 'wc-estimated-delivery-date');
            }
            $shipping_methods = $zone->get_shipping_methods(false); // Get only enabled methods for settings table

            foreach ($shipping_methods as $instance_id => $method) {
                 $method_key = $method->id . ':' . $instance_id;
                 $saved_settings = $saved_method_settings[$method_key] ?? ['min_transit' => '', 'max_transit' => ''];

                $all_methods[] = [
                    'instance_id' => $instance_id,
                    'id' => $method->id,
                    'method_key' => $method_key,
                    'zone_name' => $zone_name,
                    'title' => $method->get_title(), // User-defined title
                    'method_title' => $method->get_method_title(), // Method type title
                    'enabled' => $method->is_enabled(), // Should always be true due to get_shipping_methods(false)
                    'min_transit' => $saved_settings['min_transit'],
                    'max_transit' => $saved_settings['max_transit'],
                ];
            }
        }

        // Sort methods by zone then title
        usort($all_methods, function($a, $b) {
            $zone_cmp = strcmp($a['zone_name'], $b['zone_name']);
            if ($zone_cmp !== 0) return $zone_cmp;
            return strcmp($a['title'], $b['title']);
        });


        wp_send_json_success(['methods' => $all_methods]);
    }

    /**
     * AJAX handler for saving shipping method transit times.
     */
    public function ajax_save_shipping_methods(): void {
        check_ajax_referer('wc_edd_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Permission denied.', 'wc-estimated-delivery-date')], 403);
        }

        if (!isset($_POST['methods']) || !is_array($_POST['methods'])) {
            wp_send_json_error(['message' => __('Invalid data format.', 'wc-estimated-delivery-date')]);
        }

        $methods_data = wp_unslash($_POST['methods']); // Input is expected as an array of method objects
        $sanitized_settings = [];

        foreach ($methods_data as $method_data) {
            if (isset($method_data['method_key'])) {
                $key = sanitize_key($method_data['method_key']);
                $min = isset($method_data['min_transit']) && $method_data['min_transit'] !== '' ? absint($method_data['min_transit']) : '';
                $max = isset($method_data['max_transit']) && $method_data['max_transit'] !== '' ? absint($method_data['max_transit']) : '';

                // Ensure min <= max if both are set and numeric
                if (is_numeric($min) && is_numeric($max) && $min > $max) {
                    $max = $min;
                }

                // Only save if at least one value is provided
                if ($min !== '' || $max !== '') {
                    $sanitized_settings[$key] = [
                        'min_transit' => $min,
                        'max_transit' => $max,
                    ];
                }
            }
        }

        // Save all method settings under the separate option key
        update_option($this->shipping_option_name, $sanitized_settings);

        wp_send_json_success(['message' => __('Shipping method transit times saved successfully.', 'wc-estimated-delivery-date')]);
    }

    /** Convert PHP date format string to jQuery UI Datepicker format */
    private function convert_php_to_jqueryui_date_format(string $php_format): string {
        $map = [
            // Day
            'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
            // Month
            'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M',
            // Year
            'Y' => 'yy', 'y' => 'y',
            // Separators - Assume they remain the same
        ];
        $jqueryui_format = '';
        $escaping = false;
        for ($i = 0; $i < strlen($php_format); $i++) {
            $char = $php_format[$i];
            if ($char === '\\') { // Handle escaped characters
                $i++;
                $jqueryui_format .= ($i < strlen($php_format)) ? "'" . $php_format[$i] . "'" : '\\';
                continue;
            }
            if (isset($map[$char])) {
                $jqueryui_format .= $map[$char];
            } else {
                // Escape non-format characters
                $jqueryui_format .= "'" . $char . "'";
            }
        }
        return $jqueryui_format;
    }


} // End class WC_EDD_Admin_Settings