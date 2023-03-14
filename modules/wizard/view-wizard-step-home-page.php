<?php
/**
 * Displays the wizard home page step
 *
 * @package Polylang
 *
 * @since 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

$languages = $this->model->get_languages_list();
$default_language = count( $languages ) > 0 ? $this->options['default_lang'] : null;
$home_page_id = get_option( 'page_on_front' );
$translations = $this->model->post->get_translations( $home_page_id );
$untranslated_languages = array();
$home_page = $home_page_id > 0 ? get_post( $home_page_id ) : null;
$home_page_language = $this->model->post->get_language( $home_page_id );

foreach ( $languages as $language ) {
	if ( ! $this->model->post->get( $home_page_id, $language ) ) {
		$untranslated_languages[] = $language;
	}
}
?>
<input type="hidden" name="home_page" value="<?php echo esc_attr( $home_page->ID ); ?>" />
<input type="hidden" name="home_page_title" value="<?php echo esc_attr( $home_page->post_title ); ?>" />
<?php if ( false !== $home_page_language ) : ?>
	<input type="hidden" name="home_page_language" value="<?php echo esc_attr( $home_page_language->slug ); ?>" />
<?php endif; ?>
<h2><?php esc_html_e( 'Homepage', 'polylang' ); ?></h2>
<p>
	<?php
		printf(
			/* translators: %s is the post title of the front page */
			esc_html__( 'You defined this page as your static homepage: %s.', 'polylang' ),
			'<strong>' . esc_html( $home_page->post_title ) . '</strong>'
		);
		?>
	<br />
	<?php
		printf(
			/* translators: %s is the language of the front page ( flag, native name and locale ) */
			esc_html__( 'Its language is : %s.', 'polylang' ),
			$home_page_language->flag . ' <strong>' . esc_html( $home_page_language->name ) . ' ' . esc_html( $home_page_language->locale ) . '</strong>' //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		)
		?>
	<br />
	<?php esc_html_e( 'For your site to work correctly, this page must be translated in all available languages.', 'polylang' ); ?>
</p>
<p>
	<?php esc_html_e( 'After the pages is created, it is up to you to put the translated content in each page linked to each language.', 'polylang' ); ?>
</p>
<?php if ( $translations ) : ?>
<table id="translated-languages" class="striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Your static homepage is already translated in', 'polylang' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( array_keys( $translations ) as $lang ) {
		$language = $this->model->get_language( $lang );
		?>
		<tr>
			<td>
				<?php
				echo $language->flag;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ' ' . esc_html( $language->name ) . ' ' . esc_html( $language->locale ) . ' ';
				?>
				<?php if ( $language->is_default ) : ?>
					<span class="icon-default-lang">
						<span class="screen-reader-text">
							<?php esc_html_e( 'Default language', 'polylang' ); ?>
						</span>
					</span>
				<?php endif; ?>
				<input type="hidden" name="translated_languages[]" value="<?php echo esc_attr( $language->slug ); ?>" />
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
<?php endif; ?>
<table id="untranslated-languages" class="striped">
	<?php if ( ! is_null( $default_language ) ) : ?>
		<caption><span class="icon-default-lang"></span> <?php esc_html_e( 'Default language', 'polylang' ); ?></caption>
	<?php endif; ?>
	<thead>
		<?php if ( count( $untranslated_languages ) >= 1 ) : ?>
			<tr>
				<th><?php esc_html_e( 'We are going to prepare this page in', 'polylang' ); ?></th>
			</tr>
		<?php elseif ( false !== $home_page_language && count( $untranslated_languages ) <= 0 ) : ?>
			<tr>
				<th>
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'One language is well defined and assigned to your home page.', 'polylang' ); ?>
				</th>
			</tr>
			<tr>
				<td><?php esc_html_e( "If you add a new language, don't forget to translate your homepage.", 'polylang' ); ?></td>
			</tr>
		<?php endif; ?>
	</thead>
	<tbody>
	<?php
	foreach ( $untranslated_languages as $lg ) {
		?>
		<tr>
			<td>
				<?php
				echo $lg->flag;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ' ' . esc_html( $lg->name ) . ' ' . esc_html( $lg->locale ) . ' ';
				?>
				<?php if ( $lg->is_default ) : ?>
					<span class="icon-default-lang">
						<span class="screen-reader-text">
							<?php esc_html_e( 'Default language', 'polylang' ); ?>
						</span>
					</span>
				<?php endif; ?>
				<input type="hidden" name="untranslated_languages[]" value="<?php echo esc_attr( $lg->slug ); ?>" />
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
