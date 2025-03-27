<?php
/**
 * @package Polylang
 */

/**
 * Links model for use when the language code is added in the url as a directory
 * for example mysite.com/en/something.
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
	}

	/**
	 * Adds hooks for rewrite rules.
	 *
	 * @since 1.6
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'pll_prepare_rewrite_rules', array( $this, 'prepare_rewrite_rules' ) ); // Ensure it's hooked before `self::do_prepare_rewrite_rules()` is called.

		parent::init();
	}

	/**
	 * Adds the language code in a url.
	 *
	 * @since 1.2
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param string                    $url      The url to modify.
	 * @param PLL_Language|string|false $language Language object or slug.
	 * @return string The modified url.
	 */
	public function add_language_to_link( $url, $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		if ( ! empty( $language ) ) {
			$base = $this->options['rewrite'] ? '' : 'language/';
			$slug = $this->options['default_lang'] === $language && $this->options['hide_default'] ? '' : $base . $language . '/';
			$root = ( false === strpos( $url, '://' ) ) ? $this->home_relative . $this->root : preg_replace( '#^https?://#', '://', $this->home . '/' . $this->root );

			if ( false === strpos( $url, $new = $root . $slug ) ) {
				$pattern = preg_quote( $root, '#' );
				$pattern = '#' . $pattern . '#';
				return preg_replace( $pattern, $new, $url, 1 ); // Only once.
			}
		}
		return $url;
	}

	/**
	 * Returns the url without the language code.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_language_from_link( $url ) {
		$languages = $this->model->get_languages_list(
			array(
				'hide_default' => $this->options['hide_default'],
				'fields'       => 'slug',
			)
		);

		if ( ! empty( $languages ) ) {
			$root = ( false === strpos( $url, '://' ) ) ? $this->home_relative . $this->root : preg_replace( '#^https?://#', '://', $this->home . '/' . $this->root );

			$pattern = preg_quote( $root, '@' );
			$pattern = '@' . $pattern . ( $this->options['rewrite'] ? '' : 'language/' ) . '(' . implode( '|', $languages ) . ')(([?#])|(/|$))@';
			$url = preg_replace( $pattern, $root . '$3', $url );
		}
		return $url;
	}

	/**
	 * Returns the language based on the language code in the url.
	 *
	 * @since 1.2
	 * @since 2.0 Add the $url argument.
	 *
	 * @param string $url Optional, defaults to the current url.
	 * @return string The language slug.
	 */
	public function get_language_from_url( $url = '' ) {
		if ( empty( $url ) ) {
			$url = pll_get_requested_url();
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$root = ( false === strpos( $url, '://' ) ) ? $this->home_relative . $this->root : $this->home . '/' . $this->root;

		$pattern = (string) wp_parse_url( $root . ( $this->options['rewrite'] ? '' : 'language/' ), PHP_URL_PATH );
		$pattern = preg_quote( $pattern, '#' );
		$pattern = '#^' . $pattern . '(' . implode( '|', $this->model->get_languages_list( array( 'fields' => 'slug' ) ) ) . ')(/|$)#';
		return preg_match( $pattern, trailingslashit( $path ), $matches ) ? $matches[1] : ''; // $matches[1] is the slug of the requested language.
	}

	/**
	 * Returns the home url in a given language.
	 *
	 * @since 1.3.1
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param PLL_Language|string $language Language object or slug.
	 * @return string
	 */
	public function home_url( $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		$base = $this->options['rewrite'] ? '' : 'language/';
		$slug = $this->options['default_lang'] === $language && $this->options['hide_default'] ? '' : '/' . $this->root . $base . $language;
		return trailingslashit( $this->home . $slug );
	}

	/**
	 * Prepares the rewrite rules filters.
	 *
	 * @since 0.8.1
	 * @since 3.5 Hooked to `pll_prepare_rewrite_rules` and remove `$pre` parameter.
	 *
	 * @return void
	 */
	public function prepare_rewrite_rules() {
		/*
		 * Don't modify the rules if there is no languages created yet and make sure
		 * to add the filters only once and if all custom post types and taxonomies
		 * have been registered.
		 */
		if ( ! $this->model->has_languages() || ! did_action( 'wp_loaded' ) || ! self::$can_filter_rewrite_rules ) {
			return;
		}

		foreach ( $this->get_rewrite_rules_filters_with_callbacks() as $rule => $callback ) {
			add_filter( $rule, $callback );
		}
	}

	/**
	 * The rewrite rules !
	 *
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

		// For custom post type archives.
		$cpts = array_intersect( $this->model->get_translated_post_types(), get_post_types( array( '_builtin' => false ) ) );
		$cpts = $cpts ? '#post_type=(' . implode( '|', $cpts ) . ')#' : '';

		foreach ( $rules as $key => $rule ) {
			if ( ! is_string( $rule ) || ! is_string( $key ) ) {
				// Protection against a bug in Sendinblue for WooCommerce. See: https://wordpress.org/support/topic/bug-introduced-in-rewrite-rules/
				continue;
			}

			// Special case for translated post types and taxonomies to allow canonical redirection.
			if ( $this->options['force_lang'] && in_array( $filter, array_merge( $this->model->get_translated_post_types(), $this->model->get_translated_taxonomies() ) ) ) {

				/**
				 * Filters the rewrite rules to modify.
				 *
				 * @since 1.9.1
				 *
				 * @param bool        $modify  Whether to modify or not the rule, defaults to true.
				 * @param array       $rule    Original rewrite rule.
				 * @param string      $filter  Current set of rules being modified.
				 * @param string|bool $archive Custom post post type archive name or false if it is not a cpt archive.
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

			// Rewrite rules filtered by language.
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

			// Unmodified rules.
			else {
				$newrules[ $key ] = $rule;
			}
		}

		// The home rewrite rule.
		if ( 'root' == $filter && isset( $slug ) ) {
			$newrules[ $slug . '?$' ] = $wp_rewrite->index . '?lang=$matches[1]';
		}

		return $newrules;
	}

	/**
	 * Removes hooks to filter rewrite rules, called when switching blog @see {PLL_Base::switch_blog()}.
	 * See `self::prepare_rewrite_rules()` for added hooks.
	 *
	 * @since 3.5
	 *
	 * @return void
	 */
	public function remove_filters() {
		parent::remove_filters();

		foreach ( $this->get_rewrite_rules_filters_with_callbacks() as $rule => $callback ) {
			remove_filter( $rule, $callback );
		}
	}

	/**
	 * Returns *all* rewrite rules filters with their associated callbacks.
	 *
	 * @since 3.5
	 *
	 * @return callable[] Array of hook names as key and callbacks as values.
	 */
	protected function get_rewrite_rules_filters_with_callbacks() {
		$filters = array(
			'rewrite_rules_array'    => array( $this, 'rewrite_rules' ), // Needed for post type archives.
		);

		foreach ( $this->get_rewrite_rules_filters() as $type ) {
			$filters[ $type . '_rewrite_rules' ] = array( $this, 'rewrite_rules' );
		}

		return $filters;
	}
}
