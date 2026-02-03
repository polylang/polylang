<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Polylang's options registry.
 *
 * @since 3.7
 */
class Registry {
	protected const OPTIONS = array(
		// URL modifications.
		Business\Force_Lang::class,
		Business\Domains::class,
		Business\Hide_Default::class,
		Business\Rewrite::class,
		Business\Redirect_Lang::class,
		// Detect browser language.
		Business\Browser::class,
		// Media.
		Business\Media_Support::class,
		// Custom post types and taxonomies.
		Business\Post_Types::class,
		Business\Taxonomies::class,
		// Synchronization.
		Business\Sync::class,
		// Internal.
		Business\Default_Lang::class,
		Business\Nav_Menus::class,
		// Read only.
		Business\First_Activation::class,
		Business\Previous_Version::class,
		Business\Version::class,
	);

	/**
	 * Registers Polylang's options.
	 *
	 * @since 3.7
	 *
	 * @param Options $options Instance of the options.
	 * @return void
	 */
	public static function register( Options $options ): void {
		array_map( array( $options, 'register' ), static::OPTIONS );
	}
}
