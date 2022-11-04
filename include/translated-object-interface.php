<?php
/**
 * @package Polylang
 */

/**
 * Interface to use for object types that support translations.
 *
 * @since 3.3
 */
interface PLL_Translated_Object_Interface {

	/**
	 * Returns the translations group taxonomy name.
	 *
	 * @since 3.3
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_tax_translations();

	/**
	 * Adds hooks.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	public function init();

	/**
	 * Returns a list of object translations, given a `tax_translations` term ID.
	 *
	 * @since 3.3
	 *
	 * @param int $term_id Term ID.
	 * @return int[] An associative array of translations with language code as key and translation ID as value.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
	 */
	public function get_translations_from_term_id( $term_id );

	/**
	 * Saves translations for objects.
	 *
	 * @since 3.3
	 *
	 * @param int   $id           Object ID.
	 * @param int[] $translations An associative array of translations with language code as key and translation ID as value.
	 * @return int[] An associative array with language codes as key and object IDs as values.
	 *
	 * @phpstan-param array<non-empty-string, positive-int> $translations
	 * @phpstan-return array<non-empty-string, positive-int>
	 */
	public function save_translations( $id, $translations );

	/**
	 * Deletes a translation of an object.
	 *
	 * @since 3.3
	 *
	 * @param int $id Object ID.
	 * @return void
	 */
	public function delete_translation( $id );

	/**
	 * Returns an array of translations of an object.
	 *
	 * @since 3.3
	 *
	 * @param int $id Object ID.
	 * @return int[] An associative array of translations with language code as key and translation ID as value.
	 *
	 * @phpstan-return array<non-empty-string, positive-int>
	 */
	public function get_translations( $id );

	/**
	 * Returns the ID of the translation of an object.
	 *
	 * @since 3.3
	 *
	 * @param int                 $id   Object ID.
	 * @param PLL_Language|string $lang Language (slug or object).
	 * @return int|false Object ID of the translation, false if there is none.
	 *
	 * @phpstan-return positive-int|false
	 */
	public function get_translation( $id, $lang );

	/**
	 * Among the object and its translations, returns the ID of the object which is in `$lang`.
	 *
	 * @since 3.3
	 *
	 * @param int                     $id   Object ID.
	 * @param int|string|PLL_Language $lang Language (term_id or slug or object).
	 * @return int|false The translation object ID if exists, otherwise the passed ID, false if the passed object has no language.
	 *
	 * @phpstan-return positive-int|false
	 */
	public function get( $id, $lang );

	/**
	 * Checks if a user can synchronize translations.
	 *
	 * @since 3.3
	 *
	 * @param int $id Object ID.
	 * @return bool
	 */
	public function current_user_can_synchronize( $id );
}
