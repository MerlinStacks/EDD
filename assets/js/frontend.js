/* global jQuery, wcEddFrontend */ // Updated object name
jQuery(function($) {
    'use strict';

    var WC_EDD_Frontend = { // Updated internal object name
        init: function() {
            this.bindEvents();
            this.initVariationListener();
        },

        bindEvents: function() {
            $(document.body).on('updated_cart_totals', this.updateCartDates);
            $(document.body).on('updated_checkout', this.updateCheckoutDates);
        },

        initVariationListener: function() {
            $('.variations_form').on('found_variation', function(event, variation) {
                WC_EDD_Frontend.updateProductDate(variation.variation_id); // Updated object name
            }).on('reset_data', function() {
                WC_EDD_Frontend.updateProductDate($('.variations_form').data('product_id')); // Updated object name
            });
        },

        updateProductDate: function(productId) {
            // Target the block wrapper, assuming it has a specific class or the main block class
            var $container = $('.wp-block-wc-edd-estimated-delivery-date'); // Use block class selector
            if (!$container.length) return;

            // TODO: Add a loading indicator?

            $.ajax({
                url: wcEddFrontend.ajaxUrl, // Updated object name
                type: 'POST',
                data: {
                    action: 'wc_edd_get_estimated_delivery', // Updated action name
                    product_id: productId,
                    nonce: wcEddFrontend.nonce // Updated object name and nonce key (assuming 'nonce' is correct)
                },
                success: function(response) {
                    // TODO: Update this based on the actual response structure from the refactored AJAX handler
                    // The AJAX handler currently returns error 'Not implemented yet.'
                    // It should return a formatted date string or range based on settings.
                    if (response.success && response.data.formatted_date) { // Assuming response provides formatted date
                        // Find the specific element within the block to update
                        $container.find('.wc-edd-date').text(response.data.formatted_date); // Updated class selector
                        // Potentially update label as well if custom text is dynamic
                        // $container.find('.wc-edd-label').text(response.data.label_text);
                        $container.show();
                    } else {
                        $container.hide(); // Hide block if calculation fails
                    }
                }
            });
        },

        updateCartDates: function() {
            // Use the class added in the PHP render function for cart/checkout
            $('.wc-edd-cart-checkout-date').each(function() { // Updated selector
                var $container = $(this);
                // Find product ID - This might need adjustment based on cart structure/themes
                var $cartItemRow = $container.closest('.cart_item');
                var productId = $cartItemRow.find('.product-remove a').data('product_id'); // Common way to find product ID in cart
                var variationId = $cartItemRow.find('input.variation_id').val(); // Check for variation ID
                var finalProductId = variationId || productId;

                if (!finalProductId) return;

                $.ajax({
                    url: wcEddFrontend.ajaxUrl, // Updated object name
                    type: 'POST',
                    data: {
                        action: 'wc_edd_get_estimated_delivery', // Updated action name
                        product_id: finalProductId,
                        nonce: wcEddFrontend.nonce // Updated object name and nonce key
                    },
                    success: function(response) {
                        // TODO: Update based on actual AJAX response
                        if (response.success && response.data.formatted_date) {
                            // Update the content of the container directly
                            $container.html(wcEddFrontend.i18n.estimatedDelivery + ' ' + response.data.formatted_date); // Example update
                            $container.show();
                        } else {
                            $container.hide(); // Hide if no date
                        }
                    }
                });
            });
        },

        updateCheckoutDates: function() {
            // Use the class added in the PHP render function for cart/checkout
            $('.wc-edd-cart-checkout-date').each(function() { // Updated selector
                var $container = $(this);
                // Find product ID - This might need adjustment based on checkout structure/themes
                var $cartItemRow = $container.closest('.cart_item'); // Common class in checkout review table
                var productId = $cartItemRow.find('.product-remove a').data('product_id'); // May not exist or be reliable here
                var variationId = $cartItemRow.find('input.variation_id').val(); // May not exist
                // Alternative: Look for data attribute on the container itself if added via PHP filter
                var dataProductId = $container.data('product-id');
                var dataVariationId = $container.data('variation-id');

                var finalProductId = dataVariationId || variationId || dataProductId || productId;

                if (!finalProductId) return;

                $.ajax({
                    url: wcEddFrontend.ajaxUrl, // Updated object name
                    type: 'POST',
                    data: {
                        action: 'wc_edd_get_estimated_delivery', // Updated action name
                        product_id: finalProductId,
                        nonce: wcEddFrontend.nonce // Updated object name and nonce key
                    },
                    success: function(response) {
                        // TODO: Update based on actual AJAX response
                        if (response.success && response.data.formatted_date) {
                            // Update the content of the container directly
                            $container.html(wcEddFrontend.i18n.estimatedDelivery + ' ' + response.data.formatted_date); // Example update
                            $container.show();
                        } else {
                            $container.hide(); // Hide if no date
                        }
                    }
                });
            });
        }
    };

    WC_EDD_Frontend.init(); // Updated object name
});