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
 * ED_Dates_CK_Blocks Class
 */
class ED_Dates_CK_Blocks {

    /**
     * The single instance of the class.
     *
     * @var ED_Dates_CK_Blocks|null
     */
    protected static $instance = null;

    /**
     * Main ED_Dates_CK_Blocks Instance.
     * Ensures only one instance of ED_Dates_CK_Blocks is loaded or can be loaded.
     *
     * @return ED_Dates_CK_Blocks - Main instance.
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
    }

    /**
     * Register blocks.
     */
    public function register_blocks() {
        // Register block script
        wp_register_script(
            'ed-dates-ck-block-editor',
            ED_DATES_CK_PLUGIN_URL . 'blocks/build/index.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            ED_DATES_CK_VERSION
        );

        // Register block styles
        wp_register_style(
            'ed-dates-ck-block-editor',
            ED_DATES_CK_PLUGIN_URL . 'blocks/build/index.css',
            array('wp-edit-blocks'),
            ED_DATES_CK_VERSION
        );

        wp_register_style(
            'ed-dates-ck-block-style',
            ED_DATES_CK_PLUGIN_URL . 'blocks/build/style-index.css',
            array(),
            ED_DATES_CK_VERSION
        );

        // Register block view script
        wp_register_script(
            'ed-dates-ck-block-view',
            ED_DATES_CK_PLUGIN_URL . 'blocks/build/view.js',
            array(),
            ED_DATES_CK_VERSION,
            true
        );

        register_block_type(
            ED_DATES_CK_PLUGIN_PATH . 'blocks/build',
            array(
                'editor_script' => 'ed-dates-ck-block-editor',
                'editor_style' => 'ed-dates-ck-block-editor',
                'style' => 'ed-dates-ck-block-style',
                'view_script' => 'ed-dates-ck-block-view',
                'render_callback' => array($this, 'render_estimated_delivery_block'),
                'attributes' => array(
                    'showIcon' => array(
                        'type' => 'boolean',
                        'default' => true
                    ),
                    'iconPosition' => array(
                        'type' => 'string',
                        'default' => 'left'
                    ),
                    'displayStyle' => array(
                        'type' => 'string',
                        'default' => 'default'
                    ),
                    'borderStyle' => array(
                        'type' => 'string',
                        'default' => 'left-accent'
                    ),
                    'dateFormat' => array(
                        'type' => 'string',
                        'default' => 'range'
                    ),
                    'className' => array(
                        'type' => 'string'
                    ),
                    'textColor' => array(
                        'type' => 'string'
                    ),
                    'backgroundColor' => array(
                        'type' => 'string'
                    ),
                    'fontSize' => array(
                        'type' => 'string'
                    ),
                    'style' => array(
                        'type' => 'object'
                    )
                )
            )
        );

        // Enqueue dashicons for the calendar icon
        wp_enqueue_style('dashicons');
    }

    /**
     * Enqueue editor assets.
     */
    public function enqueue_editor_assets() {
        $asset_file = ED_DATES_CK_PLUGIN_PATH . '/blocks/build/index.asset.php';
        
        if ( file_exists( $asset_file ) ) {
            $asset = require $asset_file;
            
            // Enqueue editor script
            wp_enqueue_script(
                'ed-dates-ck-editor-script',
                ED_DATES_CK_PLUGIN_URL . 'blocks/build/index.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );

            // Enqueue editor styles
            wp_enqueue_style(
                'ed-dates-ck-editor-style',
                ED_DATES_CK_PLUGIN_URL . 'blocks/build/index.css',
                array( 'wp-edit-blocks' ),
                $asset['version']
            );

            // Add translation support
            if ( function_exists( 'wp_set_script_translations' ) ) {
                wp_set_script_translations(
                    'ed-dates-ck-editor-script',
                    'ed-dates-ck',
                    ED_DATES_CK_PLUGIN_PATH . '/languages'
                );
            }
        } else {
            error_log( 'ED Dates CK - Asset file not found: ' . $asset_file );
        }
    }

    /**
     * Enqueue block assets for both editor and front-end.
     */
    public function enqueue_block_assets() {
        $asset_file = ED_DATES_CK_PLUGIN_PATH . '/blocks/build/index.asset.php';
        
        if ( file_exists( $asset_file ) ) {
            $asset = require $asset_file;
            
            // Enqueue block styles
            wp_enqueue_style(
                'ed-dates-ck-style',
                ED_DATES_CK_PLUGIN_URL . 'blocks/build/style-index.css',
                array(),
                $asset['version']
            );

            // Enqueue dashicons for the calendar icon
            wp_enqueue_style( 'dashicons' );
        }
    }

    /**
     * Render the estimated delivery block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block content.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block HTML.
     */
    public function render_estimated_delivery_block($attributes, $content, $block) {
        global $product;

        // Get the current product ID
        $product_id = null;
        
        if (is_product()) {
            // If we're on a product page
            $product_id = get_the_ID();
        } elseif (isset($block->context['postId'])) {
            // If we're in a block context
            $product_id = $block->context['postId'];
        } elseif (is_object($product) && method_exists($product, 'get_id')) {
            // If we have a global product object
            $product_id = $product->get_id();
        }

        if (!$product_id || get_post_type($product_id) !== 'product') {
            return '';
        }

        // Get the calculator instance
        $calculator = ED_Dates_CK_Calculator::get_instance();
        if (!$calculator) {
            return '';
        }

        // Get the settings
        $settings = get_option('ed_dates_ck_settings', array());
        $default_lead_time = !empty($settings['default_lead_time']) ? intval($settings['default_lead_time']) : 0;

        // Calculate the estimated delivery dates
        try {
            $delivery_dates = $calculator->calculate_delivery_range($product_id, $default_lead_time);
            if (empty($delivery_dates)) {
                return '';
            }
        } catch (Exception $e) {
            error_log('ED Dates CK - Error calculating delivery date: ' . $e->getMessage());
            return '';
        }

        // Format the delivery date based on settings
        $date_format = isset($attributes['dateFormat']) ? $attributes['dateFormat'] : 'range';
        $formatted_date = '';

        if ($date_format === 'range' && isset($delivery_dates['start']) && isset($delivery_dates['end'])) {
            $formatted_date = sprintf(
                '%s - %s',
                date_i18n('F j', strtotime($delivery_dates['start'])),
                date_i18n('F j', strtotime($delivery_dates['end']))
            );
        } else {
            // Use the latest date for 'latest' format
            $formatted_date = sprintf(
                __('Delivery by %s', 'ed-dates-ck'),
                date_i18n('jS \o\f F', strtotime($delivery_dates['end']))
            );
        }

        // Extract attributes with defaults
        $show_icon = isset($attributes['showIcon']) ? $attributes['showIcon'] : true;
        $icon_position = isset($attributes['iconPosition']) ? $attributes['iconPosition'] : 'left';
        $display_style = isset($attributes['displayStyle']) ? $attributes['displayStyle'] : 'default';
        $border_style = isset($attributes['borderStyle']) ? $attributes['borderStyle'] : 'left-accent';
        $class_name = isset($attributes['className']) ? $attributes['className'] : '';

        // Build classes
        $classes = array(
            'wp-block-ed-dates-ck-estimated-delivery',
            'ed-dates-ck-block',
            "ed-dates-ck-style-{$display_style}",
            "ed-dates-ck-border-{$border_style}",
            "ed-dates-ck-icon-{$icon_position}",
            $class_name
        );

        // Build inline styles
        $styles = array();
        if (!empty($attributes['textColor'])) {
            $styles[] = sprintf('color: %s;', esc_attr($attributes['textColor']));
        }
        if (!empty($attributes['backgroundColor'])) {
            $styles[] = sprintf('background-color: %s;', esc_attr($attributes['backgroundColor']));
        }
        if (!empty($attributes['fontSize'])) {
            $styles[] = sprintf('font-size: %s;', esc_attr($attributes['fontSize']));
        }
        if (!empty($attributes['style'])) {
            if (!empty($attributes['style']['spacing']['padding'])) {
                $styles[] = sprintf('padding: %s;', esc_attr($attributes['style']['spacing']['padding']));
            }
            if (!empty($attributes['style']['spacing']['margin'])) {
                $styles[] = sprintf('margin: %s;', esc_attr($attributes['style']['spacing']['margin']));
            }
        }

        // Start output buffering
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"
             <?php echo !empty($styles) ? sprintf('style="%s"', esc_attr(implode(' ', $styles))) : ''; ?>>
            <?php if ($show_icon && $icon_position === 'left') : ?>
                <span class="ed-dates-ck-icon dashicons dashicons-calendar-alt"></span>
            <?php endif; ?>

            <div class="ed-dates-ck-content">
                <h3><?php echo esc_html__('Estimated Delivery', 'ed-dates-ck'); ?></h3>
                <p class="delivery-date"><?php echo esc_html($formatted_date); ?></p>
            </div>

            <?php if ($show_icon && $icon_position === 'right') : ?>
                <span class="ed-dates-ck-icon dashicons dashicons-calendar-alt"></span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the blocks class.
ED_Dates_CK_Blocks::get_instance(); 