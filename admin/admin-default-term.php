<?php
/**
 * @package Polylang
 */

/**
 * Manages filters and actions related to default terms.
 *
 * @since 3.1
 */
class PLL_Admin_Default_Term {

	/**
	 * A reference to the PLL_Model instance.
	 *
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Preferred language to assign to new contents.
	 *
	 * @var PLL_Language|null
	 */
	protected $pref_lang;

	/**
	 * Array of registered taxonomy names for which Polylang manages languages and translations.
	 *
	 * @var string[]
	 */
	protected $taxonomies;

	/**
	 * Constructor: setups properties.
	 *
	 * @since 3.1
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model      = &$polylang->model;
		$this->pref_lang  = &$polylang->pref_lang;
		$this->taxonomies = $this->model->get_translated_taxonomies();
	}

	/**
	 * Setups filters and actions needed.
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	public function add_hooks() {
		foreach ( $this->taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				// Allows to get the default terms in all languages
				add_filter( 'option_default_' . $taxonomy, array( $this, 'option_default_term' ) );
				add_action( 'update_option_default_' . $taxonomy, array( $this, 'update_option_default_term' ), 10, 2 );

				// Adds the language column in the 'Terms' table.
				add_filter( 'manage_' . $taxonomy . '_custom_column', array( $this, 'term_column' ), 10, 3 );
			}
		}
		add_action( 'pll_add_language', array( $this, 'handle_default_term_on_create_language' ) );

		// The default term should be in the default language
		add_action( 'pll_update_default_lang', array( $this, 'update_default_term_language' ) );

		// Prevents deleting all the translations of the default term
		add_filter( 'map_meta_cap', array( $this, 'fix_delete_default_term' ), 10, 4 );
	}

	/**
	 * Filters the default term in note below the term list table and in settings->writing dropdown
	 *
	 * @since 1.2
	 *
	 * @param  int $taxonomy_term_id The taxonomy term id.
	 * @return int                   A taxonomy term id.
	 */
	public function option_default_term( $taxonomy_term_id ) {
		if ( isset( $this->pref_lang ) && $tr = $this->model->term->get( $taxonomy_term_id, $this->pref_lang ) ) {
			$taxonomy_term_id = $tr;
		}
		return $taxonomy_term_id;
	}

	/**
	 * Checks if the new default term is translated in all languages
	 * If not, create the translations
	 *
	 * @since 1.7
	 *
	 * @param  int $old_value The old option value.
	 * @param  int $value     The new option value.
	 * @return void
	 */
	public function update_option_default_term( $old_value, $value ) {
		$default_cat_lang = $this->model->term->get_language( $value );

		// Assign a default language to default term
		if ( ! $default_cat_lang ) {
			$default_cat_lang = $this->model->get_default_language();
			$this->model->term->set_language( (int) $value, $default_cat_lang );
		}

		if ( empty( $default_cat_lang ) ) {
			return;
		}

		$taxonomy = substr( current_filter(), 22 );

		foreach ( $this->model->get_languages_list() as $language ) {
			if ( $language->slug != $default_cat_lang->slug && ! $this->model->term->get_translation( $value, $language ) ) {
				$this->create_default_term( $language, $taxonomy );
			}
		}
	}

	/**
	 * Create a default term for a language
	 *
	 * @since 1.2
	 *
	 * @param object|string|int $lang     language
	 * @param string            $taxonomy The current taxonomy
	 * @return void
	 */
	public function create_default_term( $lang, $taxonomy ) {
		$lang = $this->model->get_language( $lang );

		// create a new term
		// FIXME this is translated in admin language when we would like it in $lang
		$cat_name = __( 'Uncategorized', 'polylang' );
		$cat_slug = sanitize_title( $cat_name . '-' . $lang->slug );
		$cat = wp_insert_term( $cat_name, $taxonomy, array( 'slug' => $cat_slug ) );

		// check that the term was not previously created ( in case the language was deleted and recreated )
		$cat = isset( $cat->error_data['term_exists'] ) ? $cat->error_data['term_exists'] : $cat['term_id'];

		// set language
		$this->model->term->set_language( (int) $cat, $lang );

		// this is a translation of the default term
		$default = (int) get_option( 'default_' . $taxonomy );
		$translations = $this->model->term->get_translations( $default );

		$this->model->term->save_translations( (int) $cat, $translations );
	}

	/**
	 * Manages the default term when new languages are created.
	 *
	 * @since 3.1
	 *
	 * @param  array $args Argument used to create the language. @see PLL_Admin_Model::add_language().
	 * @return void
	 */
	public function handle_default_term_on_create_language( $args ) {
		foreach ( $this->taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				$default = (int) get_option( 'default_' . $taxonomy );

				// Assign default language to default term
				if ( ! $this->model->term->get_language( $default ) ) {
					$this->model->term->set_language( $default, $args['slug'] );
				} elseif ( empty( $args['no_default_cat'] ) && ! $this->model->term->get( $default, $args['slug'] ) ) {
					$this->create_default_term( $args['slug'], $taxonomy );
				}
			}
		}
	}

	/**
	 * Identify the default term in the terms list table to disable the language dropdown in js.
	 *
	 * @since 3.1
	 *
	 * @param  string $out     The output.
	 * @param  string $column  The custom column's name.
	 * @param  int    $term_id The term id.
	 * @return string          The HTML string.
	 */
	public function term_column( $out, $column, $term_id ) {
		if ( $column === $this->get_first_language_column() && $this->is_default_term( $term_id ) ) {
			$out .= sprintf( '<div class="hidden" id="default_cat_%1$d">%1$d</div>', intval( $term_id ) );
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
	 * Prevents deleting all the translations of the default term
	 *
	 * @since 2.1
	 *
	 * @param  array  $caps    The user's actual capabilities.
	 * @param  string $cap     Capability name.
	 * @param  int    $user_id The user ID.
	 * @param  array  $args    Adds the context to the cap. The term id.
	 * @return array
	 */
	public function fix_delete_default_term( $caps, $cap, $user_id, $args ) {
		if ( 'delete_term' === $cap && $this->is_default_term( reset( $args ) ) ) {
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}

	/**
	 * Check if the term is the default term.
	 *
	 * @since 3.1
	 *
	 * @param  int $term_id The term id.
	 * @return bool         True if the term is the default term, false otherwise.
	 */
	public function is_default_term( $term_id ) {
		$term = get_term( $term_id );
		if ( $term instanceof WP_Term ) {
			$default_term_id = get_option( 'default_' . $term->taxonomy );
			return $default_term_id && in_array( $default_term_id, $this->model->term->get_translations( $term_id ) );
		}
		return false;
	}

	/**
	 * Updates the default term language.
	 *
	 * @since 3.1
	 *
	 * @param  string $slug Language slug.
	 * @return void
	 */
	public function update_default_term_language( $slug ) {
		foreach ( $this->taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				$default_cats = $this->model->term->get_translations( get_option( 'default_' . $taxonomy ) );
				if ( isset( $default_cats[ $slug ] ) ) {
					update_option( 'default_' . $taxonomy, $default_cats[ $slug ] );
				}
			}
		}
	}
}
