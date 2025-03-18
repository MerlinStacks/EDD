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
    }

    /**
     * Register blocks.
     */
    public function register_blocks() {
        // Register blocks from block.json.
        register_block_type( 
            ED_DATES_CK_PLUGIN_PATH . '/blocks/build',
            array(
                'render_callback' => array( $this, 'render_estimated_delivery_block' ),
            )
        );
    }

    /**
     * Enqueue editor assets.
     */
    public function enqueue_editor_assets() {
        // Enqueue editor styles.
        wp_enqueue_style(
            'ed-dates-ck-editor-style',
            ED_DATES_CK_PLUGIN_URL . 'blocks/build/editor.css',
            array(),
            ED_DATES_CK_VERSION
        );
    }

    /**
     * Render the estimated delivery block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     * @return string Rendered block HTML.
     */
    public function render_estimated_delivery_block($attributes, $content) {
        try {
            // Get the current product ID
            $product_id = get_the_ID();
            if (!$product_id || get_post_type($product_id) !== 'product') {
                return '';
            }

            // Get the calculator instance
            $calculator = ED_Dates_CK_Calculator::get_instance();
            if (!$calculator) {
                return '';
            }

            // Calculate the estimated delivery date
            $delivery_date = $calculator->calculate_estimated_delivery($product_id);
            if (!$delivery_date) {
                return '';
            }

            // Extract attributes
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
                $icon_position === 'left' ? 'ed-dates-ck-icon-left' : 'ed-dates-ck-icon-right',
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
        } catch (Exception $e) {
            error_log('ED Dates CK - Error rendering block: ' . $e->getMessage());
            return '';
        }
    }
}

// Initialize the blocks class.
ED_Dates_CK_Blocks::get_instance(); 