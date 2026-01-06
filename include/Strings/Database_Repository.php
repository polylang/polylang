<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use PLL_MO;
use PLL_Language;
use PLL_Admin_Strings;

/**
 * Database repository for storing and retrieving Translatable string entities and their translations.
 *
 * @since 3.8
 */
class Database_Repository {
	/**
	 * Saves a collection of registered strings.
	 * Note: This integrates with PLL_Admin_Strings for backwards compatibility.
	 *
	 * @since 3.8
	 *
	 * @param Collection $collection The collection to save.
	 * @return void
	 */
	public function save( Collection $collection ): void {
		foreach ( $collection->all() as $translatable ) {
			PLL_Admin_Strings::register_string(
				$translatable->get_name(),
				$translatable->get_value(),
				$translatable->get_context(),
				$translatable->is_multiline()
			);
		}
	}

	/**
	 * Finds all registered translatables from PLL_Admin_Strings.
	 *
	 * @since 3.8
	 *
	 * @return Collection
	 */
	public function find_all(): Collection {
		$strings = PLL_Admin_Strings::get_strings();
		$translatables = array();

		foreach ( $strings as $string_data ) {
			$translatable = new Translatable(
				$string_data['string'],
				$string_data['name'],
				$string_data['context'] ?? null,
				$string_data['multiline'] ?? false,
				$string_data['sanitize_callback'] ?? null
			);
			$translatables[ $translatable->get_id() ] = $translatable;
		}

		return new Collection( $translatables );
	}

	/**
	 * Finds a translatable by ID.
	 *
	 * @since 3.8
	 *
	 * @param string $id The identifier (md5 hash of the string).
	 * @return Translatable|null The translatable if found, null otherwise.
	 */
	public function find_by_id( string $id ): ?Translatable {
		$strings = PLL_Admin_Strings::get_strings();

		foreach ( $strings as $string_data ) {
			if ( md5( $string_data['string'] ) === $id ) {
				return new Translatable(
					$string_data['string'],
					$string_data['name'],
					$string_data['context'] ?? null,
					$string_data['multiline'] ?? false,
					$string_data['sanitize_callback'] ?? null
				);
			}
		}

		return null;
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
	public function remove( string $id ): void {
		$strings = PLL_Admin_Strings::get_strings();

		foreach ( $strings as $string_data ) {
			if ( md5( $string_data['string'] ) === $id && function_exists( 'icl_unregister_string' ) ) {
				icl_unregister_string( $string_data['context'], $string_data['name'] );
				break;
			}
		}
	}

	/**
	 * Saves translations for a language.
	 *
	 * @since 3.8
	 *
	 * @param Collection            $collection The collection of translatables.
	 * @param PLL_Language          $language The language to save translations for.
	 * @param array<string, string> $translations Map of translatable IDs to translation values.
	 * @return void
	 */
	public function save_translations( Collection $collection, PLL_Language $language, array $translations ): void {
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		foreach ( $collection->all() as $translatable ) {
			$id = $translatable->get_id();

			if ( ! isset( $translations[ $id ] ) ) {
				continue;
			}

			$translation = $translations[ $id ];

			/**
			 * Filters the translation before it is saved in DB.
			 *
			 * @since 3.8
			 *
			 * @param string $translation The translation value.
			 * @param string $name      The name as defined in pll_register_string.
			 * @param string $context   The context as defined in pll_register_string.
			 * @param string $original  The original string to translate.
			 * @param string $previous  The previous translation if any.
			 */
			$translation = apply_filters(
				'pll_sanitize_string_translation',
				$translation,
				$translatable->get_name(),
				$translatable->get_context(),
				$translatable->get_value(),
				$mo->translate_if_any( $translatable->get_value() )
			);

			$mo->add_entry(
				$mo->make_entry(
					$translatable->get_value(),
					$translation
				)
			);
		}

		$mo->export_to_db( $language );
	}

	/**
	 * Gets translations for a language.
	 *
	 * @since 3.8
	 *
	 * @param Collection   $collection The collection of translatables.
	 * @param PLL_Language $language The language to get translations for.
	 * @return array<string, string> Map of translatable IDs to translation values.
	 */
	public function get_translations( Collection $collection, PLL_Language $language ): array {
		$mo = new PLL_MO();
		$mo->import_from_db( $language );
		$translations = array();

		foreach ( $collection->all() as $translatable ) {
			$translation = $mo->translate_if_any( $translatable->get_value() );
			if ( '' !== $translation ) {
				$translations[ $translatable->get_id() ] = $translation;
			}
		}

		return $translations;
	}
}
