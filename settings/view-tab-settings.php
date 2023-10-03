<?php
/**
 * Displays the settings tab in Polylang settings
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}
?>
<div class="form-wrap">
	<?php
	wp_nonce_field( 'pll_options', '_pll_nonce' );
	$list_table = new PLL_Table_Settings();
	$list_table->prepare_items( $this->modules );
	$list_table->display();
	?>
</div>
