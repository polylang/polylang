<?php

/**
 * Displays the translations fields for media
 * Needs WP 3.5+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};
?>
<p><strong><?php esc_html_e( 'Translations', 'polylang' ); ?></strong></p>
<table>
	<?php
	foreach ( $this->model->get_languages_list() as $language ) {
		if ( $language->term_id == $lang->term_id ) {
			continue;
		}
		?>
		<tr>
			<td class = "pll-media-language-column"><span class = "pll-translation-flag"><?php echo esc_html( $language->flag ); ?></span><?php echo esc_html( $language->name ); ?></td>
			<td class = "pll-media-edit-column">
				<?php
				if ( ( $translation_id = $this->model->post->get_translation( $post_ID, $language ) ) && $translation_id !== $post_ID ) {
					// The translation exists
					printf(
						'<input type="hidden" name="media_tr_lang[%s]" value="%d" />',
						esc_attr( $language->slug ),
						esc_attr( $translation_id )
					);
					echo esc_html( $this->links->edit_post_translation_link( $translation_id ) );
				} else {
					// No translation
					echo esc_html( $this->links->new_post_translation_link( $post_ID, $language ) );
				}
				?>
			</td>
		</tr>
		<?php
	} // End foreach
	?>
</table>
