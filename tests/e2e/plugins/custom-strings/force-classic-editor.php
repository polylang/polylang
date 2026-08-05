<?php
/**
 * Plugin Name: Force Classic Editor for E2E
 * Description: Forces Polylang to use classic editor for specific E2E tests.
 */
add_filter( 'pll_use_block_editor_plugin', '__return_false' );
