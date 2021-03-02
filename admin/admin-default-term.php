<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to default terms.
 *
 * @since 3.0
 */
class PLL_Admin_Default_Term {

	/**
	 * A reference to the PLL_Model instance.
	 *
	 * @since 2.8
	 *
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Preferred language to assign to new contents.
	 *
	 * @var PLL_Language
	 */
	protected $pref_lang;

	/**
	 * Reference to Polylang options array
	 *
	 * @var array $options
	 */
	protected $options;

	/**
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->pref_lang = &$polylang->pref_lang;
		$this->options = &$polylang->options;

		$taxonomies = get_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				// Allows to get the default categories in all languages
				add_filter( 'option_default' . $taxonomy, array( $this, 'option_default_category' ) );
				add_action( 'update_option_default_' . $taxonomy, array( $this, 'update_option_default_category' ), 10, 2 );
				add_action( 'pll_add_language', array( $this, 'handle_default_category_on_create_language' ) );

				// The default category should be in the default language
				add_action( 'pll_update_default_lang', array( $this, 'update_default_category_language' ) );

				// Adds the language column in the 'Categories' table.
				add_filter( 'manage_' . $taxonomy . '_custom_column', array( $this, 'term_column' ), 10, 3 );
			}

			// Adds the language column in the 'Post Tags'table.
			if ( 'post_tag' === $taxonomy ) {
				add_filter( 'manage_' . $taxonomy . '_custom_column', array( $this, 'term_column' ), 10, 3 );
			}
		}

		// Prevents deleting all the translations of the default category
		add_filter( 'map_meta_cap', array( $this, 'fix_delete_default_category' ), 10, 4 );
	}

	/**
	 * Filters the default category in note below the category list table and in settings->writing dropdown
	 *
	 * @since 1.2
	 *
	 * @param int $value
	 * @return int
	 */
	public function option_default_category( $value ) {
		if ( isset( $this->pref_lang ) && $tr = $this->model->term->get( $value, $this->pref_lang ) ) {
			$value = $tr;
		}
		return $value;
	}

	/**
	 * Checks if the new default category is translated in all languages
	 * If not, create the translations
	 *
	 * @since 1.7
	 *
	 * @param int $old_value
	 * @param int $value
	 *
	 * @return void
	 */
	public function update_option_default_category( $old_value, $value ) {
		$default_cat_lang = $this->model->term->get_language( $value );

		// Assign a default language to default category
		if ( ! $default_cat_lang ) {
			$default_cat_lang = $this->model->get_language( $this->options['default_lang'] );
			$this->model->term->set_language( (int) $value, $default_cat_lang );
		}

		foreach ( $this->model->get_languages_list() as $language ) {
			if ( $language->slug != $default_cat_lang->slug && ! $this->model->term->get_translation( $value, $language ) ) {
				$this->create_default_category( $language );
			}
		}
	}

	/**
	 * Create a default category for a language
	 *
	 * @since 1.2
	 *
	 * @param object|string|int $lang language
	 *
	 * @return void
	 */
	public function create_default_category( $lang ) {
		$lang = $this->model->get_language( $lang );

		// create a new category
		// FIXME this is translated in admin language when we would like it in $lang
		$cat_name = __( 'Uncategorized', 'polylang' );
		$cat_slug = sanitize_title( $cat_name . '-' . $lang->slug );
		$cat = wp_insert_term( $cat_name, 'category', array( 'slug' => $cat_slug ) );

		// check that the category was not previously created ( in case the language was deleted and recreated )
		$cat = isset( $cat->error_data['term_exists'] ) ? $cat->error_data['term_exists'] : $cat['term_id'];

		// set language
		$this->model->term->set_language( (int) $cat, $lang );

		// this is a translation of the default category
		$default = (int) get_option( 'default_category' );
		$translations = $this->model->term->get_translations( $default );

		$this->model->term->save_translations( (int) $cat, $translations );
	}

	/**
	 * Manages the default category when new languages are created.
	 *
	 * @param array $args Argument used to create the language. @see PLL_Admin_Model::add_language().
	 *
	 * @return void
	 */
	public function handle_default_category_on_create_language( $args ) {
		$default = (int) get_option( 'default_category' );

		// Assign default language to default category
		if ( ! $this->model->term->get_language( $default ) ) {
			$this->model->term->set_language( $default, $args['slug'] );
		} elseif ( empty( $args['no_default_cat'] ) && ! $this->model->term->get( $default, $args['slug'] ) ) {
			$this->create_default_category( $args['slug'] );
		}
	}

	/**
	 * Adds the language column in the tables.
	 *
	 * @param string $out The output.
	 * @param string $column The custom column's name.
	 * @param int    $term_id The term id.
	 *
	 * @return string
	 */
	public function term_column( $out, $column, $term_id ) {
		if ( $column == $this->get_first_language_column() ) {
			// Identify the default categories to disable the language dropdown in js
			if ( in_array( get_option( 'default_category' ), $this->model->term->get_translations( $term_id ) ) ) {
				$out .= sprintf( '<div class="hidden" id="default_cat_%1$d">%1$d</div>', intval( $term_id ) );
			}
		}

		return $out;
	}

	/**
	 * Returns the first language column in the posts, pages and media library tables
	 *
	 * @since 0.9
	 *
	 * @return string first language column name
	 */
	protected function get_first_language_column() {
		$columns = array();

		foreach ( $this->model->get_languages_list() as $language ) {
			$columns[] = 'language_' . $language->slug;
		}

		return empty( $columns ) ? '' : reset( $columns );
	}

	/**
	 * Prevents deleting all the translations of the default category
	 *
	 * @since 2.1
	 *
	 * @param array  $caps    The user's actual capabilities.
	 * @param string $cap     Capability name.
	 * @param int    $user_id The user ID.
	 * @param array  $args    Adds the context to the cap. The category id.
	 * @return array
	 */
	public function fix_delete_default_category( $caps, $cap, $user_id, $args ) {
		if ( 'delete_term' === $cap ) {
			$term = get_term( reset( $args ) ); // Since WP 4.4, we can get the term to get the taxonomy
			if ( $term instanceof WP_Term ) {
				$default_cat = get_option( 'default_' . $term->taxonomy );
				if ( $default_cat && array_intersect( $args, $this->model->term->get_translations( $default_cat ) ) ) {
					$caps[] = 'do_not_allow';
				}
			}
		}

		return $caps;
	}

	/**
	 * @param int $term_id The term id.
	 *
	 * @return bool
	 */
	public function is_term_the_default_category( $term_id ) {
		return in_array( get_option( 'default_category' ), $this->model->term->get_translations( $term_id ) );
	}

	/**
	 * @param string $slug
	 *
	 * @return void
	 */
	public function update_default_category_language( $slug ) {
		$default_cats = $this->model->term->get_translations( get_option( 'default_category' ) );
		if ( isset( $default_cats[ $slug ] ) ) {
			update_option( 'default_category', $default_cats[ $slug ] );
		}
	}
}
