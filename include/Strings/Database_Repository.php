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
	 * Removes a translatable string by ID.
	 * Note: This only works for strings registered via WPML API (icl_register_string).
	 *
	 * @since 3.8
	 *
	 * @param string $id The identifier.
	 * @return void
	 */
	public function remove_wpml_string( string $id ): void {
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
	 * @return void
	 */
	public function save( Collection $collection, PLL_Language $language ): void {
		$mo = new PLL_MO();
		$mo->import_from_db( $language );

		foreach ( $collection as $translatable ) {
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
				$translatable->get_value(),
				$translatable->get_name(),
				$translatable->get_context(),
				$mo->translate_if_any( $translatable->get_previous_value() ),
				$translatable->get_previous_value(),
			);
			$translatable->set_value( $translation );

			$mo->add_entry(
				$mo->make_entry(
					$translatable->get_previous_value(),
					$translatable->get_value()
				)
			);
		}

		$mo->export_to_db( $language );
	}
}
