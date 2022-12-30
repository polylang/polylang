<?php
/**
 * @package Polylang
 */

/**
 * Setup features available on all admin pages.
 *
 * @since 1.8
 */
abstract class PLL_Admin_Base extends PLL_Base {
	/**
	 * Current language (used to filter the content).
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Language selected in the admin language filter.
	 *
	 * @var PLL_Language|null
	 */
	public $filter_lang;

	/**
	 * Preferred language to assign to new contents.
	 *
	 * @var PLL_Language|null
	 */
	public $pref_lang;

	/**
	 * @var PLL_Filters_Links|null
	 */
	public $filters_links;

	/**
	 * @var PLL_Admin_Links|null
	 */
	public $links;

	/**
	 * @var PLL_Admin_Notices|null
	 */
	public $notices;

	/**
	 * @var PLL_Admin_Static_Pages|null
	 */
	public $static_pages;

	/**
	 * @var PLL_Admin_Default_Term|null
	 */
	public $default_term;

	/**
	 * Setups actions needed on all admin pages.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Links_Model $links_model Reference to the links model.
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );

		// Adds the link to the languages panel in the WordPress admin menu
		add_action( 'admin_menu', array( $this, 'add_menus' ) );

		add_action( 'admin_menu', array( $this, 'remove_customize_submenu' ) );

		// Setup js scripts and css styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ), 0 ); // High priority in case an ajax request is sent by an immediately invoked function

		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls_enqueue_scripts' ) );
	}

	/**
	 * Setups filters and action needed on all admin pages and on plugins page
	 * Loads the settings pages or the filters base on the request
	 *
	 * @since 1.2
	 */
	public function init() {
		parent::init();

		$this->notices = new PLL_Admin_Notices( $this );

		$this->default_term = new PLL_Admin_Default_Term( $this );
		$this->default_term->add_hooks();

		if ( ! $this->model->get_languages_list() ) {
			return;
		}

		$this->links = new PLL_Admin_Links( $this ); // FIXME needed here ?
		$this->static_pages = new PLL_Admin_Static_Pages( $this ); // FIXME needed here ?
		$this->filters_links = new PLL_Filters_Links( $this ); // FIXME needed here ?

		// Filter admin language for users
		// We must not call user info before WordPress defines user roles in wp-settings.php
		add_action( 'setup_theme', array( $this, 'init_user' ) );
		add_filter( 'request', array( $this, 'request' ) );

		// Adds the languages in admin bar
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 100 ); // 100 determines the position
	}

	/**
	 * Adds the link to the languages panel in the WordPress admin menu
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function add_menus() {
		global $admin_page_hooks;

		// Prepare the list of tabs
		$tabs = array( 'lang' => __( 'Languages', 'polylang' ) );

		// Only if at least one language has been created
		if ( $this->model->get_languages_list() ) {
			$tabs['strings'] = __( 'Translations', 'polylang' );
		}

		$tabs['settings'] = __( 'Settings', 'polylang' );

		/**
		 * Filter the list of tabs in Polylang settings
		 *
		 * @since 1.5.1
		 *
		 * @param array $tabs list of tab names
		 */
		$tabs = apply_filters( 'pll_settings_tabs', $tabs );

		$parent = '';

		foreach ( $tabs as $tab => $title ) {
			$page = 'lang' === $tab ? 'mlang' : "mlang_$tab";
			if ( empty( $parent ) ) {
				$parent = $page;
				add_menu_page( $title, __( 'Languages', 'polylang' ), 'manage_options', $page, '__return_null', 'dashicons-translation' );
				$admin_page_hooks[ $page ] = 'languages'; // Hack to avoid the localization of the hook name. See: https://core.trac.wordpress.org/ticket/18857
			}

			add_submenu_page( $parent, $title, $title, 'manage_options', $page, array( $this, 'languages_page' ) );
		}
	}

	/**
	 * Setup js scripts & css styles ( only on the relevant pages )
	 *
	 * @since 0.6
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		/*
		 * For each script:
		 * 0 => the pages on which to load the script
		 * 1 => the scripts it needs to work
		 * 2 => 1 if loaded even if languages have not been defined yet, 0 otherwise
		 * 3 => 1 if loaded in footer
		 */
		$scripts = array(
			'user'    => array( array( 'profile', 'user-edit' ), array( 'jquery' ), 0, 0 ),
			'widgets' => array( array( 'widgets' ), array( 'jquery' ), 0, 0 ),
		);

		$block_screens = array( 'widgets', 'site-editor' );

		if ( ! empty( $screen->post_type ) && $this->model->is_translated_post_type( $screen->post_type ) ) {
			$scripts['post'] = array( array( 'edit', 'upload' ), array( 'jquery', 'wp-ajax-response' ), 0, 1 );

			// Classic editor.
			if ( ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) {
				$scripts['classic-editor'] = array( array( 'post', 'media', 'async-upload' ), array( 'jquery', 'wp-ajax-response', 'post', 'jquery-ui-dialog', 'wp-i18n' ), 0, 1 );
			}

			// Block editor with legacy metabox in WP 5.0+.
			$block_screens[] = 'post';
		}

		if ( $this->is_block_editor( $screen ) ) {
			$scripts['block-editor'] = array( $block_screens, array( 'jquery', 'wp-ajax-response', 'wp-api-fetch', 'jquery-ui-dialog', 'wp-i18n' ), 0, 1 );
		}

		if ( ! empty( $screen->taxonomy ) && $this->model->is_translated_taxonomy( $screen->taxonomy ) ) {
			$scripts['term'] = array( array( 'edit-tags', 'term' ), array( 'jquery', 'wp-ajax-response', 'jquery-ui-autocomplete' ), 0, 1 );
		}

		foreach ( $scripts as $script => $v ) {
			if ( in_array( $screen->base, $v[0] ) && ( $v[2] || $this->model->get_languages_list() ) ) {
				wp_enqueue_script( 'pll_' . $script, plugins_url( '/js/build/' . $script . $suffix . '.js', POLYLANG_ROOT_FILE ), $v[1], POLYLANG_VERSION, $v[3] );
				if ( 'classic-editor' === $script || 'block-editor' === $script ) {
					wp_set_script_translations( 'pll_' . $script, 'polylang' );
				}
			}
		}

		wp_register_style( 'polylang_admin', plugins_url( '/css/build/admin' . $suffix . '.css', POLYLANG_ROOT_FILE ), array( 'wp-jquery-ui-dialog' ), POLYLANG_VERSION );
		wp_enqueue_style( 'polylang_dialog', plugins_url( '/css/build/dialog' . $suffix . '.css', POLYLANG_ROOT_FILE ), array( 'polylang_admin' ), POLYLANG_VERSION );

		$this->add_inline_scripts();
	}

	/**
	 * Tells whether or not the given screen is block editor kind.
	 * e.g. widget, site or post editor.
	 *
	 * @since 3.3
	 *
	 * @param WP_Screen $screen Screen object.
	 * @return bool True if the screen is a block editor, false otherwise.
	 */
	protected function is_block_editor( $screen ) {
		return method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() && ! pll_use_block_editor_plugin();
	}

	/**
	 * Enqueue scripts to the WP Customizer.
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	public function customize_controls_enqueue_scripts() {
		if ( $this->model->get_languages_list() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'pll_widgets', plugins_url( '/js/build/widgets' . $suffix . '.js', POLYLANG_ROOT_FILE ), array( 'jquery' ), POLYLANG_VERSION, true );
			$this->add_inline_scripts();
		}
	}

	/**
	 * Adds inline scripts to set the default language in JS
	 * and localizes scripts.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	private function add_inline_scripts() {
		if ( wp_script_is( 'pll_block-editor', 'enqueued' ) ) {
			$default_lang_script = 'const pllDefaultLanguage = "' . $this->options['default_lang'] . '";';
			wp_add_inline_script(
				'pll_block-editor',
				$default_lang_script,
				'before'
			);
		}
		if ( wp_script_is( 'pll_widgets', 'enqueued' ) ) {
			wp_localize_script(
				'pll_widgets',
				'pll_widgets',
				array(
					'flags' => wp_list_pluck( $this->model->get_languages_list(), 'flag', 'slug' ),
				)
			);
		}
	}

	/**
	 * Sets pll_ajax_backend on all backend ajax request
	 * The final goal is to detect if an ajax request is made on admin or frontend
	 *
	 * Takes care to various situations:
	 * when the ajax request has no options.data thanks to ScreenfeedFr
	 * see: https://wordpress.org/support/topic/ajaxprefilter-may-not-work-as-expected
	 * when options.data is a json string
	 * see: https://wordpress.org/support/topic/polylang-breaking-third-party-ajax-requests-on-admin-panels
	 * when options.data is an empty string (GET request with the method 'load')
	 * see: https://wordpress.org/support/topic/invalid-url-during-wordpress-new-dashboard-widget-operation
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
	public function admin_print_footer_scripts() {
		global $post_ID, $tag_ID;

		$params = array( 'pll_ajax_backend' => 1 );
		if ( ! empty( $post_ID ) ) {
			$params = array_merge( $params, array( 'pll_post_id' => (int) $post_ID ) );
		}

		if ( ! empty( $tag_ID ) ) {
			$params = array_merge( $params, array( 'pll_term_id' => (int) $tag_ID ) );
		}

		$str = http_build_query( $params );
		$arr = wp_json_encode( $params );
		?>
		<script type="text/javascript">
			if (typeof jQuery != 'undefined') {
				jQuery(
					function( $ ){
						$.ajaxPrefilter( function ( options, originalOptions, jqXHR ) {
							if ( -1 != options.url.indexOf( ajaxurl ) || -1 != ajaxurl.indexOf( options.url ) ) {

								function addPolylangParametersAsString() {
									if ( 'undefined' === typeof options.data || '' === options.data.trim() ) {
										// Only Polylang data need to be send. So it could be as a simple query string.
										options.data = '<?php echo $str; // phpcs:ignore WordPress.Security.EscapeOutput ?>';
									} else {
										/*
										 * In some cases data could be a JSON string like in third party plugins.
										 * So we need not to break their process by adding polylang parameters as valid JSON datas.
										 */
										try {
											options.data = JSON.stringify( Object.assign( JSON.parse( options.data ), <?php echo $arr; // phpcs:ignore WordPress.Security.EscapeOutput ?> ) );
										} catch( exception ) {
											// Add Polylang data to the existing query string.
											options.data = options.data + '&<?php echo $str; // phpcs:ignore WordPress.Security.EscapeOutput ?>';
										}
									}
								}

								/*
								 * options.processData set to true is the default jQuery process where the data is converted in a query string by using jQuery.param().
								 * This step is done before applying filters. Thus here the options.data is already a string in this case.
								 * @See https://github.com/jquery/jquery/blob/3.5.1/src/ajax.js#L563-L569 jQuery ajax function.
								 * It is the most case WordPress send ajax request this way however third party plugins or themes could be send JSON string.
								 * Use JSON format is recommended in jQuery.param() documentation to be able to send complex data structures.
								 * @See https://api.jquery.com/jquery.param/ jQuery param function.
								 */
								if ( options.processData ) {
									addPolylangParametersAsString();
								} else {
									/*
									 * If options.processData is set to false data could be undefined or pass as a string.
									 * So data as to be processed as if options.processData is set to true.
									 */
									if ( 'undefined' === typeof options.data || 'string' === typeof options.data ) {
										addPolylangParametersAsString();
									} else {
										// Otherwise options.data is probably an object.
										options.data = Object.assign( options.data || {} , <?php echo $arr; // phpcs:ignore WordPress.Security.EscapeOutput ?> );
									}
								}
							}
						});
					}
				);
			}
		</script>
		<?php
	}

	/**
	 * Sets the admin current language, used to filter the content
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function set_current_language() {
		$this->curlang = $this->filter_lang;

		// Edit Post
		if ( isset( $_REQUEST['pll_post_id'] ) && $lang = $this->model->post->get_language( (int) $_REQUEST['pll_post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->curlang = $lang;
		} elseif ( 'post.php' === $GLOBALS['pagenow'] && isset( $_GET['post'] ) && $this->model->is_translated_post_type( get_post_type( (int) $_GET['post'] ) ) && $lang = $this->model->post->get_language( (int) $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->curlang = $lang;
		} elseif ( 'post-new.php' === $GLOBALS['pagenow'] && ( empty( $_GET['post_type'] ) || $this->model->is_translated_post_type( sanitize_key( $_GET['post_type'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->curlang = empty( $_GET['new_lang'] ) ? $this->pref_lang : $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// Edit Term
		elseif ( isset( $_REQUEST['pll_term_id'] ) && $lang = $this->model->term->get_language( (int) $_REQUEST['pll_term_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->curlang = $lang;
		} elseif ( in_array( $GLOBALS['pagenow'], array( 'edit-tags.php', 'term.php' ) ) && isset( $_GET['taxonomy'] ) && $this->model->is_translated_taxonomy( sanitize_key( $_GET['taxonomy'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['tag_ID'] ) && $lang = $this->model->term->get_language( (int) $_GET['tag_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->curlang = $lang;
			} elseif ( ! empty( $_GET['new_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->curlang = $this->model->get_language( sanitize_key( $_GET['new_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			} elseif ( empty( $this->curlang ) ) {
				$this->curlang = $this->pref_lang;
			}
		}

		// Ajax
		if ( wp_doing_ajax() && ! empty( $_REQUEST['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->curlang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		/**
		 * Filters the current language used by Polylang in the admin context.
		 *
		 * @since 3.2
		 *
		 * @param PLL_Language|false|null $curlang  Instance of the current language.
		 * @param PLL_Admin_Base          $polylang Instance of the main Polylang's object.
		 */
		$this->curlang = apply_filters( 'pll_admin_current_language', $this->curlang, $this );

		// Inform that the admin language has been set.
		if ( $this->curlang instanceof PLL_Language ) {
			/** This action is documented in frontend/choose-lang.php */
			do_action( 'pll_language_defined', $this->curlang->slug, $this->curlang );
		} else {
			/** This action is documented in include/class-polylang.php */
			do_action( 'pll_no_language_defined' ); // To load overridden textdomains.
		}
	}

	/**
	 * Defines the backend language and the admin language filter based on user preferences
	 *
	 * @since 1.2.3
	 *
	 * @return void
	 */
	public function init_user() {
		// Language for admin language filter: may be empty
		// $_GET['lang'] is numeric when editing a language, not when selecting a new language in the filter
		// We intentionally don't use a nonce to update the language filter
		if ( ! wp_doing_ajax() && ! empty( $_GET['lang'] ) && ! is_numeric( sanitize_key( $_GET['lang'] ) ) && current_user_can( 'edit_user', $user_id = get_current_user_id() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_user_meta( $user_id, 'pll_filter_content', ( $lang = $this->model->get_language( sanitize_key( $_GET['lang'] ) ) ) ? $lang->slug : '' ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$this->filter_lang = $this->model->get_language( get_user_meta( get_current_user_id(), 'pll_filter_content', true ) );

		// Set preferred language for use when saving posts and terms: must not be empty
		$this->pref_lang = empty( $this->filter_lang ) ? $this->model->get_language( $this->options['default_lang'] ) : $this->filter_lang;

		/**
		 * Filters the preferred language on admin side.
		 * The preferred language is used for example to determine the language of a new post.
		 *
		 * @since 1.2.3
		 *
		 * @param PLL_Language $pref_lang Preferred language.
		 */
		$this->pref_lang = apply_filters( 'pll_admin_preferred_language', $this->pref_lang );

		$this->set_current_language();

		// Plugin i18n, only needed for backend.
		load_plugin_textdomain( 'polylang' );
	}

	/**
	 * Avoids parsing a tax query when all languages are requested
	 * Fixes https://wordpress.org/support/topic/notice-undefined-offset-0-in-wp-includesqueryphp-on-line-3877 introduced in WP 4.1
	 *
	 * @see https://core.trac.wordpress.org/ticket/31246 the suggestion of @boonebgorges.
	 *
	 * @since 1.6.5
	 *
	 * @param array $qvars
	 * @return array
	 */
	public function request( $qvars ) {
		if ( isset( $qvars['lang'] ) && 'all' === $qvars['lang'] ) {
			unset( $qvars['lang'] );
		}

		return $qvars;
	}

	/**
	 * Adds the languages list in admin bar for the admin languages filter.
	 *
	 * @since 0.9
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar global object.
	 * @return void
	 */
	public function admin_bar_menu( $wp_admin_bar ) {
		$all_item = (object) array(
			'slug' => 'all',
			'name' => __( 'Show all languages', 'polylang' ),
			'flag' => '<span class="ab-icon"></span>',
		);

		$selected = empty( $this->filter_lang ) ? $all_item : $this->filter_lang;

		$title = sprintf(
			'<span class="ab-label"%1$s><span class="screen-reader-text">%2$s</span>%3$s</span>',
			'all' === $selected->slug ? '' : sprintf( ' lang="%s"', esc_attr( $selected->get_locale( 'display' ) ) ),
			__( 'Filters content by language', 'polylang' ),
			esc_html( $selected->name )
		);

		/**
		 * Filters the admin languages filter submenu items
		 *
		 * @since 2.6
		 *
		 * @param array $items The admin languages filter submenu items.
		 */
		$items = apply_filters( 'pll_admin_languages_filter', array_merge( array( $all_item ), $this->model->get_languages_list() ) );

		$menu = array(
			'id'    => 'languages',
			'title' => $selected->flag . $title,
			'href'  => esc_url( add_query_arg( 'lang', $selected->slug, remove_query_arg( 'paged' ) ) ),
			'meta'  => array(
				'title' => __( 'Filters content by language', 'polylang' ),
			),
		);

		if ( 'all' !== $selected->slug ) {
			$menu['meta']['class'] = 'pll-filtered-languages';
		}

		if ( ! empty( $items ) ) {
			$wp_admin_bar->add_menu( $menu );
		}

		foreach ( $items as $lang ) {
			if ( $selected->slug === $lang->slug ) {
				continue;
			}

			$wp_admin_bar->add_menu(
				array(
					'parent' => 'languages',
					'id'     => $lang->slug,
					'title'  => $lang->flag . esc_html( $lang->name ),
					'href'   => esc_url( add_query_arg( 'lang', $lang->slug, remove_query_arg( 'paged' ) ) ),
					'meta'   => 'all' === $lang->slug ? array() : array( 'lang' => esc_attr( $lang->get_locale( 'display' ) ) ),
				)
			);
		}
	}

	/**
	 * Remove the customize submenu when using a block theme.
	 *
	 * WordPress removes the Customizer menu if a block theme is activated and no other plugins interact with it.
	 * As Polylang interacts with the Customizer, we have to delete this menu ourselves in the case of a block theme,
	 * unless another plugin than Polylang interacts with the Customizer.
	 *
	 * @since 3.2
	 *
	 * @return void
	 */
	public function remove_customize_submenu() {
		if ( ! $this->should_customize_menu_be_removed() ) {
			return;
		}

		global $submenu;

		if ( ! empty( $submenu['themes.php'] ) ) {
			foreach ( $submenu['themes.php'] as $submenu_item ) {
				if ( 'customize' === $submenu_item[1] ) {
					remove_submenu_page( 'themes.php', $submenu_item[2] );
				}
			}
		}
	}
}
