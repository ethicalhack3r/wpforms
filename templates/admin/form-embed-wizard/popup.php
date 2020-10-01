<?php
/**
 * Form Embed Wizard.
 * Embed popup HTML template.
 *
 * @since 1.6.2
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

$pages_exists = (int) wp_count_posts( 'page' )->publish > 0;

?>
<div id="wpforms-admin-form-embed-wizard-container" class="wpforms-admin-popup-container">
	<div id="wpforms-admin-form-embed-wizard" class="wpforms-admin-popup" data-pages-exists="<?php echo esc_attr( $pages_exists ); ?>">
		<div class="wpforms-admin-popup-content">
			<h3><?php esc_html_e( 'Embed in a Page', 'wpforms-lite' ); ?></h3>
			<div id="wpforms-admin-form-embed-wizard-content-initial">
				<p class="no-gap"><b><?php esc_html_e( 'We can help embed your form with just a few clicks!', 'wpforms-lite' ); ?></b></p>
				<p><?php esc_html_e( 'Would you like to embed your form in an existing page, or create a new one?', 'wpforms-lite' ); ?></p>
			</div>
			<div id="wpforms-admin-form-embed-wizard-content-select-page" style="display: none;">
				<p><?php esc_html_e( 'Select the page you would like to embed your form in.', 'wpforms-lite' ); ?></p>
			</div>
			<div id="wpforms-admin-form-embed-wizard-content-create-page" style="display: none;">
				<p><?php esc_html_e( 'What would you like to call the new page?', 'wpforms-lite' ); ?></p>
			</div>
			<div id="wpforms-admin-form-embed-wizard-section-btns" class="wpforms-admin-popup-bottom">
				<button type="button" data-action="select-page" class="wpforms-admin-popup-btn"><?php esc_html_e( 'Select Existing Page', 'wpforms-lite' ); ?></button>
				<button type="button" data-action="create-page" class="wpforms-admin-popup-btn"><?php esc_html_e( 'Create New Page', 'wpforms-lite' ); ?></button>
			</div>
			<div id="wpforms-admin-form-embed-wizard-section-go" class="wpforms-admin-popup-bottom wpforms-admin-popup-flex" style="display: none;">
				<?php
				wp_dropdown_pages(
					[
						'show_option_none' => esc_html__( 'Select a Page', 'wpforms-lite' ),
						'id'               => 'wpforms-admin-form-embed-wizard-select-page',
						'name'             => '',
					]
				);
				?>
				<input type="text" id="wpforms-admin-form-embed-wizard-new-page-title" value="" placeholder="<?php esc_attr_e( 'Name Your Page', 'wpforms-lite' ); ?>">
				<button type="button" data-action="go" class="wpforms-admin-popup-btn"><?php esc_html_e( 'Let’s Go!', 'wpforms-lite' ); ?></button>
			</div>
			<div id="wpforms-admin-form-embed-wizard-section-toggles" class="wpforms-admin-popup-bottom">
				<p class="secondary">
					<?php
					printf(
						wp_kses( /* translators: %s - Video tutorial toggle class. */
							__( 'You can also <a href="#" class="%1$s">embed your form manually</a> or <a href="#" class="%2$s">use a shortcode</a>', 'wpforms-lite' ),
							[
								'a' => [
									'href'  => [],
									'class' => [],
								],
							]
						),
						'tutorial-toggle wpforms-admin-popup-toggle',
						'shortcode-toggle wpforms-admin-popup-toggle'
					);
					?>
				</p>
				<?php $video_id = wpforms_is_gutenberg_active() ? '_29nTiDvmLw' : 'IxGVz3AjEe0'; ?>
				<iframe style="display: none;" src="https://youtube.com/embed/<?php echo esc_attr( $video_id ); ?>?rel=0&showinfo=0" frameborder="0" id="wpforms-admin-form-embed-wizard-tutorial" allowfullscreen width="450" height="256"></iframe>
				<input type="text" id="wpforms-admin-form-embed-wizard-shortcode" class="wpforms-admin-popup-shortcode" disabled style="display: none;"/>
			</div>
			<div id="wpforms-admin-form-embed-wizard-section-goback" class="wpforms-admin-popup-bottom" style="display: none;">
				<p class="secondary">
					<a href="#" class="wpforms-admin-popup-toggle initialstate-toggle">« <?php esc_html_e( 'Go back', 'wpforms-lite' ); ?></a>
				</p>
			</div>
		</div>
		<div class="wpforms-admin-popup-close">×</div>
	</div>
</div>
