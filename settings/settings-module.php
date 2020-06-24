<?php
/**
 * @package Polylang
 */

/**
 * Base class for all settings
 *
 * @since 1.8
 */
class PLL_Settings_Module {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Instance of PLL_Model.
	 *
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Stores if the module is active.
	 *
	 * @var bool
	 */
	public $active_option;

	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 100;

	/**
	 * Stores the module name.
	 * It must be unique.
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Stores the module title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Stores the module description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Stores the settings actions.
	 *
	 * @var array
	 */
	protected $action_links;

	/**
	 * Stores html fragment for the buttons.
	 *
	 * @var array
	 */
	protected $buttons;

	/**
	 * Stores html form when provided by a child class.
	 *
	 * @var bool|string
	 */
	protected $form = false;

	/**
	 * Constructor
	 *
	 * @since 1.8
	 *
	 * @param object $polylang Polylang object
	 * @param array  $args
	 */
	public function __construct( &$polylang, $args ) {
		$this->options = &$polylang->options;
		$this->model = &$polylang->model;
		$this->links_model = &$polylang->links_model;

		$args = wp_parse_args(
			$args,
			array(
				'title'         => '',
				'description'   => '',
				'active_option' => false,
			)
		);

		foreach ( $args as $prop => $value ) {
			$this->$prop = $value;
		}

		// All possible action links, even if not always a link ;-)
		$this->action_links = array(
			'configure'   => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Configure this module', 'polylang' ),
				'#',
				esc_html__( 'Settings', 'polylang' )
			),
			'deactivate'  => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Deactivate this module', 'polylang' ),
				esc_url( wp_nonce_url( '?page=mlang&tab=modules&pll_action=deactivate&noheader=true&module=' . $this->module, 'pll_deactivate' ) ),
				esc_html__( 'Deactivate', 'polylang' )
			),
			'activate'    => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Activate this module', 'polylang' ),
				esc_url( wp_nonce_url( '?page=mlang&tab=modules&pll_action=activate&noheader=true&module=' . $this->module, 'pll_activate' ) ),
				esc_html__( 'Activate', 'polylang' )
			),
			'activated'   => esc_html__( 'Activated', 'polylang' ),
			'deactivated' => esc_html__( 'Deactivated', 'polylang' ),
		);

		$this->buttons = array(
			'cancel' => sprintf( '<button type="button" class="button button-secondary cancel">%s</button>', esc_html__( 'Cancel', 'polylang' ) ),
			'save'   => sprintf( '<button type="button" class="button button-primary save">%s</button>', esc_html__( 'Save Changes', 'polylang' ) ),
		);

		// Ajax action to save options
		add_action( 'wp_ajax_pll_save_options', array( $this, 'save_options' ) );
	}

	/**
	 * Tells if the module is active
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_active() {
		return empty( $this->active_option ) || ! empty( $this->options[ $this->active_option ] );
	}

	/**
	 * Activates the module
	 *
	 * @since 1.8
	 */
	public function activate() {
		if ( ! empty( $this->active_option ) ) {
			$this->options[ $this->active_option ] = true;
			update_option( 'polylang', $this->options );
		}
	}

	/**
	 * Deactivates the module
	 *
	 * @since 1.8
	 */
	public function deactivate() {
		if ( ! empty( $this->active_option ) ) {
			$this->options[ $this->active_option ] = false;
			update_option( 'polylang', $this->options );
		}
	}

	/**
	 * Protected method to display a configuration form
	 *
	 * @since 1.8
	 */
	protected function form() {
		// Child classes can provide a form
	}

	/**
	 * Public method returning the form if any
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function get_form() {
		// Read the form only once
		if ( false === $this->form ) {
			ob_start();
			$this->form();
			$this->form = ob_get_clean();
		}

		return $this->form;
	}

	/**
	 * Allows child classes to validate their options before saving
	 *
	 * @since 1.8
	 *
	 * @param array $options Raw options
	 * @return array Options
	 */
	protected function update( $options ) {
		return array(); // It's responsibility of the child class to decide what is saved
	}

	/**
	 * Ajax method to save the options
	 *
	 * @since 1.8
	 */
	public function save_options() {
		check_ajax_referer( 'pll_options', '_pll_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( isset( $_POST['module'] ) && $this->module === $_POST['module'] ) {
			// It's up to the child class to decide which options are saved, whether there are errors or not
			$post = array_diff_key( $_POST, array_flip( array( 'action', 'module', 'pll_ajax_backend', '_pll_nonce' ) ) );
			$options = $this->update( $post );
			$this->options = array_merge( $this->options, $options );
			update_option( 'polylang', $this->options );

			// Refresh language cache in case home urls have been modified
			$this->model->clean_languages_cache();

			// Refresh rewrite rules in case rewrite,  hide_default, post types or taxonomies options have been modified
			// Don't use flush_rewrite_rules as we don't have the right links model and permastruct
			delete_option( 'rewrite_rules' );

			ob_start();

			if ( ! get_settings_errors() ) {
				// Send update message
				add_settings_error( 'general', 'settings_updated', __( 'Settings saved.', 'polylang' ), 'updated' );
				settings_errors();
				$x = new WP_Ajax_Response( array( 'what' => 'success', 'data' => ob_get_clean() ) );
				$x->send();
			} else {
				// Send error messages
				settings_errors();
				$x = new WP_Ajax_Response( array( 'what' => 'error', 'data' => ob_get_clean() ) );
				$x->send();
			}
		}
	}

	/**
	 * Get the row actions
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	protected function get_actions() {
		$actions = array();

		if ( $this->is_active() && $this->get_form() ) {
			$actions[] = 'configure';
		}

		if ( $this->active_option ) {
			$actions[] = $this->is_active() ? 'deactivate' : 'activate';
		}

		if ( empty( $actions ) ) {
			$actions[] = $this->is_active() ? 'activated' : 'deactivated';
		}

		return $actions;
	}

	/**
	 * Get the actions links
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function get_action_links() {
		return array_intersect_key( $this->action_links, array_flip( $this->get_actions() ) );
	}

	/**
	 * Default upgrade message ( to Pro version )
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function default_upgrade_message() {
		return sprintf(
			'%s <a href="%s">%s</a>',
			__( 'To enable this feature, you need Polylang Pro.', 'polylang' ),
			'https://polylang.pro',
			__( 'Upgrade now.', 'polylang' )
		);
	}

	/**
	 * Allows child classes to display an upgrade message
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function get_upgrade_message() {
		return '';
	}

	/**
	 * Get the buttons
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_buttons() {
		return $this->buttons;
	}
}
