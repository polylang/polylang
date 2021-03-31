<?php
/**
 * @package Polylang
 */

/**
 * Class PLL_Translated_Object_With_Capapbilties
 *
 * Decorates a {@see PLL_Translated_Object_Interface} to protect sensitive methods that can modify the database.
 */
class PLL_Translated_Object_With_Capapbilty implements PLL_Translated_Object_Interface {

	/**
	 * @var PLL_Translated_Object_Interface
	 */
	private $translated_object;

	/**
	 * @var string A WordPress capability, {@see https://wordpress.org/support/article/roles-and-capabilities/}.
	 */
	private $capabilty;

	/**
	 * PLL_Translated_Object_With_Capapbilties constructor.
	 *
	 * @param PLL_Translated_Object_Interface $translated_object
	 * @param string                          $capability
	 */
	public function __construct( $translated_object, $capability ) {
		$this->translated_object = $translated_object;
		$this->capabilty = $capability;
	}

	/**
	 * Pass through calls to methods that are not defined in the {@see PLL_Translated_Object_Interface}.
	 *
	 * @since 3.1
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return mixed|void
	 */
	public function __call( $name, $args ) {
		return call_user_func_array( array( $this->translated_object, $name ), $args );
	}

	/**
	 * Stores the language in the database.
	 *
	 * @since 0.6
	 *
	 * @param int                     $id   Object id.
	 * @param int|string|PLL_Language $lang Language (term_id or slug or object).
	 * @return void
	 */
	public function set_language( $id, $lang ) {
		if ( current_user_can( $this->capabilty, $id ) ) {
			$this->translated_object->set_language( $id, $lang );
		}
	}

	/**
	 * Returns the language of an object.
	 *
	 * @since 0.1
	 *
	 * @param int $id Object id.
	 * @return PLL_Language|false PLL_Language object, false if no language is associated to that object.
	 */
	public function get_language( $id ) {
		return $this->translated_object->get_language( $id );
	}

	/**
	 * Updates the language of an object, and sets its translations group accordingly.
	 *
	 * @since 3.1
	 *
	 * @param int          $id Object id.
	 * @param PLL_Language $lang New language to assign to the object.
	 *
	 * @return bool True if succeeded, false if failed to update.
	 */
	public function update_language( $id, $lang ) {
		if ( current_user_can( $this->capabilty, $id ) ) {
			return $this->translated_object->update_language( $id, $lang );
		}

		return false;
	}

	/**
	 * Saves translations for posts or terms.
	 *
	 * @since 0.5
	 *
	 * @param int   $id           Object id ( typically a post_id or term_id ).
	 * @param int[] $translations An associative array of translations with language code as key and translation id as value.
	 * @return void
	 */
	public function save_translations( $id, $translations ) {
		if ( current_user_can( $this->capabilty, $id ) ) {
			$this->translated_object->save_translations( $id, $translations );
		}
	}

	/**
	 * Deletes a translation of a post or term.
	 *
	 * @since 0.5
	 *
	 * @param int $id Object id ( typically a post_id or term_id ).
	 * @return void
	 */
	public function delete_translation( $id ) {
		if ( current_user_can( $this->capabilty, $id ) ) {
			$this->translated_object->delete_translation( $id );
		}
	}

	/**
	 * Returns an array of translations of a post or term.
	 *
	 * @since 0.5
	 *
	 * @param int $id Object id ( typically a post_id or term_id ).
	 * @return int[] An associative array of translations with language code as key and translation id as value.
	 */
	public function get_translations( $id ) {
		return $this->translated_object->get_translations( $id );
	}

	/**
	 * Returns the id of the translation of a post or term.
	 *
	 * @since 0.5
	 *
	 * @param int                 $id   Object id ( typically a post_id or term_id ).
	 * @param PLL_Language|string $lang Language ( slug or object ).
	 * @return int|false Object id of the translation, false if there is none.
	 */
	public function get_translation( $id, $lang ) {
		return $this->translated_object->get_translation( $id, $lang );
	}

	/**
	 * Among the object and its translations, returns the id of the object which is in $lang
	 *
	 * @since 0.1
	 *
	 * @param int                     $id   Object id ( typically a post_id or term_id ).
	 * @param int|string|PLL_Language $lang Language ( term_id or slug or object ).
	 * @return int|false The translation object id if exists, otherwise the passed id, false if the passed object has no language.
	 */
	public function get( $id, $lang ) {
		return $this->translated_object->get( $id, $lang );
	}

	/**
	 * Check if a user can synchronize translations.
	 *
	 * @since 2.6
	 *
	 * @param int $id Object id.
	 * @return bool
	 */
	public function current_user_can_synchronize( $id ) {
		return $this->translated_object->current_user_can_synchronize( $id );
	}
}
