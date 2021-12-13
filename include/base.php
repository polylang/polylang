<?php
/**
 * @package Polylang
 */

/**
 * Base class for both admin and frontend
 *
 * @since 1.2
 */
abstract class PLL_Base {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Registers hooks on insert / update post related actions and filters.
	 *
	 * @var PLL_CRUD_Posts
	 */
	public $posts;

	/**
	 * Registers hooks on insert / update term related action and filters.
	 *
	 * @var PLL_CRUD_Terms
	 */
	public $terms;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Links_Model $links_model Links Model.
	 */
	public function __construct( &$links_model ) {
		$this->links_model = &$links_model;
		$this->model = &$links_model->model;
		$this->options = &$this->model->options;

		$GLOBALS['l10n_unloaded']['pll_string'] = true; // Short-circuit _load_textdomain_just_in_time() for 'pll_string' domain in WP 4.6+

		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		// User defined strings translations
		add_action( 'pll_language_defined', array( $this, 'load_strings_translations' ), 5 );
		add_action( 'change_locale', array( $this, 'load_strings_translations' ) ); // Since WP 4.7
		add_action( 'personal_options_update', array( $this, 'load_strings_translations' ), 1, 0 ); // Before WP, for confirmation request when changing the user email.

		// Switch_to_blog
		add_action( 'switch_blog', array( $this, 'switch_blog' ), 10, 2 );
	}

	/**
	 * Instantiates classes reacting to CRUD operations on posts and terms,
	 * only when at least one language is defined.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->model->get_languages_list() ) {
			$this->posts = new PLL_CRUD_Posts( $this );
			$this->terms = new PLL_CRUD_Terms( $this );

			// WordPress options.
			new PLL_Translate_Option( 'blogname', array(), array( 'context' => 'WordPress' ) );
			new PLL_Translate_Option( 'blogdescription', array(), array( 'context' => 'WordPress' ) );
			new PLL_Translate_Option( 'date_format', array(), array( 'context' => 'WordPress' ) );
			new PLL_Translate_Option( 'time_format', array(), array( 'context' => 'WordPress' ) );
		}
	}

	/**
	 * Registers our widgets
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function widgets_init() {
		register_widget( 'PLL_Widget_Languages' );

		// Overwrites the calendar widget to filter posts by language
		if ( ! defined( 'PLL_WIDGET_CALENDAR' ) || PLL_WIDGET_CALENDAR ) {
			unregister_widget( 'WP_Widget_Calendar' );
			register_widget( 'PLL_Widget_Calendar' );
		}
	}

	/**
	 * Loads user defined strings translations
	 *
	 * @since 1.2
	 * @since 2.1.3 $locale parameter added.
	 *
	 * @param string $locale Locale. Defaults to current locale.
	 * @return void
	 */
	public function load_strings_translations( $locale = '' ) {
		if ( empty( $locale ) ) {
			$locale = ( is_admin() && ! Polylang::is_ajax_on_front() ) ? get_user_locale() : get_locale();
		}

		$language = $this->model->get_language( $locale );

		if ( ! empty( $language ) ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			$GLOBALS['l10n']['pll_string'] = &$mo;
		} else {
			unset( $GLOBALS['l10n']['pll_string'] );
		}
	}

	/**
	 * Resets some variables when the blog is switched.
	 * Applied only if Polylang is active on the new blog.
	 *
	 * @since 1.5.1
	 *
	 * @param int $new_blog_id  New blog ID.
	 * @param int $prev_blog_id Previous blog ID.
	 * @return void
	 */
	public function switch_blog( $new_blog_id, $prev_blog_id ) {
		if ( $this->is_active_on_new_blog( $new_blog_id, $prev_blog_id ) ) {
			$this->options = get_option( 'polylang' ); // Needed for menus.
			remove_action( 'pre_option_rewrite_rules', array( $this->links_model, 'prepare_rewrite_rules' ) );
			$this->links_model = $this->model->get_links_model();
		}
	}

	/**
	 * Checks if Polylang is active on the new blog when the blog is switched.
	 *
	 * @since 3.0
	 *
	 * @param int $new_blog_id  New blog ID.
	 * @param int $prev_blog_id Previous blog ID.
	 * @return bool
	 */
	protected function is_active_on_new_blog( $new_blog_id, $prev_blog_id ) {
		$plugins = ( $sitewide_plugins = get_site_option( 'active_sitewide_plugins' ) ) && is_array( $sitewide_plugins ) ? array_keys( $sitewide_plugins ) : array();
		$plugins = array_merge( $plugins, get_option( 'active_plugins', array() ) );

		/*
		 * The 2nd test is needed when Polylang is not networked activated.
		 * The 3rd test is needed when Polylang is networked activated and a new site is created.
		 */
		return $new_blog_id !== $prev_blog_id && in_array( POLYLANG_BASENAME, $plugins ) && get_option( 'polylang' );
	}
}
