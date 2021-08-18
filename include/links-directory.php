<?php
/**
 * @package Polylang
 */

/**
 * Links model for use when the language code is added in url as a directory
 * for example mysite.com/en/something
 * implements the "links_model interface"
 *
 * @since 1.2
 */
class PLL_Links_Directory extends PLL_Links_Permalinks {
	/**
	 * Relative path to the home url.
	 *
	 * @var string
	 */
	protected $home_relative;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Model $model PLL_Model instance.
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		$this->home_relative = home_url( '/', 'relative' );

		if ( did_action( 'pll_init' ) ) {
			$this->init();
		} else {
			add_action( 'pll_init', array( $this, 'init' ) );
		}
	}

	/**
	 * Called only at first object creation to avoid duplicating filters when switching blog
	 *
	 * @since 1.6
	 *
	 * @return void
	 */
	public function init() {
		if ( did_action( 'setup_theme' ) ) {
			$this->add_permastruct();
		} else {
			add_action( 'setup_theme', array( $this, 'add_permastruct' ), 2 );
		}

		// Make sure to prepare rewrite rules when flushing
		add_action( 'pre_option_rewrite_rules', array( $this, 'prepare_rewrite_rules' ) );
	}

	/**
	 * Adds the language code in a url.
	 * links_model interface.
	 *
	 * @since 1.2
	 *
	 * @param string       $url  The url to modify.
	 * @param PLL_Language $lang The language object.
	 * @return string Modified url.
	 */
	public function add_language_to_link( $url, $lang ) {
		if ( ! empty( $lang ) ) {
			$base = $this->options['rewrite'] ? '' : 'language/';
			$slug = $this->options['default_lang'] == $lang->slug && $this->options['hide_default'] ? '' : $base . $lang->slug . '/';
			$root = ( false === strpos( $url, '://' ) ) ? $this->home_relative . $this->root : preg_replace( '#^https?://#', '://', $this->home . '/' . $this->root );

			if ( false === strpos( $url, $new = $root . $slug ) ) {
				$pattern = preg_quote( $root, '#' );
				$pattern = '#' . $pattern . '#';
				return preg_replace( $pattern, $new, $url, 1 ); // Only once
			}
		}
		return $url;
	}

	/**
	 * Returns the url without language code
	 * links_model interface
	 *
	 * @since 1.2
	 *
	 * @param string $url url to modify
	 * @return string modified url
	 */
	public function remove_language_from_link( $url ) {
		$languages = array();

		foreach ( $this->model->get_languages_list() as $language ) {
			if ( ! $this->options['hide_default'] || $this->options['default_lang'] != $language->slug ) {
				$languages[] = $language->slug;
			}
		}

		if ( ! empty( $languages ) ) {
			$root = ( false === strpos( $url, '://' ) ) ? $this->home_relative . $this->root : preg_replace( '#^https?://#', '://', $this->home . '/' . $this->root );

			$pattern = preg_quote( $root, '#' );
			$pattern = '#' . $pattern . ( $this->options['rewrite'] ? '' : 'language/' ) . '(' . implode( '|', $languages ) . ')(/|$)#';
			$url = preg_replace( $pattern, $root, $url );
		}
		return $url;
	}

	/**
	 * Returns the language based on language code in url
	 * links_model interface
	 *
	 * @since 1.2
	 * @since 2.0 add $url argument
	 *
	 * @param string $url optional, defaults to current url
	 * @return string language slug
	 */
	public function get_language_from_url( $url = '' ) {
		if ( empty( $url ) ) {
			$url = pll_get_requested_url();
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		$root = ( false === strpos( $url, '://' ) ) ? $this->home_relative . $this->root : $this->home . '/' . $this->root;

		$pattern = wp_parse_url( $root . ( $this->options['rewrite'] ? '' : 'language/' ), PHP_URL_PATH );
		$pattern = preg_quote( $pattern, '#' );
		$pattern = '#^' . $pattern . '(' . implode( '|', $this->model->get_languages_list( array( 'fields' => 'slug' ) ) ) . ')(/|$)#';
		return preg_match( $pattern, trailingslashit( $path ), $matches ) ? $matches[1] : ''; // $matches[1] is the slug of the requested language
	}

	/**
	 * Returns the home url in a given language.
	 * links_model interface.
	 *
	 * @since 1.3.1
	 *
	 * @param PLL_Language $lang PLL_Language object.
	 * @return string
	 */
	public function home_url( $lang ) {
		$base = $this->options['rewrite'] ? '' : 'language/';
		$slug = $this->options['default_lang'] == $lang->slug && $this->options['hide_default'] ? '' : '/' . $this->root . $base . $lang->slug;
		return trailingslashit( $this->home . $slug );
	}

	/**
	 * Optionally removes 'language' in permalinks so that we get http://www.myblog/en/ instead of http://www.myblog/language/en/
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function add_permastruct() {
		// Language information always in front of the uri ( 'with_front' => false )
		// The 3rd parameter structure has been modified in WP 3.4
		// Leads to error 404 for pages when there is no language created yet
		if ( $this->model->get_languages_list() ) {
			add_permastruct( 'language', $this->options['rewrite'] ? '%language%' : 'language/%language%', array( 'with_front' => false ) );
		}
	}

	/**
	 * Prepares the rewrite rules filters.
	 *
	 * @since 0.8.1
	 *
	 * @param mixed $pre Not used as the filter is used as an action.
	 * @return mixed
	 */
	public function prepare_rewrite_rules( $pre ) {
		// Don't modify the rules if there is no languages created yet
		// Make sure to add filter only once and if all custom post types and taxonomies have been registered
		if ( $this->model->get_languages_list() && did_action( 'wp_loaded' ) && ! has_filter( 'language_rewrite_rules', '__return_empty_array' ) ) {
			// Suppress the rules created by WordPress for our taxonomy
			add_filter( 'language_rewrite_rules', '__return_empty_array' );

			foreach ( $this->get_rewrite_rules_filters() as $type ) {
				add_filter( $type . '_rewrite_rules', array( $this, 'rewrite_rules' ) );
			}

			add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules' ) ); // needed for post type archives
		}
		return $pre;
	}

	/**
	 * The rewrite rules !
	 * Always make sure that the default language is at the end in case the language information is hidden for default language.
	 * Thanks to brbrbr http://wordpress.org/support/topic/plugin-polylang-rewrite-rules-not-correct.
	 *
	 * @since 0.8.1
	 *
	 * @param string[] $rules Rewrite rules.
	 * @return string[] Modified rewrite rules.
	 */
	public function rewrite_rules( $rules ) {
		$filter = str_replace( '_rewrite_rules', '', current_filter() );

		global $wp_rewrite;
		$newrules = array();

		$languages = $this->model->get_languages_list( array( 'fields' => 'slug' ) );
		if ( $this->options['hide_default'] ) {
			$languages = array_diff( $languages, array( $this->options['default_lang'] ) );
		}

		if ( ! empty( $languages ) ) {
			$slug = $wp_rewrite->root . ( $this->options['rewrite'] ? '' : 'language/' ) . '(' . implode( '|', $languages ) . ')/';
		}

		// For custom post type archives
		$cpts = array_intersect( $this->model->get_translated_post_types(), get_post_types( array( '_builtin' => false ) ) );
		$cpts = $cpts ? '#post_type=(' . implode( '|', $cpts ) . ')#' : '';

		foreach ( $rules as $key => $rule ) {
			// Special case for translated post types and taxonomies to allow canonical redirection
			if ( $this->options['force_lang'] && in_array( $filter, array_merge( $this->model->get_translated_post_types(), $this->model->get_translated_taxonomies() ) ) ) {

				/**
				 * Filters the rewrite rules to modify
				 *
				 * @since 1.9.1
				 *
				 * @param bool        $modify  whether to modify or not the rule, defaults to true
				 * @param array       $rule    original rewrite rule
				 * @param string      $filter  current set of rules being modified
				 * @param string|bool $archive custom post post type archive name or false if it is not a cpt archive
				 */
				if ( isset( $slug ) && apply_filters( 'pll_modify_rewrite_rule', true, array( $key => $rule ), $filter, false ) ) {
					$newrules[ $slug . str_replace( $wp_rewrite->root, '', ltrim( $key, '^' ) ) ] = str_replace(
						array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
						array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
						$rule
					); // Should be enough!
				}

				$newrules[ $key ] = $rule;
			}

			// Rewrite rules filtered by language
			elseif ( in_array( $filter, $this->always_rewrite ) || in_array( $filter, $this->model->get_filtered_taxonomies() ) || ( $cpts && preg_match( $cpts, $rule, $matches ) && ! strpos( $rule, 'name=' ) ) || ( 'rewrite_rules_array' != $filter && $this->options['force_lang'] ) ) {

				/** This filter is documented in include/links-directory.php */
				if ( apply_filters( 'pll_modify_rewrite_rule', true, array( $key => $rule ), $filter, empty( $matches[1] ) ? false : $matches[1] ) ) {
					if ( isset( $slug ) ) {
						$newrules[ $slug . str_replace( $wp_rewrite->root, '', ltrim( $key, '^' ) ) ] = str_replace(
							array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
							array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
							$rule
						); // Should be enough!
					}

					if ( $this->options['hide_default'] ) {
						$newrules[ $key ] = str_replace( '?', '?lang=' . $this->options['default_lang'] . '&', $rule );
					}
				} else {
					$newrules[ $key ] = $rule;
				}
			}

			// Unmodified rules
			else {
				$newrules[ $key ] = $rule;
			}
		}

		// The home rewrite rule
		if ( 'root' == $filter && isset( $slug ) ) {
			$newrules[ $slug . '?$' ] = $wp_rewrite->index . '?lang=$matches[1]';
		}

		return $newrules;
	}
}
