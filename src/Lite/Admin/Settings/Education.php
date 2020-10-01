<?php

namespace WPForms\Lite\Admin\Settings;

/**
 * Settings changes and enhancements to educate Lite users on what is
 * available in WPForms Pro.
 *
 * @since 1.5.5
 */
class Education {

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.5.1
	 */
	public function hooks() {

		// Only proceed for the Settings > Integrations tab.
		if ( ! \wpforms_is_admin_page( 'settings' ) ) {
			return;
		}

		// Integrations related hooks.
		if ( \wpforms_is_admin_page( 'settings', 'integrations' ) ) {
			\add_filter( 'wpforms_admin_strings', array( $this, 'js_strings' ) );
			\add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
			\add_action( 'wpforms_settings_providers', array( $this, 'providers' ), 10000, 1 );
		}
	}

	/**
	 * Localize needed strings.
	 *
	 * @since 1.5.5
	 *
	 * @param array $strings JS strings.
	 *
	 * @return array
	 */
	public function js_strings( $strings ) {

		$strings['upgrade'] = [
			'pro'   => [
				'title'   => esc_html__( 'is a PRO Feature', 'wpforms-lite' ),
				'message' => '<p>' . esc_html__( 'We\'re sorry, the %name% is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.', 'wpforms-lite' ) . '</p>',
				'bonus'   => '<p>' .
					wp_kses(
						__( '<strong>Bonus:</strong> WPForms Lite users get <span>50% off</span> regular price, automatically applied at checkout.', 'wpforms-lite' ),
						[
							'strong' => [],
							'span'   => [],
						]
					) .
					'</p>',
				'doc'     => '<a href="https://wpforms.com/docs/upgrade-wpforms-lite-paid-license/?utm_source=WordPress&amp;utm_medium=link&amp;utm_campaign=liteplugin&amp;utm_content=upgrade-pro" target="_blank" rel="noopener noreferrer" class="already-purchased">' . esc_html__( 'Already purchased?', 'wpforms-lite' ) . '</a>',
				'button'  => esc_html__( 'Upgrade to PRO', 'wpforms-lite' ),
				'url'     => wpforms_admin_upgrade_link( 'settings-modal', 'upgrade-pro' ),
				'modal'   => wpforms_get_upgrade_modal_text( 'pro' ),
			],
			'elite' => [
				'title'   => esc_html__( 'is an Elite Feature', 'wpforms-lite' ),
				'message' => '<p>' . esc_html__( 'We\'re sorry, the %name% is not available on your plan. Please upgrade to the Elite plan to unlock all these awesome features.', 'wpforms-lite' ) . '</p>',
				'bonus'   => '<p>' .
					wp_kses(
						__( '<strong>Bonus:</strong> WPForms Lite users get <span>50% off</span> regular price, automatically applied at checkout.', 'wpforms-lite' ),
						[
							'strong' => [],
							'span'   => [],
						]
					) .
					'</p>',
				'doc'     => '<a href="https://wpforms.com/docs/upgrade-wpforms-lite-paid-license/?utm_source=WordPress&amp;utm_medium=link&amp;utm_campaign=liteplugin&amp;utm_content=upgrade-elite" target="_blank" rel="noopener noreferrer" class="already-purchased">' . esc_html__( 'Already purchased?', 'wpforms-lite' ) . '</a>',
				'button'  => esc_html__( 'Upgrade to Elite', 'wpforms-lite' ),
				'url'     => wpforms_admin_upgrade_link( 'settings-modal', 'upgrade-elite' ),
				'modal'   => wpforms_get_upgrade_modal_text( 'elite' ),
			],
		];

		return $strings;
	}

	/**
	 * Load enqueues.
	 *
	 * @since 1.5.5
	 */
	public function enqueues() {

		$min = \wpforms_get_min_suffix();

		\wp_enqueue_script(
			'wpforms-settings-education',
			\WPFORMS_PLUGIN_URL . "lite/assets/js/admin/settings-education{$min}.js",
			array( 'jquery', 'jquery-confirm' ),
			\WPFORMS_VERSION,
			false
		);
	}

	/**
	 * Display providers.
	 *
	 * @since 1.5.5
	 */
	public function providers() {

		$providers = wpforms_get_providers_all();

		foreach ( $providers as $provider ) {

			/* translators: %s - addon name. */
			$modal_name = sprintf( \__( '%s addon', 'wpforms' ), $provider['name'] );

			/* translators: %s - addon name. */
			$descr = sprintf( \__( 'Integrate %s with WPForms', 'wpforms' ), $provider['name'] );

			printf(
				'<div id="wpforms-integration-%1$s" class="wpforms-settings-provider wpforms-clear focus-out education-modal" data-name="%2$s" data-action="upgrade" data-url="%3$s" data-license="%4$s">
					<div class="wpforms-settings-provider-header wpforms-clear">
						<div class="wpforms-settings-provider-logo ">
							<i class="fa fa-chevron-right"></i>
							%5$s
						</div>
						<div class="wpforms-settings-provider-info">
							<h3>%6$s</h3>
							<p>%7$s</p>
						</div>
					</div>
				</div>',
				\esc_attr( $provider['slug'] ),
				\esc_attr( $modal_name ),
				isset( $provider['url'] ) ? \esc_attr( $provider['url'] ) : '',
				\esc_attr( $provider['license'] ),
				'<img src="' . \esc_attr( WPFORMS_PLUGIN_URL ) . 'assets/images/' . \esc_attr( $provider['img'] ) . '">',
				\esc_html( $provider['name'] ),
				\esc_html( $descr )
			);
		}
	}
}
