<?php
/**
 * @package Polylang
 */

/**
 * Class for handling term slugs.
 *
 * @since 3.7
 */
class PLL_Term_Slug {

	/**
	 * @var PLL_Model
	 */
	private $model;

	/**
	 * @var string
	 */
	private $slug;

	/**
	 * @var string
	 */
	private $taxonomy;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int
	 */
	private $term_id;

	/**
	 * @var PLL_Language
	 */
	private $lang;

	/**
	 * @var int
	 */
	private $parent;

	/**
	 * Constructor.
	 *
	 * @since 3.7
	 *
	 * @param PLL_Model $model    Instance of PLL_Model.
	 * @param string    $slug     The term slug.
	 * @param string    $taxonomy The term taxonomy.
	 * @param string    $name     The term name.
	 * @param int       $term_id  The term ID if exists, or 0 if there's no need to know that we are editing an existing term.
	 */
	public function __construct( PLL_Model $model, string $slug, string $taxonomy, string $name, int $term_id = 0 ) {
		$this->model    = $model;
		$this->slug     = $slug;
		$this->taxonomy = $taxonomy;
		$this->name     = $name;
		$this->term_id  = $term_id;
	}

	/**
	 * Tells if the suffix can be added or not.
	 *
	 * @since 3.7
	 *
	 * @return bool True if the suffix can be added, false otherwise.
	 */
	private function can_add_suffix() {
		/**
		 * Filters the subsequently inserted term language.
		 *
		 * @since 3.3
		 *
		 * @param PLL_Language|null $lang     Found language object, null otherwise.
		 * @param string            $taxonomy Term taxonomy.
		 * @param string            $slug     Term slug
		 */
		$lang = apply_filters( 'pll_inserted_term_language', null, $this->taxonomy, $this->slug );
		if ( ! $lang instanceof PLL_Language ) {
			return false;
		}
		$this->lang = $lang;

		$this->parent = 0;
		if ( is_taxonomy_hierarchical( $this->taxonomy ) ) {
			/**
			 * Filters the subsequently inserted term parent.
			 *
			 * @since 3.3
			 *
			 * @param int          $parent   Parent term ID, 0 if none.
			 * @param string       $taxonomy Term taxonomy.
			 * @param string       $slug     Term slug
			 */
			$this->parent = apply_filters( 'pll_inserted_term_parent', 0, $this->taxonomy, $this->slug );

			$this->slug .= $this->maybe_get_parent_suffix();
		}

		if ( ! $this->slug ) {
			if ( $this->model->term_exists( $this->name, $this->taxonomy, $this->parent, $this->lang ) ) {
				// Returns the current empty slug if the term exists with the same name and an empty slug.
				// Same as WP does when providing a term with a name that already exists and no slug.
				return false;
			} else {
				$this->slug = sanitize_title( $this->name );
			}
		}

		if ( ! term_exists( $this->slug, $this->taxonomy ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the parent suffix for the slug only if parent slug is the same as the given one.
	 * Recursively appends the parents slugs like WordPress does.
	 *
	 * @since 3.3
	 * @since 3.7 Moved from `PLL_Share_Term_Slug`to `PLL_Term_Slug`.
	 *
	 * @return string Parents slugs if they are the same as the child slug, empty string otherwise.
	 */
	private function maybe_get_parent_suffix() {
		$parent_suffix = '';
		$the_parent    = get_term( $this->parent, $this->taxonomy );

		if ( ! $the_parent instanceof WP_Term || $the_parent->slug !== $this->slug ) {
			return $parent_suffix;
		}

		/**
		 * Mostly copied from {@see wp_unique_term_slug()}.
		 */
		while ( ! empty( $the_parent ) ) {
			$parent_term = get_term( $the_parent, $this->taxonomy );
			if ( ! $parent_term instanceof WP_Term ) {
				break;
			}
			$parent_suffix .= '-' . $parent_term->slug;
			if ( ! term_exists( $this->slug . $parent_suffix ) ) {
				break;
			}
			$the_parent = $parent_term->parent;
		}

		return $parent_suffix;
	}

	/**
	 * Returns the term slug, suffixed or not.
	 *
	 * @since 3.7
	 *
	 * @param string $separator The separator for the slug suffix.
	 * @return string The suffixed slug, or not if the lang isn't defined.
	 */
	public function get_suffixed_slug( string $separator ): string {
		if ( ! $this->can_add_suffix() ) {
			return $this->slug;
		}

		$term_id = (int) $this->model->term_exists_by_slug( $this->slug, $this->lang, $this->taxonomy, $this->parent );

		// If no term exists in the given language with that slug, it can be created, or if we are editing the existing term.
		if ( ! $term_id || $this->term_id === $term_id ) {
			return $this->slug . $separator . $this->lang->slug;
		}

		return $this->slug;
	}
}
