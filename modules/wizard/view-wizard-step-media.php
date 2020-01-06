<?php

/**
 * Displays the wizard media step
 *
 * @since 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

$default_options = PLL_Install::get_default_options();
$options = wp_parse_args( get_option( 'polylang' ), $default_options );
$media_support = $options['media_support'];
$rtl = '';
if ( function_exists( 'is_rtl' ) && is_rtl() ) {
	$rtl = '-rtl';
}
$help_screenshot = '/modules/wizard/images/media-screen' . $rtl . '.png';

?>
<h2><?php esc_html_e( 'Media', 'polylang' ); ?></h2>
<p>
	<?php esc_html_e( 'Polylang allows you to translate the texts attached to your media such as the title, the alternative text, the caption and the description.', 'polylang' ); ?>
	<?php esc_html_e( 'When you translate a media, the file is not duplicated on your disk, however you will see one entry per language in the media library.', 'polylang' ); ?>
	<?php esc_html_e( 'When you want to insert a media in a post, only the media in the language of the current post are displayed.', 'polylang' ); ?>
</p>
<p>
	<?php esc_html_e( 'If you need to translate the title, the alternative text, the caption or the description, then you must activate the media translation. Otherwise you can safely deactivate it.', 'polylang' ); ?>
</p>
<ul class="pll-wizard-services">
	<li class="pll-wizard-service-item">
		<div class="pll-wizard-service-enable">
			<span class="pll-wizard-service-toggle">
				<input
					id="pll-wizard-service-media"
					type="checkbox"
					name="media_support"
					value="yes" <?php checked( $media_support ); ?>
				/>
				<label for="pll-wizard-service-media" />
			</span>
		</div>
		<div class="pll-wizard-service-description">
			<p>
				<?php esc_html_e( 'Allow to translate media', 'polylang' ); ?>
			</p>
		</div>
	</li>
	<li class="pll-wizard-service-item">
		<div class="pll-wizard-service-example">
			<p>
				<input id="slide-toggle" type="checkbox" checked="checked">
				<label for="slide-toggle" class="button button-primary button-small">
					<span class="dashicons dashicons-visibility"></span><?php esc_html_e( 'Help', 'polylang' ); ?>
				</label>
				<span id="screenshot">
					<img src="<?php echo esc_url_raw( esc_url( plugins_url( $help_screenshot, POLYLANG_FILE ) ) ); ?>" />
				</span>
			</p>
		</div>
	</li>
</ul>
