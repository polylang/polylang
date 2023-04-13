<?php
/**
 * Displays the Languages admin panel
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

require ABSPATH . 'wp-admin/options-head.php'; // Displays the errors messages as when we were a child of options-general.php
?>
<div class="wrap">
	<h1><?php echo esc_html( $GLOBALS['title'] ); ?></h1>
	<?php
	/**
	 * Fires when loading the active Polylang settings tab
	 * Allows plugins to add their own tab
	 *
	 * @since 1.5.1
	 */
	do_action( 'pll_settings_active_tab_' . $this->active_tab );
	?>
</div><!-- wrap -->
