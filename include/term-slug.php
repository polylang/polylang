<?php
/**
 * @package Polylang
 */

/**
 * @since 3.7
 */
class PLL_Term_Slug {

	/**
	 * @var PLL_Translated_Term
	 */
	private $translated_term;

	/**
	 * @var PLL_Language
	 */
	private $lang;

	/**
	 * @var int
	 */
	private $parent;

	/**
	 * @var string
	 */
	private $taxonomy;

	/**
	 * @var string
	 */
	private $slug;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * Constructor
	 *
	 * @since 3.7
	 *
	 * @param PLL_Translated_Term $translated_term Term translation object.
	 * @param string              $slug            The term slug.
	 * @param string              $taxonomy        The term taxonomy.
	 * @param string              $name            The term name.
	 */
	public function __construct( PLL_Translated_Term $translated_term, string $slug, string $taxonomy, string $name ) {
		$this->translated_term = $translated_term;
		$this->slug            = $slug;
		$this->taxonomy        = $taxonomy;
		$this->name            = $name;
	}

	/**
	 * Tells if the suffix can be added or not.
	 *
	 * @since 3.7
	 *
	 * @return bool True if the suffix can be added, false otherwise.
	 */
	public function can_add_suffix() {
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
			if ( $this->term_exists( $this->name, $this->taxonomy, $this->parent, $this->lang ) ) {
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
	 * @param string $separator The separator for the slug suffix, or empty.
	 * @return string
	 */
	public function get( string $separator = '' ): string {
		if ( empty( $separator ) ) {
			return $this->slug;
		}

		return $this->slug . $separator . $this->lang->slug;
	}

	/**
	 * Returns the existing term ID.
	 *
	 * @since 3.7
	 *
	 * @return int
	 */
	public function get_term_id(): int {
		return (int) $this->term_exists_by_slug( $this->slug, $this->lang, $this->taxonomy, $this->parent );
	}

	/**
	 * It is possible to have several terms with the same name in the same taxonomy ( one per language )
	 * but the native term_exists() will return true even if only one exists.
	 * So here the function adds the language parameter.
	 *
	 * @since 1.4
	 *
	 * @param string       $term_name The term name.
	 * @param string       $taxonomy  Taxonomy name.
	 * @param int          $parent    Parent term id.
	 * @param PLL_Language $language  The language slug or object.
	 * @return int The `term_id` of the found term. 0 otherwise.
	 *
	 * @phpstan-return int<0, max>
	 */
	public function term_exists( $term_name, $taxonomy, $parent, PLL_Language $language ) {
		global $wpdb;

		$term_name = trim( wp_unslash( $term_name ) );
		$term_name = _wp_specialchars( $term_name );

		$select = "SELECT t.term_id FROM $wpdb->terms AS t";
		$join = " INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id";
		$join .= $this->translated_term->join_clause();
		$where = $wpdb->prepare( ' WHERE tt.taxonomy = %s AND t.name = %s', $taxonomy, $term_name );
		$where .= $this->translated_term->where_clause( $language );

		if ( $parent > 0 ) {
			$where .= $wpdb->prepare( ' AND tt.parent = %d', $parent );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		$term_id = $wpdb->get_var( $select . $join . $where );
		return max( 0, (int) $term_id );
	}

	/**
	 * Checks if a term slug exists in a given language, taxonomy, hierarchy.
	 *
	 * @since 1.9
	 * @since 2.8 Moved from PLL_Share_Term_Slug::term_exists() to PLL_Model::term_exists_by_slug().
	 *
	 * @param string       $slug     The term slug to test.
	 * @param PLL_Language $language The language slug or object.
	 * @param string       $taxonomy Optional taxonomy name.
	 * @param int          $parent   Optional parent term id.
	 * @return int The `term_id` of the found term. 0 otherwise.
	 */
	public function term_exists_by_slug( $slug, PLL_Language $language, $taxonomy = '', $parent = 0 ) {
		global $wpdb;

		$select = "SELECT t.term_id FROM {$wpdb->terms} AS t";
		$join   = " INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id";
		$join  .= $this->translated_term->join_clause();
		$where  = $wpdb->prepare( ' WHERE t.slug = %s', $slug );
		$where .= $this->translated_term->where_clause( $language );

		if ( ! empty( $taxonomy ) ) {
			$where .= $wpdb->prepare( ' AND tt.taxonomy = %s', $taxonomy );
		}

		if ( $parent > 0 ) {
			$where .= $wpdb->prepare( ' AND tt.parent = %d', $parent );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $select . $join . $where );
	}
}
