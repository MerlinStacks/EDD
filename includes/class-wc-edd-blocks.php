<?php
/**
 * Blocks Registration and Rendering
 *
 * @package ED_Dates_CK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WC_EDD_Blocks Class
 */
class WC_EDD_Blocks { // Renamed class

    /**
     * The single instance of the class.
     *
     * @var WC_EDD_Blocks|null // Updated type hint
     */
    protected static $instance = null;

    /**
     * Main WC_EDD_Blocks Instance.
     * Ensures only one instance of WC_EDD_Blocks is loaded or can be loaded.
     *
     * @return WC_EDD_Blocks - Main instance. // Updated return type hint
     */
    public static function get_instance() { // Renamed class
        if ( is_null( self::$instance ) ) {
            self::$instance = new self(); // Renamed class
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('admin_init', array($this, 'add_editor_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }

    /**
     * Register blocks
     */
    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register the block using block.json
        register_block_type( WC_EDD_PLUGIN_PATH . '/block.json' ); // Updated constant

        // The following PHP registration might be redundant if block.json is comprehensive.
        // Keeping render_callback registration for server-side rendering.
        // If block.json handles attributes, supports, styles etc., this PHP array can be simplified/removed.
        /*
        register_block_type('wc-edd/estimated-delivery-date', array( // Updated block name
            'api_version' => 2,
            'render_callback' => array($this, 'render_delivery_block'),
            'attributes' => array( // These should ideally be defined in block.json
                'iconPosition' => array(
                    'type' => 'string',
                    'default' => 'left'
                ),
                'showIcon' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'style' => array(
                    'type' => 'object',
                    'default' => array()
                ),
                'borderColor' => array(
                    'type' => 'string'
                ),
                'backgroundColor' => array(
                    'type' => 'string'
                ),
                'textColor' => array(
                    'type' => 'string'
                ),
                'fontSize' => array(
                    'type' => 'string'
                ),
                'fontFamily' => array(
                    'type' => 'string'
                ),
                'className' => array(
                    'type' => 'string'
                )
            ),
            'supports' => array(
                'align' => true,
                'html' => false,
                'className' => true,
                'color' => array(
                    'text' => true,
                    'background' => true,
                    'gradients' => true,
                    'link' => false,
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    'blockGap' => true,
                ),
                'typography' => array(
                    'fontSize' => true,
                    'lineHeight' => true,
                    '__experimentalFontFamily' => true,
                    '__experimentalFontStyle' => true,
                    '__experimentalFontWeight' => true,
                    '__experimentalLetterSpacing' => true,
                    '__experimentalTextTransform' => true,
                    '__experimentalTextDecoration' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true,
                        'fontFamily' => true,
                    ),
                ),
                'border' => array(
                    'color' => true,
                    'radius' => true,
                    'style' => true,
                    'width' => true,
                    '__experimentalDefaultControls' => array(
                        'color' => true,
                        'radius' => true,
                        'style' => true,
                        'width' => true,
                    ),
                ),
            ),
            'styles' => array(
                array(
                    'name' => 'default',
                    'label' => __('Default', 'ed-dates-ck'),
                    'isDefault' => true,
                ),
                array(
                    'name' => 'outlined',
                    'label' => __('Outlined', 'ed-dates-ck'),
                ),
            ),
            'example' => array(
                'attributes' => array(
                    'showIcon' => true,
                    'iconPosition' => 'left',
                ),
            ),
            'editor_script' => 'wc-edd-block-editor', // Updated handle
            'editor_style' => 'wc-edd-block-editor', // Updated handle
        ));
        */

        // Remove auto-insertion of block
        // add_action('woocommerce_before_add_to_cart_form', function() {
        //     if (is_product()) {
        //         echo do_blocks('<!-- wp:ed-dates-ck/estimated-delivery /-->');
        //     }
        // }, 10);
    }

    /**
     * Render the delivery block dynamically on the server-side.
     *
     * @param array $attributes Block attributes.
     * @param string $content Block content (not used for dynamic blocks).
     * @return string Rendered block HTML.
     */
    public function render_delivery_block(array $attributes, string $content): string {
        if (!function_exists('wc_get_product') || !class_exists('WC_EDD_Calculator')) {
            return ''; // Essential functions/classes missing
        }

        global $product;
        $current_product = $product; // Store global product temporarily

        // Ensure we have a product context (check loop, global, or block context)
        if (!$current_product instanceof \WC_Product) {
            $post_id = get_the_ID();
            if ($post_id) {
                $current_product = wc_get_product($post_id);
            }
        }

        if (!$current_product instanceof \WC_Product) {
            return ''; // No product context found
        }

        $product_id = $current_product->get_id();

        // Get Calculator instance
        $calculator = WC_EDD_Calculator::get_instance();
        $date_range = $calculator->calculate_delivery_range($product_id); // Pass product ID

        if (empty($date_range)) {
            return ''; // Calculation failed or returned no dates
        }

        // Get settings for formatting
        $settings = get_option('wc_edd_settings', []);
        $general_settings = $settings['general'] ?? [];
        $display_format = $general_settings['display_format'] ?? 'range';
        $date_format_php = $general_settings['date_format'] ?? 'F j, Y';
        $custom_text = $general_settings['custom_text'] ?? __('Estimated Delivery:', 'wc-estimated-delivery-date');

        // Format dates based on settings
        $start_date_obj = date_create($date_range['start']);
        $end_date_obj = date_create($date_range['end']);

        if (!$start_date_obj || !$end_date_obj) {
             error_log('WC EDD - Error creating date objects from range: ' . print_r($date_range, true));
             return ''; // Invalid date format from calculator
        }

        $formatted_start_date = date_i18n($date_format_php, $start_date_obj->getTimestamp());
        $formatted_end_date = date_i18n($date_format_php, $end_date_obj->getTimestamp());

        // Build the display string
        $display_string = '';
        if ($display_format === 'range') {
            // Check if start and end dates are the same
            if ($formatted_start_date === $formatted_end_date) {
                $display_string = $formatted_end_date;
            } else {
                /* translators: 1: Start date, 2: End date */
                $display_string = sprintf(__('%1$s - %2$s', 'wc-estimated-delivery-date'), $formatted_start_date, $formatted_end_date);
            }
        } else { // 'latest'
            /* translators: %s: End date */
            $display_string = sprintf(__('by %s', 'wc-estimated-delivery-date'), $formatted_end_date);
        }

        // Add custom text prefix if not empty
        $full_display_text = !empty(trim($custom_text)) ? trim($custom_text) . ' ' . $display_string : $display_string;

        // --- Apply Block Attributes ---
        // Note: WordPress automatically adds classes for alignment, colors, fonts etc. based on block.json supports
        // We just need get_block_wrapper_attributes() without manually adding style attributes unless necessary for unsupported features.
        $wrapper_attributes = get_block_wrapper_attributes(); // Let WP handle styles based on attributes

        // --- Build Block Content ---
        // TODO: Add icon support based on attributes (Dashicon or custom image)
        $icon_html = '';
        // Example: $icon = $attributes['icon'] ?? 'dashicons-calendar-alt'; $icon_html = '<span class="dashicons ' . esc_attr($icon) . '"></span>';

        $output = sprintf('<div %s>', $wrapper_attributes);

        // TODO: Add icon position logic based on $attributes['iconPosition']
        // if ($icon_html && $attributes['iconPosition'] === 'left') { $output .= $icon_html; }

        // Use 'Date Only' or 'Text' display type from attributes if implemented
        $display_type = $attributes['displayType'] ?? 'text'; // Default to 'text'

        if ($display_type === 'text') {
            $output .= sprintf(
                '<span class="wc-edd-delivery-text">%s</span>', // Use consistent class name
                esc_html($full_display_text)
            );
        } else { // 'dateOnly'
             $output .= sprintf(
                '<span class="wc-edd-delivery-text">%s</span>',
                esc_html($display_string) // Only the date part
            );
        }

        // TODO: Add icon position logic
        // if ($icon_html && $attributes['iconPosition'] === 'right') { $output .= $icon_html; }

        $output .= '</div>';

        return $output;
    }

    /**
     * Add editor styles
     */
    public function add_editor_styles() {
        // Use the main build CSS file for editor styles
        add_editor_style(WC_EDD_PLUGIN_URL . 'build/style-style.css'); // Updated constant
    }

    /**
     * Enqueue styles for frontend
     */
    public function enqueue_styles() {
        // Only enqueue if the block is present? Or always enqueue? Check block.json settings.
        // Assuming block.json handles style enqueueing if 'style' property is set.
        // If manual enqueueing is needed:
        /*
        wp_enqueue_style('dashicons'); // Only if needed by frontend styles
        wp_enqueue_style(
            'wc-edd-block-style', // Updated handle
            ED_DATES_CK_PLUGIN_URL . 'build/style-style.css', // Updated path
            array(),
            ED_DATES_CK_VERSION
        );
        */
        // Also enqueue the frontend.css from assets
         wp_enqueue_style(
             'wc-edd-frontend-style', // New handle for frontend specific styles
             WC_EDD_PLUGIN_URL . 'assets/css/frontend.css', // Updated constant
             array(), // Potentially add 'wc-edd-block-style' as dependency if needed
             WC_EDD_VERSION // Updated constant
         );
     }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        // Block editor assets (JS and CSS) should be automatically enqueued
        // by WordPress when using register_block_type with block.json.
        // Manual enqueueing here might be redundant or cause conflicts.
        // If specific editor-only assets are needed beyond what block.json handles, enqueue them here.
        /*
        wp_enqueue_style('dashicons'); // Only if needed by editor script/styles

        // Register and enqueue block editor script
        wp_enqueue_script(
            'wc-edd-block-editor', // Updated handle
            ED_DATES_CK_PLUGIN_URL . 'build/index.js', // Updated path
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'), // Review dependencies based on block.json asset file
            ED_DATES_CK_VERSION,
            true
        );
        */
    }
}

// Initialize the blocks class.
WC_EDD_Blocks::get_instance(); // Updated class name