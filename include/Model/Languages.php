<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Cache;
use PLL_Language;
use PLL_Language_Factory;
use PLL_Translatable_Objects;
use WP_Error;
use WP_Term;
use WP_Syntex\Polylang\Options\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Model for the languages.
 *
 * @since 3.7
 */
class Languages {
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
	 * Tells if {@see WP_Syntex\Polylang\Model\Languages::get_list()} can be used.
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
	 * Returns the language by its term_id, tl_term_id, slug or locale.
	 *
	 * @since 0.1
	 * @since 3.4 Allow to get a language by `term_taxonomy_id`.
	 * @since 3.7 Moved from `PLL_Model::get_language()` to `WP_Syntex\Polylang\Model\Languages::get()`.
	 *
	 * @param mixed $value `term_id`, `term_taxonomy_id`, `slug`, `locale`, or `w3c` of the queried language.
	 *                     `term_id` and `term_taxonomy_id` can be fetched for any language taxonomy.
	 *                     /!\ For the `term_taxonomy_id`, prefix the ID by `tt:` (ex: `"tt:{$tt_id}"`),
	 *                     this is to prevent confusion between `term_id` and `term_taxonomy_id`.
	 * @return PLL_Language|false Language object, false if no language found.
	 *
	 * @phpstan-param PLL_Language|WP_Term|int|string $value
	 */
	public function get( $value ) {
		if ( $value instanceof PLL_Language ) {
			return $value;
		}

		// Cast WP_Term to PLL_Language.
		if ( $value instanceof WP_Term ) {
			return $this->get( $value->term_id );
		}

		$return = $this->cache->get( 'language:' . $value );

		if ( $return instanceof PLL_Language ) {
			return $return;
		}

		foreach ( $this->get_list() as $lang ) {
			foreach ( $lang->get_tax_props() as $props ) {
				$this->cache->set( 'language:' . $props['term_id'], $lang );
				$this->cache->set( 'language:tt:' . $props['term_taxonomy_id'], $lang );
			}
			$this->cache->set( 'language:' . $lang->slug, $lang );
			$this->cache->set( 'language:' . $lang->locale, $lang );
			$this->cache->set( 'language:' . $lang->w3c, $lang );
		}

		/** @var PLL_Language|false */
		return $this->cache->get( 'language:' . $value );
	}

	/**
	 * Adds a new language and creates a default category for this language.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Admin_Model::add_language()` to `WP_Syntex\Polylang\Model\Languages::add()`.
	 *
	 * @param array $args {
	 *   Arguments used to create the language.
	 *
	 *   @type string $name           Language name (used only for display).
	 *   @type string $slug           Language code (ideally 2-letters ISO 639-1 language code).
	 *   @type string $locale         WordPress locale. If something wrong is used for the locale, the .mo files will
	 *                                not be loaded...
	 *   @type bool   $rtl            True if rtl language, false otherwise.
	 *   @type int    $term_group     Language order when displayed.
	 *   @type string $flag           Optional. Country code, {@see settings/flags.php}.
	 *   @type bool   $no_default_cat Optional. If set, no default category will be created for this language.
	 * }
	 * @return true|WP_Error True success, a `WP_Error` otherwise.
	 *
	 * @phpstan-param array{
	 *     name: string,
	 *     slug: string,
	 *     locale: string,
	 *     rtl: bool,
	 *     term_group: int|numeric-string,
	 *     flag?: string,
	 *     no_default_cat?: bool
	 * } $args
	 */
	public function add( $args ) {
		$errors = $this->validate_lang( $args );
		if ( $errors->has_errors() ) {
			return $errors;
		}

		/**
		 * @phpstan-var array{
		 *     name: non-empty-string,
		 *     slug:  non-empty-string,
		 *     locale:  non-empty-string,
		 *     rtl: bool,
		 *     term_group: int|numeric-string,
		 *     flag?:  non-empty-string,
		 *     no_default_cat?: bool
		 * } $args
		 */
		// First the language taxonomy.
		$r = wp_insert_term(
			$args['name'],
			'language',
			array(
				'slug'        => $args['slug'],
				'description' => $this->build_metas( $args ),
			)
		);
		if ( is_wp_error( $r ) ) {
			// Avoid an ugly fatal error if something went wrong (reported once in the forum).
			return new WP_Error( 'pll_add_language', __( 'Impossible to add the language.', 'polylang' ) );
		}
		wp_update_term( (int) $r['term_id'], 'language', array( 'term_group' => (int) $args['term_group'] ) ); // Can't set the term group directly in `wp_insert_term()`.

		// The other language taxonomies.
		$this->update_secondary_language_terms( $args['slug'], $args['name'] );

		if ( empty( $this->options['default_lang'] ) ) {
			// If this is the first language created, set it as default language
			$this->options['default_lang'] = $args['slug'];
		}

		// Refresh languages.
		$this->clean_cache();
		$this->get_list();

		flush_rewrite_rules(); // Refresh rewrite rules.

		/**
		 * Fires after a language is added.
		 *
		 * @since 1.9
		 *
		 * @param array $args {
		 *   Arguments used to create the language.
		 *
		 *   @type string $name           Language name (used only for display).
		 *   @type string $slug           Language code (ideally 2-letters ISO 639-1 language code).
		 *   @type string $locale         WordPress locale. If something wrong is used for the locale, the .mo files will
		 *                                not be loaded...
		 *   @type bool   $rtl            True if rtl language, false otherwise.
		 *   @type int    $term_group     Language order when displayed.
		 *   @type string $flag           Optional. Country code, {@see settings/flags.php}.
		 *   @type bool   $no_default_cat Optional. If set, no default category will be created for this language.
		 * }
		 */
		do_action( 'pll_add_language', $args );

		return true;
	}

	/**
	 * Updates language properties.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Admin_Model::update_language()` to `WP_Syntex\Polylang\Model\Languages::update()`.
	 *
	 * @param array $args {
	 *   Arguments used to modify the language.
	 *
	 *   @type int    $lang_id    ID of the language to modify.
	 *   @type string $name       Language name (used only for display).
	 *   @type string $slug       Language code (ideally 2-letters ISO 639-1 language code).
	 *   @type string $locale     WordPress locale. If something wrong is used for the locale, the .mo files will
	 *                            not be loaded...
	 *   @type bool   $rtl        True if rtl language, false otherwise.
	 *   @type int    $term_group Language order when displayed.
	 *   @type string $flag       Optional, country code, {@see settings/flags.php}.
	 * }
	 * @return true|WP_Error True success, a `WP_Error` otherwise.
	 *
	 * @phpstan-param array{
	 *     lang_id: int|numeric-string,
	 *     name: string,
	 *     slug: string,
	 *     locale: string,
	 *     rtl: bool,
	 *     term_group: int|numeric-string,
	 *     flag?: string
	 * } $args
	 */
	public function update( $args ) {
		$lang = $this->get( (int) $args['lang_id'] );

		if ( empty( $lang ) ) {
			return new WP_Error( 'pll_invalid_language_id', __( 'The language does not seem to exist.', 'polylang' ) );
		}

		$errors = $this->validate_lang( $args, $lang );
		if ( $errors->has_errors() ) {
			return $errors;
		}

		/**
		 * @phpstan-var array{
		 *     lang_id: int|numeric-string,
		 *     name: non-empty-string,
		 *     slug:  non-empty-string,
		 *     locale:  non-empty-string,
		 *     rtl: bool,
		 *     term_group: int|numeric-string,
		 *     flag?:  non-empty-string
		 * } $args
		 */
		// Update links to this language in posts and terms in case the slug has been modified.
		$slug     = $args['slug'];
		$old_slug = $lang->slug;

		if ( $old_slug !== $slug ) {
			// Update the language slug in translations.
			$this->update_translations( $old_slug, $slug );

			// Update language option in widgets.
			foreach ( $GLOBALS['wp_registered_widgets'] as $widget ) {
				if ( ! empty( $widget['callback'][0] ) && ! empty( $widget['params'][0]['number'] ) ) {
					$obj = $widget['callback'][0];
					$number = $widget['params'][0]['number'];
					if ( is_object( $obj ) && method_exists( $obj, 'get_settings' ) && method_exists( $obj, 'save_settings' ) ) {
						$settings = $obj->get_settings();
						if ( isset( $settings[ $number ]['pll_lang'] ) && $settings[ $number ]['pll_lang'] == $old_slug ) {
							$settings[ $number ]['pll_lang'] = $slug;
							$obj->save_settings( $settings );
						}
					}
				}
			}

			// Update menus locations.
			if ( ! empty( $this->options['nav_menus'] ) ) {
				foreach ( $this->options['nav_menus'] as $theme => $locations ) {
					foreach ( array_keys( $locations ) as $location ) {
						if ( ! empty( $this->options['nav_menus'][ $theme ][ $location ][ $old_slug ] ) ) {
							$this->options['nav_menus'][ $theme ][ $location ][ $slug ] = $this->options['nav_menus'][ $theme ][ $location ][ $old_slug ];
							unset( $this->options['nav_menus'][ $theme ][ $location ][ $old_slug ] );
						}
					}
				}
			}

			// Update domains.
			if ( ! empty( $this->options['domains'][ $old_slug ] ) ) {
				$this->options['domains'][ $slug ] = $this->options['domains'][ $old_slug ];
				unset( $this->options['domains'][ $old_slug ] );
			}

			// Update the default language option if necessary.
			if ( $lang->is_default ) {
				$this->options['default_lang'] = $slug;
			}
		}

		// And finally update the language itself.
		$this->update_secondary_language_terms( $args['slug'], $args['name'], $lang );

		$description = $this->build_metas( $args );
		wp_update_term( $lang->get_tax_prop( 'language', 'term_id' ), 'language', array( 'slug' => $slug, 'name' => $args['name'], 'description' => $description, 'term_group' => (int) $args['term_group'] ) );

		// Refresh languages.
		$this->clean_cache();
		$this->get_list();

		// Refresh rewrite rules.
		flush_rewrite_rules();

		/**
		 * Fires after a language is updated.
		 *
		 * @since 1.9
		 * @since 3.2 Added $lang parameter.
		 *
		 * @param array $args {
		 *   Arguments used to modify the language. @see WP_Syntex\Polylang\Model\Languages::update().
		 *
		 *   @type string $name           Language name (used only for display).
		 *   @type string $slug           Language code (ideally 2-letters ISO 639-1 language code).
		 *   @type string $locale         WordPress locale.
		 *   @type bool   $rtl            True if rtl language, false otherwise.
		 *   @type int    $term_group     Language order when displayed.
		 *   @type string $no_default_cat Optional, if set, no default category has been created for this language.
		 *   @type string $flag           Optional, country code, @see flags.php.
		 * }
		 * @param PLL_Language $lang Previous value of the language being edited.
		 */
		do_action( 'pll_update_language', $args, $lang );

		return true;
	}

	/**
	 * Deletes a language.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Admin_Model::delete_language()` to `WP_Syntex\Polylang\Model\Languages::delete()`.
	 *
	 * @param int $lang_id Language term_id.
	 * @return bool
	 */
	public function delete( $lang_id ): bool {
		$lang = $this->get( (int) $lang_id );

		if ( empty( $lang ) ) {
			return false;
		}

		// Oops! We are deleting the default language...
		// Need to do this before loosing the information for default category translations.
		if ( $lang->is_default ) {
			$slugs = $this->get_list( array( 'fields' => 'slug' ) );
			$slugs = array_diff( $slugs, array( $lang->slug ) );

			if ( ! empty( $slugs ) ) {
				$this->update_default( reset( $slugs ) ); // Arbitrary choice...
			} else {
				unset( $this->options['default_lang'] );
			}
		}

		// Delete the translations.
		$this->update_translations( $lang->slug );

		// Delete language option in widgets.
		foreach ( $GLOBALS['wp_registered_widgets'] as $widget ) {
			if ( ! empty( $widget['callback'][0] ) && ! empty( $widget['params'][0]['number'] ) ) {
				$obj = $widget['callback'][0];
				$number = $widget['params'][0]['number'];
				if ( is_object( $obj ) && method_exists( $obj, 'get_settings' ) && method_exists( $obj, 'save_settings' ) ) {
					$settings = $obj->get_settings();
					if ( isset( $settings[ $number ]['pll_lang'] ) && $settings[ $number ]['pll_lang'] == $lang->slug ) {
						unset( $settings[ $number ]['pll_lang'] );
						$obj->save_settings( $settings );
					}
				}
			}
		}

		// Delete menus locations.
		if ( ! empty( $this->options['nav_menus'] ) ) {
			foreach ( $this->options['nav_menus'] as $theme => $locations ) {
				foreach ( array_keys( $locations ) as $location ) {
					unset( $this->options['nav_menus'][ $theme ][ $location ][ $lang->slug ] );
				}
			}
		}

		// Delete users options.
		delete_metadata( 'user', 0, 'pll_filter_content', '', true );
		delete_metadata( 'user', 0, "description_{$lang->slug}", '', true );

		// Delete domain.
		unset( $this->options['domains'][ $lang->slug ] );

		/*
		 * Delete the language itself.
		 *
		 * Reverses the language taxonomies order is required to make sure 'language' is deleted in last.
		 *
		 * The initial order with the 'language' taxonomy at the beginning of 'PLL_Language::term_props' property
		 * is done by {@see PLL_Model::filter_terms_orderby()}
		 */
		foreach ( array_reverse( $lang->get_tax_props( 'term_id' ) ) as $taxonomy_name => $term_id ) {
			wp_delete_term( $term_id, $taxonomy_name );
		}

		// Refresh languages.
		$this->clean_cache();
		$this->get_list();

		flush_rewrite_rules(); // refresh rewrite rules
		return true;
	}

	/**
	 * Checks if there are languages or not.
	 *
	 * @since 3.3
	 * @since 3.7 Moved from `PLL_Model::has_languages()` to `WP_Syntex\Polylang\Model\Languages::has()`.
	 *
	 * @return bool True if there are, false otherwise.
	 */
	public function has(): bool {
		if ( ! empty( $this->cache->get( self::CACHE_KEY ) ) ) {
			return true;
		}

		if ( ! empty( get_transient( self::TRANSIENT_NAME ) ) ) {
			return true;
		}

		return ! empty( $this->get_terms() );
	}

	/**
	 * Returns the list of available languages.
	 * - Stores the list in a db transient (except flags), unless `PLL_CACHE_LANGUAGES` is set to false.
	 * - Caches the list (with flags) in a `PLL_Cache` object.
	 *
	 * @since 0.1
	 * @since 3.7 Moved from `PLL_Model::get_languages_list()` to `WP_Syntex\Polylang\Model\Languages::get_list()`.
	 *
	 * @param array $args {
	 *   @type bool   $hide_empty   Hides languages with no posts if set to `true` (defaults to `false`).
	 *   @type bool   $hide_default Hides default language from the list (default to `false`).
	 *   @type string $fields       Returns only that field if set; {@see PLL_Language} for a list of fields.
	 * }
	 * @return array List of PLL_Language objects or PLL_Language object properties.
	 */
	public function get_list( $args = array() ): array {
		if ( ! $this->are_ready() ) {
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
				$languages = $this->get_from_taxonomies();
			} else {
				$languages = get_transient( self::TRANSIENT_NAME );

				if ( empty( $languages ) || ! is_array( $languages ) || empty( reset( $languages )['term_props'] ) ) { // Test `term_props` in case we got a transient older than 3.4.
					// Create the languages from taxonomies.
					$languages = $this->get_from_taxonomies();
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

			if ( $this->are_ready() ) {
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
	 * Tells if {@see WP_Syntex\Polylang\Model\Languages::get_list()} can be used.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::are_languages_ready()` to `WP_Syntex\Polylang\Model\Languages::are_ready()`.
	 *
	 * @return bool
	 */
	public function are_ready(): bool {
		return $this->languages_ready;
	}

	/**
	 * Sets the internal property `$languages_ready` to `true`, telling that {@see WP_Syntex\Polylang\Model\Languages::get_list()} can be used.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::set_languages_ready()` to `WP_Syntex\Polylang\Model\Languages::set_ready()`.
	 *
	 * @return void
	 */
	public function set_ready(): void {
		$this->languages_ready = true;
	}

	/**
	 * Returns the default language.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::get_default_language()` to `WP_Syntex\Polylang\Model\Languages::get_default()`.
	 *
	 * @return PLL_Language|false Default language object, `false` if no language found.
	 */
	public function get_default() {
		if ( empty( $this->options['default_lang'] ) ) {
			return false;
		}

		return $this->get( $this->options['default_lang'] );
	}

	/**
	 * Updates the default language.
	 * taking care to update the default category & the nav menu locations.
	 *
	 * @since 1.8
	 * @since 3.7 Moved from `PLL_Admin_Model::update_default_lang()` to `WP_Syntex\Polylang\Model\Languages::update_default()`.
	 *
	 * @param string $slug New language slug.
	 * @return void
	 */
	public function update_default( $slug ): void {
		// The nav menus stored in theme locations should be in the default language.
		$theme = get_stylesheet();
		if ( ! empty( $this->options['nav_menus'][ $theme ] ) ) {
			$menus = array();

			foreach ( $this->options['nav_menus'][ $theme ] as $key => $loc ) {
				$menus[ $key ] = empty( $loc[ $slug ] ) ? 0 : $loc[ $slug ];
			}
			set_theme_mod( 'nav_menu_locations', $menus );
		}

		/**
		 * Fires when a default language is updated.
		 *
		 * @since 3.1
		 *
		 * @param string $slug Slug.
		 */
		do_action( 'pll_update_default_lang', $slug );

		// Update options
		$this->options['default_lang'] = $slug;

		$this->clean_cache();
		flush_rewrite_rules();
	}

	/**
	 * Maybe adds the missing language terms for 3rd party language taxonomies.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::maybe_create_language_terms()` to `WP_Syntex\Polylang\Model\Languages::maybe_create_terms()`.
	 *
	 * @return void
	 */
	public function maybe_create_terms(): void {
		$registered_taxonomies = array_diff(
			$this->translatable_objects->get_taxonomy_names( array( 'language' ) ),
			// Exclude the post and term language taxonomies from the list.
			array(
				$this->translatable_objects->get( 'post' )->get_tax_language(),
				$this->translatable_objects->get( 'term' )->get_tax_language(),
			)
		);

		if ( empty( $registered_taxonomies ) ) {
			// No 3rd party language taxonomies.
			return;
		}

		// We have at least one 3rd party language taxonomy.
		$known_taxonomies = $this->options['language_taxonomies'];
		$new_taxonomies   = array_diff( $registered_taxonomies, $known_taxonomies );

		if ( empty( $new_taxonomies ) ) {
			// No new 3rd party language taxonomies.
			return;
		}

		// We have at least one unknown 3rd party language taxonomy.
		foreach ( $this->get_list() as $language ) {
			$this->update_secondary_language_terms( $language->slug, $language->name, $language, $new_taxonomies );
		}

		// Clear the cache, so the new `term_id` and `term_taxonomy_id` appear in the languages list.
		$this->clean_cache();

		// Keep the previous values, so this is triggered only once per taxonomy.
		$this->options['language_taxonomies'] = array_merge( $known_taxonomies, $new_taxonomies );
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
	 * Builds the language metas into an array and serializes it, to be stored in the term description.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Admin_Model::build_language_metas()` to `WP_Syntex\Polylang\Model\Languages::build_metas()`.
	 *
	 * @param array $args {
	 *   Arguments used to build the language metas.
	 *
	 *   @type string $name       Language name (used only for display).
	 *   @type string $slug       Language code (ideally 2-letters ISO 639-1 language code).
	 *   @type string $locale     WordPress locale. If something wrong is used for the locale, the .mo files will not
	 *                            be loaded...
	 *   @type bool   $rtl        True if rtl language, false otherwise.
	 *   @type int    $term_group Language order when displayed.
	 *   @type int    $lang_id    Optional, ID of the language to modify. An empty value means the language is being
	 *                            created.
	 *   @type string $flag       Optional, country code, {@see settings/flags.php}.
	 * }
	 * @return string The serialized description array updated.
	 *
	 * @phpstan-param array{
	 *     name: non-empty-string,
	 *     slug: non-empty-string,
	 *     locale: non-empty-string,
	 *     rtl: bool,
	 *     term_group: int|numeric-string,
	 *     lang_id?: int|numeric-string,
	 *     flag?: non-empty-string
	 * } $args
	 */
	protected function build_metas( array $args ): string {
		if ( ! empty( $args['lang_id'] ) ) {
			$language_term = get_term( (int) $args['lang_id'] );

			if ( $language_term instanceof WP_Term ) {
				$old_data = maybe_unserialize( $language_term->description );
			}
		}

		if ( empty( $old_data ) || ! is_array( $old_data ) ) {
			$old_data = array();
		}

		$new_data = array(
			'locale'    => $args['locale'],
			'rtl'       => ! empty( $args['rtl'] ),
			'flag_code' => empty( $args['flag'] ) ? '' : $args['flag'],
		);

		/**
		 * Allow to add data to store for a language.
		 * `$locale`, `$rtl`, and `$flag_code` cannot be overwritten.
		 *
		 * @since 3.4
		 *
		 * @param mixed[] $add_data Data to add.
		 * @param mixed[] $args     {
		 *     Arguments used to create the language.
		 *
		 *     @type string $name       Language name (used only for display).
		 *     @type string $slug       Language code (ideally 2-letters ISO 639-1 language code).
		 *     @type string $locale     WordPress locale. If something wrong is used for the locale, the .mo files will
		 *                              not be loaded...
		 *     @type bool   $rtl        True if rtl language, false otherwise.
		 *     @type int    $term_group Language order when displayed.
		 *     @type int    $lang_id    Optional, ID of the language to modify. An empty value means the language is
		 *                              being created.
		 *     @type string $flag       Optional, country code, {@see settings/flags.php}.
		 * }
		 * @param mixed[] $new_data New data.
		 * @param mixed[] $old_data {
		 *     Original data. Contains at least the following:
		 *
		 *     @type string $locale    WordPress locale.
		 *     @type bool   $rtl       True if rtl language, false otherwise.
		 *     @type string $flag_code Country code.
		 * }
		 */
		$add_data = apply_filters( 'pll_language_metas', array(), $args, $new_data, $old_data );
		// Don't allow to overwrite `$locale`, `$rtl`, and `$flag_code`.
		$new_data = array_merge( $old_data, $add_data, $new_data );

		/** @var non-empty-string $serialized maybe_serialize() cannot return anything else than a string when fed by an array. */
		$serialized = maybe_serialize( $new_data );
		return $serialized;
	}

	/**
	 * Validates data entered when creating or updating a language.
	 *
	 * @since 0.4
	 * @since 3.7 Moved from `PLL_Admin_Model::validate_lang()` to `WP_Syntex\Polylang\Model\Languages::validate_lang()`.
	 *
	 * @param array             $args Parameters of {@see WP_Syntex\Polylang\Model\Languages::add() or @see WP_Syntex\Polylang\Model\Languages::update()}.
	 * @param PLL_Language|null $lang Optional the language currently updated, the language is created if not set.
	 * @return WP_Error
	 *
	 * @phpstan-param array{
	 *     locale: string,
	 *     slug: string,
	 *     name: string,
	 *     flag?: string
	 * } $args
	 */
	protected function validate_lang( $args, ?PLL_Language $lang = null ): WP_Error {
		$errors = new WP_Error();

		// Validate locale with the same pattern as WP 4.3. See #28303.
		if ( ! preg_match( '#^[a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?$#', $args['locale'], $matches ) ) {
			$errors->add( 'pll_invalid_locale', __( 'Enter a valid WordPress locale', 'polylang' ) );
		}

		// Validate slug characters.
		if ( ! preg_match( '#^[a-z_-]+$#', $args['slug'] ) ) {
			$errors->add( 'pll_invalid_slug', __( 'The language code contains invalid characters', 'polylang' ) );
		}

		// Validate slug is unique.
		foreach ( $this->get_list() as $language ) {
			if ( $language->slug === $args['slug'] && ( null === $lang || $lang->term_id !== $language->term_id ) ) {
				$errors->add( 'pll_non_unique_slug', __( 'The language code must be unique', 'polylang' ) );
			}
		}

		// Validate name.
		// No need to sanitize it as `wp_insert_term()` will do it for us.
		if ( empty( $args['name'] ) ) {
			$errors->add( 'pll_invalid_name', __( 'The language must have a name', 'polylang' ) );
		}

		// Validate flag.
		if ( ! empty( $args['flag'] ) && ! is_readable( POLYLANG_DIR . '/flags/' . $args['flag'] . '.png' ) ) {
			$flag = PLL_Language::get_flag_information( $args['flag'] );

			if ( ! empty( $flag['url'] ) ) {
				$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( sanitize_url( $flag['url'] ) ) : wp_remote_get( sanitize_url( $flag['url'] ) );
			}

			if ( empty( $response ) || is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$errors->add( 'pll_invalid_flag', __( 'The flag does not exist', 'polylang' ) );
			}
		}

		return $errors;
	}

	/**
	 * Updates the translations when a language slug has been modified in settings or deletes them when a language is removed.
	 *
	 * @since 0.5
	 * @since 3.7 Moved from `PLL_Admin_Model::update_translations()` to `WP_Syntex\Polylang\Model\Languages::update_translations()`.
	 *            Visibility changed from public to protected.
	 *
	 * @param string $old_slug The old language slug.
	 * @param string $new_slug Optional, the new language slug, if not set it means that the language has been deleted.
	 * @return void
	 *
	 * @phpstan-param non-empty-string $old_slug
	 */
	protected function update_translations( $old_slug, $new_slug = '' ): void {
		global $wpdb;

		$term_ids = array();
		$dr       = array();
		$dt       = array();
		$ut       = array();

		$taxonomies = $this->translatable_objects->get_taxonomy_names( array( 'translations' ) );
		$terms      = get_terms( array( 'taxonomy' => $taxonomies ) );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_ids[ $term->taxonomy ][] = $term->term_id;
				$tr = maybe_unserialize( $term->description );
				$tr = is_array( $tr ) ? $tr : array();

				/**
				 * Filters the unserialized translation group description before it is
				 * updated when a language is deleted or a language slug is changed.
				 *
				 * @since 3.2
				 *
				 * @param (int|string[])[] $tr {
				 *     List of translations with lang codes as array keys and IDs as array values.
				 *     Also in this array:
				 *
				 *     @type string[] $sync List of synchronized translations with lang codes as array keys and array values.
				 * }
				 * @param string           $old_slug The old language slug.
				 * @param string           $new_slug The new language slug.
				 * @param WP_Term          $term     The term containing the post or term translation group.
				 */
				$tr = apply_filters( 'update_translation_group', $tr, $old_slug, $new_slug, $term );

				if ( ! empty( $tr[ $old_slug ] ) ) {
					if ( $new_slug ) {
						$tr[ $new_slug ] = $tr[ $old_slug ]; // Suppress this for delete
					} else {
						$dr['id'][] = (int) $tr[ $old_slug ];
						$dr['tt'][] = (int) $term->term_taxonomy_id;
					}
					unset( $tr[ $old_slug ] );

					if ( empty( $tr ) || 1 == count( $tr ) ) {
						$dt['t'][] = (int) $term->term_id;
						$dt['tt'][] = (int) $term->term_taxonomy_id;
					} else {
						$ut['case'][] = $wpdb->prepare( 'WHEN %d THEN %s', $term->term_id, maybe_serialize( $tr ) );
						$ut['in'][] = (int) $term->term_id;
					}
				}
			}
		}

		// Delete relationships
		if ( ! empty( $dr ) ) {
			$wpdb->query(
				"DELETE FROM $wpdb->term_relationships
				WHERE object_id IN ( " . implode( ',', $dr['id'] ) . ' ) ' . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'AND term_taxonomy_id IN ( ' . implode( ',', $dr['tt'] ) . ' )' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);
		}

		// Delete terms
		if ( ! empty( $dt ) ) {
			$wpdb->query( "DELETE FROM $wpdb->terms WHERE term_id IN ( " . implode( ',', $dt['t'] ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ( " . implode( ',', $dt['tt'] ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Update terms
		if ( ! empty( $ut ) ) {
			$wpdb->query(
				"UPDATE $wpdb->term_taxonomy
				SET description = ( CASE term_id " . implode( ' ', $ut['case'] ) . ' END ) ' . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'WHERE term_id IN ( ' . implode( ',', $ut['in'] ) . ' )' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);
		}

		if ( ! empty( $term_ids ) ) {
			foreach ( $term_ids as $taxonomy => $ids ) {
				clean_term_cache( $ids, $taxonomy );
			}
		}
	}

	/**
	 * Updates or adds new terms for a secondary language taxonomy (aka not 'language').
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::update_secondary_language_terms()` to `WP_Syntex\Polylang\Model\Languages::update_secondary_language_terms()`.
	 *
	 * @param string            $slug       Language term slug (with or without the `pll_` prefix).
	 * @param string            $name       Language name (label).
	 * @param PLL_Language|null $language   Optional. A language object. Required to update the existing terms.
	 * @param string[]          $taxonomies Optional. List of language taxonomies to deal with. An empty value means
	 *                                      all of them. Defaults to all taxonomies.
	 * @return void
	 *
	 * @phpstan-param non-empty-string $slug
	 * @phpstan-param non-empty-string $name
	 * @phpstan-param array<non-empty-string> $taxonomies
	 */
	protected function update_secondary_language_terms( $slug, $name, ?PLL_Language $language = null, array $taxonomies = array() ): void {
		$slug = 0 === strpos( $slug, 'pll_' ) ? $slug : "pll_$slug";

		foreach ( $this->translatable_objects->get_secondary_translatable_objects() as $object ) {
			if ( ! empty( $taxonomies ) && ! in_array( $object->get_tax_language(), $taxonomies, true ) ) {
				// Not in the list.
				continue;
			}

			if ( ! empty( $language ) ) {
				$term_id = $language->get_tax_prop( $object->get_tax_language(), 'term_id' );
			} else {
				$term_id = 0;
			}

			if ( empty( $term_id ) ) {
				// Attempt to repair the language if a term has been deleted by a database cleaning tool.
				wp_insert_term( $name, $object->get_tax_language(), array( 'slug' => $slug ) );
				continue;
			}

			/** @var PLL_Language $language */
			if ( "pll_{$language->slug}" !== $slug || $language->name !== $name ) {
				// Something has changed.
				wp_update_term( $term_id, $object->get_tax_language(), array( 'slug' => $slug, 'name' => $name ) );
			}
		}
	}

	/**
	 * Returns the list of available languages, based on the language taxonomy terms.
	 * Stores the list in a db transient and in a `PLL_Cache` object.
	 *
	 * @since 3.4
	 * @since 3.7 Moved from `PLL_Model::get_languages_from_taxonomies()` to `WP_Syntex\Polylang\Model\Languages::get_from_taxonomies()`.
	 *
	 * @return PLL_Language[] An array of `PLL_Language` objects, array keys are the type.
	 *
	 * @phpstan-return list<PLL_Language>
	 */
	protected function get_from_taxonomies(): array {
		$terms_by_slug = array();

		foreach ( $this->get_terms() as $term ) {
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
		 * @param Language $model     `Language` object.
		 */
		$languages = apply_filters_deprecated( 'pll_languages_list', array( $languages, $this ), '3.4', 'pll_additional_language_data' );

		if ( ! $this->are_ready() ) {
			// Do not cache an incomplete list.
			/** @var list<PLL_Language> $languages */
			return $languages;
		}

		/*
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
	 * - Terms are ordered by `term_group` and `term_id` (see `WP_Syntex\Polylang\Model\Languages::filter_terms_orderby()`).
	 *
	 * @since 3.2.3
	 * @since 3.7 Moved from `PLL_Model::get_language_terms()` to `WP_Syntex\Polylang\Model\Languages::get_terms()`.
	 *
	 * @return WP_Term[]
	 */
	protected function get_terms(): array {
		$callback = \Closure::fromCallable( array( $this, 'filter_terms_orderby' ) );
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
	 * @since 3.7 Moved from `PLL_Model::filter_language_terms_orderby()` to `WP_Syntex\Polylang\Model\Languages::filter_terms_orderby()`.
	 *            Visibility changed from `public` to `protected`.
	 *
	 * @param  string   $orderby    `ORDERBY` clause of the terms query.
	 * @param  array    $args       An array of term query arguments.
	 * @param  string[] $taxonomies An array of taxonomy names.
	 * @return string
	 */
	protected function filter_terms_orderby( $orderby, $args, $taxonomies ) {
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
