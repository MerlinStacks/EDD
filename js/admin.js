/* global jQuery, wcEddAdminData, wp */
jQuery( function ( $ ) {
	'use strict';

	/**
	 * Get localized data.
	 */
	const i18n = wcEddAdminData.i18n || {};
	const ajaxUrl = wcEddAdminData.ajax_url;
	const nonce = wcEddAdminData.nonce;
	const wpDateFormat = wcEddAdminData.dateFormat || 'yy-mm-dd'; // Default if WP format conversion fails

	/**
	 * Initialize multi-date pickers.
	 */
	function initDatePickers() {
		$( '.wc-edd-date-picker-container' ).each( function () {
			const $container = $( this );
			const $trigger = $container.find( '.wc-edd-date-picker-trigger' );
			const $selectedDatesContainer = $container.find(
				'.wc-edd-selected-dates'
			);
			const inputNameBase = $selectedDatesContainer
				.find( 'input[type="hidden"]' )
				.first()
				.attr( 'name' )
				?.replace( /\[\]$/, '' ); // Get base name like wc_edd_settings[closed_days][store_specific]

			if ( ! inputNameBase ) {
				console.error( 'Could not determine input name for date picker.' );
				return; // Skip if name not found
			}

			let selectedDates = $selectedDatesContainer
				.find( 'input[type="hidden"]' )
				.map( function () {
					return $( this ).val();
				} )
				.get();

			// Destroy existing datepicker if any to prevent duplicates
			if ( $trigger.hasClass( 'hasDatepicker' ) ) {
				try {
					$trigger.datepicker( 'destroy' );
				} catch ( e ) {
					console.error( 'Error destroying datepicker:', e );
				}
			}

			// Initialize jQuery UI Datepicker
			$trigger.datepicker( {
				dateFormat: wpDateFormat, // Use WP date format
				beforeShowDay: function ( date ) {
					const dateString = $.datepicker.formatDate( 'yy-mm-dd', date );
					const isSelected = selectedDates.includes( dateString );
					return [ true, isSelected ? 'ui-state-active' : '' ]; // Highlight selected dates
				},
				onSelect: function ( dateText, inst ) {
					const dateString = $.datepicker.formatDate( 'yy-mm-dd', inst.selectedDay, inst.selectedMonth, inst.selectedYear );

					if ( selectedDates.includes( dateString ) ) {
						// Remove date
						selectedDates = selectedDates.filter(
							( d ) => d !== dateString
						);
						$selectedDatesContainer
							.find( `span[data-date="${ dateString }"]` )
							.remove();
					} else {
						// Add date
						selectedDates.push( dateString );
						const displayDate = $.datepicker.formatDate(
							wpDateFormat,
							new Date( dateString + 'T00:00:00' ) // Ensure correct date object for formatting
						);
						const $newDateSpan = $(
							'<span class="wc-edd-selected-date" data-date="' +
								dateString +
								'" style="display: inline-block; background: #eee; padding: 2px 5px; margin: 2px; border-radius: 3px;">' +
								displayDate +
								'<input type="hidden" name="' +
								inputNameBase +
								'[]" value="' +
								dateString +
								'">' +
								'<button type="button" class="wc-edd-remove-date button-link delete" style="text-decoration: none; margin-left: 5px;" aria-label="' +
								( i18n.removeDateConfirm || 'Remove date' ) +
								'">&times;</button>' +
								'</span>'
						);
						$selectedDatesContainer.append( $newDateSpan );
					}
					// Re-highlight dates in the picker
					$( this ).datepicker( 'refresh' );
					// Keep the input field empty as it's just a trigger
					$( this ).val( '' );
				},
			} );
		} );

		// Handle removal of dates via button click
		$( document ).on(
			'click',
			'.wc-edd-remove-date',
			function ( e ) {
				e.preventDefault();
				const $button = $( this );
				const $dateSpan = $button.closest( '.wc-edd-selected-date' );
				const dateString = $dateSpan.data( 'date' );
				const $container = $dateSpan.closest(
					'.wc-edd-date-picker-container'
				);
				const $trigger = $container.find( '.wc-edd-date-picker-trigger' );

				// Remove from array (find the correct array based on container)
				let selectedDates = $container
					.find( 'input[type="hidden"]' )
					.map( function () {
						return $( this ).val();
					} )
					.get();

				selectedDates = selectedDates.filter( ( d ) => d !== dateString );

				// Update hidden inputs (by removing the span)
				$dateSpan.remove();

				// Refresh datepicker to unhighlight
				if ( $trigger.hasClass( 'hasDatepicker' ) ) {
					$trigger.datepicker( 'refresh' );
				}
			}
		);
	}

	/**
	 * Load Shipping Methods Table via AJAX.
	 */
	function loadShippingMethods() {
		const $container = $( '#wc-edd-shipping-methods-container' );
		const $table = $container.find( '.wc-edd-shipping-methods-table' );
		const $tbody = $table.find( 'tbody' );
		const $spinner = $container.find( '.spinner' );
		const $notice = $( '#wc-edd-shipping-methods-notice' );
		const $saveButton = $( '#wc-edd-save-shipping-methods' );

		$spinner.addClass( 'is-active' );
		$notice.empty();
		$tbody.html(
			'<tr><td colspan="5">' +
				( i18n.loading || 'Loading...' ) +
				'</td></tr>'
		);
		$table.show();
		$saveButton.prop( 'disabled', true );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_edd_get_shipping_methods',
				nonce: nonce,
			},
			success: function ( response ) {
				$spinner.removeClass( 'is-active' );
				if ( response.success && response.data.methods ) {
					$tbody.empty(); // Clear loading message
					if ( response.data.methods.length > 0 ) {
						$.each( response.data.methods, function ( index, method ) {
							const checked = method.enabled ? 'checked' : '';
							const disabled = method.enabled ? '' : 'disabled';
							const row = `
                                <tr>
                                    <td><input type="checkbox" ${ checked } disabled></td>
                                    <td>${ method.title } (${
								method.method_title
							})</td>
                                    <td>${ method.zone_name }</td>
                                    <td><input type="number" class="small-text wc-edd-transit-min" name="methods[${
										method.method_key
									}][min_transit]" value="${
								method.min_transit || ''
							}" min="0" step="1" ${ disabled }></td>
                                    <td><input type="number" class="small-text wc-edd-transit-max" name="methods[${
										method.method_key
									}][max_transit]" value="${
								method.max_transit || ''
							}" min="0" step="1" ${ disabled }></td>
                                </tr>
                            `;
							$tbody.append( row );
						} );
						$saveButton.prop( 'disabled', false );
					} else {
						$tbody.html(
							'<tr class="no-items"><td colspan="5">' +
								( i18n.noMethods ||
									'No shipping methods found.' ) +
								'</td></tr>'
						);
					}
				} else {
					$tbody.html(
						'<tr class="no-items"><td colspan="5">' +
							( response.data?.message ||
								i18n.error ||
								'An error occurred.' ) +
							'</td></tr>'
					);
					$notice.html(
						'<div class="notice notice-error is-dismissible"><p>' +
							( response.data?.message ||
								i18n.error ||
								'An error occurred.' ) +
							'</p></div>'
					);
				}
			},
			error: function ( jqXHR, textStatus, errorThrown ) {
				console.error( 'AJAX Error:', textStatus, errorThrown );
				$spinner.removeClass( 'is-active' );
				$tbody.html(
					'<tr class="no-items"><td colspan="5">' +
						( i18n.error || 'An error occurred.' ) +
						'</td></tr>'
				);
				$notice.html(
					'<div class="notice notice-error is-dismissible"><p>' +
						( i18n.error || 'An error occurred.' ) +
						'</p></div>'
				);
			},
		} );
	}

	/**
	 * Save Shipping Methods Table via AJAX.
	 */
	function saveShippingMethods() {
		const $container = $( '#wc-edd-shipping-methods-container' );
		const $spinner = $container.find( '.spinner' );
		const $notice = $( '#wc-edd-shipping-methods-notice' );
		const $saveButton = $( '#wc-edd-save-shipping-methods' );
		const $table = $container.find( '.wc-edd-shipping-methods-table' );

		// Collect data
		let methodsData = [];
		$table.find( 'tbody tr' ).each( function () {
			const $row = $( this );
			const $minInput = $row.find( '.wc-edd-transit-min' );
			const $maxInput = $row.find( '.wc-edd-transit-max' );
			const nameAttr = $minInput.attr( 'name' ); // e.g., methods[flat_rate:1][min_transit]

			if ( nameAttr ) {
				const keyMatch = nameAttr.match( /methods\[(.*?)\]/ );
				if ( keyMatch && keyMatch[ 1 ] ) {
					methodsData.push( {
						method_key: keyMatch[ 1 ],
						min_transit: $minInput.val(),
						max_transit: $maxInput.val(),
					} );
				}
			}
		} );

		$spinner.addClass( 'is-active' );
		$notice.empty();
		$saveButton.prop( 'disabled', true );

		$.ajax( {
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_edd_save_shipping_methods',
				nonce: nonce,
				methods: methodsData, // Send collected data
			},
			success: function ( response ) {
				$spinner.removeClass( 'is-active' );
				$saveButton.prop( 'disabled', false );
				if ( response.success ) {
					$notice.html(
						'<div class="notice notice-success is-dismissible"><p>' +
							( response.data?.message ||
								i18n.settingsSaved ||
								'Settings saved.' ) +
							'</p></div>'
					);
				} else {
					$notice.html(
						'<div class="notice notice-error is-dismissible"><p>' +
							( response.data?.message ||
								i18n.error ||
								'An error occurred.' ) +
							'</p></div>'
					);
				}
				// Auto-dismiss notice after a few seconds
				setTimeout( function () {
					$notice.find( '.notice' ).fadeOut();
				}, 5000 );
			},
			error: function ( jqXHR, textStatus, errorThrown ) {
				console.error( 'AJAX Error:', textStatus, errorThrown );
				$spinner.removeClass( 'is-active' );
				$saveButton.prop( 'disabled', false );
				$notice.html(
					'<div class="notice notice-error is-dismissible"><p>' +
						( i18n.error || 'An error occurred.' ) +
						'</p></div>'
				);
			},
		} );
	}

	// --- Initialization ---

	// Init date pickers on relevant tabs
	if (
		$( '#store-specific-dates' ).length ||
		$( '#postage-specific-dates' ).length
	) {
		initDatePickers();
	}

	// Load shipping methods if on the shipping tab
	if ( $( '#wc-edd-shipping-methods-container' ).length ) {
		loadShippingMethods();
		// Save button handler
		$( '#wc-edd-save-shipping-methods' ).on( 'click', function ( e ) {
			e.preventDefault();
			saveShippingMethods();
		} );
	}

	// Tab navigation (simple hash based) - Consider using WP's recommended way if available
	$( '.nav-tab-wrapper a.nav-tab' ).on( 'click', function ( e ) {
		// Prevent default only if it's a hash link within the same page
		if ( this.hash && window.location.pathname === this.pathname ) {
			// e.preventDefault(); // Let WP handle the URL change for state
			// window.location.hash = this.hash; // Update hash manually if needed
			// $( '.nav-tab-wrapper a.nav-tab' ).removeClass( 'nav-tab-active' );
			// $( this ).addClass( 'nav-tab-active' );
			// $( '.wc-edd-settings-content > div' ).hide();
			// $( this.hash ).show();
		}
	} );

	// Show the correct tab on page load (handled by PHP adding style="display:none;")
} );