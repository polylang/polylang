<?php

/**
 * Admin side controller
 * accessible in $polylang global object
 *
 * Properties:
 * options         => inherited, reference to Polylang options array
 * model           => inherited, reference to PLL_Model object
 * links_model     => inherited, reference to PLL_Links_Model object
 * links           => inherited, reference to PLL_Admin_Links object
 * static_pages    => inherited, reference to PLL_Admin_Static_Pages object
 * filters_links   => inherited, reference to PLL_Filters_Links object
 * curlang         => inherited, optional, current language used to filter the content (language of the post or term being edited, equal to filter_lang otherwise)
 * filter_lang     => inherited, optional, current status of the admin languages filter (in the admin bar)
 * pref_lang       => inherited, preferred language used as default when saving posts or terms
 * posts           => reference to PLL_CRUD_Posts object
 * terms           => reference to PLL_CRUD_Terms object
 * filters         => reference to PLL_Admin_Filters object
 * filters_columns => reference to PLL_Admin_Filters_Columns object
 * filters_post    => reference to PLL_Admin_Filters_Post object
 * filters_term    => reference to PLL_Admin_filters_Term object
 * nav_menu        => reference to PLL_Admin_Nav_Menu object
 * sync            => reference to PLL_Admin_Sync object
 * block_editor    => reference to PLL_Admin_Block_Editor object
 * classic_editor  => reference to PLL_Admin_Classic_Editor object
 * filters_media   => optional, reference to PLL_Admin_Filters_Media object
 * bulk_translate  => reference, a PLL_Bulk_Translate subclass instance
 * wizard          => reference, a PLL_Wizard object
 *
 * @since 1.2
 * @since 2.7 Added a reference to a PLL_Bulk_Translate instance.
 * @since 2.7 Added a reference to a PLL_Wizard object.
 */
class PLL_Admin extends PLL_Admin_Base {
	public $filters, $filters_columns, $filters_post, $filters_term, $nav_menu, $sync, $filters_media;

	/**
	 * Loads the polylang text domain
	 * Setups filters and action needed on all admin pages and on plugins page
	 *
	 * @since 1.2
	 *
	 * @param object $links_model
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );

		// Adds a 'settings' link in the plugins table
		add_filter( 'plugin_action_links_' . POLYLANG_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'in_plugin_update_message-' . POLYLANG_BASENAME, array( $this, 'plugin_update_message' ), 10, 2 );
	}

	/**
	 * Setups filters and action needed on all admin pages and on plugins page
	 * Loads the settings pages or the filters base on the request
	 *
	 * @since 1.2
	 */
	public function init() {
		parent::init();

		// Setup filters for admin pages
		// Priority 5 to make sure filters are there before customize_register is fired
		if ( $this->model->get_languages_list() ) {
			add_action( 'wp_loaded', array( $this, 'add_filters' ), 5 );
			add_action( 'admin_init', array( $this, 'maybe_load_sync_post' ), 20 ); // After fusion Builder.
		}
	}

	/**
	 * Adds a 'settings' link in the plugins table
	 *
	 * @since 0.1
	 *
	 * @param array $links list of links associated to the plugin
	 * @return array modified list of links
	 */
	public function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="admin.php?page=mlang">' . __( 'Settings', 'polylang' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds the upgrade notice in plugins table
	 *
	 * @since 1.1.6
	 *
	 * @param array  $plugin_data Not used
	 * @param object $r           Plugin update data
	 */
	public function plugin_update_message( $plugin_data, $r ) {
		if ( isset( $r->upgrade_notice ) ) {
			printf( '<p style="margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">%s</p>', esc_html( $r->upgrade_notice ) );
		}
	}

	/**
	 * Setup filters for admin pages
	 *
	 * @since 1.2
	 * @since 2.7 instantiate a PLL_Bulk_Translate instance.
	 */
	public function add_filters() {
		// All these are separated just for convenience and maintainability
		$classes = array( 'Filters', 'Filters_Columns', 'Filters_Post', 'Filters_Term', 'Nav_Menu', 'Sync', 'Classic_Editor', 'Block_Editor' );

		// Don't load media filters if option is disabled or if user has no right
		if ( $this->options['media_support'] && ( $obj = get_post_type_object( 'attachment' ) ) && ( current_user_can( $obj->cap->edit_posts ) || current_user_can( $obj->cap->create_posts ) ) ) {
			$classes[] = 'Filters_Media';
		}

		foreach ( $classes as $class ) {
			$obj = strtolower( $class );

			/**
			 * Filter the class to instantiate when loading admin filters
			 *
			 * @since 1.5
			 *
			 * @param string $class class name
			 */
			$class = apply_filters( 'pll_' . $obj, 'PLL_Admin_' . $class );
			$this->$obj = new $class( $this );
		}

		$this->posts = new PLL_CRUD_Posts( $this );
		$this->terms = new PLL_CRUD_Terms( $this );

		// Bulk Translate
		// Needs to be loaded before other modules.
		if ( class_exists( 'PLL_Bulk_Translate' ) ) {
			$this->bulk_translate = new PLL_Bulk_Translate( $this->model );
			add_action( 'current_screen', array( $this->bulk_translate, 'init' ) );
		}

		// Advanced media
		if ( $this->options['media_support'] && class_exists( 'PLL_Admin_Advanced_Media' ) ) {
			$this->advanced_media = new PLL_Admin_Advanced_Media( $this );
		}

		// Share term slugs
		if ( get_option( 'permalink_structure' ) && $this->options['force_lang'] && class_exists( 'PLL_Admin_Share_Term_Slug' ) ) {
			$this->share_term_slug = new PLL_Admin_Share_Term_Slug( $this );
		}

		// Duplicate content
		if ( class_exists( 'PLL_Duplicate' ) ) {
			$this->duplicate = new PLL_Duplicate( $this );
		}

		if ( class_exists( 'PLL_Duplicate_REST' ) ) {
			$this->duplicate_rest = new PLL_Duplicate_REST();
		}

		if ( class_exists( 'PLL_Sync_Post_Model' ) ) {
			$this->sync_post_model = new PLL_Sync_Post_Model( $this );
		}

		// Block editor metabox
		if ( pll_use_block_editor_plugin() ) {
			$this->block_editor_plugin = new PLL_Block_Editor_Plugin( $this );
		}

		// FIXME: Specific for WP CRON and WP CLI as the action admin_init is not fired.
		// Waiting for a better way to handle the cases without loading the complete admin.
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			$this->maybe_load_sync_post();
		}

	}

	/**
	 * Load the post synchronization object, depending on the editor in use.
	 *
	 * @since 2.6
	 */
	public function maybe_load_sync_post() {
		// Post synchronization
		if ( 'post-new.php' === $GLOBALS['pagenow'] && function_exists( 'use_block_editor_for_post' ) ) {
			// We need to wait until we know which editor is in use
			add_filter( 'use_block_editor_for_post', array( $this, '_maybe_load_sync_post' ), 999 ); // After the plugin Classic Editor
		} elseif ( 'post.php' === $GLOBALS['pagenow'] && function_exists( 'use_block_editor_for_post' ) && isset( $_GET['post'] ) && empty( $_GET['meta-box-loader'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->_maybe_load_sync_post( use_block_editor_for_post( (int) $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		} else {
			$this->_maybe_load_sync_post( false );
		}
	}

	/**
	 * Load the post synchronization object, depending on the editor in use.
	 *
	 * We must make sure to instantiate the class only once, as the function may be called from a filter,
	 * and that the synchronization model has been instantiated (due to InfiniteWP messing the actions wp_loaded and admin_init).
	 *
	 * @since 2.6
	 *
	 * @param bool $is_block_editor Whether to use the block editor or not.
	 * @return bool
	 */
	public function _maybe_load_sync_post( $is_block_editor ) {
		if ( ! isset( $this->sync_post ) && isset( $this->sync_post_model ) ) {
			if ( class_exists( 'PLL_Sync_Post_REST' ) && pll_use_block_editor_plugin() && $is_block_editor ) {
				$this->sync_post = new PLL_Sync_Post_REST( $this );
			} elseif ( class_exists( 'PLL_Sync_Post' ) ) {
				$this->sync_post = new PLL_Sync_Post( $this );
			}
		}

		return $is_block_editor;
	}
}
