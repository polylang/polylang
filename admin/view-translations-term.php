<?php
/**
 * Displays the translations fields for terms
 *
 * @package Polylang
 *
 * @var PLL_Admin_Filters_Term $this      PLL_Admin_Filters_Term object.
 * @var PLL_Language           $lang      The post language. Default language if no language assigned yet.
 * @var string                 $taxonomy  Taxonomy name.
 * @var string                 $post_type Post type.
 */

defined( 'ABSPATH' ) || exit;

if ( isset( $term_id ) ) {
	// Edit term form ?>
	<th scope="row"><?php esc_html_e( 'Translations', 'polylang' ); ?></th>
	<td>
	<?php
}
else {
	// Add term form
	?>
	<p><?php esc_html_e( 'Translations', 'polylang' ); ?></p>
	<?php
}
?>
<table class="widefat term-translations"  id="<?php echo isset( $term_id ) ? 'edit' : 'add'; ?>-term-translations">
	<?php
	foreach ( $this->model->get_languages_list() as $language ) {
		if ( $language->term_id == $lang->term_id ) {
			continue;
		}

		// Look for any existing translation in this language
		// Take care not to propose a self link
		$translation = null;
		if ( isset( $term_id ) && ( $translation_id = $this->model->term->get_translation( $term_id, $language ) ) && $translation_id != $term_id ) {
			$translation = get_term( $translation_id, $taxonomy );
		}
		if ( ! empty( $from_term_id ) && ( $translation_id = $this->model->term->get( $from_term_id, $language ) ) && ! $this->model->term->get_translation( $translation_id, $lang ) ) {
			$translation = get_term( $translation_id, $taxonomy );
		}
		$translation_exists = $translation instanceof WP_Term;

		$add_link = isset( $term_id ) ? $this->links->new_term_translation_link( $term_id, $taxonomy, $post_type, $language ) : ''; // Do not display the add new link in add term form ($term_id not set).
		$link = $add_link;

		if ( $translation_exists ) {
			$link = $this->links->edit_term_translation_link( $translation->term_id, $taxonomy, $post_type );
		}
		?>
		<tr>
			<th class = "pll-language-column">
				<span class = "pll-translation-flag"><?php echo $language->flag ?: esc_html( $language->slug ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<?php
				printf(
					'<span class="pll-language-name%1$s">%2$s</span>',
					isset( $term_id ) ? '' : ' screen-reader-text',
					esc_html( $language->name )
				);
				?>
			</th>
			<?php
			if ( isset( $term_id ) ) {
				?>
				<td class = "hidden"><?php echo $add_link; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<td class = "pll-edit-column"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
				<?php
			}
			?>
			<td class = "pll-translation-column">
				<?php
				printf(
					'<label class="screen-reader-text" for="tr_lang_%1$s">%2$s</label>
					<input type="hidden" class="htr_lang" name="term_tr_lang[%1$s]" id="htr_lang_%1$s" value="%3$d" />
					<span lang="%6$s" dir="%7$s"><input type="text" class="tr_lang" id="tr_lang_%1$s" value="%4$s"%5$s /></span>',
					esc_attr( $language->slug ),
					/* translators: accessibility text */
					esc_html__( 'Translation', 'polylang' ),
					$translation_exists ? (int) $translation->term_id : 0,
					$translation_exists ? esc_attr( $translation->name ) : '',
					disabled( empty( $disabled ), false, false ),
					esc_attr( $language->get_locale( 'display' ) ),
					( $language->is_rtl ? 'rtl' : 'ltr' )
				);
				?>
			</td>
		</tr>
		<?php
	} // End foreach
	?>
</table>
<?php

if ( isset( $term_id ) ) {
	// Edit term form
	?>
	</td>
	<?php
}
