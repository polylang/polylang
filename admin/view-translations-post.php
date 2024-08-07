<?php
/**
 * Displays the translations fields for posts
 *
 * @package Polylang
 *
 * @var PLL_Admin_Classic_Editor $this    PLL_Admin_Classic_Editor object.
 * @var PLL_Language             $lang    The post language. Default language if no language assigned yet.
 * @var int                      $post_ID The post id.
 */

defined( 'ABSPATH' ) || exit;
?>
<p><strong><?php esc_html_e( 'Translations', 'polylang' ); ?></strong></p>
<table>
	<?php
	foreach ( $this->model->get_languages_list() as $language ) {
		if ( $language->term_id === $lang->term_id ) {
			continue;
		}

		$translation_id = $this->model->post->get_translation( $post_ID, $language );
		if ( ! $translation_id || $translation_id === $post_ID ) { // $translation_id == $post_ID happens if the post has been (auto)saved before changing the language.
			$translation_id = 0;
		}

		if ( ! empty( $from_post_id ) ) {
			$translation_id = $this->model->post->get( $from_post_id, $language );
		}

		$add_link    = $this->links->new_post_translation_link( $post_ID, $language );
		$link        = $add_link;
		$translation = null;
		if ( $translation_id ) {
			$translation = get_post( $translation_id );
			$link = $this->links->edit_post_translation_link( $translation_id );
		}
		?>
		<tr>
			<th class = "pll-language-column"><?php echo $language->flag ?: esc_html( $language->slug ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
			<td class = "hidden"><?php echo $add_link; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
			<td class = "pll-edit-column pll-column-icon"><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
			<?php

			/**
			 * Fires before the translation column is outputted in the language metabox.
			 * The dynamic portion of the hook name, `$lang`, refers to the language code.
			 *
			 * @since 2.1
			 */
			do_action( 'pll_before_post_translation_' . $language->slug );
			?>
			<td class = "pll-translation-column">
				<?php
				printf(
					'<label class="screen-reader-text" for="tr_lang_%1$s">%2$s</label>
					<input type="hidden" name="post_tr_lang[%1$s]" id="htr_lang_%1$s" value="%3$s" />
					<span lang="%5$s" dir="%6$s"><input type="text" class="tr_lang" id="tr_lang_%1$s" value="%4$s" /></span>',
					esc_attr( $language->slug ),
					/* translators: accessibility text */
					esc_html__( 'Translation', 'polylang' ),
					( empty( $translation ) ? '0' : esc_attr( (string) $translation->ID ) ),
					( empty( $translation ) ? '' : esc_attr( $translation->post_title ) ),
					esc_attr( $language->get_locale( 'display' ) ),
					( $language->is_rtl ? 'rtl' : 'ltr' )
				);
				?>
			</td>
		</tr>
		<?php
	}
	?>
</table>
