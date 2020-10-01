/* globals wpforms_builder_lite, wpforms_builder */
/**
 * WPForms Form Builder Education function.
 *
 * @since 1.5.1
 */

'use strict';

var WPFormsBuilderEducation = window.WPFormsBuilderEducation || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.5.1
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.5.1
		 */
		init: function() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.5.1
		 */
		ready: function() {
			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.5.1
		 */
		events: function() {
			app.clickEvents();
		},

		/**
		 * Registers JS click events.
		 *
		 * @since 1.5.1
		 */
		clickEvents: function() {

			$( document ).on(
				'click',
				'.wpforms-add-fields-button, .wpforms-panel-sidebar-section, .wpforms-builder-settings-block-add, .wpforms-field-option-group-toggle',
				function( event ) {

					var $this = $( this );

					if ( $this.hasClass( 'upgrade-modal' ) ) {

						event.preventDefault();
						event.stopImmediatePropagation();

						if ( $this.hasClass( 'wpforms-add-fields-button' ) ) {
							app.upgradeModal( $this.text() + ' ' + wpforms_builder.field, $this.data( 'license' ) );
						} else {
							app.upgradeModal( $this.data( 'name' ), $this.data( 'license' ) );
						}
					}
				}
			);

			// "Did You Know?" Click on the dismiss button.
			$( '.wpforms-dyk' ).on( 'click', '.dismiss', function( e ) {

				var $t = $( this ),
					$dyk = $t.closest( '.wpforms-dyk' ),
					data = {
						action: 'wpforms_dyk_dismiss',
						nonce: wpforms_builder.nonce,
						section: $t.attr( 'data-section' ),
					};

				$dyk.find( '.wpforms-dyk-fbox' ).addClass( 'out' );
				setTimeout(
					function() {
						$dyk.remove();
					},
					300
				);

				$.get( wpforms_builder.ajax_url, data );
			} );
		},

		/**
		 * Upgrade modal.
		 *
		 * @since 1.5.1
		 *
		 * @param {string} feature Feature name.
		 * @param {string} type Feature license type: pro or elite.
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

			var message    = wpforms_builder_lite.upgrade[type].message.replace( /%name%/g, feature ),
				upgradeURL = wpforms_builder_lite.upgrade[type].url + '&utm_content=' + encodeURIComponent( feature.trim() );

			$.alert( {
				title   : feature + ' ' + wpforms_builder_lite.upgrade[type].title,
				icon    : 'fa fa-lock',
				content : message,
				boxWidth: '550px',
				onOpenBefore: function() {
					this.$btnc.after( '<div class="discount-note">' + wpforms_builder_lite.upgrade[type].bonus + wpforms_builder_lite.upgrade[type].doc + '</div>' );
					this.$body.find( '.jconfirm-content' ).addClass( 'lite-upgrade' );
				},
				buttons : {
					confirm: {
						text    : wpforms_builder_lite.upgrade[type].button,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action: function() {
							window.open( upgradeURL, '_blank' );
							$.alert( {
								title   : false,
								content : wpforms_builder_lite.upgrade[type].modal,
								icon    : 'fa fa-info-circle',
								type    : 'blue',
								boxWidth: '565px',
								buttons : {
									confirm: {
										text    : wpforms_builder.ok,
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
WPFormsBuilderEducation.init();
