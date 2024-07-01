<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Models;

use PLL_Cache;
use PLL_Language;
use PLL_Language_Factory;
use PLL_Translatable_Objects;
use WP_Error;
use WP_Term;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Model for the languages list.
 *
 * @since 3.7
 */
class Languages_List_Model {
	public const TRANSIENT_NAME = 'pll_languages_list';
	private const CACHE_KEY     = 'languages';

	/**
	 * Polylang's options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Translatable objects registry.
	 *
	 * @var PLL_Translatable_Objects
	 */
	private $translatable_objects;

	/**
	 * Internal non persistent cache object.
	 *
	 * @var PLL_Cache<mixed>
	 */
	private $cache;

	/**
	 * Flag set to true during the language objects creation.
	 *
	 * @var bool
	 */
	private $is_creating_list = false;

	/**
	 * Tells if {@see WP_Syntex\Polylang\Models\Languages_List_Model::get_languages_list()} can be used.
	 *
	 * @var bool
	 */
	private $languages_ready = false;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param Options                  $options              Polylang's options.
	 * @param PLL_Translatable_Objects $translatable_objects Translatable objects registry.
	 * @param PLL_Cache                $cache                Internal non persistent cache object.
	 *
	 * @phpstan-param PLL_Cache<mixed> $cache
	 */
	public function __construct( Options $options, PLL_Translatable_Objects $translatable_objects, PLL_Cache $cache ) {
		$this->options              = $options;
		$this->translatable_objects = $translatable_objects;
		$this->cache                = $cache;
	}

	/**
	 * Checks if there are languages or not.
	 *
	 * @since 3.3
	 * @since 3.7 Moved from `PLL_Model::has_languages()` to `WP_Syntex\Polylang\Models\Languages_List_Model::has_languages()`.
	 *
	 * @return bool True if there are, false otherwise.
	 */
	public function has_languages(): bool {
		if ( ! empty( $this->cache->get( self::CACHE_KEY ) ) ) {
			return true;
		}

		if ( ! empty( get_transient( self::TRANSIENT_NAME ) ) ) {
			return true;
		}

		return ! empty( $this->get_language_terms() );
	}

	/**
	 * Returns the list of available languages.
	 * - Stores the list in a db transient (except flags), unless `PLL_CACHE_LANGUAGES` is set to false.
	 * - Caches the list (with flags) in a `PLL_Cache` object.
	 *
	 * @since 0.1
	 * @since 3.7 Moved from `PLL_Model::get_languages_list()` to `WP_Syntex\Polylang\Models\Languages_List_Model::get_languages_list()`.
	 *
	 * @param array $args {
	 *   @type bool   $hide_empty   Hides languages with no posts if set to `true` (defaults to `false`).
	 *   @type bool   $hide_default Hides default language from the list (default to `false`).
	 *   @type string $fields       Returns only that field if set; {@see PLL_Language} for a list of fields.
	 * }
	 * @return array List of PLL_Language objects or PLL_Language object properties.
	 */
	public function get_languages_list( array $args = array() ): array {
		if ( ! $this->are_languages_ready() ) {
			_doing_it_wrong(
				__METHOD__ . '()',
				"It must not be called before the hook 'pll_pre_init'.",
				'3.4'
			);
		}

		$languages = $this->cache->get( self::CACHE_KEY );

		if ( ! is_array( $languages ) ) {
			// Bail out early if languages are currently created to avoid an infinite loop.
			if ( $this->is_creating_list ) {
				return array();
			}

			$this->is_creating_list = true;

			if ( ! pll_get_constant( 'PLL_CACHE_LANGUAGES', true ) ) {
				// Create the languages from taxonomies.
				$languages = $this->get_languages_from_taxonomies();
			} else {
				$languages = get_transient( self::TRANSIENT_NAME );

				if ( empty( $languages ) || ! is_array( $languages ) || empty( reset( $languages )['term_props'] ) ) { // Test `term_props` in case we got a transient older than 3.4.
					// Create the languages from taxonomies.
					$languages = $this->get_languages_from_taxonomies();
				} else {
					// Create the languages directly from arrays stored in the transient.
					$languages = array_map(
						array( new PLL_Language_Factory( $this->options ), 'get' ),
						$languages
					);

					// Remove potential empty language.
					$languages = array_filter( $languages );

					// Re-index.
					$languages = array_values( $languages );
				}
			}

			/**
			 * Filters the list of languages *after* it is stored in the persistent cache.
			 * /!\ This filter is fired *before* the $polylang object is available.
			 *
			 * @since 1.8
			 * @since 3.4 Deprecated. If you used this hook to filter URLs, you may hook `'site_url'` instead.
			 * @deprecated
			 *
			 * @param PLL_Language[] $languages The list of language objects.
			 */
			$languages = apply_filters_deprecated( 'pll_after_languages_cache', array( $languages ), '3.4' );

			if ( $this->are_languages_ready() ) {
				$this->cache->set( self::CACHE_KEY, $languages );
			}

			$this->is_creating_list = false;
		}

		$languages = array_filter(
			$languages,
			function ( $lang ) use ( $args ) {
				$keep_empty   = empty( $args['hide_empty'] ) || $lang->get_tax_prop( 'language', 'count' );
				$keep_default = empty( $args['hide_default'] ) || ! $lang->is_default;
				return $keep_empty && $keep_default;
			}
		);

		$languages = array_values( $languages ); // Re-index.

		return empty( $args['fields'] ) ? $languages : wp_list_pluck( $languages, $args['fields'] );
	}

	/**
	 * Tells if {@see WP_Syntex\Polylang\Models\Languages_List_Model::get_languages_list()} can be used.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::are_languages_ready()` to `WP_Syntex\Polylang\Models\Languages_List_Model::are_languages_ready()`.
	 *
	 * @return bool
	 */
	public function are_languages_ready(): bool {
		return $this->languages_ready;
	}

	/**
	 * Sets the internal property `$languages_ready` to `true`, telling that {@see WP_Syntex\Polylang\Models\Languages_List_Model::get_languages_list()} can be used.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::set_languages_ready()` to `WP_Syntex\Polylang\Models\Languages_List_Model::set_languages_ready()`.
	 *
	 * @return void
	 */
	public function set_languages_ready(): void {
		$this->languages_ready = true;
	}

	/**
	 * Cleans language cache.
	 *
	 * @since 3.7
	 * @return void
	 */
	public function clean_cache(): void {
		delete_transient( self::TRANSIENT_NAME );
		$this->cache->clean();
	}

	/**
	 * Returns the list of available languages, based on the language taxonomy terms.
	 * Stores the list in a db transient and in a `PLL_Cache` object.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::get_languages_from_taxonomies()` to `WP_Syntex\Polylang\Models\Languages_List_Model::get_languages_from_taxonomies()`.
	 *
	 * @return PLL_Language[] An array of `PLL_Language` objects, array keys are the type.
	 *
	 * @phpstan-return list<PLL_Language>
	 */
	private function get_languages_from_taxonomies(): array {
		$terms_by_slug = array();

		foreach ( $this->get_language_terms() as $term ) {
			// Except for language taxonomy term slugs, remove 'pll_' prefix from the other language taxonomy term slugs.
			$key = 'language' === $term->taxonomy ? $term->slug : substr( $term->slug, 4 );
			$terms_by_slug[ $key ][ $term->taxonomy ] = $term;
		}

		/**
		 * @var (
		 *     array{
		 *         string: array{
		 *             language: WP_Term,
		 *         }&array<non-empty-string, WP_Term>
		 *     }
		 * ) $terms_by_slug
		 */
		$languages = array_filter(
			array_map(
				array( new PLL_Language_Factory( $this->options ), 'get_from_terms' ),
				array_values( $terms_by_slug )
			)
		);

		/**
		 * Filters the list of languages *before* it is stored in the persistent cache.
		 * /!\ This filter is fired *before* the $polylang object is available.
		 *
		 * @since 1.7.5
		 * @since 3.4 Deprecated.
		 * @deprecated
		 *
		 * @param PLL_Language[]       $languages The list of language objects.
		 * @param Languages_List_Model $model     `Languages_List_Model` object.
		 */
		$languages = apply_filters_deprecated( 'pll_languages_list', array( $languages, $this ), '3.4', 'pll_additional_language_data' );

		if ( ! $this->are_languages_ready() ) {
			// Do not cache an incomplete list.
			/** @var list<PLL_Language> $languages */
			return $languages;
		}

		/**
		 * Don't store directly objects as it badly break with some hosts ( GoDaddy ) due to race conditions when using object cache.
		 * Thanks to captin411 for catching this!
		 *
		 * @see https://wordpress.org/support/topic/fatal-error-pll_model_languages_list?replies=8#post-6782255
		 */
		$languages_data = array_map(
			function ( $language ) {
				return $language->to_array( 'db' );
			},
			$languages
		);

		set_transient( self::TRANSIENT_NAME, $languages_data );

		/** @var list<PLL_Language> $languages */
		return $languages;
	}

	/**
	 * Returns the list of existing language terms.
	 * - Returns all terms, that are or not assigned to posts.
	 * - Terms are ordered by `term_group` and `term_id` (see `WP_Syntex\Polylang\Models\Languages_List_Model->filter_language_terms_orderby()`).
	 *
	 * @since 3.2.3
	 * @since 3.7 Moved from `PLL_Model::get_language_terms()` to `WP_Syntex\Polylang\Models\Languages_List_Model::get_language_terms()`.
	 *
	 * @return WP_Term[]
	 */
	private function get_language_terms(): array {
		$callback = \Closure::fromCallable( array( $this, 'filter_language_terms_orderby' ) );
		add_filter( 'get_terms_orderby', $callback, 10, 3 );
		$terms = get_terms(
			array(
				'taxonomy'   => $this->translatable_objects->get_taxonomy_names( array( 'language' ) ),
				'orderby'    => 'term_group',
				'hide_empty' => false,
			)
		);
		remove_filter( 'get_terms_orderby', $callback );

		return empty( $terms ) || is_wp_error( $terms ) ? array() : $terms;
	}

	/**
	 * Filters the ORDERBY clause of the languages query.
	 *
	 * This allows to order languages terms by `taxonomy` first then by `term_group` and `term_id`.
	 * Ordering terms by taxonomy allows not to mix terms between all language taxomonomies.
	 * Having the "language' taxonomy first is important for {@see PLL_Admin_Model:delete_language()}.
	 *
	 * @since 3.2.3
	 * @since 3.7 Moved from `PLL_Model::filter_language_terms_orderby()` to `WP_Syntex\Polylang\Models\Languages_List_Model::filter_language_terms_orderby()`.
	 *            Visibility changed from `public` to `private`.
	 *
	 * @param  string   $orderby    `ORDERBY` clause of the terms query.
	 * @param  array    $args       An array of term query arguments.
	 * @param  string[] $taxonomies An array of taxonomy names.
	 * @return string
	 */
	private function filter_language_terms_orderby( $orderby, $args, $taxonomies ) {
		$allowed_taxonomies = $this->translatable_objects->get_taxonomy_names( array( 'language' ) );

		if ( ! is_array( $taxonomies ) || ! empty( array_diff( $taxonomies, $allowed_taxonomies ) ) ) {
			return $orderby;
		}

		if ( empty( $orderby ) || ! is_string( $orderby ) ) {
			return $orderby;
		}

		if ( ! preg_match( '@^(?<alias>[^.]+)\.term_group$@', $orderby, $matches ) ) {
			return $orderby;
		}

		return sprintf( 'tt.taxonomy = \'language\' DESC, %1$s.term_group, %1$s.term_id', $matches['alias'] );
	}
}
