<?php
/**
 * @package Polylang
 */

/**
 * Interface to use for object types that support at least one language.
 *
 * @since 3.3
 */
interface PLL_Object_With_Language_Interface {

	/**
	 * Returns the language taxonomy name.
	 *
	 * @since 3.3
	 *
	 * @return string
	 *
	 * @phpstan-return non-empty-string
	 */
	public function get_tax_language();

	/**
	 * Adds hooks.
	 *
	 * @since 3.3
	 *
	 * @return self
	 */
	public function init();

	/**
	 * Stores the object's language in the database.
	 *
	 * @since 3.3
	 *
	 * @param int                     $id   Object ID.
	 * @param int|string|PLL_Language $lang Language (term_id, slug, or object).
	 * @return bool True on success (or if the given language is already assigned to the object). False otherwise.
	 */
	public function set_language( $id, $lang );

	/**
	 * Returns the language of an object.
	 *
	 * @since 3.3
	 *
	 * @param int $id Object ID.
	 * @return PLL_Language|false A `PLL_Language` object, `false` if no language is associated to that object.
	 */
	public function get_language( $id );

	/**
	 * Assigns a new language to an object.
	 *
	 * @since 3.3
	 *
	 * @param int          $id   Object ID.
	 * @param PLL_Language $lang New language to assign to the object.
	 * @return bool True on success (or if the given language is already assigned to the object). False otherwise.
	 */
	public function update_language( $id, PLL_Language $lang );

	/**
	 * Wraps `wp_get_object_terms()` to cache it and return only one object.
	 * Inspired by the WordPress function `get_the_terms()`.
	 *
	 * @since 3.3
	 *
	 * @param int    $id       Object ID.
	 * @param string $taxonomy Polylang taxonomy depending if we are looking for a post (or term, or else) language.
	 * @return WP_Term|false The term associated to the object in the requested taxonomy if it exists, `false` otherwise.
	 */
	public function get_object_term( $id, $taxonomy );

	/**
	 * A JOIN clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 3.3
	 *
	 * @param string $alias Optional alias for object table.
	 * @return string The JOIN clause.
	 *
	 * @phpstan-return non-empty-string
	 */
	public function join_clause( $alias = '' );

	/**
	 * A WHERE clause to add to sql queries when filtering by language is needed directly in query.
	 *
	 * @since 3.3
	 *
	 * @param PLL_Language|PLL_Language[]|string|string[] $lang A `PLL_Language` object, or a comma separated list of language slugs, or an array of language slugs or objects.
	 * @return string The WHERE clause.
	 */
	public function where_clause( $lang );
}
