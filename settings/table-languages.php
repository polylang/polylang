<?php
/**
 * @package Polylang
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; // since WP 3.1
}

/**
 * A class to create the languages table in Polylang settings
 * Thanks to Matt Van Andel ( http://www.mattvanandel.com ) for its plugin "Custom List Table Example" !
 *
 * @since 0.1
 */
class PLL_Table_Languages extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct(
			array(
				'plural' => 'Languages', // Do not translate ( used for css class )
				'ajax'   => false,
			)
		);
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language $item The language item.
	 * @return void
	 */
	public function single_row( $item ) {
		/**
		 * Filter the list of classes assigned a row in the languages list table
		 *
		 * @since 1.8
		 *
		 * @param array        $classes The list of class names.
		 * @param PLL_Language $item    The language item.
		 */
		$classes = apply_filters( 'pll_languages_row_classes', array(), $item );
		echo '<tr' . ( empty( $classes ) ? '>' : ' class="' . esc_attr( implode( ' ', $classes ) ) . '">' );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Displays the item information in a column ( default case ).
	 *
	 * @since 0.1
	 *
	 * @param PLL_Language $item        The language item.
	 * @param string       $column_name The column name.
	 * @return string|int
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'locale':
			case 'slug':
				return esc_html( $item->$column_name );

			case 'term_group':
				return (int) $item->$column_name;

			case 'count':
				return $item->get_tax_prop( 'language', $column_name );

			default:
				return $item->$column_name; // Flag.
		}
	}

	/**
	 * Displays the item information in the column 'name'
	 * Displays the edit and delete action links
	 *
	 * @since 0.1
	 *
	 * @param PLL_Language $item The language item.
	 * @return string
	 */
	public function column_name( $item ) {
		return sprintf(
			'<a title="%s" href="%s">%s</a>',
			esc_attr__( 'Edit this language', 'polylang' ),
			esc_url( admin_url( 'admin.php?page=mlang&amp;pll_action=edit&amp;lang=' . $item->term_id ) ),
			esc_html( $item->name )
		);
	}

	/**
	 * Displays the item information in the default language
	 * Displays the 'make default' action link
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language $item The language item.
	 * @return string
	 */
	public function column_default_lang( $item ) {
		if ( ! $item->is_default ) {
			$s = sprintf(
				'<div class="row-actions"><span class="default-lang">
				<a class="icon-default-lang" title="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>
				</span></div>',
				esc_attr__( 'Select as default language', 'polylang' ),
				wp_nonce_url( '?page=mlang&amp;pll_action=default-lang&amp;noheader=true&amp;lang=' . $item->term_id, 'default-lang' ),
				/* translators: accessibility text, %s is a native language name */
				esc_html( sprintf( __( 'Choose %s as default language', 'polylang' ), $item->name ) )
			);

			/**
			 * Filters the default language row action in the languages list table.
			 *
			 * @since 1.8
			 *
			 * @param string       $s    The html markup of the action.
			 * @param PLL_Language $item The language item.
			 */
			$s = apply_filters( 'pll_default_lang_row_action', $s, $item );
		} else {
			$s = sprintf(
				'<span class="icon-default-lang"><span class="screen-reader-text">%1$s</span></span>',
				/* translators: accessibility text */
				esc_html__( 'Default language', 'polylang' )
			);
		}

		return $s;
	}

	/**
	 * Gets the list of columns
	 *
	 * @since 0.1
	 *
	 * @return string[] The list of column titles.
	 */
	public function get_columns() {
		return array(
			'name'         => esc_html__( 'Full name', 'polylang' ),
			'locale'       => esc_html__( 'Locale', 'polylang' ),
			'slug'         => esc_html__( 'Code', 'polylang' ),
			'default_lang' => sprintf( '<span title="%1$s" class="icon-default-lang"><span class="screen-reader-text">%2$s</span></span>', esc_attr__( 'Default language', 'polylang' ), esc_html__( 'Default language', 'polylang' ) ),
			'term_group'   => esc_html__( 'Order', 'polylang' ),
			'flag'         => esc_html__( 'Flag', 'polylang' ),
			'count'        => esc_html__( 'Posts', 'polylang' ),
		);
	}

	/**
	 * Gets the list of sortable columns
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'name'       => array( 'name', true ), // sorted by name by default
			'locale'     => array( 'locale', false ),
			'slug'       => array( 'slug', false ),
			'term_group' => array( 'term_group', false ),
			'count'      => array( 'count', false ),
		);
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 2.1
	 *
	 * @return string Name of the default primary column, in this case, 'name'.
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Language $item        The language item being acted upon.
	 * @param string       $column_name Current column name.
	 * @param string       $primary     Primary column name.
	 * @return string The row actions output.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array(
			'edit'   => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Edit this language', 'polylang' ),
				esc_url( admin_url( 'admin.php?page=mlang&amp;pll_action=edit&amp;lang=' . $item->term_id ) ),
				esc_html__( 'Edit', 'polylang' )
			),
			'delete' => sprintf(
				'<a title="%s" href="%s" onclick = "return confirm( \'%s\' );">%s</a>',
				esc_attr__( 'Delete this language and all its associated data', 'polylang' ),
				wp_nonce_url( '?page=mlang&amp;pll_action=delete&amp;noheader=true&amp;lang=' . $item->term_id, 'delete-lang' ),
				esc_js( __( 'You are about to permanently delete this language. Are you sure?', 'polylang' ) ),
				esc_html__( 'Delete', 'polylang' )
			),
		);

		/**
		 * Filters the list of row actions in the languages list table.
		 *
		 * @since 1.8
		 *
		 * @param array        $actions A list of html markup actions.
		 * @param PLL_Language $item    The language item.
		 */
		$actions = apply_filters( 'pll_languages_row_actions', $actions, $item );

		return $this->row_actions( $actions );
	}

	/**
	 * Sorts language items.
	 *
	 * @since 0.1
	 *
	 * @param PLL_Language $a The first language to compare.
	 * @param PLL_Language $b The second language to compare.
	 * @return int -1 or 1 if $a is considered to be respectively less than or greater than $b.
	 */
	protected function usort_reorder( $a, $b ) {
		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'name'; // phpcs:ignore WordPress.Security.NonceVerification
		// Determine sort order
		if ( is_numeric( $a->$orderby ) ) {
			$result = $a->$orderby > $b->$orderby ? 1 : -1;
		} else {
			$result = strcmp( $a->$orderby, $b->$orderby );
		}
		// Send final sort direction to usort.
		return ( empty( $_GET['order'] ) || 'asc' === $_GET['order'] ) ? $result : -$result; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Prepares the list of languages for display.
	 *
	 * @since 0.1
	 *
	 * @param PLL_Language[] $data The list of languages.
	 * @return void
	 */
	public function prepare_items( $data = array() ) {
		$per_page = $this->get_items_per_page( 'pll_lang_per_page' );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		usort( $data, array( $this, 'usort_reorder' ) );

		$total_items = count( $data );
		$this->items = array_slice( $data, ( $this->get_pagenum() - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);
	}
}
