<?php

namespace WPForms\Admin;

/**
 * WPForms admin bar menu.
 *
 * @since 1.6.0
 */
class AdminBarMenu {

	/**
	 * Initialize class.
	 *
	 * @since 1.6.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.6.0
	 */
	public function hooks() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueues' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );

		add_action( 'admin_bar_menu', [ $this, 'register' ], 999 );
	}

	/**
	 * Check if current user has access to see admin bar menu.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function has_access() {

		$access = false;

		if (
			is_user_logged_in() &&
			wpforms_current_user_can() &&
			! wpforms_setting( 'hide-admin-bar', false )
		) {
			$access = true;
		}

		return apply_filters( 'wpforms_admin_adminbarmenu_has_access', $access );
	}

	/**
	 * Check if new notifications are available.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function has_notifications() {

		return wpforms()->get( 'notifications' )->get_count();
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.6.0
	 */
	public function enqueues() {

		if ( ! $this->has_access() ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-admin-bar',
			WPFORMS_PLUGIN_URL . "assets/css/admin-bar{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Register and render admin menu bar items.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function register( \WP_Admin_Bar $wp_admin_bar ) {

		if ( ! $this->has_access() ) {
			return;
		}

		$items = apply_filters(
			'wpforms_admin_adminbarmenu_register',
			[
				'main_menu',
				'notification_menu',
				'forms_menu',
				'all_forms_menu',
				'add_new_menu',
				'community_menu',
				'support_menu',
			],
			$wp_admin_bar
		);

		foreach ( $items as $item ) {

			$this->{ $item }( $wp_admin_bar );

			do_action( "wpforms_admin_adminbarmenu_register_{$item}_after", $wp_admin_bar );
		}
	}

	/**
	 * Render primary top-level admin menu bar item.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function main_menu( \WP_Admin_Bar $wp_admin_bar ) {

		$indicator = '';

		if ( $this->has_notifications() ) {
			$count     = $this->has_notifications() < 10 ? $this->has_notifications() : '!';
			$indicator = ' <div class="wpforms-menu-notification-counter"><span>' . $count . '</span></div>';
		}

		$wp_admin_bar->add_menu(
			[
				'id'    => 'wpforms-menu',
				'title' => 'WPForms' . $indicator,
				'href'  => admin_url( 'admin.php?page=wpforms-overview' ),
			]
		);
	}

	/**
	 * Render Notifications admin menu bar item.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function notification_menu( \WP_Admin_Bar $wp_admin_bar ) {

		if ( ! $this->has_notifications() ) {
			return;
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => 'wpforms-menu',
				'id'     => 'wpforms-notifications',
				'title'  => __( 'Notifications', 'wpforms-lite' ) . ' <div class="wpforms-menu-notification-indicator"></div>',
				'href'   => admin_url( 'admin.php?page=wpforms-overview' ),
			]
		);
	}

	/**
	 * Render individual forms admin menu bar items and sub-items.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function forms_menu( \WP_Admin_Bar $wp_admin_bar ) {

		if ( is_admin() ) {
			return;
		}

		$forms = wpforms()->frontend->forms;
		$x     = 0;

		if ( empty( $forms ) ) {
			return;
		}

		foreach ( $forms as $form ) {

			$x++;

			$form_id                = absint( $form['id'] );
			$class                  = 'wpforms-menu-form';
			$this->displaying_forms = true;

			if ( $this->has_notifications() && $x === 1 ) {
				$class .= ' wpforms-menu-form-notifications';
			}

			if ( $x === count( $forms ) ) {
				$class .= ' wpforms-menu-form-last';
			}

			// Shrink the long form title.
			$form_title = sanitize_text_field( $form['settings']['form_title'] );
			$form_title = mb_strlen( $form_title ) > 99 ? mb_substr( $form_title, 0, 99 ) . '&hellip;' : $form_title;

			$wp_admin_bar->add_menu(
				[
					'parent' => 'wpforms-menu',
					'id'     => 'wpforms-form-id-' . $form_id,
					'title'  => $form_title,
					'href'   => '#wpforms-' . $form_id,
					'meta'   => [
						'class' => $class,
					],
				]
			);

			$wp_admin_bar->add_menu(
				[
					'parent' => 'wpforms-form-id-' . $form_id,
					'id'     => 'wpforms-edit-form-id-' . $form_id,
					'title'  => __( 'Edit Form', 'wpforms-lite' ),
					'href'   => admin_url( 'admin.php?page=wpforms-builder&view=fields&form_id=' . $form_id ),
				]
			);

			do_action( 'wpforms_admin_adminbarmenu_forms_menu_after', $wp_admin_bar, $form );
		}
	}

	/**
	 * Render All Forms admin menu bar item.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function all_forms_menu( \WP_Admin_Bar $wp_admin_bar ) {

		$wp_admin_bar->add_menu(
			[
				'parent' => 'wpforms-menu',
				'id'     => 'wpforms-forms',
				'title'  => __( 'All Forms', 'wpforms-lite' ),
				'href'   => admin_url( 'admin.php?page=wpforms-overview' ),
			]
		);
	}

	/**
	 * Render Add New admin menu bar item.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function add_new_menu( \WP_Admin_Bar $wp_admin_bar ) {

		$wp_admin_bar->add_menu(
			[
				'parent' => 'wpforms-menu',
				'id'     => 'wpforms-add-new',
				'title'  => __( 'Add New', 'wpforms-lite' ),
				'href'   => admin_url( 'admin.php?page=wpforms-builder' ),
			]
		);
	}

	/**
	 * Render Community admin menu bar item.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function community_menu( \WP_Admin_Bar $wp_admin_bar ) {

		$wp_admin_bar->add_menu(
			[
				'parent' => 'wpforms-menu',
				'id'     => 'wpforms-community',
				'title'  => __( 'Community', 'wpforms-lite' ),
				'href'   => 'https://www.facebook.com/groups/wpformsvip/',
				'meta'   => [
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				],
			]
		);
	}

	/**
	 * Render Support admin menu bar item.
	 *
	 * @since 1.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
	 */
	public function support_menu( \WP_Admin_Bar $wp_admin_bar ) {

		$wp_admin_bar->add_menu(
			[
				'parent' => 'wpforms-menu',
				'id'     => 'wpforms-support',
				'title'  => __( 'Support', 'wpforms-lite' ),
				'href'   => 'https://wpforms.com/docs/',
				'meta'   => [
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				],
			]
		);
	}
}
