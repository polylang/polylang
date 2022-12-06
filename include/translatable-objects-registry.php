<?php
/**
 * @package Polylang
 */

/**
 * Registry for all translatable objects.
 *
 * @since 3.4
 */
class PLL_Translatable_Objects_Registry {

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
	 * @return PLL_Translatable_Object[] Array keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-return array<non-empty-string, PLL_Translatable_Object>
	 */
	public function get_all() {
		return $this->objects;
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
}
