<?php

/**
 * Trait that makes sure that `user_can()` is used with a post ID as argument, and not a post object.
 */
trait PLL_UnitTest_Map_Meta_Cap_Trait {
	/**
	 * Inits an assertion that will make sure we always pass an integer to our calls to `user_can()`.
	 *
	 * @return void
	 */
	protected static function assert_map_meta_cap_args() {
		add_filter(
			'map_meta_cap',
			function ( $caps, $cap, $user_id, $args ) {
				if ( empty( $args[0] ) ) {
					return $caps;
				}
				// https://github.com/WordPress/wordpress-develop/blob/c70675b7a1a04101065b0533f5c778bd92f4c69f/src/wp-includes/block-editor.php#L635
				if ( 'edit_block_binding' === $cap && $args[0] instanceof WP_Block_Editor_Context ) {
					return $caps;
				}
				// https://github.com/the-events-calendar/the-events-calendar/blob/8131277580621b35df981e0dd96b3c59505e7151/src/Tribe/Views/V2/Views/Traits/HTML_Cache.php#L368
				if ( 'read_private_posts' === $cap && 'tribe_events' === $args[0] ) {
					return $caps;
				}

				static::assertIsInt( $args[0], 'map_meta_cap $args[0] must be an object ID.' );

				return $caps;
			},
			PHP_INT_MIN,
			4
		);
	}
}
