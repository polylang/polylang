<?php
/**
 * Displays the settings tab in Polylang settings
 *
 * @package Polylang
 *
 * @var PLL_Settings_Module[] $modules List of Polylang modules.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="form-wrap">
	<?php
	wp_nonce_field( 'pll_options', '_pll_nonce' );
	$list_table = new PLL_Table_Settings();
	$list_table->prepare_items( $modules );
	$list_table->display();
	?>
</div>
