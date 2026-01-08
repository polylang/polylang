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
	protected $model;

	/**
	 * @var string
	 */
	protected $slug;

	/**
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * The term ID if editing an existing term, 0 otherwise.
	 *
	 * @var int
	 */
	protected $term_id = 0;

	/**
	 * @var PLL_Language
	 */
	protected $lang;

	/**
	 * @var int
	 */
	protected $parent;

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
	 * @since 3.8 Changed visibility from private to protected to allow to reuse this logic.
	 *
	 * @return bool True if the suffix can be added, false otherwise.
	 */
	protected function can_add_suffix() {
		/**
		 * Filters the subsequently inserted term language.
		 *
		 * @since 3.3
		 *
		 * @param PLL_Language|null $lang     Found language object, null otherwise.
		 * @param string            $taxonomy Term taxonomy.
		 * @param string            $slug     Term slug.
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
			 * @param int    $parent   Parent term ID, 0 if none.
			 * @param string $taxonomy Term taxonomy.
			 * @param string $slug     Term slug.
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

		// Check if the slug exists globally, excluding the current term if we're editing.
		if ( ! $this->model->term_exists_by_slug_globally( $this->slug, $this->taxonomy, $this->term_id ) ) {
			// Slug doesn't exist anywhere: no suffix needed.
			return false;
		}

		// Slug exists for other terms: suffix needed.
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
	 * Applies the suffix logic.
	 *
	 * This method determines when to add a language suffix based on term conflicts.
	 * Can be overridden by subclasses to implement different suffix behaviors.
	 *
	 * @since 3.8
	 *
	 * @param string $separator The separator for the slug suffix.
	 * @return string The slug with or without suffix.
	 */
	protected function apply_suffix_logic( string $separator ): string {
		$term_id = (int) $this->model->term_exists_by_slug_and_language( $this->slug, $this->lang, $this->taxonomy, $this->parent );

		// Editing the same term: preserve slug without suffix.
		if ( $term_id && $this->term_id === $term_id ) {
			return $this->slug;
		}

		// No term exists in this language: add suffix.
		if ( ! $term_id ) {
			return $this->slug . $separator . $this->lang->slug;
		}

		// Different term exists in same language: no suffix, WordPress will handle uniqueness.
		return $this->slug;
	}

	/**
	 * Returns the term slug, suffixed or not.
	 *
	 * @since 3.7
	 *
	 * @param string $separator The separator for the slug suffix.
	 * @return string The slug with or without suffix.
	 */
	public function get_suffixed_slug( string $separator ): string {
		if ( ! $this->can_add_suffix() ) {
			return $this->slug;
		}

		return $this->apply_suffix_logic( $separator );
	}
}
