<?php
/**
 * Plugin Name: Force Classic Editor
 * Description: Forces Polylang to use classic editor for specific E2E tests.
 */
add_filter('use_block_editor_for_post_type', '__return_false', 100);
