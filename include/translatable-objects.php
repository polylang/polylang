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
 * @phpstan-type TranslatedObjectWithTypes PLL_Translated_Object&PLL_Translatable_Object_With_Types_Interface
 * @phpstan-type TranslatableObjectWithTypes PLL_Translatable_Object&PLL_Translatable_Object_With_Types_Interface
 */
class PLL_Translatable_Objects implements IteratorAggregate {

	/**
	 * Type of the main translatable object.
	 *
	 * @var string
	 */
	private $main_type = '';

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
		if ( empty( $this->main_type ) ) {
			$this->main_type = $object->get_type();
		}

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
	#[\ReturnTypeWillChange]
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
	 *
	 * @phpstan-return (
	 *     $object_type is 'post' ? TranslatedObjectWithTypes : (
	 *         $object_type is 'term' ? TranslatedObjectWithTypes : (
	 *             TranslatedObjectWithTypes|TranslatableObjectWithTypes|PLL_Translated_Object|PLL_Translatable_Object|null
	 *         )
	 *     )
	 * )
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
		return array_diff_key( $this->objects, array( $this->main_type => null ) );
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
			if ( in_array( 'language', $filter, true ) ) {
				$taxonomies[] = $object->get_tax_language();
			}

			if ( in_array( 'translations', $filter, true ) && $object instanceof PLL_Translated_Object ) {
				$taxonomies[] = $object->get_tax_translations();
			}
		}

		return $taxonomies;
	}
}
