<?php

/**
 * Links model for use when the language code is added in url as a directory
 * for example mysite.com/en/something
 * implements the "links_model interface"
 *
 * @since 1.2
 */
class PLL_Links_Directory extends PLL_Links_Permalinks {

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $model PLL_Model instance
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		if ( did_action( 'pll_init' ) ) {
			$this->init();
		} else {
			add_action( 'pll_init', array( &$this, 'init' ) );
		}
	}

	/**
	 * Called only at first object creation to avoid duplicating filters when switching blog
	 *
	 * @since 1.6
	 */
	public function init() {
		if ( did_action( 'setup_theme' ) ) {
			$this->add_permastruct();
		} else {
			add_action( 'setup_theme', array( &$this, 'add_permastruct' ), 2 );
		}

		// Make sure to prepare rewrite rules when flushing
		add_action( 'pre_option_rewrite_rules', array( &$this, 'prepare_rewrite_rules' ) );
	}

	/**
	 * Adds the language code in url
	 * links_model interface
	 *
	 * @since 1.2
	 *
	 * @param string $url  url to modify
	 * @param object $lang language
	 * @return string modified url
	 */
	public function add_language_to_link( $url, $lang ) {
		if ( ! empty( $lang ) ) {
			$base = $this->options['rewrite'] ? '' : 'language/';
			$slug = $this->options['default_lang'] == $lang->slug && $this->options['hide_default'] ? '' : $base . $lang->slug . '/';
			return str_replace( $this->home . '/' . $this->root, $this->home . '/' . $this->root . $slug, $url );
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
	function remove_language_from_link( $url ) {
		foreach ( $this->model->get_languages_list() as $language ) {
			if ( ! $this->options['hide_default'] || $this->options['default_lang'] != $language->slug ) {
				$languages[] = $language->slug;
			}
		}

		if ( ! empty( $languages ) ) {
			$pattern = str_replace( '/', '\/', $this->home . '/' . $this->root );
			$pattern = '#' . $pattern . ( $this->options['rewrite'] ? '' : 'language\/' ) . '('.implode( '|', $languages ).')(\/|$)#';
			$url = preg_replace( $pattern,  $this->home . '/' . $this->root, $url );
		}
		return $url;
	}

	/**
	 * Returns the language based on language code in url
	 * links_model interface
	 *
	 * @since 1.2
	 *
	 * @return string language slug
	 */
	public function get_language_from_url() {
		$requested_url  = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$pattern = str_replace( '/', '\/', $this->home . '/' . $this->root . ( $this->options['rewrite'] ? '' : 'language/' ) );
		$pattern = '#' . $pattern . '('. implode( '|', $this->model->get_languages_list( array( 'fields' => 'slug' ) ) ) . ')(\/|$)#';
		return preg_match( $pattern, trailingslashit( $requested_url ), $matches ) ? $matches[1] : ''; // $matches[1] is the slug of the requested language
	}

	/**
	 * Returns the home url
	 * links_model interface
	 *
	 * @since 1.3.1
	 *
	 * @param object $lang PLL_Language object
	 * @return string
	 */
	public function home_url( $lang ) {
		$base = $this->options['rewrite'] ? '' : 'language/';
		$slug = $this->options['default_lang'] == $lang->slug && $this->options['hide_default'] ? '' : '/' . $this->root . $base . $lang->slug;
		return trailingslashit( $this->home . $slug );
	}

	/**
	 * Optionaly removes 'language' in permalinks so that we get http://www.myblog/en/ instead of http://www.myblog/language/en/
	 *
	 * @since 1.2
	 */
	function add_permastruct() {
		// Language information always in front of the uri ( 'with_front' => false )
		// The 3rd parameter structure has been modified in WP 3.4
		add_permastruct( 'language', $this->options['rewrite'] ? '%language%' : 'language/%language%', array( 'with_front' => false ) );
	}

	/**
	 * Prepares rewrite rules filters
	 *
	 * @since 0.8.1
	 *
	 * @param array $pre not used
	 * @return unmodified $pre
	 */
	public function prepare_rewrite_rules( $pre ) {
		// Don't modify the rules if there is no languages created yet
		if ( $this->model->get_languages_list() && ! has_filter( 'language_rewrite_rules', '__return_empty_array' ) ) {
			// Suppress the rules created by WordPress for our taxonomy
			add_filter( 'language_rewrite_rules', '__return_empty_array' );

			foreach ( $this->get_rewrite_rules_filters() as $type ) {
				add_filter( $type . '_rewrite_rules', array( &$this, 'rewrite_rules' ) );
			}

			add_filter( 'rewrite_rules_array', array( &$this, 'rewrite_rules' ) ); // needed for post type archives
		}
		return $pre;
	}

	/**
	 * The rewrite rules !
	 * always make sure the default language is at the end in case the language information is hidden for default language
	 * thanks to brbrbr http://wordpress.org/support/topic/plugin-polylang-rewrite-rules-not-correct
	 *
	 * @since 0.8.1
	 *
	 * @param array $rules rewrite rules
	 * @return array modified rewrite rules
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
				if ( ! apply_filters( 'pll_modify_rewrite_rule', true, array( $key => $rule ), $filter, false ) ) {
					continue;
				}

				if ( isset( $slug ) ) {
					$newrules[ $slug . str_replace( $wp_rewrite->root, '', $key ) ] = str_replace(
						array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
						array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
						$rule
					); // Should be enough!
				}

				if ( $this->options['hide_default'] ) {
					$newrules[ $key ] = $rules[ $key ];
					// Unset only if we hide the code for the default language as check_language_code_in_url will do its job in other cases
					unset( $rules[ $key ] );
				}
			}

			// Rewrite rules filtered by language
			elseif ( in_array( $filter, $this->always_rewrite ) || in_array( $filter, $this->model->get_filtered_taxonomies() ) || ( $cpts && preg_match( $cpts, $rule, $matches ) && ! strpos( $rule, 'name=' ) ) || ( 'rewrite_rules_array' != $filter && $this->options['force_lang'] ) ) {

				/** This filter is documented in include/links-directory.php */
				if ( ! apply_filters( 'pll_modify_rewrite_rule', true, array( $key => $rule ), $filter, empty( $matches[1] ) ? false : $matches[1] ) ) {
					continue;
				}

				if ( isset( $slug ) ) {
					$newrules[ $slug . str_replace( $wp_rewrite->root, '', $key ) ] = str_replace(
						array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
						array( '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '?lang=$matches[1]&' ),
						$rule
					); // Should be enough!
				}

				if ( $this->options['hide_default'] ) {
					$newrules[ $key ] = str_replace( '?', '?lang=' . $this->options['default_lang'] . '&', $rule );
				}

				unset( $rules[ $key ] ); // Now useless
			}
		}

		// The home rewrite rule
		if ( 'root' == $filter && isset( $slug ) ) {
			$newrules[ $slug . '?$' ] = $wp_rewrite->index.'?lang=$matches[1]';
		}

		return $newrules + $rules;
	}
}
