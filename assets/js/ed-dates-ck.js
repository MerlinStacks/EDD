jQuery(function($) {
    'use strict';

    var ED_Dates_CK = {
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
                ED_Dates_CK.updateProductDate(variation.variation_id);
            }).on('reset_data', function() {
                ED_Dates_CK.updateProductDate($('.variations_form').data('product_id'));
            });
        },

        updateProductDate: function(productId) {
            var $container = $('.ed-dates-ck-delivery-date');
            if (!$container.length) return;

            $.ajax({
                url: edDatesCk.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_estimated_delivery',
                    product_id: productId,
                    nonce: edDatesCk.nonce
                },
                success: function(response) {
                    if (response.success && response.data.delivery_date) {
                        $container.find('.ed-dates-ck-date').text(response.data.delivery_date);
                        $container.show();
                    } else {
                        $container.hide();
                    }
                }
            });
        },

        updateCartDates: function() {
            $('.ed-dates-ck-delivery-date').each(function() {
                var $container = $(this);
                var productId = $container.closest('tr').find('[name^="cart["]').data('product_id');
                
                if (!productId) return;

                $.ajax({
                    url: edDatesCk.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_estimated_delivery',
                        product_id: productId,
                        nonce: edDatesCk.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.delivery_date) {
                            $container.find('.ed-dates-ck-date').text(response.data.delivery_date);
                            $container.show();
                        } else {
                            $container.hide();
                        }
                    }
                });
            });
        },

        updateCheckoutDates: function() {
            $('.ed-dates-ck-delivery-date').each(function() {
                var $container = $(this);
                var productId = $container.closest('tr').find('[name^="cart["]').data('product_id');
                
                if (!productId) return;

                $.ajax({
                    url: edDatesCk.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_estimated_delivery',
                        product_id: productId,
                        nonce: edDatesCk.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.delivery_date) {
                            $container.find('.ed-dates-ck-date').text(response.data.delivery_date);
                            $container.show();
                        } else {
                            $container.hide();
                        }
                    }
                });
            });
        }
    };

    ED_Dates_CK.init();
}); 