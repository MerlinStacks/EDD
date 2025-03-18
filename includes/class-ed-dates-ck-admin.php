<?php
if (!defined('ABSPATH')) {
    exit;
}

class ED_Dates_CK_Admin {
    /**
     * @var ED_Dates_CK_Admin The single instance of the class
     */
    protected static $_instance = null;
    private $active_tab = 'general';

    /**
     * Main ED_Dates_CK_Admin Instance
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Add settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add product data tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));

        // Save product data
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));

        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Register AJAX handlers
        add_action('wp_ajax_ed_dates_ck_get_zone_methods', array($this, 'ajax_get_zone_methods'));
        add_action('wp_ajax_ed_dates_ck_get_method_settings', array($this, 'ajax_get_method_settings'));
        add_action('wp_ajax_ed_dates_ck_save_method_settings', array($this, 'ajax_save_method_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('EDDates CK Settings', 'ed-dates-ck'),
            __('EDDates CK', 'ed-dates-ck'),
            'manage_woocommerce',
            'ed-dates-ck-settings',
            array($this, 'render_settings_page'),
            'dashicons-calendar-alt',
            56 // Position after WooCommerce
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_settings');

        // General Settings Section
        add_settings_section(
            'ed_dates_ck_general_section',
            __('General Settings', 'ed-dates-ck'),
            array($this, 'render_general_section'),
            'ed_dates_ck_settings'
        );

        // Default Lead Time
        add_settings_field(
            'ed_dates_ck_default_lead_time',
            __('Default Lead Time (days)', 'ed-dates-ck'),
            array($this, 'render_default_lead_time_field'),
            'ed_dates_ck_settings',
            'ed_dates_ck_general_section'
        );

        // Date Format
        add_settings_field(
            'ed_dates_ck_date_format',
            __('Date Format', 'ed-dates-ck'),
            array($this, 'render_date_format_field'),
            'ed_dates_ck_settings',
            'ed_dates_ck_general_section'
        );

        // Preview Section
        add_settings_section(
            'ed_dates_ck_preview_section',
            __('Preview', 'ed-dates-ck'),
            array($this, 'render_preview_section'),
            'ed_dates_ck_settings'
        );

        register_setting('ed_dates_ck_settings', 'ed_dates_ck_order_cutoff_time');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_shop_closed_days');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_shop_holidays');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_postage_holidays');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_shipping_methods');
    }

    /**
     * Render general section description
     */
    public function render_general_section() {
        ?>
        <p><?php _e('Configure general settings for estimated delivery dates.', 'ed-dates-ck'); ?></p>
        <?php
    }

    /**
     * Render default lead time field
     */
    public function render_default_lead_time_field() {
        $settings = get_option('ed_dates_ck_settings', array());
        $default_lead_time = isset($settings['default_lead_time']) ? intval($settings['default_lead_time']) : 0;
        ?>
        <input type="number"
               id="ed_dates_ck_default_lead_time"
               name="ed_dates_ck_settings[default_lead_time]"
               value="<?php echo esc_attr($default_lead_time); ?>"
               min="0"
               step="1"
               class="small-text" />
        <p class="description">
            <?php _e('Default number of days needed to prepare an order. This can be overridden per product.', 'ed-dates-ck'); ?>
        </p>
        <?php
    }

    /**
     * Render date format field
     */
    public function render_date_format_field() {
        $settings = get_option('ed_dates_ck_settings', array());
        $date_format = isset($settings['date_format']) ? $settings['date_format'] : 'range';
        ?>
        <select id="ed_dates_ck_date_format"
                name="ed_dates_ck_settings[date_format]"
                class="regular-text">
            <option value="range" <?php selected($date_format, 'range'); ?>>
                <?php _e('Date Range (e.g., January 13 - January 14)', 'ed-dates-ck'); ?>
            </option>
            <option value="latest" <?php selected($date_format, 'latest'); ?>>
                <?php _e('Latest Date (e.g., Delivery by 17th of January)', 'ed-dates-ck'); ?>
            </option>
        </select>
        <p class="description">
            <?php _e('Choose how to display the estimated delivery date.', 'ed-dates-ck'); ?>
        </p>
        <?php
    }

    /**
     * Render preview section
     */
    public function render_preview_section() {
        $settings = get_option('ed_dates_ck_settings', array());
        $date_format = isset($settings['date_format']) ? $settings['date_format'] : 'range';
        $default_lead_time = isset($settings['default_lead_time']) ? intval($settings['default_lead_time']) : 0;

        // Get sample dates
        $calculator = ED_Dates_CK_Calculator::get_instance();
        if (!$calculator) {
            return;
        }

        $delivery_dates = $calculator->calculate_delivery_range(null, $default_lead_time);
        if (empty($delivery_dates)) {
            return;
        }

        $formatted_date = '';
        if ($date_format === 'range') {
            $formatted_date = sprintf(
                '%s - %s',
                date_i18n('F j', strtotime($delivery_dates['start'])),
                date_i18n('F j', strtotime($delivery_dates['end']))
            );
        } else {
            $formatted_date = sprintf(
                __('Delivery by %s', 'ed-dates-ck'),
                date_i18n('jS \o\f F', strtotime($delivery_dates['end']))
            );
        }
        ?>
        <div class="ed-dates-ck-preview">
            <h3><?php _e('Preview of Delivery Date Display', 'ed-dates-ck'); ?></h3>
            <div class="ed-dates-ck-preview-block">
                <div class="wp-block-ed-dates-ck-estimated-delivery ed-dates-ck-block ed-dates-ck-style-default ed-dates-ck-border-left-accent ed-dates-ck-icon-left">
                    <span class="ed-dates-ck-icon dashicons dashicons-calendar-alt"></span>
                    <div class="ed-dates-ck-content">
                        <h3><?php echo esc_html__('Estimated Delivery', 'ed-dates-ck'); ?></h3>
                        <p class="delivery-date"><?php echo esc_html($formatted_date); ?></p>
                    </div>
                </div>
            </div>
            <p class="description">
                <?php _e('This is how the delivery date will appear on your site.', 'ed-dates-ck'); ?>
            </p>
        </div>
        <style>
            .ed-dates-ck-preview {
                margin: 20px 0;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .ed-dates-ck-preview h3 {
                margin-top: 0;
            }
            .ed-dates-ck-preview-block {
                max-width: 400px;
                margin: 20px 0;
            }
        </style>
        <?php
    }

    /**
     * Get all available shipping methods
     */
    private function get_all_shipping_methods() {
        $shipping_methods = array();
        
        // Get shipping zones
        $zones = WC_Shipping_Zones::get_zones();
        
        // Add shipping methods from zones
        foreach ($zones as $zone_data) {
            $zone = new WC_Shipping_Zone($zone_data['id']);
            $zone_methods = $zone->get_shipping_methods(true); // Include inactive methods
            
            foreach ($zone_methods as $method) {
                $method_id = $method->id . ':' . $method->instance_id;
                $shipping_methods[$method_id] = array(
                    'title' => sprintf('%s - %s', $zone_data['zone_name'], $method->get_title()),
                    'method' => $method
                );
            }
        }
        
        // Get methods for "Rest of the World" zone
        $rest_of_world = new WC_Shipping_Zone(0);
        $rest_methods = $rest_of_world->get_shipping_methods(true);
        
        foreach ($rest_methods as $method) {
            $method_id = $method->id . ':' . $method->instance_id;
            $shipping_methods[$method_id] = array(
                'title' => sprintf('%s - %s', __('Rest of World', 'ed-dates-ck'), $method->get_title()),
                'method' => $method
            );
        }
        
        // Get non-zone methods (legacy/global methods)
        $legacy_methods = WC()->shipping()->load_shipping_methods();
        foreach ($legacy_methods as $method) {
            // Skip methods that are zone specific
            if ($method->supports('shipping-zones')) {
                continue;
            }
            
            $method_id = $method->id;
            if (!isset($shipping_methods[$method_id])) {
                $shipping_methods[$method_id] = array(
                    'title' => sprintf('%s (%s)', $method->get_method_title(), __('Global', 'ed-dates-ck')),
                    'method' => $method
                );
            }
        }
        
        return $shipping_methods;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $this->active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        $shipping_methods = $this->get_all_shipping_methods();
        $saved_methods = get_option('ed_dates_ck_shipping_methods', array());
        $shop_closed_days = get_option('ed_dates_ck_shop_closed_days', array('sunday'));
        $shop_holidays = get_option('ed_dates_ck_shop_holidays', array());
        $postage_holidays = get_option('ed_dates_ck_postage_holidays', array());
        $order_cutoff_time = get_option('ed_dates_ck_order_cutoff_time', '16:00');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('EDDates CK Settings', 'ed-dates-ck'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#general" 
                   class="nav-tab <?php echo $this->active_tab === 'general' ? 'nav-tab-active' : ''; ?>"
                   data-tab="general">
                    <?php esc_html_e('General Settings', 'ed-dates-ck'); ?>
                </a>
                <a href="#shipping" 
                   class="nav-tab <?php echo $this->active_tab === 'shipping' ? 'nav-tab-active' : ''; ?>"
                   data-tab="shipping">
                    <?php esc_html_e('Shipping Methods', 'ed-dates-ck'); ?>
                </a>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('ed_dates_ck_settings'); ?>

                <div id="general" class="ed-dates-ck-tab-content" <?php echo $this->active_tab !== 'general' ? 'style="display:none;"' : ''; ?>>
                    <div class="ed-dates-ck-settings-section">
                        <h3><?php echo esc_html__('General Settings', 'ed-dates-ck'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Order Cut-off Time', 'ed-dates-ck'); ?></th>
                                <td>
                                    <input type="time" name="ed_dates_ck_order_cutoff_time" 
                                           value="<?php echo esc_attr($order_cutoff_time); ?>">
                                </td>
                            </tr>
                        </table>

                        <h3><?php echo esc_html__('Shop Closed Days', 'ed-dates-ck'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Select Days', 'ed-dates-ck'); ?></th>
                                <td>
                                    <?php
                                    $days = array(
                                        'monday' => __('Monday', 'ed-dates-ck'),
                                        'tuesday' => __('Tuesday', 'ed-dates-ck'),
                                        'wednesday' => __('Wednesday', 'ed-dates-ck'),
                                        'thursday' => __('Thursday', 'ed-dates-ck'),
                                        'friday' => __('Friday', 'ed-dates-ck'),
                                        'saturday' => __('Saturday', 'ed-dates-ck'),
                                        'sunday' => __('Sunday', 'ed-dates-ck')
                                    );
                                    foreach ($days as $day => $label) {
                                        ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="ed_dates_ck_shop_closed_days[]" 
                                                   value="<?php echo esc_attr($day); ?>"
                                                   <?php checked(in_array($day, $shop_closed_days)); ?>>
                                            <?php echo esc_html($label); ?>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>

                        <h3><?php echo esc_html__('Holidays', 'ed-dates-ck'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php echo esc_html__('Shop Holidays', 'ed-dates-ck'); ?></th>
                                <td>
                                    <div class="holiday-picker">
                                        <input type="text" class="holiday-datepicker" id="shop-holidays-picker" 
                                               placeholder="<?php esc_attr_e('Select holiday dates', 'ed-dates-ck'); ?>">
                                        <div id="shop-holidays-container" class="holiday-dates">
                                            <?php
                                            foreach ($shop_holidays as $holiday) {
                                                ?>
                                                <div class="holiday-date">
                                                    <input type="hidden" name="ed_dates_ck_shop_holidays[]" value="<?php echo esc_attr($holiday); ?>">
                                                    <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($holiday))); ?></span>
                                                    <button type="button" class="remove-date">&times;</button>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php echo esc_html__('Postage Holidays', 'ed-dates-ck'); ?></th>
                                <td>
                                    <div class="holiday-picker">
                                        <input type="text" class="holiday-datepicker" id="postage-holidays-picker" 
                                               placeholder="<?php esc_attr_e('Select postage holiday dates', 'ed-dates-ck'); ?>">
                                        <div id="postage-holidays-container" class="holiday-dates">
                                            <?php
                                            foreach ($postage_holidays as $holiday) {
                                                ?>
                                                <div class="holiday-date">
                                                    <input type="hidden" name="ed_dates_ck_postage_holidays[]" value="<?php echo esc_attr($holiday); ?>">
                                                    <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($holiday))); ?></span>
                                                    <button type="button" class="remove-date">&times;</button>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div id="shipping" class="ed-dates-ck-tab-content" <?php echo $this->active_tab !== 'shipping' ? 'style="display:none;"' : ''; ?>>
                    <div class="ed-dates-ck-shipping-methods">
                        <!-- Step 1: Select Shipping Zone -->
                        <div class="ed-dates-ck-step">
                            <div class="ed-dates-ck-step-header">
                                <h3>
                                    <span class="step-number">1</span>
                                    <?php esc_html_e('Select Shipping Zone', 'ed-dates-ck'); ?>
                                </h3>
                            </div>
                            <div class="ed-dates-ck-step-content">
                                <div class="ed-dates-ck-zone-items">
                                    <?php
                                    $zones = WC_Shipping_Zones::get_zones();
                                    foreach ($zones as $zone_id => $zone) {
                                        ?>
                                        <div class="ed-dates-ck-zone-item" data-zone-id="<?php echo esc_attr($zone_id); ?>">
                                            <span class="zone-name"><?php echo esc_html($zone['zone_name']); ?></span>
                                            <span class="zone-regions"><?php echo esc_html($this->get_zone_regions_text($zone)); ?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <div class="ed-dates-ck-zone-item" data-zone-id="0">
                                        <span class="zone-name"><?php esc_html_e('Rest of World', 'ed-dates-ck'); ?></span>
                                        <span class="zone-regions"><?php esc_html_e('Any region not covered by other zones', 'ed-dates-ck'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Choose Shipping Method -->
                        <div class="ed-dates-ck-step">
                            <div class="ed-dates-ck-step-header">
                                <h3>
                                    <span class="step-number">2</span>
                                    <?php esc_html_e('Choose Shipping Method', 'ed-dates-ck'); ?>
                                </h3>
                            </div>
                            <div class="ed-dates-ck-step-content">
                                <div class="ed-dates-ck-method-items">
                                    <div class="ed-dates-ck-placeholder">
                                        <?php esc_html_e('Select a shipping zone to view available methods', 'ed-dates-ck'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Set Shipping Days -->
                        <div class="ed-dates-ck-step">
                            <div class="ed-dates-ck-step-header">
                                <h3>
                                    <span class="step-number">3</span>
                                    <?php esc_html_e('Configure Delivery Settings', 'ed-dates-ck'); ?>
                                </h3>
                            </div>
                            <div class="ed-dates-ck-step-content">
                                <div class="ed-dates-ck-days-settings">
                                    <div class="ed-dates-ck-form-row">
                                        <label>
                                            <?php esc_html_e('Minimum Days', 'ed-dates-ck'); ?>
                                            <span class="ed-dates-ck-info-icon dashicons dashicons-editor-help" 
                                                  title="<?php esc_attr_e('Minimum number of days for delivery', 'ed-dates-ck'); ?>">
                                            </span>
                                        </label>
                                        <input type="number" class="ed-dates-ck-min-days" min="0" step="1" value="2">
                                        <p class="description">
                                            <?php esc_html_e('The minimum number of days needed for delivery', 'ed-dates-ck'); ?>
                                        </p>
                                    </div>

                                    <div class="ed-dates-ck-form-row">
                                        <label>
                                            <?php esc_html_e('Maximum Days', 'ed-dates-ck'); ?>
                                            <span class="ed-dates-ck-info-icon dashicons dashicons-editor-help" 
                                                  title="<?php esc_attr_e('Maximum number of days for delivery', 'ed-dates-ck'); ?>">
                                            </span>
                                        </label>
                                        <input type="number" class="ed-dates-ck-max-days" min="0" step="1" value="5">
                                        <p class="description">
                                            <?php esc_html_e('The maximum number of days needed for delivery', 'ed-dates-ck'); ?>
                                        </p>
                                    </div>

                                    <div class="ed-dates-ck-form-row">
                                        <label>
                                            <?php esc_html_e('Cutoff Time', 'ed-dates-ck'); ?>
                                            <span class="ed-dates-ck-info-icon dashicons dashicons-editor-help" 
                                                  title="<?php esc_attr_e('Orders placed after this time will be processed the next day', 'ed-dates-ck'); ?>">
                                            </span>
                                        </label>
                                        <input type="time" class="ed-dates-ck-cutoff" value="16:00">
                                        <p class="description">
                                            <?php esc_html_e('Orders placed after this time will be processed the next business day', 'ed-dates-ck'); ?>
                                        </p>
                                    </div>

                                    <div class="ed-dates-ck-form-row">
                                        <label>
                                            <?php esc_html_e('Additional Settings', 'ed-dates-ck'); ?>
                                        </label>
                                        <div class="ed-dates-ck-checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" class="ed-dates-ck-non-working-days">
                                                <?php esc_html_e('Use Non-Working Days', 'ed-dates-ck'); ?>
                                                <span class="description">
                                                    <?php esc_html_e('Apply shop closed days to this method', 'ed-dates-ck'); ?>
                                                </span>
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" class="ed-dates-ck-overwrite-holidays">
                                                <?php esc_html_e('Override Global Holidays', 'ed-dates-ck'); ?>
                                                <span class="description">
                                                    <?php esc_html_e('Set method-specific holidays', 'ed-dates-ck'); ?>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="ed-dates-ck-form-row ed-dates-ck-method-holidays" style="display: none;">
                                        <label>
                                            <?php esc_html_e('Method-Specific Holidays', 'ed-dates-ck'); ?>
                                        </label>
                                        <div class="ed-dates-ck-holiday-picker-wrapper">
                                            <input type="text" class="ed-dates-ck-holiday-picker" 
                                                   placeholder="<?php esc_attr_e('Select holiday dates', 'ed-dates-ck'); ?>">
                                            <div class="ed-dates-ck-holiday-dates"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ed-dates-ck-save">
                        <button type="button" class="button button-primary ed-dates-ck-save-method">
                            <?php esc_html_e('Save Method Settings', 'ed-dates-ck'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Get formatted text for zone regions
     */
    private function get_zone_regions_text($zone) {
        $regions = array();
        
        if (!empty($zone['zone_locations'])) {
            foreach ($zone['zone_locations'] as $location) {
                switch ($location->type) {
                    case 'country':
                        $country = WC()->countries->countries[$location->code];
                        $regions[] = $country;
                        break;
                    case 'state':
                        $country_state = explode(':', $location->code);
                        $country = WC()->countries->countries[$country_state[0]];
                        $state = WC()->countries->get_states($country_state[0])[$country_state[1]];
                        $regions[] = "$state, $country";
                        break;
                    case 'postcode':
                        $regions[] = __('Postcodes: ', 'ed-dates-ck') . $location->code;
                        break;
                }
            }
        }
        
        return implode(', ', $regions) ?: __('No regions set', 'ed-dates-ck');
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
     * Register AJAX handlers for shipping methods interface
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_ed_dates_ck_get_zone_methods', array($this, 'ajax_get_zone_methods'));
        add_action('wp_ajax_ed_dates_ck_get_method_settings', array($this, 'ajax_get_method_settings'));
        add_action('wp_ajax_ed_dates_ck_save_method_settings', array($this, 'ajax_save_method_settings'));
    }

    /**
     * AJAX handler for getting shipping methods for a zone
     */
    public function ajax_get_zone_methods() {
        check_ajax_referer('ed_dates_ck_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ed-dates-ck')));
        }

        $zone_id = isset($_POST['zone_id']) ? absint($_POST['zone_id']) : 0;
        if (!$zone_id) {
            wp_send_json_error(array('message' => __('Invalid zone ID.', 'ed-dates-ck')));
        }

        $zone = WC_Shipping_Zones::get_zone($zone_id);
        if (!$zone) {
            wp_send_json_error(array('message' => __('Zone not found.', 'ed-dates-ck')));
        }

        $methods = $zone->get_shipping_methods();
        ob_start();
        ?>
        <div class="ed-dates-ck-method-items">
            <?php foreach ($methods as $method) : ?>
                <div class="ed-dates-ck-method-item" data-method-id="<?php echo esc_attr($method->id); ?>">
                    <div class="ed-dates-ck-method-title"><?php echo esc_html($method->get_title()); ?></div>
                    <div class="ed-dates-ck-method-type"><?php echo esc_html($method->get_method_title()); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler for getting shipping method settings
     */
    public function ajax_get_method_settings() {
        check_ajax_referer('ed_dates_ck_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ed-dates-ck')));
        }

        $method_id = isset($_POST['method_id']) ? sanitize_text_field($_POST['method_id']) : '';
        if (!$method_id) {
            wp_send_json_error(array('message' => __('Invalid method ID.', 'ed-dates-ck')));
        }

        $settings = get_option('ed_dates_ck_method_' . $method_id, array());
        wp_send_json_success($settings);
    }

    /**
     * AJAX handler for saving shipping method settings
     */
    public function ajax_save_method_settings() {
        check_ajax_referer('ed_dates_ck_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ed-dates-ck')));
        }

        $method_id = isset($_POST['method_id']) ? sanitize_text_field($_POST['method_id']) : '';
        if (!$method_id) {
            wp_send_json_error(array('message' => __('Invalid method ID.', 'ed-dates-ck')));
        }

        $settings = isset($_POST['settings']) ? $this->sanitize_method_settings($_POST['settings']) : array();
        update_option('ed_dates_ck_method_' . $method_id, $settings);

        wp_send_json_success(array('message' => __('Settings saved successfully.', 'ed-dates-ck')));
    }

    /**
     * Sanitize shipping method settings
     */
    private function sanitize_method_settings($settings) {
        return array(
            'min_days' => isset($settings['min_days']) ? absint($settings['min_days']) : 2,
            'max_days' => isset($settings['max_days']) ? absint($settings['max_days']) : 5,
            'cutoff_time' => isset($settings['cutoff_time']) ? sanitize_text_field($settings['cutoff_time']) : '16:00',
            'non_working_days' => isset($settings['non_working_days']) ? (bool) $settings['non_working_days'] : false,
            'overwrite_holidays' => isset($settings['overwrite_holidays']) ? (bool) $settings['overwrite_holidays'] : false,
            'holidays' => isset($settings['holidays']) ? array_map('sanitize_text_field', (array) $settings['holidays']) : array()
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'ed-dates-ck') !== false) {
            wp_enqueue_style('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-tooltip');

            wp_enqueue_style(
                'ed-dates-ck-admin',
                ED_DATES_CK_PLUGIN_URL . 'assets/css/admin.css',
                array('jquery-ui-datepicker'),
                ED_DATES_CK_VERSION
            );

            wp_enqueue_script(
                'ed-dates-ck-admin',
                ED_DATES_CK_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-datepicker', 'jquery-ui-tooltip'),
                ED_DATES_CK_VERSION,
                true
            );

            wp_localize_script('ed-dates-ck-admin', 'edDatesCkAdmin', array(
                'nonce' => wp_create_nonce('ed_dates_ck_admin'),
                'i18n' => array(
                    'settingsSaved' => __('Settings saved successfully.', 'ed-dates-ck'),
                    'errorSaving' => __('Error saving settings.', 'ed-dates-ck'),
                    'done' => __('Done', 'ed-dates-ck')
                )
            ));
        }
    }
} 