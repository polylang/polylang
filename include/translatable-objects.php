<?php
/**
 * @package Polylang
 */

/**
 * Registry for all translatable objects.
 *
 * @since 3.4
 *
 * @phpstan-implements IteratorAggregate<non-empty-string, PLL_Translatable_Object>
 */
class PLL_Translatable_Objects implements IteratorAggregate {

	/**
	 * List of registered objects.
	 *
	 * @var PLL_Translatable_Object[] Array keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-var array<non-empty-string, PLL_Translatable_Object>
	 */
	private $objects = array();

	/**
	 * Registers a translatable object.
	 *
	 * @since 3.4
	 *
	 * @param PLL_Translatable_Object $object The translatable object to register.
	 * @return PLL_Translatable_Object
	 */
	public function register( PLL_Translatable_Object $object ) {
		if ( ! isset( $this->objects[ $object->get_type() ] ) ) {
			$this->objects[ $object->get_type() ] = $object;
		}

		return $this->objects[ $object->get_type() ];
	}

	/**
	 * Returns all registered translatable objects.
	 *
	 * @since 3.4
	 *
	 * @return ArrayIterator Iterator on $objects array property. Keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-return ArrayIterator<string, PLL_Translatable_Object>
	 */
	public function getIterator() {
		return new ArrayIterator( $this->objects );
	}

	/**
	 * Returns a translatable object, given an object type.
	 *
	 * @since 3.4
	 *
	 * @param string $object_type The object type.
	 * @return PLL_Translatable_Object|null
	 */
	public function get( $object_type ) {
		if ( ! isset( $this->objects[ $object_type ] ) ) {
			return null;
		}

		return $this->objects[ $object_type ];
	}

	/**
	 * Returns all translatable objects except post one.
	 *
	 * @since 3.4
	 *
	 * @return PLL_Translatable_Object[] An array of secondary translatable objects. Array keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-return array<non-empty-string, PLL_Translatable_Object>
	 */
	public function get_secondary_translatable_objects() {
		return array_diff_key( $this->objects, array( 'post' => null ) );
	}

	/**
	 * Returns taxonomy names to manage language and translations.
	 *
	 * @since 3.4
	 *
	 * @param string[] $filter An array on value to filter taxonomy names to return.
	 * @return string[] Taxonomy names.
	 *
	 * @phpstan-param array<'language'|'translations'> $filter
	 * @phpstan-return list<non-empty-string>
	 */
	public function get_taxonomy_names( $filter = array( 'language', 'translations' ) ) {
		$taxonomies = array();

		foreach ( $this->objects as $object ) {
			if ( in_array( 'language', $filter ) ) {
				$taxonomies[] = $object->get_tax_language();
			}

			if ( in_array( 'translations', $filter ) && $object instanceof PLL_Translated_Object ) {
				$taxonomies[] = $object->get_tax_translations();
			}
		}

		return $taxonomies;
	}
}
