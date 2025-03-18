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
        add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
    }

     /**
      * Enqueue block assets for both editor and front-end.
      */
     public function enqueue_block_assets() {
         // Enqueue block styles
         wp_enqueue_style(
             'ed-dates-ck-block-style',
             ED_DATES_CK_PLUGIN_URL . 'blocks/build/style-index.css',
             array(),
             ED_DATES_CK_VERSION
         );

         // Enqueue dashicons for the calendar icon
         wp_enqueue_style( 'dashicons' );
     }

    /**
     * Register blocks.
     */
    public function register_blocks() {
        // Block name.
        $block_name = 'ed-dates-ck/estimated-delivery';

        // Block assets.
        $editor_script = 'blocks/build/index.js';
        $editor_style = 'blocks/build/index.css';
        $style = 'blocks/build/style-index.css';
        $view_script = 'blocks/build/view.js';

        // Register block script.
        /*wp_register_script(
            'ed-dates-ck-block-editor',
            ED_DATES_CK_PLUGIN_URL . $editor_script,
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            ED_DATES_CK_VERSION,
            true
        );

        // Register block styles.
        wp_register_style(
            'ed-dates-ck-block-editor',
            ED_DATES_CK_PLUGIN_URL . $editor_style,
            array('wp-edit-blocks'),
            ED_DATES_CK_VERSION
        );

        wp_register_style(
            'ed-dates-ck-block-style',
            ED_DATES_CK_PLUGIN_URL . $style,
            array(),
            ED_DATES_CK_VERSION
        );

        // Register block view script
        wp_register_script(
            'ed-dates-ck-block-view',
            ED_DATES_CK_PLUGIN_URL . $view_script,
            array(),
            ED_DATES_CK_VERSION,
            true
        );

        // Get attributes from block.json
        $block_json = json_decode( file_get_contents( ED_DATES_CK_PLUGIN_PATH . '/blocks/build/block.json' ), true );
		$attributes = $block_json['attributes'];

        register_block_type(
            $block_name,
            array(
                'editor_script'   => 'ed-dates-ck-block-editor',
                'editor_style'    => 'ed-dates-ck-block-editor',
                'style'           => 'ed-dates-ck-block-style',
                'view_script'     => 'ed-dates-ck-block-view',
                'render_callback' => array( $this, 'render_estimated_delivery_block' ),
                'attributes'      => $attributes,
            )
        ); */
    }
}

// Initialize the blocks class.
//ED_Dates_CK_Blocks::get_instance();