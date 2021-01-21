<?php
/**
 * @package Polylang
 */

/**
 * Main Polylang class when on frontend, accessible from @see PLL().
 *
 * @since 1.2
 */
class PLL_Frontend extends PLL_Base {
	/**
	 * Current language.
	 *
	 * @var PLL_Language
	 */
	public $curlang;

	/**
	 * @var PLL_Frontend_Auto_Translate
	 */
	public $auto_translate;

	/**
	 * The class selecting the current language.
	 *
	 * @var PLL_Choose_Lang
	 */
	public $choose_lang;

	/**
	 * @var PLL_Frontend_Filters
	 */
	public $filters;

	/**
	 * @var PLL_Frontend_Filters_Links
	 */
	public $filters_links;

	/**
	 * @var PLL_Frontend_Filters_Search
	 */
	public $filters_search;

	/**
	 * @var PLL_Frontend_Links
	 */
	public $links;

	/**
	 * @var PLL_Frontend_Nav_Menu
	 */
	public $nav_menu;

	/**
	 * @var PLL_Frontend_Static_Pages
	 */
	public $static_pages;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Links_Model $links_model Reference to the links model.
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );

		add_action( 'pll_language_defined', array( $this, 'pll_language_defined' ), 1 );

		// Avoids the language being the queried object when querying multiple taxonomies
		add_action( 'parse_tax_query', array( $this, 'parse_tax_query' ), 1 );

		// Filters posts by language
		add_action( 'parse_query', array( $this, 'parse_query' ), 6 );

		// Not before 'check_canonical_url'
		if ( ! defined( 'PLL_AUTO_TRANSLATE' ) || PLL_AUTO_TRANSLATE ) {
			add_action( 'template_redirect', array( $this, 'auto_translate' ), 7 );
		}
	}

	/**
	 * Setups the language chooser based on options
	 *
	 * @since 1.2
	 */
	public function init() {
		parent::init();

		$this->links = new PLL_Frontend_Links( $this );

		// Static front page and page for posts
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$this->static_pages = new PLL_Frontend_Static_Pages( $this );
		}

		// Setup the language chooser
		$c = array( 'Content', 'Url', 'Url', 'Domain' );
		$class = 'PLL_Choose_Lang_' . $c[ $this->options['force_lang'] ];
		$this->choose_lang = new $class( $this );
		$this->choose_lang->init();

		// Need to load nav menu class early to correctly define the locations in the customizer when the language is set from the content
		$this->nav_menu = new PLL_Frontend_Nav_Menu( $this );
	}

	/**
	 * Setups filters and nav menus once the language has been defined
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function pll_language_defined() {
		// Filters
		$this->filters_links = new PLL_Frontend_Filters_Links( $this );
		$this->filters = new PLL_Frontend_Filters( $this );
		$this->filters_search = new PLL_Frontend_Filters_Search( $this );

		// Auto translate for Ajax
		if ( ( ! defined( 'PLL_AUTO_TRANSLATE' ) || PLL_AUTO_TRANSLATE ) && wp_doing_ajax() ) {
			$this->auto_translate();
		}
	}

	/**
	 * When querying multiple taxonomies, makes sure that the language is not the queried object.
	 *
	 * @since 1.8
	 *
	 * @param WP_Query $query WP_Query object.
	 * @return void
	 */
	public function parse_tax_query( $query ) {
		$pll_query = new PLL_Query( $query, $this->model );
		$queried_taxonomies = $pll_query->get_queried_taxonomies();

		if ( ! empty( $queried_taxonomies ) && 'language' == reset( $queried_taxonomies ) ) {
			$query->tax_query->queried_terms['language'] = array_shift( $query->tax_query->queried_terms );
		}
	}

	/**
	 * Modifies some query vars to "hide" that the language is a taxonomy and avoid conflicts.
	 *
	 * @since 1.2
	 *
	 * @param WP_Query $query WP_Query object.
	 * @return void
	 */
	public function parse_query( $query ) {
		$qv = $query->query_vars;
		$pll_query = new PLL_Query( $query, $this->model );
		$taxonomies = $pll_query->get_queried_taxonomies();

		// Allow filtering recent posts and secondary queries by the current language
		if ( ! empty( $this->curlang ) ) {
			$pll_query->filter_query( $this->curlang );
		}

		// Modifies query vars when the language is queried
		if ( ! empty( $qv['lang'] ) || ( ! empty( $taxonomies ) && array( 'language' ) == array_values( $taxonomies ) ) ) {
			// Do we query a custom taxonomy?
			$taxonomies = array_diff( $taxonomies, array( 'language', 'category', 'post_tag' ) );

			// Remove pages query when the language is set unless we do a search
			// Take care not to break the single page, attachment and taxonomies queries!
			if ( empty( $qv['post_type'] ) && ! $query->is_search && ! $query->is_singular && empty( $taxonomies ) && ! $query->is_category && ! $query->is_tag ) {
				$query->set( 'post_type', 'post' );
			}

			// Unset the is_archive flag for language pages to prevent loading the archive template
			// Keep archive flag for comment feed otherwise the language filter does not work
			if ( empty( $taxonomies ) && ! $query->is_comment_feed && ! $query->is_post_type_archive && ! $query->is_date && ! $query->is_author && ! $query->is_category && ! $query->is_tag ) {
				$query->is_archive = false;
			}

			// Unset the is_tax flag except if another custom tax is queried
			if ( empty( $taxonomies ) && ( $query->is_category || $query->is_tag || $query->is_author || $query->is_post_type_archive || $query->is_date || $query->is_search || $query->is_feed ) ) {
				$query->is_tax = false;
				unset( $query->queried_object ); // FIXME useless?
			}
		}
	}

	/**
	 * Auto translate posts and terms ids
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function auto_translate() {
		$this->auto_translate = new PLL_Frontend_Auto_Translate( $this );
	}

	/**
	 * Resets some variables when the blog is switched.
	 * Overrides the parent method.
	 *
	 * @since 1.5.1
	 *
	 * @param int $new_blog_id  New blog ID.
	 * @param int $prev_blog_id Previous blog ID.
	 * @return void
	 */
	public function switch_blog( $new_blog_id, $prev_blog_id ) {
		parent::switch_blog( $new_blog_id, $prev_blog_id );

		// Need to check that some languages are defined when user is logged in, has several blogs, some without any languages.
		if ( $this->is_active_on_new_blog( $new_blog_id, $prev_blog_id ) && did_action( 'pll_language_defined' ) && $this->model->get_languages_list() ) {
			static $restore_curlang;
			if ( empty( $restore_curlang ) ) {
				$restore_curlang = $this->curlang->slug; // To always remember the current language through blogs.
			}

			$lang = $this->model->get_language( $restore_curlang );
			$this->curlang = $lang ? $lang : $this->model->get_language( $this->options['default_lang'] );

			if ( isset( $this->static_pages ) ) {
				$this->static_pages->init();
			}

			$this->load_strings_translations();
		}
	}
}
