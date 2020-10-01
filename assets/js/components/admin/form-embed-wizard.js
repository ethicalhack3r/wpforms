/* global wpforms_admin_form_embed_wizard, WPFormsBuilder, ajaxurl, WPFormsChallenge, wpforms_builder */

/**
 * Form Embed Wizard function.
 *
 * @since 1.6.2
 */

'use strict';

var WPFormsFormEmbedWizard = window.WPFormsFormEmbedWizard || ( function( document, window, $ ) {

	/**
	 * Elements.
	 *
	 * @since 1.6.2
	 *
	 * @type {object}
	 */
	var el = {};

	/**
	 * Runtime variables.
	 *
	 * @since 1.6.2
	 *
	 * @type {object}
	 */
	var vars = {
		formId:            0,
		isBuilder:         false,
		isChallengeActive: false,
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.6.2
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.6.2
		 */
		init: function() {

			$( app.ready );
			$( window ).on( 'load', app.load );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.6.2
		 */
		ready: function() {

			app.initVars();
			app.events();
		},

		/**
		 * Window load.
		 *
		 * @since 1.6.2
		 */
		load: function() {

			// Initialize tooltip.
			if ( wpforms_admin_form_embed_wizard.is_edit_page === '1' && ! vars.isChallengeActive ) {
				app.initTooltip();
			}

			app.initialStateToggle();
		},

		/**
		 * Init variables.
		 *
		 * @since 1.6.2
		 */
		initVars: function() {

			// Caching some DOM elements for further use.
			el = {
				$wizardContainer:   $( '#wpforms-admin-form-embed-wizard-container' ),
				$wizard:            $( '#wpforms-admin-form-embed-wizard' ),
				$contentInitial:    $( '#wpforms-admin-form-embed-wizard-content-initial' ),
				$contentSelectPage: $( '#wpforms-admin-form-embed-wizard-content-select-page' ),
				$contentCreatePage: $( '#wpforms-admin-form-embed-wizard-content-create-page' ),
				$sectionBtns:       $( '#wpforms-admin-form-embed-wizard-section-btns' ),
				$sectionGo:         $( '#wpforms-admin-form-embed-wizard-section-go' ),
				$newPageTitle:      $( '#wpforms-admin-form-embed-wizard-new-page-title' ),
				$selectPage:        $( '#wpforms-admin-form-embed-wizard-select-page' ),
				$videoTutorial:     $( '#wpforms-admin-form-embed-wizard-tutorial' ),
				$sectionToggles:    $( '#wpforms-admin-form-embed-wizard-section-toggles' ),
				$sectionGoBack:     $( '#wpforms-admin-form-embed-wizard-section-goback' ),
				$shortcode:         $( '#wpforms-admin-form-embed-wizard-shortcode' ),
			};

			// Detect the form builder screen and store the flag.
			vars.isBuilder = typeof WPFormsBuilder !== 'undefined';

			// Detect the Challenge and store the flag.
			vars.isChallengeActive = typeof WPFormsChallenge !== 'undefined';

			// Are the pages exists?
			vars.pagesExists = el.$wizard.data( 'pages-exists' ) === 1;
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.6.2
		 */
		events: function() {

			el.$wizard
				.on( 'click', 'button', app.popupButtonsClick )
				.on( 'click', '.tutorial-toggle', app.tutorialToggle )
				.on( 'click', '.shortcode-toggle', app.shortcodeToggle )
				.on( 'click', '.initialstate-toggle', app.initialStateToggle )
				.on( 'click', '.wpforms-admin-popup-close', app.closePopup );
		},

		/**
		 * Popup buttons events handler.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} e Event object.
		 */
		popupButtonsClick: function( e ) {

			var $btn = $( e.target );

			if ( ! $btn.length ) {
				return;
			}

			var	$div = $btn.closest( 'div' ),
				action = $btn.data( 'action' ) || '';

			el.$contentInitial.hide();

			switch ( action ) {

				// Select existing page.
				case 'select-page':
					el.$newPageTitle.hide();
					el.$contentSelectPage.show();
					break;

				// Create a new page.
				case 'create-page':
					el.$selectPage.hide();
					el.$contentCreatePage.show();
					break;

				// Let's Go!
				case 'go':
					if ( el.$selectPage.is( ':visible' ) && el.$selectPage.val() === '' ) {
						return;
					}
					$btn.prop( 'disabled', true );
					app.saveFormAndRedirect();

					return;
			}

			$div.hide();
			$div.next().fadeIn();
			el.$sectionToggles.hide();
			el.$sectionGoBack.fadeIn();
			app.tutorialControl( 'Stop' );
		},

		/**
		 * Toggle video tutorial inside popup.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} e Event object.
		 */
		tutorialToggle: function( e ) {

			e.preventDefault();

			el.$shortcode.hide();
			el.$videoTutorial.toggle();

			if ( el.$videoTutorial[0].src.indexOf( '&autoplay=1' ) < 0 ) {
				app.tutorialControl( 'Play' );
			} else {
				app.tutorialControl( 'Stop' );
			}
		},

		/**
		 * Toggle video tutorial inside popup.
		 *
		 * @since 1.6.2.3
		 *
		 * @param {string} action One of 'Play' or 'Stop'.
		 */
		tutorialControl: function( action ) {

			var iframe = el.$videoTutorial[0];

			if ( typeof iframe === 'undefined' ) {
				return;
			}

			if ( action !== 'Stop' ) {
				iframe.src +=  iframe.src.indexOf( '&autoplay=1' ) < 0 ? '&autoplay=1' : '';
			} else {
				iframe.src = iframe.src.replace( '&autoplay=1', '' );
			}
		},

		/**
		 * Toggle shortcode input field.
		 *
		 * @since 1.6.2.3
		 *
		 * @param {object} e Event object.
		 */
		shortcodeToggle: function( e ) {

			e.preventDefault();

			el.$videoTutorial.hide();
			app.tutorialControl( 'Stop' );
			el.$shortcode.val( '[wpforms id="' + vars.formId + '" title="false" description="false"]' );
			el.$shortcode.toggle();
		},

		/**
		 * Toggle initial state.
		 *
		 * @since 1.6.2.3
		 *
		 * @param {object} e Event object.
		 */
		initialStateToggle: function( e ) {

			if ( e ) {
				e.preventDefault();
			}

			if ( vars.pagesExists ) {
				el.$contentInitial.show();
				el.$contentSelectPage.hide();
				el.$contentCreatePage.hide();
				el.$selectPage.show();
				el.$newPageTitle.show();
				el.$sectionBtns.show();
				el.$sectionGo.hide();
			} else {
				el.$contentInitial.hide();
				el.$contentSelectPage.hide();
				el.$contentCreatePage.show();
				el.$selectPage.hide();
				el.$newPageTitle.show();
				el.$sectionBtns.hide();
				el.$sectionGo.show();
			}
			el.$shortcode.hide();
			el.$videoTutorial.hide();
			app.tutorialControl( 'Stop' );
			el.$sectionToggles.show();
			el.$sectionGoBack.hide();
		},

		/**
		 * Save the form and redirect to form embed page.
		 *
		 * @since 1.6.2
		 */
		saveFormAndRedirect: function() {

			// Just redirect if no need to save the form.
			if ( ! vars.isBuilder || WPFormsBuilder.formIsSaved() ) {
				app.embedPageRedirect();
				return;
			}

			// Embedding in Challenge should save the form silently.
			if ( vars.isBuilder && vars.isChallengeActive ) {
				WPFormsBuilder.formSave().done( app.embedPageRedirect );
				return;
			}

			$.confirm( {
				title: false,
				content: wpforms_builder.exit_confirm,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				backgroundDismiss: false,
				closeIcon: false,
				buttons: {
					confirm: {
						text: wpforms_builder.save_embed,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {

							WPFormsBuilder.formSave().done( app.embedPageRedirect );
						},
					},
					cancel: {
						text: wpforms_builder.embed,
						action: function() {
							WPFormsBuilder.setCloseConfirmation( false );
							app.embedPageRedirect();
						},
					},
				},
			} );
		},

		/**
		 * Prepare data for requesting redirect URL.
		 *
		 * @since 1.6.2
		 *
		 * @returns {object} AJAX data object.
		 */
		embedPageRedirectAjaxData: function() {

			var data = {
				action  : 'wpforms_admin_form_embed_wizard_embed_page_url',
				_wpnonce: wpforms_admin_form_embed_wizard.nonce,
				formId: vars.formId,
			};

			if ( el.$selectPage.is( ':visible' ) ) {
				data.pageId = el.$selectPage.val();
			}

			if ( el.$newPageTitle.is( ':visible' ) ) {
				data.pageTitle = el.$newPageTitle.val();
			}

			return data;
		},

		/**
		 * Redirect to form embed page.
		 *
		 * @since 1.6.2
		 */
		embedPageRedirect: function() {

			var data = app.embedPageRedirectAjaxData();

			// Exit if no one page is selected.
			if ( typeof data.pageId !== 'undefined' && data.pageId === '' ) {
				return;
			}

			$.post( ajaxurl, data, function( response ) {
				if ( response.success ) {
					window.location = response.data;
				}
			} );
		},

		/**
		 * Display wizard popup.
		 *
		 * @since 1.6.2
		 *
		 * @param {numeric} openFormId Form ID to embed. Used only if called outside of the form builder.
		 */
		openPopup: function( openFormId ) {

			openFormId = openFormId || 0;

			vars.formId = vars.isBuilder ? $( '#wpforms-builder-form' ).data( 'id' ) : openFormId;

			// Regular wizard and wizard in Challenge has differences.
			el.$wizard.toggleClass( 'wpforms-challenge-popup', vars.isChallengeActive );
			el.$wizard.find( '.wpforms-admin-popup-close' ).toggle( ! vars.isChallengeActive );
			el.$wizard.find( '.wpforms-admin-popup-content-regular' ).toggle( ! vars.isChallengeActive );
			el.$wizard.find( '.wpforms-admin-popup-content-challenge' ).toggle( vars.isChallengeActive );

			// Re-init sections.
			if ( el.$selectPage.length === 0 ) {
				el.$sectionBtns.hide();
				el.$sectionGo.show();
			} else {
				el.$sectionBtns.show();
				el.$sectionGo.hide();
			}
			el.$newPageTitle.show();
			el.$selectPage.show();

			el.$wizardContainer.fadeIn();
		},

		/**
		 * Close wizard popup.
		 *
		 * @since 1.6.2
		 */
		closePopup: function() {

			el.$wizardContainer.fadeOut();
			app.initialStateToggle();
		},

		/**
		 * Init embed page tooltip.
		 *
		 * @since 1.6.2
		 */
		initTooltip: function() {

			var $dot = $( '<span class="wpforms-challenge-dot wpforms-challenge-dot-step5" data-wpforms-challenge-step="5">&nbsp;</span>' ),
				isGutengerg = app.isGutenberg(),
				anchor = isGutengerg ? '.block-editor .edit-post-header' : '#wp-content-editor-tools .wpforms-insert-form-button';

			var tooltipsterArgs = {
				content          : $( '#tooltip-content5' ),
				trigger          : 'custom',
				interactive      : true,
				animationDuration: 0,
				delay            : 0,
				theme            : [ 'tooltipster-default', 'wpforms-challenge-tooltip' ],
				side             : isGutengerg ? 'bottom' : 'right',
				distance         : 3,
				functionReady    : function( instance, helper ) {

					// We need this to properly reuse styles from the challenge.
					$( helper.tooltip ).addClass( 'wpforms-challenge-tooltip-step5' );

					instance._$tooltip.on( 'click', 'button', function() {

						instance.close();
						$( '.wpforms-challenge-dot.wpforms-challenge-dot-step5' ).remove();
					} );

					instance.reposition();
				},
			};

			$dot.insertAfter( anchor ).tooltipster( tooltipsterArgs ).tooltipster( 'open' );
		},

		/**
		 * Check if we're in Gutenberg editor.
		 *
		 * @since 1.6.2
		 *
		 * @returns {boolean} Is Gutenberg or not.
		 */
		isGutenberg: function() {

			return typeof wp !== 'undefined' && Object.prototype.hasOwnProperty.call( wp, 'blocks' );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsFormEmbedWizard.init();
