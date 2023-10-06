<?php
/**
 * @package Polylang
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; // since WP 3.1
}

/**
 * A class to create a table to list all settings modules
 *
 * @since 1.8
 */
class PLL_Table_Settings extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 1.8
	 */
	public function __construct() {
		parent::__construct(
			array(
				'plural' => 'Settings', // Do not translate ( used for css class )
				'ajax'   => false,
			)
		);
	}

	/**
	 * Get the table classes for styling
	 *
	 * @since 1.8
	 */
	protected function get_table_classes() {
		return array( 'wp-list-table', 'widefat', 'plugins', 'pll-settings' ); // get the style of the plugins list table + one specific class
	}

	/**
	 * Displays a single row.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Settings_Module $item Settings module item.
	 * @return void
	 */
	public function single_row( $item ) {
		// Classes to reuse css from the plugins list table
		$classes = $item->is_active() ? 'active' : 'inactive';
		if ( $message = $item->get_upgrade_message() ) {
			$classes .= ' update';
		}

		// Display the columns
		printf( '<tr id="pll-module-%s" class="%s">', esc_attr( $item->module ), esc_attr( $classes ) );
		$this->single_row_columns( $item );
		echo '</tr>';

		// Display an upgrade message if there is any, reusing css from the plugins updates
		if ( $message = $item->get_upgrade_message() ) {
			printf(
				'<tr class="plugin-update-tr">
					<td colspan="3" class="plugin-update colspanchange">%s</td>
				</tr>',
				sprintf( '<div class="update-message notice inline notice-warning notice-alt"><p>%s</p></div>', $message ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}

		// The settings if there are
		// "inactive" class to reuse css from the plugins list table
		if ( $form = $item->get_form() ) {
			printf(
				'<tr id="pll-configure-%s" class="pll-configure inactive inline-edit-row" style="display: none;">
					<td colspan="3">
						<legend>%s</legend>
						%s
						<p class="submit inline-edit-save">
							%s
						</p>
					</td>
				</tr>',
				esc_attr( $item->module ),
				esc_html( $item->title ),
				$form, // phpcs:ignore
				implode( $item->get_buttons() ) // phpcs:ignore
			);
		}
	}

	/**
	 * Generates the columns for a single row of the table.
	 *
	 * @since 1.8
	 *
	 * @param PLL_Settings_Module $item Settings module item.
	 * @return void
	 */
	protected function single_row_columns( $item ) {
		$column_info = $this->get_column_info();
		$columns     = $column_info[0];
		$primary     = $column_info[3];

		foreach ( array_keys( $columns ) as $column_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' column-primary';
			}

			if ( 'cb' == $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</th>';
			} else {
				printf( '<td class="%s">', esc_attr( $classes ) );
				echo $this->column_default( $item, $column_name ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</td>';
			}
		}
	}

	/**
	 * Displays the item information in a column (default case).
	 *
	 * @since 1.8
	 *
	 * @param PLL_Settings_Module $item        Settings module item.
	 * @param string              $column_name Column name.
	 * @return string The column name.
	 */
	protected function column_default( $item, $column_name ) {
		if ( 'plugin-title' == $column_name ) {
			return sprintf( '<strong>%s</strong>', esc_html( $item->title ) ) . $this->row_actions( $item->get_action_links(), true /*always visible*/ );
		}
		return $item->$column_name;
	}

	/**
	 * Gets the list of columns.
	 *
	 * @since 1.8
	 *
	 * @return string[] The list of column titles.
	 */
	public function get_columns() {
		return array(
			'cb'           => '', // For the 4px border inherited from plugins when the module is activated
			'plugin-title' => esc_html__( 'Module', 'polylang' ), // plugin-title for styling
			'description'  => esc_html__( 'Description', 'polylang' ),
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 1.8
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'plugin-title';
	}

	/**
	 * Prepares the list of items for displaying
	 *
	 * @since 1.8
	 *
	 * @param PLL_Settings_Module[] $items Array of settings module items.
	 * @return void
	 */
	public function prepare_items( $items = array() ) {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), $this->get_primary_column_name() );

		// Sort rows, lowest priority on top.
		usort(
			$items,
			function ( $a, $b ) {
				return $a->priority > $b->priority ? 1 : -1;
			}
		);
		$this->items = $items;
	}

	/**
	 * Avoids displaying an empty tablenav
	 *
	 * @since 2.1
	 *
	 * @param string $which 'top' or 'bottom'
	 * @return void
	 */
	protected function display_tablenav( $which ) {} // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
}
