<?php

use PHPUnit\Framework\Assert;

trait Factory_For_Translated_Object_Trait {
	/**
	 * @var PLL_Translated_Post
	 */
	protected $translatable_object;

	public function create( $args = array(), $generation_definitions = null ) {
		$object_id = parent::create( $args, $generation_definitions );

		if ( empty( $args['lang'] ) || ! $object_id || is_wp_error( $object_id ) ) {
			return $object_id;
		}

		$existing_language = $this->translatable_object->get_language( $object_id );
		if ( $existing_language instanceof PLL_Language && $existing_language->slug === $args['lang'] ) {
			return $object_id;
		}

		$has_language = $this->translatable_object->set_language( $object_id, $args['lang'] );

		if ( ! $has_language ) {
			return new WP_Error( 'pll-test-error', 'Could not assign a language to the created object.' );
		}

		return $object_id;
	}

	/**
	 * Create an object and expect an error.
	 *
	 * @param array $args The arguments for the object creation.
	 * @param array $generation_definitions The generation definitions.
	 * @throws InvalidArgumentException If the object is created successfully.
	 * @return void
	 */
	public function create_and_expect_error( $args = array(), $generation_definitions = null ) {
		try {
			$object_id = $this->create( $args, $generation_definitions );
		} catch ( WP_UnitTest_Factory_Exception $e ) {
			$object_id = new WP_Error( 'pll-test-error', $e->getMessage() );
		}

		Assert::assertInstanceOf( WP_Error::class, $object_id );
	}

	public function create_translated( array ...$objects ) {
		$translations = array();

		foreach ( $objects as $object ) {
			if ( empty( $object['lang'] ) ) {
				throw new InvalidArgumentException( 'A language is required for all translated objects.' );
			}

			$translations[ $object['lang'] ] = $this->create( $object );
		}

		return $this->translatable_object->save_translations( reset( $translations ), $translations );
	}

	public function create_translated_and_get( array ...$objects ) {
		$translations = $this->create_translated( ...$objects );

		return array_map(
			array( $this, 'get_object_by_id' ),
			$translations
		);
	}
}
