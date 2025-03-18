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
        // Check if Gutenberg is active.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        // Register the block using metadata from block.json
        $block_json_path = ED_DATES_CK_PLUGIN_PATH . '/blocks/src/block.json';
        if ( ! file_exists( $block_json_path ) ) {
            error_log( 'ED Dates CK - Block JSON file not found: ' . $block_json_path );
            return;
        }

        register_block_type(
            ED_DATES_CK_PLUGIN_PATH . '/blocks/src',
            array(
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

        // Debug output
        if (WP_DEBUG) {
            error_log('ED Dates CK - Debug Info:');
            error_log('Product ID: ' . print_r($product_id, true));
            error_log('Is Product: ' . (is_product() ? 'yes' : 'no'));
            error_log('Post Type: ' . get_post_type($product_id));
        }

        if (!$product_id || get_post_type($product_id) !== 'product') {
            if (WP_DEBUG) {
                error_log('ED Dates CK - Invalid product ID or not a product');
            }
            return '';
        }

        // Get the calculator instance
        $calculator = ED_Dates_CK_Calculator::get_instance();
        if (!$calculator) {
            if (WP_DEBUG) {
                error_log('ED Dates CK - Calculator instance not found');
            }
            return '';
        }

        // Calculate the estimated delivery date
        try {
            $delivery_date = $calculator->calculate_estimated_delivery($product_id);
            if (!$delivery_date) {
                if (WP_DEBUG) {
                    error_log('ED Dates CK - No delivery date calculated');
                }
                return '';
            }
        } catch (Exception $e) {
            error_log('ED Dates CK - Error calculating delivery date: ' . $e->getMessage());
            return '';
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
                <p class="delivery-date"><?php echo esc_html($delivery_date); ?></p>
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