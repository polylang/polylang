<?php

/**
 * Allows to easily create translator users.
 */
trait PLL_UnitTest_Translator_Trait {
	/**
	 * @var int[]
	 */
	protected static $translators = array();

	/**
	 * Deletes all translator users.
	 * Should be called in `wpTearDownAfterClass()`.
	 *
	 * @return void
	 */
	protected static function delete_translators(): void {
		array_map( array( self::class, 'delete_user' ), static::$translators );
		static::$translators = array();
	}

	/**
	 * Creates a translator user and returns its ID.
	 *
	 * @param string $lang Language slug.
	 * @return int User ID.
	 */
	protected static function get_translator_id( string $lang ): int {
		$user = self::factory()->user->create_and_get(
			array(
				'role'          => 'editor',
				'user_nicename' => "translator-{$lang}",
			)
		);
		$user->add_cap( 'manage_translations' );
		$user->add_cap( "translate_{$lang}" );

		static::$translators[] = $user->ID;

		return $user->ID;
	}
}
