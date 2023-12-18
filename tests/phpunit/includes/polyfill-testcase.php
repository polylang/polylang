<?php

/**
 * Polyfill class to replace `WP_UnitTest_Factory` with `PLL_UnitTest_Factory`.
 * Backward compatibility for WordPress < 6.5-alpha.
 * Back then, `WP_UnitTestCase::factory()` was called with `self` instead of `static` keyword,
 * preventing us to override it.
 *
 * @see https://github.com/WordPress/wordpress-develop/pull/5723.
 */
abstract class WP_UnitTestCase_Polyfill extends WP_UnitTestCase {
	/**
	 * Rewrites `WP_UnitTestCase::set_up_before_class()` using `static` keyword.
	 * If no polfill required, call `WP_UnitTestCase::set_up_before_class()` as usual.
	 */
	public static function set_up_before_class() {
		global $wpdb, $wp_version;

		if ( version_compare( $wp_version, '6.5-alpha', '>=' ) ) {
			return parent::set_up_before_class();
		}

		// Backward compatibility with WP < 6.5.
		PHPUnit_Adapter_TestCase::set_up_before_class(); // Call grandpa!

		$wpdb->suppress_errors = false;
		$wpdb->show_errors     = true;
		$wpdb->db_connect();
		ini_set( 'display_errors', 1 ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed

		$class = get_called_class();

		if ( method_exists( $class, 'wpSetUpBeforeClass' ) ) {
			call_user_func( array( $class, 'wpSetUpBeforeClass' ), static::factory() );
		}

		self::commit_transaction();
	}

	/**
	 * Rewrites `WP_UnitTestCase::set_up()` using `static` keyword.
	 * If no polfill required, call `WP_UnitTestCase::set_up()` as usual.
	 */
	public function set_up() {
		global $wp_version;

		if ( version_compare( $wp_version, '6.5-alpha', '>=' ) ) {
			return parent::set_up();
		}

		// Backward compatibility with WP < 6.5.
		set_time_limit( 0 );

		$this->factory = static::factory();

		if ( ! self::$ignore_files ) {
			self::$ignore_files = $this->scan_user_uploads();
		}

		if ( ! self::$hooks_saved ) {
			$this->_backup_hooks();
		}

		global $wp_rewrite;

		$this->clean_up_global_scope();

		/*
		 * When running core tests, ensure that post types and taxonomies
		 * are reset for each test. We skip this step for non-core tests,
		 * given the large number of plugins that register post types and
		 * taxonomies at 'init'.
		 */
		if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) {
			$this->reset_post_types();
			$this->reset_taxonomies();
			$this->reset_post_statuses();
			$this->reset__SERVER();

			if ( $wp_rewrite->permalink_structure ) {
				$this->set_permalink_structure( '' );
			}
		}

		$this->start_transaction();
		$this->expectDeprecated();
		add_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ) );
	}
}
