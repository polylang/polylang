<?php
/**
 * Displays the content of the About metabox
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};
?>
<p>
	<?php
	printf(
		/* translators: %1$s is link start tag, %2$s is link end tag. */
		esc_html__( 'Polylang is provided with an extensive %1$sdocumentation%2$s (in English). It includes information on how to set up your multilingual site and use it on a daily basis; FAQs, and documentation for developers to adapt their plugins and themes.', 'polylang' ),
		'<a href="https://polylang.pro/doc/">',
		'</a>'
	);
	if ( ! defined( 'POLYLANG_PRO' ) ) {
		echo ' ';
		printf(
			/* translators: %1$s is link start tag, %2$s is link end tag. */
			esc_html__( 'Support and extra features are available to %1$sPolylang Pro%2$s users.', 'polylang' ),
			'<a href="https://polylang.pro">',
			'</a>'
		);
	}
	?>
</p>
