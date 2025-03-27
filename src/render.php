<?php
/**
 * Server-side rendering of the block.
 *
 * @package ED_Dates_CK
 */

/**
 * Renders the block on the server side.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Returns the block content.
 */
function render_block_ed_dates_ck_estimated_delivery($attributes, $content, $block) {
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
    try {
        $delivery_date = $calculator->calculate_estimated_delivery($product_id);
        if (!$delivery_date) {
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