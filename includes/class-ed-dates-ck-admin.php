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
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_order_cutoff_time');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_shop_closed_days');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_shop_holidays');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_postage_holidays');
        register_setting('ed_dates_ck_settings', 'ed_dates_ck_shipping_methods');
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
                    <div class="ed-dates-ck-settings-section">
                        <h3><?php echo esc_html__('Shipping Methods', 'ed-dates-ck'); ?></h3>
                        <div class="shipping-methods-grid">
                            <?php
                            foreach ($shipping_methods as $method_id => $method_data) {
                                $method_settings = isset($saved_methods[$method_id]) ? $saved_methods[$method_id] : array(
                                    'min_days' => 1,
                                    'max_days' => 3
                                );
                                ?>
                                <div class="shipping-method-card">
                                    <h4><?php echo esc_html($method_data['title']); ?></h4>
                                    <div class="method-settings">
                                        <div class="setting-group">
                                            <label><?php esc_html_e('Minimum Days', 'ed-dates-ck'); ?></label>
                                            <input type="number" 
                                                   name="ed_dates_ck_shipping_methods[<?php echo esc_attr($method_id); ?>][min_days]" 
                                                   value="<?php echo esc_attr($method_settings['min_days']); ?>" 
                                                   min="0" step="1">
                                        </div>
                                        <div class="setting-group">
                                            <label><?php esc_html_e('Maximum Days', 'ed-dates-ck'); ?></label>
                                            <input type="number" 
                                                   name="ed_dates_ck_shipping_methods[<?php echo esc_attr($method_id); ?>][max_days]" 
                                                   value="<?php echo esc_attr($method_settings['max_days']); ?>" 
                                                   min="0" step="1">
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
        jQuery(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                
                // Update tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Update content
                $('.ed-dates-ck-tab-content').hide();
                $('#' + tab).show();
                
                // Update URL without page reload
                var url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.pushState({}, '', url);
            });
        });
        </script>
        <?php
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
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_ed-dates-ck-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-datepicker');
        
        wp_enqueue_style(
            'ed-dates-ck-admin',
            ED_DATES_CK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ED_DATES_CK_VERSION
        );

        wp_enqueue_script(
            'ed-dates-ck-admin',
            ED_DATES_CK_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            ED_DATES_CK_VERSION,
            true
        );

        wp_localize_script('ed-dates-ck-admin', 'edDatesCkAdmin', array(
            'i18n' => array(
                'selectDates' => __('Select Dates', 'ed-dates-ck'),
                'done' => __('Done', 'ed-dates-ck'),
                'cancel' => __('Cancel', 'ed-dates-ck')
            )
        ));
    }
} 