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
	const OPTIONS = array(
		// URL modifications.
		'force_lang'          => Business\Force_Lang::class,
		'domains'             => Business\Domains::class,
		'hide_default'        => Business\Hide_Default::class,
		'rewrite'             => Business\Rewrite::class,
		'redirect_lang'       => Business\Redirect_Lang::class,
		// Detect browser language.
		'browser'             => Business\Browser::class,
		// Media.
		'media_support'       => Business\Media_Support::class,
		// Custom post types and taxonomies.
		'post_types'          => Business\Post_Types::class,
		'taxonomies'          => Business\Taxonomies::class,
		// Synchronization.
		'sync'                => Business\Sync::class,
		// Internal.
		'default_lang'        => Business\Language_Slug::class,
		'nav_menus'           => Business\Nav_Menus::class,
		'language_taxonomies' => Business\Language_Taxonomies::class,
		// Read only.
		'first_activation'    => Business\First_Activation::class,
		'previous_version'    => Business\Previous_Version::class,
		'version'             => Business\Version::class,
	);

	/**
	 * Registers Polylang's options.
	 *
	 * @since 3.7
	 *
	 * @param Options $options Instance of the options.
	 * @return void
	 */
	public static function register_options( $options ): void {
		if ( ! $options instanceof Options ) {
			// Somebody is messing up our hooks.
			return;
		}

		foreach ( static::OPTIONS as $option => $class ) {
			$options->register( $class, $option );
		}
	}
}
