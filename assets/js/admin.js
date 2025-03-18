jQuery(function($) {
    'use strict';

    // Initialize datepickers
    $('.holiday-datepicker').each(function() {
        var $input = $(this);
        var $container = $input.siblings('.holiday-dates');
        var selectedDates = [];

        // Get initial selected dates
        $container.find('input[type="hidden"]').each(function() {
            selectedDates.push($(this).val());
        });

        $input.datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            numberOfMonths: 2,
            beforeShow: function(input, inst) {
                inst.selectedDates = selectedDates;
                
                // Add custom buttons
                setTimeout(function() {
                    var buttonPane = $(input)
                        .datepicker('widget')
                        .find('.ui-datepicker-buttonpane');

                    if (!buttonPane.length) {
                        buttonPane = $('<div>', {
                            'class': 'ui-datepicker-buttonpane ui-widget-content'
                        }).appendTo($(input).datepicker('widget'));
                    }

                    $('<button>', {
                        text: edDatesCkAdmin.i18n.done,
                        class: 'ui-datepicker-close',
                        click: function(e) {
                            e.preventDefault();
                            $input.datepicker('hide');
                        }
                    }).appendTo(buttonPane);
                });
            },
            beforeShowDay: function(date) {
                var dateString = $.datepicker.formatDate('yy-mm-dd', date);
                return [true, selectedDates.indexOf(dateString) !== -1 ? 'ui-state-active' : ''];
            },
            onSelect: function(dateText, inst) {
                var index = selectedDates.indexOf(dateText);
                
                if (index === -1) {
                    selectedDates.push(dateText);
                    addHolidayDate($container, dateText);
                } else {
                    selectedDates.splice(index, 1);
                    removeHolidayDate($container, dateText);
                }
                
                inst.selectedDates = selectedDates;
                $(this).datepicker('refresh');
            }
        });
    });

    // Add a single holiday date to the container
    function addHolidayDate($container, dateString) {
        var inputName = $container.attr('id').replace('-container', '');
        var date = $.datepicker.parseDate('yy-mm-dd', dateString);
        var displayDate = $.datepicker.formatDate(wp.dateFormat || 'MM d, yy', date);

        var holidayDate = $('<div>', {
            'class': 'holiday-date'
        });

        $('<input>', {
            type: 'hidden',
            name: 'ed_dates_ck_' + inputName + '[]',
            value: dateString
        }).appendTo(holidayDate);

        $('<span>', {
            text: displayDate
        }).appendTo(holidayDate);

        $('<button>', {
            type: 'button',
            'class': 'remove-date',
            text: '×',
            click: function(e) {
                e.preventDefault();
                var $datepicker = $(this).closest('.holiday-picker').find('.holiday-datepicker');
                var dateValue = $(this).siblings('input[type="hidden"]').val();
                var index = $datepicker.datepicker('instance').selectedDates.indexOf(dateValue);
                
                if (index !== -1) {
                    $datepicker.datepicker('instance').selectedDates.splice(index, 1);
                    $datepicker.datepicker('refresh');
                }
                
                $(this).closest('.holiday-date').remove();
            }
        }).appendTo(holidayDate);

        $container.append(holidayDate);
    }

    // Remove a holiday date from the container
    function removeHolidayDate($container, dateString) {
        $container.find('input[type="hidden"]').each(function() {
            if ($(this).val() === dateString) {
                $(this).closest('.holiday-date').remove();
            }
        });
    }
}); 