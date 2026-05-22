<?php
/**
 * @package Polylang
 */

/**
 * Manages strings translations storage.
 * A static cache is used internally to enhance performances,
 * for it to work as expected, use `import_from_db` for the cache to be used
 * and `export_to_db` for the cache to be cleared.
 *
 * @since 1.2
 * @since 2.1 Stores the strings in a post meta instead of post content to avoid unserialize issues (See #63)
 * @since 3.4 Stores the strings into language taxonomy term meta instead of a post meta.
 */
class PLL_MO extends MO {
	/**
	 * Built-in groups shown in the Polylang strings UI. Stored in the database with an empty gettext context, mostly for backward compatibility.
	 *
	 * @since 3.9
	 *
	 * @var non-empty-list<string>
	 */
	private const BUILTIN_STRING_TABLE_GROUPS = array( 'Polylang', 'WordPress', 'Widget' );

	/**
	 * Third field in each stored tuple when the MO uses a singular msgid (empty stored context).
	 *
	 * @since 3.9
	 *
	 * @var string
	 */
	private const TUPLE_MSGID_SINGULAR_MARKER = '';

	/**
	 * Whether a strings-table group is stored with an empty gettext context (same key as {@see pll__()}).
	 *
	 * @since 3.9
	 *
	 * @param string $context Group name from {@see pll_register_string()} or marker from stored tuples.
	 * @return bool True for Polylang / WordPress / Widget rows (admin labels); MO uses singular msgid only.
	 */
	public static function is_builtin_strings_table_context( string $context ): bool {
		return in_array( $context, self::BUILTIN_STRING_TABLE_GROUPS, true );
	}

	/**
	 * Static cache for the translations.
	 *
	 * @var PLL_Cache<array>
	 */
	private static $cache;

	/**
	 * Constructor, initializes the cache if not already done.
	 *
	 * @since 3.7
	 */
	public function __construct() {
		if ( empty( self::$cache ) ) {
			self::$cache = new PLL_Cache();
		}
	}

	/**
	 * Writes the strings into a term meta.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language $lang The language in which we want to export strings.
	 * @return void
	 */
	public function export_to_db( $lang ) {
		/*
		 * It would be convenient to store the whole object, but it would take a huge space in DB.
		 * So let's keep only the strings in an array.
		 * The strings are slashed to avoid breaking slashed strings in update_term_meta.
		 * @see https://codex.wordpress.org/Function_Reference/update_post_meta#Character_Escaping.
		 *
		 * Each row is [ original, translation, marker ]. Marker is empty when the MO entry uses no gettext context
		 * ({@see pll__()}). Other plugin groups use `marker = group name` and rebuild `group\4msgid` on import.
		 */
		$strings = array();
		foreach ( $this->entries as $entry ) {
			if ( '' === $entry->singular ) {
				continue;
			}

			$gettext_context = null;
			if ( isset( $entry->context ) && is_string( $entry->context ) && '' !== $entry->context ) {
				$gettext_context = $entry->context;
			}

			if ( ! is_array( $entry->translations ) || ! isset( $entry->translations[0] ) ) {
				continue;
			}

			$translation = $entry->translations[0];

			if ( ! is_string( $translation ) || '' === $translation ) {
				continue;
			}

			$tuple_marker = self::TUPLE_MSGID_SINGULAR_MARKER;
			if ( null !== $gettext_context && ! self::is_builtin_strings_table_context( $gettext_context ) ) {
				$tuple_marker = $gettext_context;
			}

			$strings[] = wp_slash(
				array(
					$entry->singular,
					$translation,
					$tuple_marker,
				)
			);
		}

		update_term_meta( $lang->term_id, '_pll_strings_translations', $strings );

		self::$cache->clean( $lang->slug );
	}

	/**
	 * Reads a PLL_MO object from the term meta.
	 *
	 * @since 1.2
	 * @since 3.4 Reads a PLL_MO from the term meta.
	 *
	 * @param PLL_Language $lang The language in which we want to get strings.
	 * @return void
	 */
	public function import_from_db( $lang ) {
		$this->set_header( 'Language', $lang->slug );

		if ( ! empty( self::$cache->get( $lang->slug ) ) ) {
			$this->entries = self::$cache->get( $lang->slug );
			return;
		}

		$strings = get_term_meta( $lang->term_id, '_pll_strings_translations', true );
		if ( empty( $strings ) || ! is_array( $strings ) ) {
			self::$cache->set( $lang->slug, array() );
			$this->entries = array();

			return;
		}

		foreach ( $strings as $tuple ) {
			if ( ! is_array( $tuple ) || ! isset( $tuple[0], $tuple[1] ) ) {
				continue;
			}

			if ( ! is_string( $tuple[0] ) || ! is_string( $tuple[1] ) ) {
				continue;
			}

			$singular    = $tuple[0];
			$translation = $tuple[1];

			if ( '' === $singular || '' === $translation ) {
				continue;
			}

			$tuple_marker = self::TUPLE_MSGID_SINGULAR_MARKER;
			if ( isset( $tuple[2] ) && is_string( $tuple[2] ) ) {
				$tuple_marker = $tuple[2];
			}

			$original = $singular;
			if ( self::TUPLE_MSGID_SINGULAR_MARKER !== $tuple_marker && ! self::is_builtin_strings_table_context( $tuple_marker ) ) {
				$original = $tuple_marker . "\4" . $singular;
			}

			$this->add_entry( $this->make_entry( $original, $translation ) );
		}

		self::$cache->set( $lang->slug, $this->entries );
	}

	/**
	 * Deletes a string
	 *
	 * @since 2.9
	 * @since 3.9 Parameter `$context` added.
	 *
	 * @param string      $string The source string to remove from the translations.
	 * @param string|null $context Optional. Non-built-in Polylang group or gettext msgctxt.
	 * @return void
	 */
	public function delete_entry( $string, $context = null ) {
		if ( null === $context || '' === $context || self::is_builtin_strings_table_context( $context ) ) {
			$key = $string;
		} else {
			$key = $context . "\4" . $string;
		}

		unset( $this->entries[ $key ] );
	}

	/**
	 * Translates a singular string. Built-in table groups use the same MO key as {@see pll__()}.
	 *
	 * @since 3.9
	 *
	 * @param string      $singular The string to translate.
	 * @param string|null $context  Optional. Polylang group or gettext context.
	 * @return string
	 */
	public function translate( $singular, $context = null ) {
		if ( null !== $context && '' !== $context && self::is_builtin_strings_table_context( $context ) ) {
			$context = null;
		}

		return parent::translate( $singular, $context );
	}

	/**
	 * Translates a string or returns false if the translation is not found.
	 * Contrary to `self::translate()`, this method doesn't fallback to the source string but returns empty string instead.
	 *
	 * @since 3.7
	 * @since 3.9 Parameter `$context` added.
	 *
	 * @param string      $source  The source string to translate.
	 * @param string|null $context Optional. Polylang group / gettext msgctxt.
	 * @return string The translated string or empty string if not found.
	 */
	public function translate_if_any( string $source, ?string $context = null ) {
		$lookup_context = null;
		if ( null !== $context && '' !== $context && ! self::is_builtin_strings_table_context( $context ) ) {
			$lookup_context = $context;
		}

		$entry_args = array( 'singular' => $source );
		if ( null !== $lookup_context ) {
			$entry_args['context'] = $lookup_context;
		}

		$entry = new Translation_Entry( $entry_args );
		$entry = $this->translate_entry( $entry );

		if ( ! $entry instanceof Translation_Entry || empty( $entry->translations ) ) {
			return '';
		}

		return $entry->translations[0];
	}
}
