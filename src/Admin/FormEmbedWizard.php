<?php

namespace WPForms\Admin;

/**
 * Embed Form in a Page wizard.
 *
 * @since 1.6.2
 */
class FormEmbedWizard {

	/**
	 * Initialize class.
	 *
	 * @since 1.6.2
	 */
	public function init() {

		// Form Embed Wizard should load only in the Form Builder and on the Edit/Add Page screen.
		if (
			! wpforms_is_admin_page( 'builder' ) &&
			! wp_doing_ajax() &&
			! $this->is_form_embed_page()
		) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.6.2
	 */
	public function hooks() {

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );
		add_action( 'admin_footer', [ $this, 'output' ] );
		add_filter( 'default_title', [ $this, 'embed_page_title' ], 10, 2 );
		add_filter( 'default_content', [ $this, 'embed_page_content' ], 10, 2 );
		add_action( 'wp_ajax_wpforms_admin_form_embed_wizard_embed_page_url', [ $this, 'get_embed_page_url_ajax' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.6.2
	 */
	public function enqueues() {

		$min = wpforms_get_min_suffix();

		if ( $this->is_form_embed_page() ) {

			wp_enqueue_style(
				'wpforms-challenge',
				WPFORMS_PLUGIN_URL . "assets/css/challenge{$min}.css",
				[],
				WPFORMS_VERSION
			);

			wp_enqueue_style(
				'tooltipster',
				WPFORMS_PLUGIN_URL . 'assets/css/tooltipster.css',
				null,
				'4.2.6'
			);

			wp_enqueue_script(
				'tooltipster',
				WPFORMS_PLUGIN_URL . 'assets/js/jquery.tooltipster.min.js',
				[ 'jquery' ],
				'4.2.6',
				true
			);
		}

		wp_enqueue_script(
			'wpforms-admin-form-embed-wizard',
			WPFORMS_PLUGIN_URL . "assets/js/components/admin/form-embed-wizard{$min}.js",
			[ 'jquery' ],
			WPFORMS_VERSION
		);

		wp_localize_script(
			'wpforms-admin-form-embed-wizard',
			'wpforms_admin_form_embed_wizard',
			[
				'nonce'        => wp_create_nonce( 'wpforms_admin_form_embed_wizard_nonce' ),
				'is_edit_page' => (int) $this->is_form_embed_page( 'edit' ),
			]
		);
	}

	/**
	 * Output HTML.
	 *
	 * @since 1.6.2
	 */
	public function output() {

		$template = $this->is_form_embed_page() ? 'admin/form-embed-wizard/tooltip' : 'admin/form-embed-wizard/popup';

		echo wpforms_render( $template ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$this->delete_meta();
	}

	/**
	 * Check if the current page is a form embed page edit related to Challenge.
	 *
	 * @since 1.6.2
	 *
	 * @param string $type Type of the embed page to check. Can be '', 'add' or 'edit'. By default is empty string.
	 *
	 * @return boolean
	 */
	public function is_form_embed_page( $type = '' ) {

		global $pagenow;

		$type = $type === 'add' || $type === 'edit' ? $type : '';

		if (
			$pagenow !== 'post.php' &&
			$pagenow !== 'post-new.php'
		) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$post_id   = empty( $_GET['post'] ) ? 0 : (int) $_GET['post'];
		$post_type = empty( $_GET['post_type'] ) ? '' : sanitize_key( $_GET['post_type'] );
		$action    = empty( $_GET['action'] ) ? 'add' : sanitize_key( $_GET['action'] );
		// phpcs:enable

		if ( $pagenow === 'post-new.php' &&
			( empty( $post_type ) || $post_type !== 'page' )
		) {
			return false;
		}

		if (
			$pagenow === 'post.php' &&
			( empty( $post_id ) || get_post_type( $post_id ) !== 'page' )
		) {
			return false;
		}

		$meta       = $this->get_meta();
		$embed_page = ! empty( $meta['embed_page'] ) ? (int) $meta['embed_page'] : 0;

		if ( 'add' === $action && 0 === $embed_page && $type !== 'edit' ) {
			return true;
		}

		if ( ! empty( $post_id ) && $embed_page === $post_id && $type !== 'add' ) {
			return true;
		}

		return false;
	}

	/**
	 * Set user's embed meta data.
	 *
	 * @since 1.6.2
	 *
	 * @param array $data Data array to set.
	 */
	public function set_meta( $data ) {

		update_user_meta( get_current_user_id(), 'wpforms_admin_form_embed_wizard', $data );
	}

	/**
	 * Get user's embed meta data.
	 *
	 * @since 1.6.2
	 *
	 * @return array User's embed meta data.
	 */
	public function get_meta() {

		return get_user_meta( get_current_user_id(), 'wpforms_admin_form_embed_wizard', true );
	}

	/**
	 * Delete user's embed meta data.
	 *
	 * @since 1.6.2
	 */
	public function delete_meta() {

		delete_user_meta( get_current_user_id(), 'wpforms_admin_form_embed_wizard' );
	}

	/**
	 * Get embed page URL via AJAX.
	 *
	 * @since 1.6.2
	 */
	public function get_embed_page_url_ajax() {

		check_admin_referer( 'wpforms_admin_form_embed_wizard_nonce' );

		$page_id = ! empty( $_POST['pageId'] ) ? sanitize_key( wp_unslash( $_POST['pageId'] ) ) : 0;

		if ( ! empty( $page_id ) ) {
			$url  = get_edit_post_link( $page_id, '' );
			$meta = [
				'embed_page' => $page_id,
			];
		} else {
			$url  = add_query_arg( 'post_type', 'page', admin_url( 'post-new.php' ) );
			$meta = [
				'embed_page'       => 0,
				'embed_page_title' => ! empty( $_POST['pageTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['pageTitle'] ) ) : '',
			];
		}

		$meta['form_id'] = ! empty( $_POST['formId'] ) ? sanitize_key( wp_unslash( $_POST['formId'] ) ) : 0;

		$this->set_meta( $meta );

		// Update challenge option to properly continue challenge on the embed page.
		$challenge = wpforms()->get( 'challenge' );
		if ( $challenge->challenge_active() ) {
			$challenge->set_challenge_option( [ 'embed_page' => $meta['embed_page'] ] );
		}

		wp_send_json_success( $url );
	}

	/**
	 * Set default title for the new page.
	 *
	 * @since 1.6.2
	 *
	 * @param string   $post_title Default post title.
	 * @param \WP_Post $post       Post object.
	 *
	 * @return string New default post title.
	 */
	public function embed_page_title( $post_title, $post ) {

		$meta = $this->get_meta();

		$this->delete_meta();

		return empty( $meta['embed_page_title'] ) ? $post_title : $meta['embed_page_title'];
	}

	/**
	 * Embed the form to the new page.
	 *
	 * @since 1.6.2
	 *
	 * @param string   $post_content Default post content.
	 * @param \WP_Post $post         Post object.
	 *
	 * @return string Embedding string (shortcode or GB component code).
	 */
	public function embed_page_content( $post_content, $post ) {

		$meta = $this->get_meta();

		$form_id = ! empty( $meta['form_id'] ) ? $meta['form_id'] : 0;
		$page_id = ! empty( $meta['embed_page'] ) ? $meta['embed_page'] : 0;

		if ( ! empty( $page_id ) || empty( $form_id ) ) {
			return $post_content;
		}

		if ( wpforms_is_gutenberg_active() ) {
			$pattern = '<!-- wp:wpforms/form-selector {"formId":"%d"} /-->';
		} else {
			$pattern = '[wpforms id="%d" title="false" description="false"]';
		}

		return sprintf( $pattern, absint( $form_id ) );
	}
}
