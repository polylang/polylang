<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ); // since WP 3.1
}

/**
 * a class to create the languages table in Polylang settings
 * Thanks to Matt Van Andel ( http://www.mattvanandel.com ) for its plugin "Custom List Table Example" !
 *
 * @since 0.1
 */
class PLL_Table_Languages extends WP_List_Table {

	/**
	 * constructor
	 *
	 * @since 0.1
	 */
	function __construct() {
		parent::__construct( array(
			'plural'   => 'Languages', // do not translate ( used for css class )
			'ajax'	   => false,
		) );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.8
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		/*
		 * Filter the list of classes assigned a row in the languages list table
		 *
		 * @since 1.8
		 *
		 * @param array  $classes list of class names
		 * @param object $item    the current item
		 */
		$classes = apply_filters( 'pll_languages_row_classes', array(), $item );
		echo '<tr' . ( empty( $classes ) ? '>' : ' class="' . esc_attr( implode( ' ', $classes ) ) . '">' );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * displays the item information in a column ( default case )
	 *
	 * @since 0.1
	 *
	 * @param object $item
	 * @param string $column_name
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'locale':
			case 'slug':
				return esc_html( $item->$column_name );

			case 'term_group':
			case 'count':
				return (int) $item->$column_name;

			default:
				return $item->$column_name; // flag
		}
	}

	/**
	 * displays the item information in the column 'name'
	 * displays the edit and delete action links
	 *
	 * @since 0.1
	 *
	 * @param object $item
	 * @return string
	 */
	function column_name( $item ) {
		return sprintf(
			'<a title="%s" href="%s">%s</a>',
			__( 'Edit this language', 'polylang' ),
			esc_url( admin_url( 'options-general.php?page=mlang&amp;pll_action=edit&amp;lang=' . $item->term_id ) ),
			esc_html( $item->name )
		);
	}

	/**
	 * displays the item information in the default language
	 * displays the 'make default' action link
	 *
	 * @since 1.8
	 *
	 * @param object $item
	 * @return string
	 */
	function column_default_lang( $item ) {
		$options = get_option( 'polylang' );

		if ( $options['default_lang'] != $item->slug ) {
			$s = sprintf('
				<div class="row-actions"><span class="default-lang">
				<a class="icon-default-lang" title="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>
				</span></div>',
				__( 'Select as default language', 'polylang' ),
				wp_nonce_url( '?page=mlang&amp;pll_action=default-lang&amp;noheader=true&amp;lang=' . $item->term_id, 'default-lang' ),
				/* translators: %s is a native language name */
				esc_html( sprintf( __( 'Choose %s as default language', 'polylang' ), $item->name ) )
			);

			/*
			 * Filter the default language row action in the languages list table
			 *
			 * @since 1.8
			 *
			 * @param string $s    html markup of the action
			 * @param object $item
			 */
			$s = apply_filters( 'pll_default_lang_row_action', $s, $item );
		} else {
			$s = sprintf(
				'<span class="icon-default-lang"><span class="screen-reader-text">%1$s</span></span>',
				__( 'Default language', 'polylang' )
			);
			$actions = array();
		}

		return $s;
	}

	/**
	 * gets the list of columns
	 *
	 * @since 0.1
	 *
	 * @return array the list of column titles
	 */
	function get_columns() {
		return array(
			'name'         => __( 'Full name', 'polylang' ),
			'locale'       => __( 'Locale', 'polylang' ),
			'slug'         => __( 'Code', 'polylang' ),
			'default_lang' => sprintf( '<span title="%1$s" class="icon-default-lang"><span class="screen-reader-text">%1$s</span></span>', __( 'Default language', 'polylang' ) ),
			'term_group'   => __( 'Order', 'polylang' ),
			'flag'         => __( 'Flag', 'polylang' ),
			'count'        => __( 'Posts', 'polylang' ),
		);
	}

	/**
	 * gets the list of sortable columns
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		return array(
			'name'		    => array( 'name', true ), // sorted by name by default
			'locale'      => array( 'locale', false ),
			'slug'		    => array( 'slug', false ),
			'term_group'  => array( 'term_group', false ),
			'count'	      => array( 'count', false ),
		);
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @since 1.8
	 *
	 * @param object $item        The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions output.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array(
			'edit'   => sprintf(
				'<a title="%s" href="%s">%s</a>',
				__( 'Edit this language', 'polylang' ),
				esc_url( admin_url( 'options-general.php?page=mlang&amp;pll_action=edit&amp;lang=' . $item->term_id ) ),
				__( 'Edit','polylang' )
			),
			'delete' => sprintf(
				'<a title="%s" href="%s" onclick = "return confirm( \'%s\' );">%s</a>',
				__( 'Delete this language and all its associated data', 'polylang' ),
				wp_nonce_url( '?page=mlang&amp;pll_action=delete&amp;noheader=true&amp;lang=' . $item->term_id, 'delete-lang' ),
				__( 'You are about to permanently delete this language. Are you sure?', 'polylang' ),
				__( 'Delete','polylang' )
			),
		);

		/*
		 * Filter the list of row actions in the languages list table
		 *
		 * @since 1.8
		 *
		 * @param array  $actions list of html markup actions
		 * @param object $item
		 */
		$actions = apply_filters( 'pll_languages_row_actions', $actions, $item );

		return $this->row_actions( $actions );
	}

	/**
	 * Sort items
	 *
	 * @since 0.1
	 *
	 * @param object $a The first object to compare
	 * @param object $b The second object to compare
	 * @return int -1 or 1 if $a is considered to be respectively less than or greater than $b.
	 */
	protected function usort_reorder( $a, $b ) {
		$orderby = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'name';
		// determine sort order
		if ( is_numeric( $a->$orderby ) ) {
			$result = $a->$orderby > $b->$orderby ? 1 : -1;
		} else {
			$result = strcmp( $a->$orderby, $b->$orderby );
		}
		// send final sort direction to usort
		return ( empty( $_GET['order'] ) || 'asc' == $_GET['order'] ) ? $result : -$result;
	}

	/**
	 * prepares the list of items for displaying
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 */
	function prepare_items( $data = array() ) {
		$per_page = $this->get_items_per_page( 'pll_lang_per_page' );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		usort( $data, array( &$this, 'usort_reorder' ) );

		$total_items = count( $data );
		$this->items = array_slice( $data, ( $this->get_pagenum() - 1 ) * $per_page, $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'	=> $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
