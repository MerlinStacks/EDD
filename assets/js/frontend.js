/* global wc_edd_params */
jQuery(function($) {
    'use strict';

    /**
     * WC Estimated Delivery Date Frontend class
     */
    var WC_EDD_Frontend = function() {
        this.init();
    };

    WC_EDD_Frontend.prototype = {
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.$blocks = $('.wc-edd-block');
            
            // Bind events
            $(document.body).on('updated_shipping_method', this.onShippingMethodUpdate.bind(this));
            $(document.body).on('found_variation', this.onVariationFound.bind(this));
            $(document.body).on('reset_data', this.onVariationReset.bind(this));

            // Initialize blocks
            this.initBlocks();
        },

        /**
         * Initialize delivery date blocks
         */
        initBlocks: function() {
            this.$blocks.each(function(index, block) {
                this.updateDeliveryDate($(block));
            }.bind(this));
        },

        /**
         * Update delivery date for a block
         * 
         * @param {jQuery} $block Block element
         */
        updateDeliveryDate: function($block) {
            var productId = $block.data('product-id');
            var variationId = $block.data('variation-id') || 0;
            var shippingMethod = this.getSelectedShippingMethod();

            $block.addClass('wc-edd-loading');

            $.ajax({
                url: wc_edd_params.ajax_url,
                data: {
                    action: 'wc_edd_get_delivery_date',
                    nonce: wc_edd_params.nonce,
                    product_id: productId,
                    variation_id: variationId,
                    shipping_method: shippingMethod
                },
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        this.updateBlockContent($block, response.data);
                    }
                }.bind(this),
                complete: function() {
                    $block.removeClass('wc-edd-loading');
                }
            });
        },

        /**
         * Update block content
         * 
         * @param {jQuery} $block Block element
         * @param {Object} data Delivery date data
         */
        updateBlockContent: function($block, data) {
            var $date = $block.find('.wc-edd-date');
            var displayType = $date.data('display-type');

            if (displayType === 'text') {
                $date.html(wc_edd_params.i18n.estimated_text + ' ' + data.formatted_date);
            } else {
                $date.html(data.formatted_date);
            }
        },

        /**
         * Get selected shipping method
         * 
         * @return {string} Selected shipping method ID
         */
        getSelectedShippingMethod: function() {
            var method = '';
            var $selected = $('input[name^="shipping_method"]:checked, input[name^="shipping_method"][type="hidden"]');

            if ($selected.length) {
                method = $selected.val();
            }

            return method;
        },

        /**
         * Handle shipping method update
         */
        onShippingMethodUpdate: function() {
            this.initBlocks();
        },

        /**
         * Handle variation found
         * 
         * @param {Event} event Event object
         * @param {Object} variation Variation data
         */
        onVariationFound: function(event, variation) {
            var $block = this.$blocks.filter('[data-product-id="' + variation.variation_id + '"]');
            if ($block.length) {
                $block.data('variation-id', variation.variation_id);
                this.updateDeliveryDate($block);
            }
        },

        /**
         * Handle variation reset
         */
        onVariationReset: function() {
            this.$blocks.each(function(index, block) {
                var $block = $(block);
                $block.data('variation-id', 0);
                this.updateDeliveryDate($block);
            }.bind(this));
        }
    };

    /**
     * Initialize on document ready
     */
    $(function() {
        new WC_EDD_Frontend();
    });
});