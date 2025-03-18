jQuery(function($) {
    'use strict';

    var EDDatesAdmin = {
        init: function() {
            this.initTooltips();
            this.initDatepickers();
            this.initShippingZones();
            this.initSaveHandler();
            this.initTabSwitching();
        },

        initTooltips: function() {
            $('.ed-dates-ck-info-icon').tooltip({
                position: {
                    my: 'center bottom-10',
                    at: 'center top'
                },
                tooltipClass: 'ed-dates-ck-tooltip'
            });
        },

        initDatepickers: function() {
            $('.ed-dates-ck-holiday-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                beforeShow: function(input, inst) {
                    setTimeout(function() {
                        inst.dpDiv.css({
                            top: $(input).offset().top + 35,
                            left: $(input).offset().left
                        });
                    }, 0);
                }
            });
        },

        initShippingZones: function() {
            var self = this;

            // Zone selection
            $('.ed-dates-ck-zone-item').on('click', function() {
                $('.ed-dates-ck-zone-item').removeClass('active');
                $(this).addClass('active');
                self.loadShippingMethods($(this).data('zone-id'));
            });

            // Method selection
            $(document).on('click', '.ed-dates-ck-method-item', function() {
                $('.ed-dates-ck-method-item').removeClass('active');
                $(this).addClass('active');
                self.loadMethodSettings($(this).data('method-id'));
            });

            // Settings changes
            $('.ed-dates-ck-days-settings input').on('change', function() {
                self.updateMethodSettings();
            });
        },

        loadShippingMethods: function(zoneId) {
            var self = this;
            $('.ed-dates-ck-method-list').addClass('loading');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ed_dates_ck_get_zone_methods',
                    zone_id: zoneId,
                    nonce: edDatesCkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.ed-dates-ck-method-list').html(response.data.html);
                    }
                },
                complete: function() {
                    $('.ed-dates-ck-method-list').removeClass('loading');
                }
            });
        },

        loadMethodSettings: function(methodId) {
            var self = this;
            $('.ed-dates-ck-days-settings').addClass('loading');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ed_dates_ck_get_method_settings',
                    method_id: methodId,
                    nonce: edDatesCkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateMethodSettings(response.data);
                    }
                },
                complete: function() {
                    $('.ed-dates-ck-days-settings').removeClass('loading');
                }
            });
        },

        populateMethodSettings: function(settings) {
            $('.ed-dates-ck-min-days').val(settings.min_days || 2);
            $('.ed-dates-ck-max-days').val(settings.max_days || 5);
            $('.ed-dates-ck-cutoff').val(settings.cutoff_time || '16:00');
            $('.ed-dates-ck-non-working-days').prop('checked', settings.non_working_days || false);
            $('.ed-dates-ck-overwrite-holidays').prop('checked', settings.overwrite_holidays || false);
            
            if (settings.holidays) {
                $('.ed-dates-ck-holiday-picker').datepicker('setDate', settings.holidays);
            }
        },

        updateMethodSettings: function() {
            var activeMethod = $('.ed-dates-ck-method-item.active').data('method-id');
            if (!activeMethod) return;

            var settings = {
                min_days: $('.ed-dates-ck-min-days').val(),
                max_days: $('.ed-dates-ck-max-days').val(),
                cutoff_time: $('.ed-dates-ck-cutoff').val(),
                non_working_days: $('.ed-dates-ck-non-working-days').is(':checked'),
                overwrite_holidays: $('.ed-dates-ck-overwrite-holidays').is(':checked'),
                holidays: $('.ed-dates-ck-holiday-picker').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ed_dates_ck_save_method_settings',
                    method_id: activeMethod,
                    settings: settings,
                    nonce: edDatesCkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        this.showNotice('success', edDatesCkAdmin.i18n.settingsSaved);
                    } else {
                        // Show error message
                        this.showNotice('error', response.data.message || edDatesCkAdmin.i18n.errorSaving);
                    }
                }
            });
        },

        initSaveHandler: function() {
            var self = this;
            
            $('.ed-dates-ck-save .button-primary').on('click', function(e) {
                e.preventDefault();
                self.saveAllSettings();
            });
        },

        saveAllSettings: function() {
            var self = this;
            var $form = $('form').first();
            var formData = new FormData($form[0]);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showNotice('success', edDatesCkAdmin.i18n.settingsSaved);
                    } else {
                        self.showNotice('error', response.data.message || edDatesCkAdmin.i18n.errorSaving);
                    }
                }
            });
        },

        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        initTabSwitching: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.ed-dates-ck-tab-content').hide();
                $('#' + tab).show();
                
                var url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.pushState({}, '', url);
            });
        }
    };

    // Initialize the admin interface
    EDDatesAdmin.init();
}); 