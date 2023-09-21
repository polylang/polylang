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
	protected static $blog_without_pll_plain_links; // Default test website.

	/**
	 * Blog in pretty permalinks without Polylang.
	 *
	 * @var WP_Site
	 */
	protected static $blog_without_pll_pretty_links;

	/**
	 * Blog in plain permalinks with Polylang (i.e. default installation).
	 * Created languages are English and French.
	 *
	 * @var WP_Site
	 */
	protected static $blog_with_pll_default_links;

	/**
	 * Blog in pretty permalinks with Polylang and language as directory.
	 * Created languages are English and French.
	 *
	 * @var WP_Site
	 */
	protected static $blog_with_pll_directory;

	/**
	 * Blog in pretty permalinks with Polylang and language as domains.
	 * Created languages are English and German.
	 *
	 * @var WP_Site
	 */
	protected static $blog_with_pll_domains;

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
	 * Languages data for their creation keyed by language slug
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
	 * @param WP_UnitTest_Factory $factory WP_UnitTest_Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::$blog_without_pll_plain_links = get_blog_details();

		self::$blog_without_pll_pretty_links = $factory->blog->create_and_get(
			array(
				'domain' => 'wordpress.org',
			)
		);

		self::$blog_with_pll_directory = $factory->blog->create_and_get(
			array(
				'domain' => 'polylang-dir.org',
			)
		);

		self::$blog_with_pll_domains = $factory->blog->create_and_get(
			array(
				'domain' => 'polylang-domains.org',
			)
		);

		self::$blog_with_pll_default_links = $factory->blog->create_and_get(
			array(
				'domain' => 'polylang-plain.org',
			)
		);
	}

	public static function wpTearDownAfterClass() {
		wp_delete_site( self::$blog_without_pll_pretty_links->blog_id );
		wp_delete_site( self::$blog_with_pll_directory->blog_id );
		wp_delete_site( self::$blog_with_pll_domains->blog_id );
		wp_delete_site( self::$blog_with_pll_default_links->blog_id );

		wp_update_network_site_counts();
	}

	public function set_up() {
		parent::set_up();

		// Set up blog with Polylang not activated and plain permalinks.
		$this->set_up_blog_without_pll( self::$blog_without_pll_plain_links, $this->plain_structure );

		// Set up blog with Polylang not activated and pretty permalinks.
		$this->set_up_blog_without_pll( self::$blog_without_pll_pretty_links, $this->pretty_structure );

		// Set up blog with Polylang activated, permalinks as directory, English and French created.
		$this->set_up_blog_with_pll(
			self::$blog_with_pll_directory,
			array( $this->languages['en'], $this->languages['fr'] ),
			array( 'force_lang' => 1 ),
			$this->pretty_structure
		);

		// Set up blog with Polylang activated, permalinks with domains, English and German created.
		$this->set_up_blog_with_pll(
			self::$blog_with_pll_domains,
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

		// Set up blog with Polylang activated, plain (i.e. default) permalinks , English and French created.
		$this->set_up_blog_with_pll(
			self::$blog_with_pll_default_links,
			array( $this->languages['en'], $this->languages['fr'] ),
			array(),
			$this->plain_structure
		);

	}

	public function tear_down() {
		$options     = array_merge( PLL_Install::get_default_options() );
		$model       = new PLL_Admin_Model( $options );
		$links_model = $model->get_links_model();
		$pll_admin   = new PLL_Admin( $links_model );

		foreach ( $pll_admin->model->get_languages_list() as $lang ) {
			$pll_admin->model->delete_language( $lang->term_id );
		}

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
	protected function set_up_blog_with_pll( $blog, $languages, $options, $structure ) {
		global $wp_rewrite;

		switch_to_blog( $blog->blog_id );

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( $structure );

		$plugins = get_option( 'active_plugins', array() );
		update_option( 'active_plugins', array_merge( $plugins, $this->get_plugin_names() ) );

		$options = array_merge(
			PLL_Install::get_default_options(),
			$options
		);

		$pll_admin = $this->get_pll_env( $options );
		$pll_admin->init();

		foreach ( $languages as $language ) {
			$pll_admin->model->add_language( $language );
		}

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
	protected function set_up_blog_without_pll( $blog, $structure ) {
		global $wp_rewrite;

		switch_to_blog( $blog->blog_id );

		if ( empty( $structure ) ) {
			delete_option( 'permalink_structure' );
			$wp_rewrite->init();
		} else {
			$wp_rewrite->init();
			$wp_rewrite->set_permalink_structure( $structure );
		}

		$plugins = get_option( 'active_plugins', array() );
		update_option( 'active_plugins', array_diff( $plugins, $this->get_plugin_names() ) ); // Ensure Polylang plugins are deactivated.

		restore_current_blog();
	}

	/**
	 * Returns Polylang's plugin basenames.
	 *
	 * @return string[]
	 */
	abstract protected function get_plugin_names();

	/**
	 * Returns an instance of the main Polylang object along required instanciated classes for the tests.
	 *
	 * @param array $options Plugin options.
	 * @return PLL_Base Polylang main class instance.
	 */
	abstract protected function get_pll_env( $options );
}
