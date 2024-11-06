<?php
/**
 * @package Polylang
 */

/**
 * Manages strings translations storage
 *
 * @since 1.2
 * @since 2.1 Stores the strings in a post meta instead of post content to avoid unserialize issues (See #63)
 * @since 3.4 Stores the strings into language taxonomy term meta instead of a post meta.
 */
class PLL_MO extends MO {
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
		 */
		$strings = array();
		foreach ( $this->entries as $entry ) {
			if ( '' !== $entry->singular ) {
				$strings[] = wp_slash( array( $entry->singular, $this->translate( $entry->singular ) ) );
			}
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
			return;
		}

		foreach ( $strings as $msg ) {
			$entry = $this->make_entry( $msg[0], $msg[1] );

			if ( '' !== $entry->singular ) {
				$this->add_entry( $entry );
			}
		}

		self::$cache->set( $lang->slug, $this->entries );
	}

	/**
	 * Deletes a string
	 *
	 * @since 2.9
	 *
	 * @param string $string The source string to remove from the translations.
	 * @return void
	 */
	public function delete_entry( $string ) {
		unset( $this->entries[ $string ] );

		$language = $this->get_header( 'Language' );
		if ( ! empty( $language ) ) {
			self::$cache->set( $language, $this->entries );
		}
	}

	/**
	 * Adds an entry to the MO structure and caches it.
	 *
	 * @since 3.7
	 *
	 * @param Translation_Entry|array $entry The entry to add.
	 * @return bool True if the entry was added, false otherwise.
	 */
	public function add_entry( $entry ) {
		if ( is_array( $entry ) ) {
			$entry = new Translation_Entry( $entry );
		}

		if ( parent::add_entry( $entry ) ) {
			$language = $this->get_header( 'Language' );
			if ( ! empty( $language ) ) {
				self::$cache->set( $language, $this->entries );
			}

			return true;
		}

		return false;
	}

	/**
	 * Adds or merges an entry to the MO structure and caches it.
	 *
	 * @since 3.7
	 *
	 * @param Translation_Entry|array $entry The entry to add.
	 * @return bool True if the entry was added, false otherwise.
	 */
	public function add_entry_or_merge( $entry ) {
		if ( is_array( $entry ) ) {
			$entry = new Translation_Entry( $entry );
		}

		if ( parent::add_entry_or_merge( $entry ) ) {
			$language = $this->get_header( 'Language' );
			if ( ! empty( $language ) ) {
				self::$cache->set( $language, $this->entries );
			}

			return true;
		}

		return false;
	}
}
