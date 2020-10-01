/* globals wpforms_admin */
/**
 * WPForms Settings Education function.
 *
 * @since 1.5.5
 */

'use strict';

var WPFormsSettingsEducation = window.WPFormsSettingsEducation || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.5.5
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.5.5
		 */
		init: function() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.5.5
		 */
		ready: function() {
			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.5.5
		 */
		events: function() {
			app.clickEvents();
		},

		/**
		 * Registers JS click events.
		 *
		 * @since 1.5.5
		 */
		clickEvents: function() {

			$( document ).on(
				'click',
				'.wpforms-settings-provider.education-modal',
				function( event ) {

					var $this = $( this );

					event.preventDefault();
					event.stopImmediatePropagation();

					app.upgradeModal( $this.data( 'name' ), $this.data( 'license' ) );
				}
			);
		},

		/**
		 * Upgrade modal.
		 *
		 * @since 1.5.5
		 *
		 * @param {string} feature Feature name.
		 * @param {string} type    License type.
		 */
		upgradeModal: function( feature, type ) {

			// Provide a default value.
			if ( typeof type === 'undefined' || type.length === 0 ) {
				type = 'pro';
			}

			// Make sure we received only supported type.
			if ( $.inArray( type, [ 'pro', 'elite' ] ) < 0 ) {
				return;
			}

			var message    = wpforms_admin.upgrade[type].message.replace( /%name%/g, feature ),
				upgradeURL = wpforms_admin.upgrade[type].url + '&utm_content=' + encodeURIComponent( feature.trim() );

			$.alert( {
				title   : feature + ' ' + wpforms_admin.upgrade[type].title,
				icon    : 'fa fa-lock',
				content : message,
				boxWidth: '550px',
				onOpenBefore: function() {
					this.$btnc.after( '<div class="discount-note">' + wpforms_admin.upgrade[type].bonus + wpforms_admin.upgrade[type].doc + '</div>' );
					this.$body.find( '.jconfirm-content' ).addClass( 'lite-upgrade' );
				},
				buttons : {
					confirm: {
						text    : wpforms_admin.upgrade[type].button,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action: function() {
							window.open( upgradeURL, '_blank' );
							$.alert( {
								title   : false,
								content : wpforms_admin.upgrade[type].modal,
								icon    : 'fa fa-info-circle',
								type    : 'blue',
								boxWidth: '565px',
								buttons : {
									confirm: {
										text    : wpforms_admin.ok,
										btnClass: 'btn-confirm',
										keys    : [ 'enter' ],
									},
								},
							} );
						},
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsSettingsEducation.init();
