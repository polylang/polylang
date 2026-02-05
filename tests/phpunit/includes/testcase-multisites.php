<?php

use Brain\Monkey;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry as Options_Registry;

/**
 * Test case offering a standardized way to test blogs in multisite.
 */
abstract class PLL_Multisites_TestCase extends WP_UnitTestCase {
	/**
	 * Blog in plain permalinks without Polylang.
	 *
	 * @var WP_Site
	 */
	protected $blog_without_pll_plain_links;

	/**
	 * Blog in pretty permalinks without Polylang.
	 *
	 * @var WP_Site
	 */
	protected $blog_without_pll_pretty_links;

	/**
	 * Blog in plain permalinks with Polylang (i.e. default installation).
	 * Created languages are English and French.
	 *
	 * @var WP_Site
	 */
	protected $blog_with_pll_plain_links;

	/**
	 * Blog in pretty permalinks with Polylang and language as directory.
	 * Created languages are English and French.
	 *
	 * @var WP_Site
	 */
	protected $blog_with_pll_directory; // Main blog.

	/**
	 * Blog in pretty permalinks with Polylang and language as domains.
	 * Created languages are English and German.
	 *
	 * @var WP_Site
	 */
	protected $blog_with_pll_domains;

	/**
	 * Pretty permalinks structure.
	 *
	 * @var string
	 */
	protected $pretty_structure = '/%postname%/';

	/**
	 * Plain permalinks structure.
	 *
	 * @var string
	 */
	protected $plain_structure = '';

	/**
	 * Languages data for their creation keyed by language slug.
	 *
	 * @var array
	 */
	protected $languages = array(
		'en' => array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 0,
		),
		'fr' => array(
			'name'       => 'Français',
			'slug'       => 'fr',
			'locale'     => 'fr_FR',
			'rtl'        => 0,
			'flag'       => 'fr',
			'term_group' => 1,
		),
		'de' => array(
			'name'       => 'Deutsch',
			'slug'       => 'de',
			'locale'     => 'de_DE',
			'rtl'        => 0,
			'flag'       => 'de',
			'term_group' => 2,
		),
	);

	/**
	 * Used to mock `pll_is_plugin_active()`. Blog IDs as array keys.
	 *
	 * @var array
	 */
	protected $blogs_with_pll = array();

	/**
	 * Used to create only 1 instance of `Options` during setup.
	 *
	 * @var Options|array
	 */
	protected $blog_with_pll_options;

	public function set_up() {
		Monkey\setUp();
		Monkey\Functions\when( 'pll_is_plugin_active' )->alias(
			function ( $value ) {
				if ( POLYLANG_BASENAME !== $value ) {
					return false;
				}
				return isset( $this->blogs_with_pll[ get_current_blog_id() ] );
			}
		);

		parent::set_up();

		// Create all sites.
		$factory = $this->factory();

		$this->blog_with_pll_directory       = get_site( 1 );
		$this->blog_with_pll_domains         = $factory->blog->create_and_get();
		$this->blog_with_pll_plain_links     = $factory->blog->create_and_get();
		$this->blog_without_pll_plain_links  = $factory->blog->create_and_get();
		$this->blog_without_pll_pretty_links = $factory->blog->create_and_get();

		// Set up blog with Polylang activated, permalinks as directory, English and French created.
		$this->set_up_blog_with_pll(
			$this->blog_with_pll_directory,
			array( $this->languages['en'], $this->languages['fr'] ),
			array( 'force_lang' => 1 ),
			$this->pretty_structure
		);

		// Set up blog with Polylang activated, permalinks with domains, English and German created.
		$this->set_up_blog_with_pll(
			$this->blog_with_pll_domains,
			array( $this->languages['en'], $this->languages['de'] ),
			array(
				'force_lang' => 3,
				'domains' => array(
					'en' => 'polylang-domains.en',
					'de' => 'polylang-domains.de',
				),
			),
			$this->pretty_structure
		);

		// Set up blog with Polylang activated, plain (i.e. default) permalinks, English and French created.
		$this->set_up_blog_with_pll(
			$this->blog_with_pll_plain_links,
			array( $this->languages['en'], $this->languages['fr'] ),
			array(),
			$this->plain_structure
		);

		// Set up blog with Polylang not activated and plain permalinks.
		$this->set_up_blog_without_pll( $this->blog_without_pll_plain_links, $this->plain_structure );

		// Set up blog with Polylang not activated and pretty permalinks.
		$this->set_up_blog_without_pll( $this->blog_without_pll_pretty_links, $this->pretty_structure );

		$this->clean_up_filters();

		// Reset the `Option`'s filter (removed by `clean_up_filters()`).
		$this->blog_with_pll_options = null;

		// Backward compatibility with Polylang < 3.8 (/src/ directory restructuring). Required for PLLWC.
		$api_file = file_exists( POLYLANG_DIR . '/src/api.php' ) ? POLYLANG_DIR . '/src/api.php' : POLYLANG_DIR . '/include/api.php';
		require_once $api_file;
	}

	public function tear_down() {
		restore_current_blog();

		$this->blogs_with_pll = array();
		wp_delete_site( $this->blog_without_pll_pretty_links->blog_id );
		wp_delete_site( $this->blog_with_pll_directory->blog_id );
		wp_delete_site( $this->blog_with_pll_domains->blog_id );
		wp_delete_site( $this->blog_with_pll_plain_links->blog_id );

		wp_update_network_site_counts();

		Monkey\tearDown();
		parent::tear_down();
	}

	/**
	 * Sets a blog up with the correct structure and activates Polylang's plugins.
	 * Also creates languages.
	 *
	 * @global $wp_rewrite
	 *
	 * @param WP_Site $blog      Blog to set up.
	 * @param array   $languages Languages to create.
	 * @param array   $options   Polylang options to use.
	 * @param string  $structure Permalink structure to use.
	 * @return void
	 */
	protected function set_up_blog_with_pll( WP_Site $blog, array $languages, array $options, string $structure ) {
		global $wp_rewrite;

		$this->blogs_with_pll[ $blog->blog_id ] = 1;

		$options  = array_merge(
			array(
				'default_lang' => 'en', // First from `$this->languages`.
				'version'      => POLYLANG_VERSION, // Required to pass `PLL_Base::is_active_on_current_site()`.
			),
			$options
		);
		$callback = function () use ( $options ) {
			update_option( 'polylang', $options );
		};

		add_action( 'switch_blog', $callback, -2000 ); // Before `Options::init_options_for_blog()`.
		switch_to_blog( $blog->blog_id );
		remove_action( 'switch_blog', $callback, -2000 );

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array();
		$wp_rewrite->set_permalink_structure( $structure );

		$plugins = get_option( 'active_plugins', array() );
		update_option( 'active_plugins', array_merge( $plugins, $this->get_plugin_names() ) );

		$pll_admin = $this->get_pll_admin_env( null, false );

		foreach ( $languages as $language ) {
			$pll_admin->model->add_language( $language );
		}

		$pll_admin->model->get_links_model()->init();
		$pll_admin->init();
		$wp_rewrite->flush_rules();

		$this->create_fixtures_for_blog( $blog, $pll_admin, $languages );

		restore_current_blog();
	}

	/**
	 * Sets a blog up with the correct structure and deactivates Polylang's plugins.
	 *
	 * @global $wp_rewrite
	 *
	 * @param WP_Site $blog      Blog to set up.
	 * @param string  $structure Permalinks structure to use.
	 * @return void
	 */
	protected function set_up_blog_without_pll( WP_Site $blog, string $structure ) {
		global $wp_rewrite;

		switch_to_blog( $blog->blog_id );

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array();
		$wp_rewrite->set_permalink_structure( $structure );
		$wp_rewrite->flush_rules();

		$plugins = get_option( 'active_plugins', array() );
		update_option( 'active_plugins', array_diff( $plugins, $this->get_plugin_names() ) ); // Ensure Polylang plugins are deactivated.

		$this->create_fixtures_for_blog( $blog );

		restore_current_blog();
	}

	/**
	 * Allows child classes to create fixtures for a given blog.
	 *
	 * @param WP_Site        $blog      Current site object.
	 * @param PLL_Admin|null $pll_admin Polylang admin object, null if deactivated.
	 * @param array          $languages Array of blog languages data, empty if none.
	 * @return void
	 */
	protected function create_fixtures_for_blog( WP_Site $blog, $pll_admin = null, array $languages = array() ) {
		// Not current class job.
	}

	/**
	 * Removes *all* filters that could interfere with a test.
	 * Must be called *before* creating a new Polylang environment in a test.
	 */
	protected function clean_up_filters() {
		remove_all_filters( 'pll_init' );
		remove_all_actions( 'pll_prepare_rewrite_rules' );
		remove_all_actions( 'switch_blog' );

		remove_all_filters( 'rewrite_rules_array' );
		$types = array_merge( array( 'date', 'root', 'comments', 'search', 'author' ), get_post_types(), get_taxonomies() );

		foreach ( $types as $type ) {
			remove_all_filters( $type . '_rewrite_rules' );
		}
	}

	/**
	 * Returns Polylang's plugin basenames.
	 *
	 * @return string[]
	 */
	protected function get_plugin_names(): array {
		return array( POLYLANG_BASENAME );
	}

	/**
	 * Returns an instance of the main Polylang object along required instantiated classes for the tests.
	 *
	 * @param array|Options|null $options Optional. Plugin options.
	 *                                    Use `null` to not delete previous options (this is the default value).
	 *                                    Use an empty array to reset previous options.
	 *                                    Use a non-empty array to set new options.
	 *                                    Use a `Options` object to use it directly.
	 * @param bool               $init    Trigger `PLL_Links_Model`'s and `PLL_Admin`'s init or not. Default is `true`.
	 * @return PLL_Admin Polylang main class instance.
	 */
	protected function get_pll_admin_env( $options = null, bool $init = true ): PLL_Admin {
		$this->set_options( $options );
		$model       = new PLL_Admin_Model( $this->blog_with_pll_options );
		$links_model = $model->get_links_model();
		$pll_env     = new PLL_Admin( $links_model );

		if ( $init ) {
			$links_model->init();
			$pll_env->init();
		}

		return $pll_env;
	}

	/**
	 * Returns an instance of the main Polylang object along required instantiated classes for the tests.
	 *
	 * @param array|Options|null $options Optional. Plugin options.
	 *                                    Use `null` to not delete previous options (this is the default value).
	 *                                    Use an empty array to reset previous options.
	 *                                    Use a non-empty array to set new options.
	 *                                    Use a `Options` object to use it directly.
	 * @param bool               $init    Trigger `PLL_Links_Model`'s and `PLL_Frontend`'s init or not. Default is `true`.
	 * @return PLL_Frontend Polylang main class instance.
	 */
	protected function get_pll_frontend_env( $options = null, bool $init = true ): PLL_Frontend {
		$this->set_options( $options );
		$model       = new PLL_Model( $this->blog_with_pll_options );
		$links_model = $model->get_links_model();
		$pll_env     = new PLL_Frontend( $links_model );

		if ( $init ) {
			$links_model->init();
			$pll_env->init();
		}

		return $pll_env;
	}

	/**
	 * Sets or updates the `$blog_with_pll_options` class property.
	 *
	 * @param array|Options|null $options Plugin options.
	 * @return void
	 */
	protected function set_options( $options ) {
		if ( isset( $this->blog_with_pll_options ) ) {
			if ( is_array( $options ) ) {
				foreach ( $options as $name => $value ) {
					$this->blog_with_pll_options[ $name ] = $value;
				}
			}
		} elseif ( ! $options instanceof Options ) {
			$this->blog_with_pll_options = self::create_options( $options );
		} else {
			$this->blog_with_pll_options = $options;
		}
	}

	/**
	 * Creates a new instance of the options, resets the values, and returns the instance.
	 *
	 * @since 3.7
	 *
	 * @param array|null $options Optional. Initial options.
	 *                            Use `null` to not delete previous options.
	 *                            Use an empty array to reset previous options (this is the default value).
	 *                            Use a non-empty array to set new options.
	 * @return Options|array An instance of `Options` for PLL 3.7+, an array otherwise.
	 */
	protected static function create_options( $options = array() ) {
		if ( ! class_exists( Options::class ) ) {
			// Backward compatibility with Polylang < 3.7. Required for PLLWC.
			if ( is_null( $options ) ) {
				$options = get_option( 'polylang', array() );
				return is_array( $options ) ? $options : array();
			}
			return array_merge( PLL_Install::get_default_options(), $options );
		}

		if ( ! empty( $options ) ) {
			update_option( Options::OPTION_NAME, $options );
		} elseif ( is_array( $options ) ) {
			delete_option( Options::OPTION_NAME );
		}

		add_action( 'pll_init_options_for_blog', array( Options_Registry::class, 'register' ) );
		return new Options();
	}
}
