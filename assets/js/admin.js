jQuery(function($) {
    'use strict';

    // Initialize datepickers
    $('.holiday-datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        numberOfMonths: 2,
        multiSelect: true, // Enable multiple date selection
        beforeShow: function(input, inst) {
            // Store selected dates
            var selectedDates = [];
            $(input).siblings('.holiday-dates').find('input[type="hidden"]').each(function() {
                selectedDates.push($(this).val());
            });
            inst.settings.selectedDates = selectedDates;

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
                    click: function() {
                        var dates = $(input).datepicker('getDate');
                        if (dates) {
                            addHolidayDates(input, dates);
                        }
                        $(input).datepicker('hide');
                    }
                }).appendTo(buttonPane);

                $('<button>', {
                    text: edDatesCkAdmin.i18n.cancel,
                    click: function() {
                        $(input).datepicker('hide');
                    }
                }).appendTo(buttonPane);
            });
        },
        beforeShowDay: function(date) {
            var dateString = $.datepicker.formatDate('yy-mm-dd', date);
            var selectedDates = this.settings.selectedDates || [];
            return [true, selectedDates.indexOf(dateString) !== -1 ? 'ui-state-active' : ''];
        },
        onSelect: function(dateText, inst) {
            var selectedDates = inst.settings.selectedDates || [];
            var index = selectedDates.indexOf(dateText);
            
            if (index === -1) {
                selectedDates.push(dateText);
            } else {
                selectedDates.splice(index, 1);
            }
            
            inst.settings.selectedDates = selectedDates;
            $(this).datepicker('refresh');
        }
    });

    // Add holiday dates to the list
    function addHolidayDates(input, dates) {
        var container = $(input).siblings('.holiday-dates');
        var inputName = container.attr('id').replace('-container', '');
        var selectedDates = $(input).datepicker('option', 'settings').selectedDates || [];

        // Clear existing dates
        container.empty();

        // Add each selected date
        selectedDates.forEach(function(dateString) {
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
                click: function() {
                    $(this).closest('.holiday-date').remove();
                }
            }).appendTo(holidayDate);

            container.append(holidayDate);
        });
    }

    // Remove holiday date
    $(document).on('click', '.remove-date', function() {
        var dateContainer = $(this).closest('.holiday-date');
        var datepicker = dateContainer.closest('.holiday-picker').find('.holiday-datepicker');
        var dateValue = dateContainer.find('input[type="hidden"]').val();
        var inst = datepicker.data('datepicker');
        
        if (inst && inst.settings.selectedDates) {
            var index = inst.settings.selectedDates.indexOf(dateValue);
            if (index !== -1) {
                inst.settings.selectedDates.splice(index, 1);
            }
        }
        
        dateContainer.remove();
    });
}); 