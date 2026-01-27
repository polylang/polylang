<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use PLL_MO;
use WP_Widget;
use PLL_Language;
use WP_Widget_Custom_HTML;
use WP_Syntex\Polylang\Model\Languages;

/**
 * Database repository for storing and retrieving Translatable string entities and their translations.
 *
 * @since 3.8
 */
class Database_Repository {
	/**
	 * The registered strings.
	 *
	 * @var array
	 */
	private static array $registered_strings = array();

	/**
	 * Whether the registered strings have been read.
	 *
	 * @var bool
	 */
	private static bool $read = false;

	/**
	 * The languages model.
	 *
	 * @var Languages
	 */
	private Languages $languages;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Languages $languages The languages model.
	 */
	public function __construct( Languages $languages ) {
		$this->languages = $languages;
	}

	/**
	 * Creates a new query builder.
	 *
	 * @since 3.8
	 *
	 * @return String_Query The query builder.
	 */
	public function query(): String_Query {
		return new String_Query( $this, $this->languages );
	}

	/**
	 * Finds all translatable strings with translations for all languages.
	 *
	 * @since 3.8
	 *
	 * @return Collection The collection of translatables.
	 */
	public function find_all(): Collection {
		$strings       = self::get_strings();
		$translatables = array();

		foreach ( $strings as $string_data ) {
			$translatable = new Translatable(
				$string_data['string'],
				$string_data['name'],
				$string_data['context'] ?? null,
				$string_data['sanitize_callback'] ?? null,
				$string_data['multiline'] ?? false
			);
			$translatables[ $translatable->get_id() ] = $translatable;
		}

		foreach ( $this->languages->get_list() as $language ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );

			foreach ( $translatables as $translatable ) {
				$translation = $mo->translate_if_any( $translatable->get_source(), $translatable->get_context() );
				if ( $translation !== $translatable->get_source() ) {
					$translatable->set_translation( $language, $translation );
				}
			}
		}

		return new Collection( $translatables );
	}

	/**
	 * Removes a translatable string by ID.
	 * Note: This only works for strings registered via WPML API (icl_register_string).
	 *
	 * @since 3.8
	 *
	 * @param string $id The identifier.
	 * @return void
	 */
	public function remove_wpml_string( string $id ): void {
		$string_to_remove = array_find(
			self::get_strings(),
			static function ( $string_data ) use ( $id ) {
				return md5( $string_data['string'] . $string_data['context'] ) === $id && function_exists( 'icl_unregister_string' );
			}
		);

		if ( $string_to_remove && isset( $string_to_remove['context'], $string_to_remove['name'] ) ) {
			icl_unregister_string( $string_to_remove['context'], $string_to_remove['name'] );
		}
	}

	/**
	 * Cleans the database from non-registered strings for all languages.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public function clean(): void {
		$collection = $this->find_all();

		foreach ( $this->languages->get_list() as $language ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );

			foreach ( $mo->entries as $entry ) {
				if ( $collection->has( md5( $entry->singular . $entry->context ) ) ) {
					continue;
				}
				$mo->delete_entry( $entry->singular );
			}

			$mo->export_to_db( $language );
		}
	}

	/**
	 * Saves translations for all languages in the collection.
	 *
	 * @since 3.8
	 *
	 * @param Collection $collection The collection of translatables.
	 * @return void
	 */
	public function save( Collection $collection ): void {
		foreach ( $this->languages->get_list() as $language ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );

			foreach ( $collection as $translatable ) {
				/**
				 * Filters the translation before it is saved in DB.
				 *
				 * @since 1.6
				 * @since 2.7 The translation passed to the filter is unslashed.
				 * @since 3.7 Add original string as 4th parameter.
				 * @since 3.8 Add previous string translation as 5th parameter.
				 *
				 * @param string $translation The translation value.
				 * @param string $name        The name as defined in pll_register_string.
				 * @param string $context     The context as defined in pll_register_string.
				 * @param string $original    The original string to translate.
				 * @param string $previous    The previous translation if any.
				 */
				$sanitized_translation = apply_filters(
					'pll_sanitize_string_translation',
					$translatable->get_translation( $language ),
					$translatable->get_name(),
					$translatable->get_context(),
					$mo->translate_if_any( $translatable->get_source(), $translatable->get_context() ),
					$translatable->get_previous_translation( $language )
				);

				$mo->add_entry(
					$translatable->set_translation( $language, $sanitized_translation )
						->get_entry( $language )
				);
			}

			$mo->export_to_db( $language );
		}
	}

	/**
	 * Resets the registered strings and the read flag from memory.
	 * Does not affect the database.
	 *
	 * @since 3.8
	 */
	public static function reset(): void {
		self::$registered_strings = array();
		self::$read               = false;
	}
	/**
	 * Gets registered strings at runtime.
	 *
	 * @since 3.8 Formerly `PLL_Admin_Strings::get_strings()`.
	 *
	 * @return array list of all registered strings
	 */
	private static function get_strings(): array {
		if ( self::$read ) {
			return self::$registered_strings;
		}

		$default_strings = array(
			'widget_title' => __( 'Widget title', 'polylang' ),
			'widget_text'  => __( 'Widget text', 'polylang' ),
		);

		global $wp_registered_widgets;
		$sidebars = wp_get_sidebars_widgets();
		foreach ( $sidebars as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' == $sidebar || empty( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget ) {
				// Nothing can be done if the widget is created using pre WP2.8 API. There is no object, so we can't access it to get the widget options.
				if ( ! isset( $wp_registered_widgets[ $widget ]['callback'][0] ) || ! $wp_registered_widgets[ $widget ]['callback'][0] instanceof WP_Widget ) {
					continue;
				}

				$widget_instance = $wp_registered_widgets[ $widget ]['callback'][0];
				$widget_settings = $widget_instance->get_settings();
				$number          = $wp_registered_widgets[ $widget ]['params'][0]['number'];

				// Don't enable widget translation if the widget is visible in only one language or if there is no title.
				if ( ! empty( $widget_settings[ $number ]['pll_lang'] ) ) {
					continue;
				}

				// Widget title.
				if ( ! empty( $widget_settings[ $number ]['title'] ) ) {
					self::register( $default_strings['widget_title'], $widget_settings[ $number ]['title'], 'Widget', 'sanitize_text_field' );
				}

				// Text of the Widget text.
				if ( ! empty( $widget_settings[ $number ]['text'] ) ) {
					self::register( $default_strings['widget_text'], $widget_settings[ $number ]['text'], 'Widget', null, true );
				}

				// Content of the widget custom html.
				if ( $widget_instance instanceof WP_Widget_Custom_HTML && ! empty( $widget_settings[ $number ]['content'] ) ) {
					self::register( $default_strings['widget_text'], $widget_settings[ $number ]['content'], 'Widget', null, true );
				}
			}
		}

		/**
		 * Filter the list of strings registered for translation
		 * Mainly for use by our PLL_WPML_Compat class
		 *
		 * @since 1.0.2
		 *
		 * @param array $strings list of strings
		 */
		self::$registered_strings = apply_filters( 'pll_get_strings', self::$registered_strings );
		self::$read               = true;

		return self::$registered_strings;
	}

	/**
	 * Registers a string for translation and resets the read flag.
	 *
	 * @since 3.8 Formerly in `PLL_Admin_Strings::register_string()`.
	 *
	 * @param string        $name              A unique name for the string
	 * @param string        $string            The string to register
	 * @param string        $context           Optional, the group in which the string is registered, defaults to 'polylang'
	 * @param callable|null $sanitize_callback The sanitization callback for the string. Default is a closure that calls the default_sanitization method.
	 * @param bool          $multiline         Optional, whether the string table should display a multiline textarea or a single line input, defaults to single line
	 * @return void
	 */
	public static function register(
		string $name,
		string $string,
		string $context = 'Polylang',
		?callable $sanitize_callback = null,
		bool $multiline = false
	): void {
		self::$registered_strings[ md5( $string . $context ) ] = compact( 'name', 'string', 'context', 'sanitize_callback', 'multiline' );
		self::$read = false;
	}
}
