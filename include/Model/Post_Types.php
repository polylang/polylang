<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Model;

use PLL_Translated_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Model for post types translated by Polylang.
 *
 * @since 3.7
 */
class Post_Types {
	/**
	 * Translated post model.
	 *
	 * @var PLL_Translated_Post
	 */
	public $translated_object;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Translated_Post $translated_object Posts model.
	 */
	public function __construct( PLL_Translated_Post $translated_object ) {
		$this->translated_object = $translated_object;
	}

	/**
	 * Returns post types that need to be translated.
	 * The post types list is cached for better better performance.
	 * The method waits for 'after_setup_theme' to apply the cache
	 * to allow themes adding the filter in functions.php.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Model::get_translated_post_types()` to `WP_Syntex\Polylang\Model\Taxonomies::get_translated()`.
	 *
	 * @param bool $filter True if we should return only valid registered post types.
	 * @return string[] Post type names for which Polylang manages languages and translations.
	 */
	public function get_translated( $filter = true ): array {
		return $this->translated_object->get_translated_object_types( $filter );
	}

	/**
	 * Returns true if Polylang manages languages and translations for this post type.
	 *
	 * @since 1.2
	 * @since 3.7 Moved from `PLL_Model::is_translated_post_type()` to `WP_Syntex\Polylang\Model\Taxonomies::is_translated()`.
	 *
	 * @param string|string[] $post_type Post type name or array of post type names.
	 * @return bool
	 */
	public function is_translated( $post_type ): bool {
		if ( empty( array_filter( (array) $post_type ) ) ) {
			return false;
		}

		/** @phpstan-var non-empty-array<non-empty-string>|non-empty-string $post_type */
		return $this->translated_object->is_translated_object_type( $post_type );
	}
}
