<?php

/**
 * displays the Languages admin panel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};
?>
<div class="wrap">
	<h1><?php _e( 'Languages', 'polylang' ); ?></h1>
	<h2 class="nav-tab-wrapper"><?php
	// display tabs
	foreach ( $tabs as $key => $name ) {
		printf(
			'<a href="options-general.php?page=mlang&amp;tab=%1$s" id="nav-tab-%1$s" class="nav-tab %2$s">%3$s</a>',
			esc_attr( $key ),
			$key == $this->active_tab ? 'nav-tab-active' : '',
			esc_html( $name )
		);
	}?>
	</h2><?php

	switch ( $this->active_tab ) {
		case 'lang':     // Languages tab
		case 'strings':  // string translations tab
		case 'settings': // settings tab
			include( PLL_SETTINGS_INC.'/view-tab-' . $this->active_tab . '.php' );
		break;

		default:
			/**
			 * Fires when loading the active Polylang settings tab
			 * Allows plugins to add their own tab
			 *
			 * @since 1.5.1
			 */
			do_action( 'pll_settings_active_tab_' . $this->active_tab );
		break;
	}?>

</div><!-- wrap -->
