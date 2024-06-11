<?php
/**
 * @package Polylang
 */

/**
 * @since 3.7
 */
class PLL_Term_Slug {

	/**
	 * @var PLL_Model
	 */
	private $model;

	/**
	 * Constructor
	 *
	 * @since 3.7
	 *
	 * @param PLL_Model $model  Instance of the current PLL_Model.
	 */
	public function __construct( PLL_Model $model ) {
		$this->model = $model;
	}

	/**
	 * Appends language slug to the term slug if needed.
	 *
	 * @since 3.7
	 *
	 * @param string $name     Term name.
	 * @param string $slug     Term slug.
	 * @param string $taxonomy Term taxonomy.
	 * @return array Term data with slug, and optionally the term ID and language.
	 */
	public function set_pre_term_slug( $name, $slug, $taxonomy ) {
		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return array( 'slug' => $slug );
		}

		/**
		 * Filters the subsequently inserted term language.
		 *
		 * @since 3.3
		 *
		 * @param PLL_Language|null $lang     Found language object, null otherwise.
		 * @param string            $taxonomy Term taxonomy.
		 * @param string             $slug     Term slug
		 */
		$lang = apply_filters( 'pll_inserted_term_language', null, $taxonomy, $slug );
		if ( ! $lang instanceof PLL_Language ) {
			return array( 'slug' => $slug );
		}

		$parent = 0;
		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			/**
			 * Filters the subsequently inserted term parent.
			 *
			 * @since 3.3
			 *
			 * @param int          $parent   Parent term ID, 0 if none.
			 * @param string       $taxonomy Term taxonomy.
			 * @param string       $slug     Term slug
			 */
			$parent = apply_filters( 'pll_inserted_term_parent', 0, $taxonomy, $slug );

			$slug .= $this->maybe_get_parent_suffix( $parent, $taxonomy, $slug );
		}

		if ( ! $slug ) {
			if ( $this->model->term_exists( $name, $taxonomy, $parent, $lang ) ) {
				// Returns the current empty slug if the term exists with the same name and an empty slug.
				// Same as WP does when providing a term with a name that already exists and no slug.
				return array( 'slug' => $slug );
			} else {
				$slug = sanitize_title( $name );
			}
		}

		if ( ! term_exists( $slug, $taxonomy ) ) {
			return array( 'slug' => $slug );
		}

		$term_id = (int) $this->model->term_exists_by_slug( $slug, $lang, $taxonomy, $parent );

		return array(
			'slug'    => $slug,
			'term_id' => $term_id,
			'lang'    => $lang,
		);
	}

	/**
	 * Returns the parent suffix for the slug only if parent slug is the same as the given one.
	 * Recursively appends the parents slugs like WordPress does.
	 *
	 * @since 3.3
	 * @since 3.7 Moved from `PLL_Share_Term_Slug`to `PLL_Term_Slug`.
	 *
	 * @param int    $parent   Parent term ID.
	 * @param string $taxonomy Parent taxonomy.
	 * @param string $slug     Child term slug.
	 * @return string Parents slugs if they are the same as the child slug, empty string otherwise.
	 */
	private function maybe_get_parent_suffix( $parent, $taxonomy, $slug ) {
		$parent_suffix = '';
		$the_parent    = get_term( $parent, $taxonomy );

		if ( ! $the_parent instanceof WP_Term || $the_parent->slug !== $slug ) {
			return $parent_suffix;
		}

		/**
		 * Mostly copied from {@see wp_unique_term_slug()}.
		 */
		while ( ! empty( $the_parent ) ) {
			$parent_term = get_term( $the_parent, $taxonomy );
			if ( ! $parent_term instanceof WP_Term ) {
				break;
			}
			$parent_suffix .= '-' . $parent_term->slug;
			if ( ! term_exists( $slug . $parent_suffix ) ) {
				break;
			}
			$the_parent = $parent_term->parent;
		}

		return $parent_suffix;
	}
}
