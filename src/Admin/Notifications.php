<?php

namespace WPForms\Admin;

/**
 * Notifications.
 *
 * @since 1.6.0
 */
class Notifications {

	/**
	 * Source of notifications content.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	const SOURCE_URL = 'https://plugin-cdn.wpforms.com/wp-content/notifications.json';

	/**
	 * Option value.
	 *
	 * @since 1.6.0
	 *
	 * @var bool|array
	 */
	public $option = false;

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

		add_action( 'wpforms_overview_enqueue', [ $this, 'enqueues' ] );

		add_action( 'wpforms_admin_overview_before_table', [ $this, 'output' ] );

		add_action( 'wpforms_admin_notifications_update', [ $this, 'update' ] );

		add_action( 'wp_ajax_wpforms_notification_dismiss', [ $this, 'dismiss' ] );
	}

	/**
	 * Check if user has access and is enabled.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function has_access() {

		$access = false;

		if (
			wpforms_current_user_can( 'view_forms' ) &&
			! wpforms_setting( 'hide-announcements', false )
		) {
			$access = true;
		}

		return apply_filters( 'wpforms_admin_notifications_has_access', $access );
	}

	/**
	 * Get option value.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $cache Reference property cache if available.
	 *
	 * @return array
	 */
	public function get_option( $cache = true ) {

		if ( $this->option && $cache ) {
			return $this->option;
		}

		$option = get_option( 'wpforms_notifications', [] );

		$this->option = [
			'update'    => ! empty( $option['update'] ) ? $option['update'] : 0,
			'events'    => ! empty( $option['events'] ) ? $option['events'] : [],
			'feed'      => ! empty( $option['feed'] ) ? $option['feed'] : [],
			'dismissed' => ! empty( $option['dismissed'] ) ? $option['dismissed'] : [],
		];

		return $this->option;
	}

	/**
	 * Fetch notifications from feed.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public function fetch_feed() {

		$res = wp_remote_get( self::SOURCE_URL );

		if ( is_wp_error( $res ) ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $res );

		if ( empty( $body ) ) {
			return [];
		}

		return $this->verify( json_decode( $body, true ) );
	}

	/**
	 * Verify notification data before it is saved.
	 *
	 * @since 1.6.0
	 *
	 * @param array $notifications Array of notifications items to verify.
	 *
	 * @return array
	 */
	public function verify( $notifications ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$data = [];

		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return $data;
		}

		$option = $this->get_option();

		foreach ( $notifications as $notification ) {

			// The message and license should never be empty, if they are, ignore.
			if ( empty( $notification['content'] ) || empty( $notification['type'] ) ) {
				continue;
			}

			// Ignore if license type does not match.
			$license = wpforms_get_license_type() ? wpforms_get_license_type() : 'lite';

			if ( ! in_array( $license, $notification['type'], true ) ) {
				continue;
			}

			// Ignore if expired.
			if ( ! empty( $notification['end'] ) && time() > strtotime( $notification['end'] ) ) {
				continue;
			}

			// Ignore if notifcation has already been dismissed.
			if ( ! empty( $option['dismissed'] ) && in_array( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				continue;
			}

			// Ignore if notification existed before installing WPForms.
			// Prevents bombarding the user with notifications after activation.
			$activated = wpforms_get_activated_timestamp();

			if (
				! empty( $activated ) &&
				! empty( $notification['start'] ) &&
				$activated > strtotime( $notification['start'] )
			) {
				continue;
			}

			$data[] = $notification;
		}

		return $data;
	}

	/**
	 * Verify saved notification data for active notifications.
	 *
	 * @since 1.6.0
	 *
	 * @param array $notifications Array of notifications items to verify.
	 *
	 * @return array
	 */
	public function verify_active( $notifications ) {

		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return [];
		}

		// Remove notfications that are not active.
		foreach ( $notifications as $key => $notification ) {
			if (
				( ! empty( $notification['start'] ) && time() < strtotime( $notification['start'] ) ) ||
				( ! empty( $notification['end'] ) && time() > strtotime( $notification['end'] ) )
			) {
				unset( $notifications[ $key ] );
			}
		}

		return $notifications;
	}

	/**
	 * Get notification data.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public function get() {

		if ( ! $this->has_access() ) {
			return [];
		}

		$option = $this->get_option();

		// Update notifications using async task.
		if ( empty( $option['update'] ) || time() > $option['update'] + DAY_IN_SECONDS ) {

			if ( empty( wpforms()->get( 'tasks' )->is_scheduled( 'wpforms_admin_notifications_update' ) ) ) {

				wpforms()->get( 'tasks' )
					->create( 'wpforms_admin_notifications_update' )
					->async()
					->params()
					->register();
			}
		}

		$events = ! empty( $option['events'] ) ? $this->verify_active( $option['events'] ) : [];
		$feed   = ! empty( $option['feed'] ) ? $this->verify_active( $option['feed'] ) : [];

		return array_merge( $events, $feed );
	}

	/**
	 * Get notification count.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public function get_count() {

		return count( $this->get() );
	}

	/**
	 * Add a manual notification event.
	 *
	 * @since 1.6.0
	 *
	 * @param array $notification Notification data.
	 */
	public function add( $notification ) {

		if ( empty( $notification['id'] ) ) {
			return;
		}

		$option = $this->get_option();

		if ( in_array( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			return;
		}

		foreach ( $option['events'] as $item ) {
			if ( $item['id'] === $notification['id'] ) {
				return;
			}
		}

		$notification = $this->verify( [ $notification ] );

		update_option(
			'wpforms_notifications',
			[
				'update'    => $option['update'],
				'feed'      => $option['feed'],
				'events'    => array_merge( $notification, $option['events'] ),
				'dismissed' => $option['dismissed'],
			]
		);
	}

	/**
	 * Update notification data from feed.
	 *
	 * @since 1.6.0
	 */
	public function update() {

		$feed   = $this->fetch_feed();
		$option = $this->get_option();

		update_option(
			'wpforms_notifications',
			[
				'update'    => time(),
				'feed'      => $feed,
				'events'    => $option['events'],
				'dismissed' => $option['dismissed'],
			]
		);
	}

	/**
	 * Admin area Form Overview enqueues.
	 *
	 * @since 1.6.0
	 */
	public function enqueues() {

		if ( ! $this->has_access() ) {
			return;
		}

		$notifications = $this->get();

		if ( empty( $notifications ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-admin-notifications',
			WPFORMS_PLUGIN_URL . "assets/css/admin-notifications{$min}.css",
			[],
			WPFORMS_VERSION
		);

		wp_enqueue_script(
			'wpforms-admin-notifications',
			WPFORMS_PLUGIN_URL . "assets/js/admin-notifications{$min}.js",
			[ 'jquery' ],
			WPFORMS_VERSION,
			true
		);
	}

	/**
	 * Output notifications on Form Overview admin area.
	 *
	 * @since 1.6.0
	 */
	public function output() {

		$notifications = $this->get();

		if ( empty( $notifications ) ) {
			return;
		}

		$notifications_html   = '';
		$current_class        = ' current';
		$content_allowed_tags = [
			'em'     => [],
			'strong' => [],
			'span'   => [
				'style' => [],
			],
			'a'      => [
				'href'   => [],
				'target' => [],
				'rel'    => [],
			],
		];

		foreach ( $notifications as $notification ) {

			// Buttons HTML.
			$buttons_html = '';
			if ( ! empty( $notification['btns'] ) && is_array( $notification['btns'] ) ) {
				foreach ( $notification['btns'] as $btn_type => $btn ) {
					$buttons_html .= sprintf(
						'<a href="%1$s" class="button button-%2$s"%3$s>%4$s</a>',
						! empty( $btn['url'] ) ? esc_url( $btn['url'] ) : '',
						$btn_type === 'main' ? 'primary' : 'secondary',
						! empty( $btn['target'] ) && $btn['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '',
						! empty( $btn['text'] ) ? sanitize_text_field( $btn['text'] ) : ''
					);
				}
				$buttons_html = ! empty( $buttons_html ) ? '<div class="buttons">' . $buttons_html . '</div>' : '';
			}

			// Notification HTML.
			$notifications_html .= sprintf(
				'<div class="message%5$s" data-message-id="%4$s">
					<h3 class="title">%1$s</h3>
					<p class="content">%2$s</p>
					%3$s
				</div>',
				! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : '',
				! empty( $notification['content'] ) ? wp_kses( $notification['content'], $content_allowed_tags ) : '',
				$buttons_html,
				! empty( $notification['id'] ) ? esc_attr( sanitize_text_field( $notification['id'] ) ) : 0,
				$current_class
			);

			// Only first notification is current.
			$current_class = '';
		}
		?>

		<div id="wpforms-notifications">

			<div class="bell">
				<svg xmlns="http://www.w3.org/2000/svg" width="42" height="48" viewBox="0 0 42 48"><defs><style>.a{fill:#777;}.b{fill:#ca4a1f;}</style></defs><path class="a" d="M23-79a6.005,6.005,0,0,1-6-6h10.06a12.066,12.066,0,0,0,1.791,1.308,6.021,6.021,0,0,1-2.077,3.352A6.008,6.008,0,0,1,23-79Zm1.605-9H5.009a2.955,2.955,0,0,1-2.173-.923A3.088,3.088,0,0,1,2-91a2.919,2.919,0,0,1,.807-2.036c.111-.12.229-.243.351-.371a14.936,14.936,0,0,0,3.126-4.409A23.283,23.283,0,0,0,8.007-107.5a14.846,14.846,0,0,1,.906-5.145,14.5,14.5,0,0,1,2.509-4.324A15.279,15.279,0,0,1,20-122.046V-124a3,3,0,0,1,3-3,3,3,0,0,1,3,3v1.954a15.28,15.28,0,0,1,8.58,5.078,14.5,14.5,0,0,1,2.509,4.324,14.846,14.846,0,0,1,.906,5.145c0,.645.016,1.281.047,1.888A12.036,12.036,0,0,0,35-106a11.921,11.921,0,0,0-8.485,3.515A11.923,11.923,0,0,0,23-94a12,12,0,0,0,1.6,6Z" transform="translate(-2 127)"/><circle class="b" cx="9" cy="9" r="9" transform="translate(24 24)"/></svg>
			</div>

			<a class="dismiss" title="<?php echo esc_attr__( 'Dismiss this message', 'wpforms-lite' ); ?>"><i class="fa fa-times-circle" aria-hidden="true"></i></a>

			<div class="navigation">
				<a class="prev disabled" title="<?php echo esc_attr__( 'Previous message', 'wpforms-lite' ); ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
				<a class="next disabled" title="<?php echo esc_attr__( 'Next message', 'wpforms-lite' ); ?>"><i class="fa fa-chevron-right" aria-hidden="true"></i></a>
			</div>

			<div class="messages">
				<?php echo $notifications_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Dismiss notification via AJAX.
	 *
	 * @since 1.6.0
	 */
	public function dismiss() {

		// Run a security check.
		check_ajax_referer( 'wpforms-admin', 'nonce' );

		// Check for access and required param.
		if ( ! $this->has_access() || empty( $_POST['id'] ) ) {
			wp_send_json_error();
		}

		$id     = sanitize_text_field( wp_unslash( $_POST['id'] ) );
		$option = $this->get_option();
		$type   = is_numeric( $id ) ? 'feed' : 'events';

		$option['dismissed'][] = $id;
		$option['dismissed']   = array_unique( $option['dismissed'] );

		// Remove notification.
		if ( is_array( $option[ $type ] ) && ! empty( $option[ $type ] ) ) {
			foreach ( $option[ $type ] as $key => $notification ) {
				if ( $notification['id'] == $id ) { // phpcs:ignore WordPress.PHP.StrictComparisons
					unset( $option[ $type ][ $key ] );
					break;
				}
			}
		}

		update_option( 'wpforms_notifications', $option );

		wp_send_json_success();
	}
}
