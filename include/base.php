<?php
/**
 * @package Polylang
 */

/**
 * Base class for both admin and frontend
 *
 * @since 1.2
 */
#[AllowDynamicProperties]
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
	 * @var PLL_CRUD_Posts|null
	 */
	public $posts;

	/**
	 * Registers hooks on insert / update term related action and filters.
	 *
	 * @var PLL_CRUD_Terms|null
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

		PLL_Switch_Language::init( $this->model );

		$GLOBALS['l10n_unloaded']['pll_string'] = true; // Short-circuit _load_textdomain_just_in_time() for 'pll_string' domain in WP 4.6+

		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		// User defined strings translations
		add_action( 'pll_language_defined', array( PLL_Switch_Language::class, 'load_strings_translations' ), 5 );
		add_action( 'change_locale', array( PLL_Switch_Language::class, 'load_strings_translations' ) ); // Since WP 4.7
		add_action( 'personal_options_update', array( PLL_Switch_Language::class, 'load_strings_translations' ), 1, 0 ); // Before WP, for confirmation request when changing the user email.
		add_action( 'lostpassword_post', array( PLL_Switch_Language::class, 'load_strings_translations' ), 10, 0 ); // Password reset email.
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
		if ( $this->model->has_languages() ) {
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
		if ( (int) $new_blog_id === (int) $prev_blog_id ) {
			// Do nothing if same blog.
			return;
		}

		$this->links_model->remove_filters();

		if ( $this->is_active_on_current_site() ) {
			$this->links_model = $this->model->get_links_model();
		}
	}

	/**
	 * Checks if Polylang is active on the current blog (useful when the blog is switched).
	 *
	 * @since 3.5.2
	 *
	 * @return bool
	 */
	protected function is_active_on_current_site(): bool {
		return pll_is_plugin_active( POLYLANG_BASENAME ) && ! empty( $this->options['version'] );
	}

	/**
	 * Check if the customize menu should be removed or not.
	 *
	 * @since 3.2
	 *
	 * @return bool True if it should be removed, false otherwise.
	 */
	public function should_customize_menu_be_removed() {
		// Exit if a block theme isn't activated.
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return false;
		}

		return ! $this->is_customize_register_hooked();
	}

	/**
	 * Tells whether or not Polylang or third party callbacks are hooked to `customize_register`.
	 *
	 * @since 3.4.3
	 *
	 * @global $wp_filter
	 *
	 * @return bool True if Polylang's callbacks are hooked, false otherwise.
	 */
	protected function is_customize_register_hooked() {
		global $wp_filter;

		if ( empty( $wp_filter['customize_register'] ) || ! $wp_filter['customize_register'] instanceof WP_Hook ) {
			return false;
		}

		/*
		 * 'customize_register' is hooked by:
		 * @see PLL_Nav_Menu::create_nav_menu_locations()
		 * @see PLL_Frontend_Static_Pages::filter_customizer()
		 */
		$floor = 0;
		if ( ! empty( $this->nav_menu ) && (bool) $wp_filter['customize_register']->has_filter( 'customize_register', array( $this->nav_menu, 'create_nav_menu_locations' ) ) ) {
			++$floor;
		}

		if ( ! empty( $this->static_pages ) && (bool) $wp_filter['customize_register']->has_filter( 'customize_register', array( $this->static_pages, 'filter_customizer' ) ) ) {
			++$floor;
		}

		$count = array_sum( array_map( 'count', $wp_filter['customize_register']->callbacks ) );

		return $count > $floor;
	}

	/**
	 * Backward compatibility for `load_strings_translations()` that have been moved to `PLL_Switch_Language` class.
	 *
	 * @since 3.7
	 *
	 * @param string $name Name of the method being called.
	 * @param array  $args An array of arguments to pass to this method.
	 * @return mixed
	 */
	public function __call( string $name, array $args ) {
		if ( 'load_strings_translations' === $name ) {
			if ( WP_DEBUG ) {
				$debug = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
					sprintf(
						'%1$s() was called incorrectly in %2$s on line %3$s: the call to PLL()->%1$s() has been moved to `PLL_Switch_Language` in Polylang 3.7, use PLL_Switch_Language::%1$s() instead.' . "\nError handler",
						esc_html( $name ),
						esc_html( $debug[0]['file'] ?? '' ),
						absint( $debug[0]['line'] ?? 0 )
					)
				);
			}
			return call_user_func_array( array( PLL_Switch_Language::class, $name ), $args );
		}

		$debug = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			sprintf(
				'Call to undefined function PLL()->%1$s() in %2$s on line %3$s' . "\nError handler",
				esc_html( $name ),
				esc_html( $debug[0]['file'] ?? '' ),
				absint( $debug[0]['line'] ?? 0 )
			),
			E_USER_ERROR
		);
	}
}
