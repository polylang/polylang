<?php
/**
 * Displays the Languages admin panel
 *
 * @package Polylang
 *
 * @var string $active_tab Active Polylang settings page.
 */

defined( 'ABSPATH' ) || exit;

require ABSPATH . 'wp-admin/options-head.php'; // Displays the errors messages as when we were a child of options-general.php
?>
<div class="wrap">
	<h1><?php echo esc_html( $GLOBALS['title'] ); ?></h1>
	<?php
	switch ( $active_tab ) {
		case 'lang':     // Languages tab
		case 'strings':  // String translations tab
		case 'settings': // Settings tab
			include __DIR__ . '/view-tab-' . $active_tab . '.php';
			// Intentional fall-through to upgrade to fire the action below.

		default:
			/**
			 * Fires when loading the active Polylang settings tab
			 * Allows plugins to add their own tab
			 *
			 * @since 1.5.1
			 */
			do_action( 'pll_settings_active_tab_' . $active_tab );
			break;
	}
	?>
</div><!-- wrap -->
