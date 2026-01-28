<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Model\Languages;
use WP_Syntex\Polylang\Strings\Query;
use WP_Syntex\Polylang\Strings\Database_Repository;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; // since WP 3.1
}

/**
 * A class to create the strings translations table
 * Thanks to Matt Van Andel ( http://www.mattvanandel.com ) for its plugin "Custom List Table Example" !
 *
 * @since 0.6
 */
class PLL_Table_String extends WP_List_Table {
	/**
	 * Languages model.
	 *
	 * @var Languages
	 */
	protected $languages;

	/**
	 * The selected string group or -1 if none is selected.
	 *
	 * @var string|int
	 */
	protected $selected_group;

	/**
	 * The database access layer for strings.
	 *
	 * @var Database_Repository
	 */
	private Database_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 0.6
	 * @since 3.8 The 1st argument is an instance of the languages model.
	 *
	 * @param Languages $languages Languages model.
	 */
	public function __construct( Languages $languages ) {
		parent::__construct(
			array(
				'plural' => 'Strings translations', // Do not translate ( used for css class )
				'ajax'   => false,
			)
		);

		$this->items      = array(); // `WP_List_Table::$items` doesn't have a default value, let's be explicit.
		$this->languages  = $languages;
		$this->repository = new Database_Repository( $languages );

		$this->selected_group = -1;

		if ( ! empty( $_GET['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$group = sanitize_text_field( wp_unslash( $_GET['group'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( in_array( $group, $this->repository->get_groups() ) ) {
				$this->selected_group = $group;
			}
		}

		add_action( 'mlang_action_string-translation', array( $this, 'save_translations' ) );
	}

	/**
	 * Displays the item information in a column (default case).
	 *
	 * @since 0.6
	 *
	 * @param array  $item        Data related to the current string.
	 * @param string $column_name The current column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Displays the checkbox in first column.
	 *
	 * @since 1.1
	 *
	 * @param array $item Data related to the current string.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$s">%2$s</label><input id="cb-select-%1$s" type="checkbox" name="strings[]" value="%1$s" %3$s />',
			esc_attr( $item['row'] ),
			/* translators:  accessibility text, %s is a string potentially in any language */
			sprintf( __( 'Select %s', 'polylang' ), format_to_edit( $item['string'] ) ),
			disabled( empty( $item['icl'] ), true, false ) // Only strings registered with WPML API can be removed.
		);
	}

	/**
	 * Displays the string to translate.
	 *
	 * @since 1.0
	 *
	 * @param array $item Data related to the current string.
	 * @return string
	 */
	public function column_string( $item ) {
		return format_to_edit( $item['string'] ); // Don't interpret special chars for the string column.
	}

	/**
	 * Displays the translations to edit.
	 *
	 * @since 0.6
	 *
	 * @param array $item Data related to the current string.
	 * @return string
	 */
	public function column_translations( $item ) {
		$out       = '';
		$languages = array();

		foreach ( $this->languages->get_list() as $language ) {
			$languages[ $language->slug ] = $language->name;
		}

		foreach ( $item['translations'] as $key => $translation ) {
			$input_type = $item['multiline'] ?
				'<textarea name="translation[%1$s][%2$s]" id="%1$s-%2$s" %5$s>%4$s</textarea>' :
				'<input type="text" name="translation[%1$s][%2$s]" id="%1$s-%2$s" value="%4$s" %5$s/>';

			$out .= sprintf(
				'<div class="translation"><label for="%1$s-%2$s">%3$s</label>' . $input_type . "</div>\n",
				esc_attr( $key ),
				esc_attr( $item['row'] ),
				esc_html( $languages[ $key ] ),
				format_to_edit( $translation ), // Don't interpret special chars.
				$item['disabled'][ $key ]
			);
		}

		return $out;
	}

	/**
	 * Gets the list of columns.
	 *
	 * @since 0.6
	 *
	 * @return string[] The list of column titles.
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />', // Checkbox.
			'string'       => esc_html__( 'String', 'polylang' ),
			'name'         => esc_html__( 'Name', 'polylang' ),
			'context'      => esc_html__( 'Group', 'polylang' ),
			'translations' => esc_html__( 'Translations', 'polylang' ),
		);
	}

	/**
	 * Gets the list of sortable columns
	 *
	 * @since 0.6
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'string'  => array( 'string', false ),
			'name'    => array( 'name', false ),
			'context' => array( 'context', false ),
		);
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 2.1
	 *
	 * @return string Name of the default primary column, in this case, 'string'.
	 */
	protected function get_default_primary_column_name() {
		return 'string';
	}

	/**
	 * Search for a string in translations. Case insensitive.
	 *
	 * @since 2.6
	 *
	 * @param PLL_Language[] $languages An array of language objects.
	 * @param string         $s         Searched string.
	 * @return string[] Found strings.
	 */
	protected function search_in_translations( $languages, $s ) {
		$founds = array();

		foreach ( $languages as $language ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			foreach ( wp_list_pluck( $mo->entries, 'translations' ) as $string => $translation ) {
				if ( false !== stripos( $translation[0], $s ) ) {
					$founds[] = $string;
				}
			}
		}

		return array_unique( $founds );
	}

	/**
	 * Sorts registered string items.
	 *
	 * @since 0.6
	 *
	 * @param array $a The first item to compare.
	 * @param array $b The second item to compare.
	 * @return int -1 or 1 if $a is considered to be respectively less than or greater than $b.
	 */
	protected function usort_reorder( $a, $b ) {
		if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$orderby = sanitize_key( $_GET['orderby'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $a[ $orderby ], $b[ $orderby ] ) ) {
				$result = strcmp( $a[ $orderby ], $b[ $orderby ] ); // Determine sort order
				return ( empty( $_GET['order'] ) || 'asc' === $_GET['order'] ) ? $result : -$result; // phpcs:ignore WordPress.Security.NonceVerification
			}
		}

		return 0;
	}

	/**
	 * Prepares the list of registered strings for display.
	 *
	 * @since 0.6
	 *
	 * @return void
	 */
	public function prepare_items() {
		$languages = $this->languages->get_list();

		// Is admin language filter active?
		$filter = get_user_meta( get_current_user_id(), 'pll_filter_content', true );
		if ( $filter ) {
			$languages = wp_list_filter( $languages, array( 'slug' => $filter ) );
		}

		$per_page              = $this->get_items_per_page( 'pll_strings_per_page' );
		$paged                 = ! empty( $_GET['paged'] ) && is_numeric( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$items = ( new Query( $this->repository, $this->languages ) )
			->by_context( -1 !== $this->selected_group && is_string( $this->selected_group ) ? $this->selected_group : null )
			->by_fragment( ! empty( $_GET['s'] ) && is_string( $_GET['s'] ) ? wp_unslash( $_GET['s'] ) : null ) // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			->order_by(
				! empty( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'name', // phpcs:ignore WordPress.Security.NonceVerification
				! empty( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'asc' // phpcs:ignore WordPress.Security.NonceVerification
			)
			->paginate( $per_page, $paged )
			->get();

		$this->set_pagination_args(
			array(
				'total_items' => $items->get_total(),
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $items->get_total() / $per_page ),
			)
		);

		$allowed_language_slugs = $this->languages->filter( 'translator' )->get_list( array( 'fields' => 'slug' ) );

		/*
		 * Translate strings.
		 * Kept for the end as it is a slow process.
		 */
		foreach ( $languages as $language ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			foreach ( $items as $translatable ) {
				$this->items[ $translatable->get_id() ]['string']                          = $translatable->get_source();
				$this->items[ $translatable->get_id() ]['name']                            = $translatable->get_name();
				$this->items[ $translatable->get_id() ]['context']                         = $translatable->get_context();
				$this->items[ $translatable->get_id() ]['translations'][ $language->slug ] = $mo->translate_if_any( $translatable->get_source(), $translatable->get_context() );
				$this->items[ $translatable->get_id() ]['row']                             = $translatable->get_id();                                                          // Store the row number for convenience.
				$this->items[ $translatable->get_id() ]['multiline']                       = $translatable->is_multiline();
				$this->items[ $translatable->get_id() ]['disabled'][ $language->slug ]     = disabled( in_array( $language->slug, $allowed_language_slugs, true ), false, false );
			}
		}
	}

	/**
	 * Get the list of possible bulk actions.
	 *
	 * @since 1.1
	 *
	 * @return string[] Array of bulk actions.
	 */
	public function get_bulk_actions() {
		if ( current_user_can( 'manage_options' ) ) {
			return array( 'delete' => __( 'Delete', 'polylang' ) );
		}
		return array();
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 * overrides parent function to avoid submit button to trigger bulk actions
	 *
	 * @since 1.8
	 *
	 * @return string|false The action name or False if no action was selected
	 */
	public function current_action() {
		return empty( $_POST['submit'] ) ? parent::current_action() : false; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Displays the dropdown list to filter strings per group
	 *
	 * @since 1.1
	 *
	 * @param string $which only 'top' is supported
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		echo '<div class="alignleft actions">';
		printf(
			'<label class="screen-reader-text" for="select-group" >%s</label>',
			/* translators: accessibility text */
			esc_html__( 'Filter by group', 'polylang' )
		);
		echo '<select id="select-group" name="group">' . "\n";
		printf(
			'<option value="-1"%s>%s</option>' . "\n",
			selected( $this->selected_group, -1, false ),
			esc_html__( 'View all groups', 'polylang' )
		);

		foreach ( $this->repository->get_groups() as $group ) {
			printf(
				'<option value="%s"%s>%s</option>' . "\n",
				esc_attr( urlencode( $group ) ),
				selected( $this->selected_group, $group, false ),
				esc_html( $group )
			);
		}
		echo '</select>' . "\n";

		submit_button( __( 'Filter', 'polylang' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		echo '</div>';
	}

	/**
	 * Saves the strings translations in DB
	 * Optionally clean the DB
	 *
	 * @since 1.9
	 *
	 * @return void
	 *
	 * @phpstan-return never
	 */
	public function save_translations() {
		check_admin_referer( 'string-translation', '_wpnonce_string-translation' );

		if ( ! empty( $_POST['submit'] ) ) {
			$collection = $this->repository->find_all();

			foreach ( $this->languages->filter( 'translator' )->get_list() as $language ) {
				if ( empty( $_POST['translation'][ $language->slug ] ) || ! is_array( $_POST['translation'][ $language->slug ] ) ) { // In case the language filter is active ( thanks to John P. Bloch )
					continue;
				}

				$translations = (array) wp_unslash( $_POST['translation'][ $language->slug ] );  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				array_map(
					function ( $key, $translation ) use ( $collection, $language ) {
						if ( ! is_string( $translation ) ) {
							return;
						}

						$translatable = $collection->get( $key );
						if ( $translatable ) {
							$translatable->set_translation( $language, trim( $translation ) );
							return;
						}
					},
					array_keys( $translations ),
					array_values( $translations )
				);
			}

			// Save all languages at once
			$this->repository->save( $collection );

			// Clean database (removes all strings which were registered some day but are no more).
			if ( ! empty( $_POST['clean'] ) && current_user_can( 'manage_options' ) ) {
				$this->repository->clean();
			}

			pll_add_notice( new WP_Error( 'pll_strings_translations_updated', __( 'Translations updated.', 'polylang' ), 'success' ) );

			/**
			 * Fires after the strings translations are saved in DB
			 *
			 * @since 1.2
			 */
			do_action( 'pll_save_strings_translations' );
		}

		// Unregisters strings registered through WPML API
		if ( $this->current_action() === 'delete' && ! empty( $_POST['strings'] ) && function_exists( 'icl_unregister_string' ) && current_user_can( 'manage_options' ) ) {
			array_map(
				function ( $key ) {
					$this->repository->remove_wpml_string( sanitize_key( $key ) );
				},
				$_POST['strings'] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			);
		}

		// To refresh the page ( possible thanks to the $_GET['noheader']=true )
		$args = array_intersect_key( $_REQUEST, array_flip( array( 's', 'paged', 'group' ) ) );
		if ( ! empty( $_GET['paged'] ) && ! empty( $_POST['submit'] ) ) {
			$args['paged'] = (int) $_GET['paged']; // Don't rely on $_REQUEST['paged'] or $_POST['paged']. See #14
		}
		if ( ! empty( $args['s'] ) ) {
			$args['s'] = urlencode( $args['s'] ); // Searched string needs to be encoded as it comes from $_POST
		}
		PLL_Settings::redirect( $args );
	}
}
