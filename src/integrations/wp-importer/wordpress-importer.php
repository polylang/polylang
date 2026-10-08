<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with WordPress Importer.
 *
 * @since 2.8
 */
class PLL_WordPress_Importer {

	/**
	 * Setups filters.
	 *
	 * @since 2.8
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_wordpress_importer' ) );
	}

	/**
	 * If WordPress Importer is active, replace the wordpress_importer_init function.
	 *
	 * @since 1.2
	 */
	public function maybe_wordpress_importer() {
		if ( defined( 'WP_LOAD_IMPORTERS' ) && class_exists( 'WP_Import' ) ) {
			remove_action( 'admin_init', 'wordpress_importer_init' );
			add_action( 'admin_init', array( $this, 'wordpress_importer_init' ) );
		}
	}

	/**
	 * Loads our child class PLL_WP_Import instead of WP_Import.
	 *
	 * @since 1.2
	 */
	public function wordpress_importer_init() {
		$class = new ReflectionClass( 'WP_Import' );
		load_plugin_textdomain( 'wordpress-importer', false, basename( dirname( $class->getFileName() ) ) . '/languages' );

		$GLOBALS['wp_import'] = new PLL_WP_Import();
		register_importer( 'wordpress', 'WordPress', __( 'Import <strong>posts, pages, comments, custom fields, categories, and tags</strong> from a WordPress export file.', 'polylang' ), array( $GLOBALS['wp_import'], 'dispatch' ) ); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText
	}
}
