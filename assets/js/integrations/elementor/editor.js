/* global wpformsElementorVars, elementor, elementorFrontend */

'use strict';

/**
 * WPForms integration with Elementor in the editor.
 *
 * @since 1.6.0
 * @since 1.6.2 Moved frontend integration to `wpforms-elementor-frontend.js`
 */
var WPFormsElementor = window.WPFormsElementor || ( function( document, window, $ ) {

	/**
	 * Runtime variables.
	 *
	 * @since 1.6.2
	 *
	 * @type {object}
	 */
	var vars = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.6.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.6.0
		 */
		init: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.6.0
		 */
		events: function() {

			// Widget events.
			$( window ).on( 'elementor/frontend/init', function( event, id, instance ) {

				// Widget buttons click.
				elementor.channels.editor.on( 'elementorWPFormsAddFormBtnClick', app.addFormBtnClick );

				// Widget frontend events.
				elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', app.widgetPreviewEvents );

				// Initialize widget controls.
				elementor.hooks.addAction( 'panel/open_editor/widget/wpforms', app.widgetPanelOpen );

			} );
		},

		/**
		 * Widget events.
		 *
		 * @since 1.6.2
		 *
		 * @param {jQuery} $scope The current element wrapped with jQuery.
		 */
		widgetPreviewEvents: function( $scope ) {

			$scope
				.on( 'click', '.wpforms-btn', app.addFormBtnClick )
				.on( 'click', '.wpforms-admin-no-forms-container a', app.clickLinkInPreview )
				.on( 'change', '.wpforms-elementor-form-selector select', app.selectFormInPreview )
				.on( 'click mousedown focus keydown submit', '.wpforms-container *', app.disableEvents );
		},

		/**
		 * Initialize widget controls when widget is activated.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} panel Panel object.
		 * @param {object} model Model object.
		 */
		widgetPanelOpen: function( panel, model ) {

			vars.widgetId = model.attributes.id;
			vars.formId = model.attributes.settings.attributes.form_id;

			app.widgetPanelInit( panel );

			app.widgetPanelSectionClickObserver( panel );
		},

		/**
		 * Initialize widget controls when widget is activated.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} panel Panel object.
		 */
		widgetPanelInit: function( panel ) {

			var	$formSelectControl = panel.$el.find( '.elementor-control.elementor-control-form_id' ),
				$formSelect = $formSelectControl.find( 'select' ),
				$addFormNoticeControl = panel.$el.find( '.elementor-control.elementor-control-add_form_notice' ),
				$testFormNoticeControl = panel.$el.find( '.elementor-control.elementor-control-test_form_notice' );

			// Update form select options if it is available after adding the form.
			if ( vars.formSelectOptions ) {
				$formSelect.html( vars.formSelectOptions );
			}

			// Update form select value.
			if ( vars.formId && vars.formId !== '' ) {
				$formSelect.val( vars.formId );
			}

			// Hide not needed controls.
			if ( $formSelect.find( 'option' ).length > 0 ) {
				$addFormNoticeControl.hide();
			} else {
				$formSelectControl.hide();
				$testFormNoticeControl.hide();
			}

			// Show needed controls.
			if ( parseInt( $formSelect.val(), 10 ) > 0 ) {
				$testFormNoticeControl.show();
			}

			// Select form.
			panel.$el.find( '.elementor-control.elementor-control-form_id' ).on( 'change', 'select', function() {

				// Update `vars.formId` to be able to restore selected value after options update.
				vars.formId = $( this ).val();
			} );

			// Click on the `Edit the selected form` link.
			panel.$el.find( '.elementor-control.elementor-control-edit_form' ).on( 'click', 'a', app.editFormLinkClick );
		},

		/**
		 * Initialize observer to re-init controls on form section toggles.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} panel Panel object.
		 */
		widgetPanelSectionClickObserver: function( panel ) {

			if ( vars.observerWidgetId === vars.widgetId ) {
				return;
			}

			// Disconnect previous widget observer.
			if ( typeof vars.observer !== 'undefined' && $.isFunction( vars.observer.disconnect ) ) {
				vars.observer.disconnect();
			}

			var obs = {
				targetNode  : panel.$el.find( '#elementor-panel-page-editor' )[0],
				config      : {
					childList: true,
					subtree: true,
				},
			};

			obs.callback = function( mutationsList, observer ) {

				var mutation, node;

				for ( var i in mutationsList ) {
					mutation = mutationsList[ i ];

					if ( mutation.type !== 'childList' || mutation.addedNodes.length < 1 ) {
						continue;
					}

					for ( var n in mutation.addedNodes ) {
						node = mutation.addedNodes[ n ];

						if ( node && node.classList && node.classList.contains( 'elementor-control-section_form' ) ) {
							app.widgetPanelInit( panel );
						}
					}
				}
			};

			obs.observer = new MutationObserver( obs.callback );
			obs.observer.observe( obs.targetNode, obs.config );

			vars.observerWidgetId = vars.widgetId;
			vars.observer = obs.observer;
		},

		/**
		 * Edit selected form button click event handler.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} event Event object.
		 */
		editFormLinkClick: function( event ) {

			app.findFormSelector( event );
			app.openBuilderPopup( vars.$select.val() );
		},

		/**
		 * Add a new form button click event handler.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} event Event object.
		 */
		addFormBtnClick: function( event ) {

			app.findFormSelector( event );
			app.openBuilderPopup( 0 );
		},

		/**
		 * Find and store the form selector control wrapped in jQuery object.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} event Event object.
		 */
		findFormSelector: function( event ) {

			vars.$select = event && event.$el ?
				event.$el.closest( '#elementor-controls' ).find( 'select[data-setting="form_id"]' ) :
				window.parent.jQuery( '#elementor-controls select[data-setting="form_id"]' );
		},

		/**
		 * Preview: Form selector event handler.
		 *
		 * @since 1.6.2
		 */
		selectFormInPreview: function() {

			vars.formId = $( this ).val();

			app.findFormSelector();
			vars.$select.val( vars.formId ).trigger( 'change' );
		},

		/**
		 * Preview: Click on the link event handler.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} event Event object.
		 */
		clickLinkInPreview: function( event ) {

			if ( event.target && event.target.href ) {
				window.open( event.target.href, '_blank', 'noopener,noreferrer' );
			}
		},

		/**
		 * Disable events.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} event Event object.
		 *
		 * @returns {boolean} Always false.
		 */
		disableEvents: function( event ) {

			event.preventDefault();
			event.stopImmediatePropagation();

			return false;
		},

		/**
		 * Open builder popup.
		 *
		 * @since 1.6.2
		 *
		 * @param {number} formId Form id. 0 for create new form.
		 */
		openBuilderPopup: function( formId ) {

			formId = parseInt( formId || '0', 10 );

			if ( ! vars.$popup ) {

				// We need to add popup markup to the editor top document.
				var $elementor = window.parent.jQuery( '#elementor-editor-wrapper' ),
					popupTpl = wp.template( 'wpforms-builder-elementor-popup' );

				$elementor.after( popupTpl() );
				vars.$popup = $elementor.siblings( '#wpforms-builder-elementor-popup' );
			}

			var url = formId > 0 ? wpformsElementorVars.edit_form_url + formId : wpformsElementorVars.add_form_url,
				$iframe = vars.$popup.find( 'iframe' );

			app.builderCloseButtonEvent();
			$iframe.attr( 'src', url );
			vars.$popup.fadeIn();
		},

		/**
		 * Close button (inside the form builder) click event.
		 *
		 * @since 1.6.2
		 */
		builderCloseButtonEvent: function() {

			vars.$popup
				.off( 'wpformsBuilderInPopupClose' )
				.on( 'wpformsBuilderInPopupClose', function( e, action, formId ) {

					if ( action !== 'saved' || ! formId ) {
						return;
					}

					app.refreshFormsList( null, formId );
				} );
		},

		/**
		 * Refresh forms list event handler.
		 *
		 * @since 1.6.2
		 *
		 * @param {object} event     Event object.
		 * @param {number} setFormId Set selected form to.
		 */
		refreshFormsList: function( event, setFormId ) {

			if ( event ) {
				event.preventDefault();
			}

			app.findFormSelector();

			var data = {
				action: 'wpforms_admin_get_form_selector_options',
				nonce : wpformsElementorVars.nonce,
			};

			vars.$select.prop( 'disabled', true );

			$.post( wpformsElementorVars.ajax_url, data )
				.done( function( response ) {

					if ( ! response.success ) {
						app.debug( response );
						return;
					}

					vars.formSelectOptions = response.data;
					vars.$select.html( response.data );

					if ( setFormId ) {
						vars.formId = setFormId;
					}

					if ( vars.formId && vars.formId !== '' ) {
						vars.$select.val( vars.formId ).trigger( 'change' );
					}
				} )
				.fail( function( xhr, textStatus ) {

					app.debug( {
						xhr: xhr,
						textStatus: textStatus,
					} );
				} )
				.always( function() {

					if ( ! vars.$select || vars.$select.length < 1 ) {
						return;
					}

					vars.$select.prop( 'disabled', false );

					var $formSelectOptions = vars.$select.find( 'option' ),
						$formSelectControl = vars.$select.closest( '.elementor-control' );

					if ( $formSelectOptions.length > 0 ) {
						$formSelectControl.show();
						$formSelectControl.siblings( '.elementor-control-add_form_notice' ).hide();
					}
					if ( parseInt( vars.$select.val(), 10 ) > 0 ) {
						$formSelectControl.siblings( '.elementor-control-test_form_notice' ).show();
					}
				} );
		},

		/**
		 * Debug output helper.
		 *
		 * @since 1.6.2
		 *
		 * @param {mixed} msg Debug message.
		 */
		debug: function( msg ) {

			if ( app.isDebug() ) {
				console.log( 'WPForms Debug:', msg );
			}
		},

		/**
		 * Is debug mode.
		 *
		 * @since 1.6.2
		 *
		 * @returns {boolean} True if the debug enabled.
		 */
		isDebug: function() {

			return ( ( window.top.location.hash && '#wpformsdebug' === window.top.location.hash ) || wpformsElementorVars.debug );
		},
	};

	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsElementor.init();
