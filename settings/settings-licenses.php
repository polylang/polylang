<?php
/**
 * @package Polylang
 */

/**
 * Settings class for licenses
 *
 * @since 1.9
 */
class PLL_Settings_Licenses extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 100;

	/**
	 * Stores an array of PLL_License instances.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param object $polylang polylang object
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'      => 'licenses',
				'title'       => __( 'License keys', 'polylang' ),
				'description' => __( 'Manage licenses for Polylang Pro and add-ons.', 'polylang' ),
			)
		);

		$this->buttons['cancel'] = sprintf( '<button type="button" class="button button-secondary cancel">%s</button>', __( 'Close', 'polylang' ) );

		$this->items = apply_filters( 'pll_settings_licenses', array() );

		add_action( 'wp_ajax_pll_deactivate_license', array( $this, 'deactivate_license' ) );
	}

	/**
	 * Tells if the module is active
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_active() {
		return ! empty( $this->items );
	}

	/**
	 * Displays the settings form
	 *
	 * @since 1.9
	 */
	protected function form() {
		if ( ! empty( $this->items ) ) { ?>
			<table id="pll-licenses-table" class="form-table">
				<?php
				foreach ( $this->items as $item ) {
					echo $this->get_row( $item ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</table>
			<?php
		}
	}

	/**
	 * Get the html for a row (one per license key) for display
	 *
	 * @since 1.9
	 *
	 * @param array $item licence id, name and key
	 * @return string
	 */
	protected function get_row( $item ) {
		return $item->get_form_field();
	}

	/**
	 * Ajax method to save the license keys and activate the licenses at the same time
	 * Overrides parent's method
	 *
	 * @since 1.9
	 */
	public function save_options() {
		check_ajax_referer( 'pll_options', '_pll_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['module'] ) && $this->module === $_POST['module'] && ! empty( $_POST['licenses'] ) ) {
			$x = new WP_Ajax_Response();
			foreach ( $this->items as $item ) {
				if ( ! empty( $_POST['licenses'][ $item->id ] ) ) {
					$updated_item = $item->activate_license( sanitize_key( $_POST['licenses'][ $item->id ] ) );
					$x->Add( array( 'what' => 'license-update', 'data' => $item->id, 'supplemental' => array( 'html' => $this->get_row( $updated_item ) ) ) );
				}
			}

			// Updated message
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'polylang' ), 'updated' );
			ob_start();
			settings_errors();
			$x->Add( array( 'what' => 'success', 'data' => ob_get_clean() ) );
			$x->send();
		}
	}

	/**
	 * Ajax method to deactivate a license
	 *
	 * @since 1.9
	 */
	public function deactivate_license() {
		check_ajax_referer( 'pll_options', '_pll_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_POST['id'] ) ) {
			wp_die( 0 );
		}

		$id = substr( sanitize_text_field( wp_unslash( $_POST['id'] ) ), 11 );
		wp_send_json(
			array(
				'id'   => $id,
				'html' => $this->get_row( $this->items[ $id ]->deactivate_license() ),
			)
		);
	}
}
