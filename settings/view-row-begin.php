<?php
/**
 * @package Polylang-Pro
 *
 * @param string $row_title Can contain a `<label>` tag.
 * @param string $row_slug
 */

defined( 'ABSPATH' ) || exit;

?>
<tr id="pll-<?php echo esc_attr( $atts['row_slug'] ); ?>-label">
	<td>
		<?php
		if ( ! empty( $atts['for'] ) ) {
			printf(
				'<label for="pll-%s">%s</label>',
				esc_attr( $atts['for'] ),
				esc_html( $atts['row_title'] )
			);
		} else {
			echo esc_html( $atts['row_title'] );
		}
		?>
	</td>
	<td>
