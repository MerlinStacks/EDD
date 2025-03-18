jQuery(function($) {
    'use strict';

    var EDDatesAdmin = {
        init: function() {
            this.initTooltips();
            this.initDatepickers();
            this.initShippingZones();
            this.initSaveHandler();
            this.initTabSwitching();
            this.initHolidayToggles();
        },

        initTooltips: function() {
            $('.ed-dates-ck-info-icon').tooltip({
                position: {
                    my: 'center bottom-10',
                    at: 'center top'
                },
                classes: {
                    'ui-tooltip': 'ed-dates-ck-tooltip'
                }
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
                },
                onSelect: function(dateText) {
                    var $container = $(this).siblings('.ed-dates-ck-holiday-dates');
                    EDDatesAdmin.addHolidayDate($container, dateText);
                    $(this).val('');
                }
            });

            // Handle existing holiday dates
            $('.ed-dates-ck-holiday-date button').on('click', function() {
                $(this).closest('.ed-dates-ck-holiday-date').remove();
            });
        },

        addHolidayDate: function($container, dateText) {
            var date = $.datepicker.parseDate('yy-mm-dd', dateText);
            var displayDate = $.datepicker.formatDate('MM d, yy', date);
            
            var $existing = $container.find('input[value="' + dateText + '"]');
            if ($existing.length) {
                return;
            }

            var $date = $('<div class="ed-dates-ck-holiday-date"></div>');
            $date.append('<input type="hidden" name="method_holidays[]" value="' + dateText + '">');
            $date.append('<span>' + displayDate + '</span>');
            $date.append('<button type="button" class="remove-date">&times;</button>');
            
            $container.append($date);

            $date.find('button').on('click', function() {
                $(this).closest('.ed-dates-ck-holiday-date').remove();
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
        },

        loadShippingMethods: function(zoneId) {
            var self = this;
            var $methodList = $('.ed-dates-ck-method-items');
            
            $methodList.addClass('ed-dates-ck-loading');

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
                        $methodList.html(response.data.html);
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    self.showNotice('error', edDatesCkAdmin.i18n.errorLoading);
                },
                complete: function() {
                    $methodList.removeClass('ed-dates-ck-loading');
                }
            });
        },

        loadMethodSettings: function(methodId) {
            var self = this;
            var $settings = $('.ed-dates-ck-days-settings');
            
            $settings.addClass('ed-dates-ck-loading');

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
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    self.showNotice('error', edDatesCkAdmin.i18n.errorLoading);
                },
                complete: function() {
                    $settings.removeClass('ed-dates-ck-loading');
                }
            });
        },

        populateMethodSettings: function(settings) {
            $('.ed-dates-ck-min-days').val(settings.min_days || 2);
            $('.ed-dates-ck-max-days').val(settings.max_days || 5);
            $('.ed-dates-ck-cutoff').val(settings.cutoff_time || '16:00');
            $('.ed-dates-ck-non-working-days').prop('checked', settings.non_working_days || false);
            $('.ed-dates-ck-overwrite-holidays').prop('checked', settings.overwrite_holidays || false);
            
            var $holidayDates = $('.ed-dates-ck-holiday-dates').empty();
            if (settings.holidays && settings.holidays.length) {
                settings.holidays.forEach(function(date) {
                    EDDatesAdmin.addHolidayDate($holidayDates, date);
                });
            }

            this.toggleHolidayPicker(settings.overwrite_holidays || false);
        },

        initHolidayToggles: function() {
            var self = this;
            
            $('.ed-dates-ck-overwrite-holidays').on('change', function() {
                self.toggleHolidayPicker($(this).is(':checked'));
            });
        },

        toggleHolidayPicker: function(show) {
            $('.ed-dates-ck-method-holidays')[show ? 'slideDown' : 'slideUp']();
        },

        initSaveHandler: function() {
            var self = this;
            
            $('.ed-dates-ck-save-method').on('click', function(e) {
                e.preventDefault();
                self.saveMethodSettings();
            });
        },

        saveMethodSettings: function() {
            var self = this;
            var $activeMethod = $('.ed-dates-ck-method-item.active');
            
            if (!$activeMethod.length) {
                self.showNotice('error', edDatesCkAdmin.i18n.selectMethod);
                return;
            }

            var methodId = $activeMethod.data('method-id');
            var settings = {
                min_days: $('.ed-dates-ck-min-days').val(),
                max_days: $('.ed-dates-ck-max-days').val(),
                cutoff_time: $('.ed-dates-ck-cutoff').val(),
                non_working_days: $('.ed-dates-ck-non-working-days').is(':checked'),
                overwrite_holidays: $('.ed-dates-ck-overwrite-holidays').is(':checked'),
                holidays: $('.ed-dates-ck-holiday-dates input[type="hidden"]').map(function() {
                    return $(this).val();
                }).get()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ed_dates_ck_save_method_settings',
                    method_id: methodId,
                    settings: settings,
                    nonce: edDatesCkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                    } else {
                        self.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    self.showNotice('error', edDatesCkAdmin.i18n.errorSaving);
                }
            });
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
        },

        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize the admin interface
    EDDatesAdmin.init();
}); 