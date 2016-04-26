<?php

/**
 * displays the translations fields for posts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};
?>
<p><strong><?php _e( 'Translations', 'polylang' ); ?></strong></p>
<table><?php
	foreach ( $this->model->get_languages_list() as $language ) {
		if ( $language->term_id == $lang->term_id ) {
			continue;
		}

		$value = $this->model->post->get_translation( $post_ID, $language );
		if ( ! $value || $value == $post_ID ) { // $value == $post_ID happens if the post has been ( auto )saved before changing the language
			$value = '';
		}

		if ( isset( $_GET['from_post'] ) ) {
			$value = $this->model->post->get( (int) $_GET['from_post'], $language );
		}

		$link = $add_link = $this->links->new_post_translation_link( $post_ID, $language );

		if ( $value ) {
			$selected = get_post( $value );
			$link = $this->links->edit_post_translation_link( $value );
		} ?>

		<tr>
			<th class = "pll-language-column"><?php echo $language->flag ? $language->flag : esc_html( $language->slug ); ?></th>
			<td class = "hidden"><?php echo $add_link;?></td>
			<td class = "pll-edit-column"><?php echo $link;?></td>
			<td class = "pll-translation-column"><?php
				printf( '
					<label class="screen-reader-text" for="tr_lang_%1$s">%2$s</label>
					<input type="hidden" name="post_tr_lang[%1$s]" id="htr_lang_%1$s" value="%3$s" />
					<input type="text" class="tr_lang" id="tr_lang_%1$s" value="%4$s"%5$s />',
					esc_attr( $language->slug ),
					__( 'Translation', 'polylang' ),
					empty( $value ) ? 0 : esc_attr( $selected->ID ),
					empty( $value ) ? '' : esc_attr( $selected->post_title ),
					empty( $link ) ? ' disabled="disabled"' : ''
				); ?>
			</td>
		</tr><?php
	}?>
</table>
