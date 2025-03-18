jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab-wrapper a').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.ed-dates-ck-tab-content').hide();
        $($(this).attr('href')).show();

        // Update active tab
        activeTab = $(this).data('tab');
    });

    // AJAX setup
    function getZoneMethods(zoneId) {
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
                    $('.ed-dates-ck-method-items').html(response.data.html);
                    $('.ed-dates-ck-days-settings').hide(); // Hide settings until a method is selected
                } else {
                    console.error('Error getting zone methods:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    // Zone selection
    $(document).on('click', '.ed-dates-ck-zone-item', function() {
        $('.ed-dates-ck-zone-item').removeClass('active');
        $(this).addClass('active');
        const zoneId = $(this).data('zone-id');
        getZoneMethods(zoneId);
    });

    // Method selection
    $(document).on('click', '.ed-dates-ck-method-item', function() {
        $('.ed-dates-ck-method-item').removeClass('active');
        $(this).addClass('active');
        const methodId = $(this).data('method-id');

        // Show settings
        $('.ed-dates-ck-days-settings').show();

        // Populate settings (replace with actual data loading)
        $('.ed-dates-ck-min-days').val(2);
        $('.ed-dates-ck-max-days').val(5);
    });

    // Save method settings
    $('.ed-dates-ck-save-method').on('click', function() {
        const methodItem = $('.ed-dates-ck-method-item.active');
        if (!methodItem.length) {
            alert('Please select a shipping method to save.');
            return;
        }

        const methodId = methodItem.data('method-id');
        const settings = {
            min_days: $('.ed-dates-ck-min-days').val(),
            max_days: $('.ed-dates-ck-max-days').val(),
            // Add other settings here
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
                    alert(edDatesCkAdmin.i18n.settingsSaved);
                } else {
                    alert(edDatesCkAdmin.i18n.errorSaving);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    });

    // Holiday datepicker
    $('.holiday-datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        multidate: true,
        beforeShowDay: function(date) {
            const string = jQuery.datepicker.formatDate('yy-mm-dd', date);
            const holidays = $(this).closest('.holiday-picker').find('.holiday-dates input').map(function() {
                return $(this).val();
            }).get();

            return [holidays.indexOf(string) == -1];
        },
        onSelect: function(dateText, inst) {
            const $picker = $(this);
            const $container = $picker.closest('.holiday-picker').find('.holiday-dates');
            const $input = $('<input type="hidden" name="ed_dates_ck_shop_holidays[]">').val(dateText); // Change name as needed
            const $span = $('<span></span>').text(dateText);
            const $button = $('<button type="button" class="remove-date">&times;</button>');
            const $holiday = $('<div class="holiday-date"></div>').append($input, $span, $button);

            $container.append($holiday);
            $picker.val(''); // Clear the input
        }
    });

    // Remove holiday
    $(document).on('click', '.remove-date', function() {
        $(this).closest('.holiday-date').remove();
    });
});