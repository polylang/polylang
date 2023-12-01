<?php

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

		$has_language = $this->translatable_object->set_language( $object_id, $args['lang'] );

		if ( ! $has_language ) {
			return new WP_Error( 'pll-test-error', 'Language cannot be assigned to the given object.' );
		}

		return $object_id;
	}

	public function create_translated( array $first, array $second, array $others = array() ) {
		$others       = array_merge( array( $first ), array( $second ), $others );
		$translations = array();

		foreach ( $others as $object ) {
			if ( empty( $object['lang'] ) ) {
				throw new InvalidArgumentException( 'Please pass a language to assign to the given object.' );
			}

			$translations[ $object['lang'] ] = $this->create( $object );
		}

		return $this->translatable_object->save_translations( $translations );
	}

	public function create_translated_and_get( array $first, array $second, array $others = array() ) {
		$translations = $this->create_translated( $first, $second, $others );

		return array_map(
			array( $this, 'get_object_by_id' ),
			$translations
		);
	}
}
