<?php
/**
 * Displays 3.7 upgrade notice HTML content.
 *
 * @package Polylang
 *
 * @since 3.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

?>
<p>
	<strong>
	<?php esc_html_e( 'Untranslated strings are now emptied from the database.', 'polylang' ); ?>
	</strong>
</p>
<p class="buttons">
	<a
		class="button button-primary skip"
		href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'pll-hide-notice', 'empty-strings-translations' ), 'empty-strings-translations', '_pll_notice_nonce' ) ); ?>"
	>
		<?php esc_html_e( 'Dismiss', 'polylang' ); ?>
	</a>
</p>
