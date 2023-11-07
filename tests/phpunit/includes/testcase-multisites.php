<?php

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

	public function set_up() {
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
	}

	public function tear_down() {
		restore_current_blog();

		wp_delete_site( $this->blog_without_pll_pretty_links->blog_id );
		wp_delete_site( $this->blog_with_pll_directory->blog_id );
		wp_delete_site( $this->blog_with_pll_domains->blog_id );
		wp_delete_site( $this->blog_with_pll_plain_links->blog_id );

		wp_update_network_site_counts();

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

		switch_to_blog( $blog->blog_id );

		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = array();
		$wp_rewrite->set_permalink_structure( $structure );

		$plugins = get_option( 'active_plugins', array() );
		update_option( 'active_plugins', array_merge( $plugins, $this->get_plugin_names() ) );

		$pll_admin = $this->get_pll_admin_env( $options, false );

		foreach ( $languages as $language ) {
			$pll_admin->model->add_language( $language );
		}

		$pll_admin->model->get_links_model()->init();
		$pll_admin->init();
		$wp_rewrite->flush_rules();

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

		restore_current_blog();
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
	 * @param array $options Plugin options.
	 * @param bool  $init    Trigger `PLL_Links_Model`'s and `PLL_Admin`'s init or not. Default is `true`.
	 * @return PLL_Admin Polylang main class instance.
	 */
	protected function get_pll_admin_env( array $options = array(), bool $init = true ): PLL_Admin {
		if ( empty( $options ) ) {
			$options = (array) get_option( 'polylang', array() );
		} else {
			$options = array_merge( PLL_Install::get_default_options(), $options );
		}

		$model       = new PLL_Admin_Model( $options );
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
	 * @param array $options Plugin options.
	 * @param bool  $init    Trigger `PLL_Links_Model`'s and `PLL_Frontend`'s init or not. Default is `true`.
	 * @return PLL_Frontend Polylang main class instance.
	 */
	protected function get_pll_frontend_env( array $options = array(), bool $init = true ): PLL_Frontend {
		if ( empty( $options ) ) {
			$options = (array) get_option( 'polylang', array() );
		} else {
			$options = array_merge( PLL_Install::get_default_options(), $options );
		}

		$model       = new PLL_Model( $options );
		$links_model = $model->get_links_model();
		$pll_env     = new PLL_Frontend( $links_model );

		if ( $init ) {
			$links_model->init();
			$pll_env->init();
		}

		return $pll_env;
	}
}
