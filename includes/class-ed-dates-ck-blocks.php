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
    public function render_estimated_delivery_block( $attributes, $content ) {
        // Get the current product ID.
        $product_id = get_the_ID();
        if ( ! $product_id || get_post_type( $product_id ) !== 'product' ) {
            return '';
        }

        // Get the calculator instance.
        $calculator = ED_Dates_CK_Calculator::get_instance();
        
        // Calculate the estimated delivery date.
        $delivery_date = $calculator->calculate_estimated_delivery( $product_id );
        
        if ( ! $delivery_date ) {
            return '';
        }

        // Format the date.
        $formatted_date = $calculator->format_delivery_date( $delivery_date );

        // Build the HTML.
        $wrapper_class = 'ed-dates-ck-block';
        if ( ! empty( $attributes['className'] ) ) {
            $wrapper_class .= ' ' . esc_attr( $attributes['className'] );
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $wrapper_class ); ?>">
            <h3><?php esc_html_e( 'Estimated Delivery Date', 'ed-dates-ck' ); ?></h3>
            <p><?php echo esc_html( $formatted_date ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the blocks class.
ED_Dates_CK_Blocks::get_instance(); 