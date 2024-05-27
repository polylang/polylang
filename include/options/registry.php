<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Options;

use WP_Syntex\Polylang\Options\Business;
use WP_Syntex\Polylang\Options\Primitive;

defined( 'ABSPATH' ) || exit;

/**
 * Polylang's options registry.
 *
 * @since 3.7
 */
class Registry {
	const OPTIONS = array(
		// URL modifications.
		'force_lang'          => array(
			'class'       => Primitive\Choice::class,
			'default'     => 1,
			'description' => 'Determine how the current language is defined.',
			'args'        => array( array( 0, 1, 2, 3 ) ),
		),
		'domains'             => array(
			'class'       => Business\Domains::class,
			'default'     => array(),
			'description' => 'Domains used when the language is set from different domains.',
		),
		'hide_default'        => array(
			'class'       => Business\Hide_Default::class,
			'default'     => true,
			'description' => 'Remove language code in URL for default language: true to hide, false to display.',
		),
		'rewrite'             => array(
			'class'       => Primitive\Boolean::class,
			'default'     => true,
			'description' => 'Remove /language/ in pretty permalinks: true to remove, false to keep.',
		),
		'redirect_lang'       => array(
			'class'       => Primitive\Boolean::class,
			'default'     => false,
			'description' => 'Remove the page name or page id from the URL of the front page: true to remove, false to keep.',
		),
		// Detect browser language.
		'browser'             => array(
			'class'       => Business\Browser::class,
			'default'     => false,
			'description' => 'Detect browser language on front page: true to detect, false to not detect.',
		),
		// Media.
		'media_support'       => array(
			'class'       => Primitive\Boolean::class,
			'default'     => false,
			'description' => 'Translate media: true to translate, false to not translate.',
		),
		// Custom post types and taxonomies.
		'post_types'          => array(
			'class'       => Business\Post_Types::class,
			'default'     => array(),
			'description' => 'List of post types to translate.',
			'args'        => array( 'string' ),
		),
		'taxonomies'          => array(
			'class'       => Business\Taxonomies::class,
			'default'     => array(),
			'description' => 'List of taxonomies to translate.',
			'args'        => array( 'string' ),
		),
		// Synchronization.
		'sync'                => array(
			'class'       => Business\Sync::class,
			'default'     => array(),
			'description' => 'List of data to synchronize.',
			'args'        => array( 'string' ),
		),
		// Internal.
		'default_lang'        => array(
			'class'       => Business\Language_Slug::class,
			'default'     => '',
			'description' => 'Slug of the default language.',
		),
		'nav_menus'           => array(
			'class'       => Business\Nav_Menu::class,
			'default'     => array(),
			'description' => 'Translated navigation menus for each theme.',
		),
		'language_taxonomies' => array(
			'class'       => Business\Language_Taxonomies::class,
			'default'     => array(),
			'description' => 'List of language taxonomies used for custom DB tables.',
			'args'        => array( 'string' ),
		),
		// Read only.
		'first_activation'    => array(
			'class'       => Primitive\Integer::class,
			'default'     => array(),
			'description' => 'Time of first activation of Polylang.',
		),
		'previous_version'    => array(
			'class'       => Primitive\String_Type::class,
			'default'     => '',
			'description' => "Polylang's previous version.",
		),
		'version'             => array(
			'class'       => Primitive\String_Type::class,
			'default'     => '',
			'description' => "Polylang's version.",
		),
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

		foreach ( static::OPTIONS as $option_name => $option_args ) {
			$args = $option_args['args'] ?? array();
			$options->register( $option_args['class'], $option_name, $option_args['default'], $option_args['description'], ...$args );
		}
	}
}
