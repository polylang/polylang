<?php

/**
 * base class for all settings
 *
 * @since 1.8
 */
class PLL_Settings_Module {
	public $active_option, $configure;
	public $module, $title, $description;
	public $options;
	protected $action_links, $buttons;

	/**
	 * constructor
	 *
	 * @since 1.8
	 *
	 * @param object $polylang polylang object
	 * @param array $args
	 */
	public function __construct( &$polylang, $args ) {
		$this->options = &$polylang->options;
		$this->model = &$polylang->model;
		$this->links_model = &$polylang->links_model;

		$args = wp_parse_args( $args, array(
			'title' => '',
			'description' => '',
			'active_option' => false,
		) );

		foreach ( $args as $prop => $value ) {
			$this->$prop = $value;
		}

		// all possible action links, even if not always a link ;- )
		$this->action_links = array(
			'configure' => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Configure this module', 'polylang' ),
				'#',
				esc_html__( 'Settings', 'polylang' )
			),

			'deactivate' => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Deactivate this module', 'polylang' ),
				wp_nonce_url( '?page=mlang&amp;tab=modules&amp;pll_action=deactivate&amp;noheader=true&amp;module=' . $this->module, 'pll_deactivate' ),
				esc_html__( 'Deactivate', 'polylang' )
			),

			'activate' => sprintf(
				'<a title="%s" href="%s">%s</a>',
				esc_attr__( 'Activate this module', 'polylang' ),
				wp_nonce_url( '?page=mlang&amp;tab=modules&amp;pll_action=activate&amp;noheader=true&amp;module=' . $this->module, 'pll_activate' ),
				esc_html__( 'Activate', 'polylang' )
			),

			'activated' => esc_html__( 'Activated', 'polylang' ),

			'deactivated' => esc_html__( 'Deactivated', 'polylang' ),
		);

		$this->buttons = array(
			'cancel' => sprintf( '<button type="button" class="button button-secondary cancel">%s</button>', esc_html__( 'Cancel' ) ),
			'save'   => sprintf( '<button type="button" class="button button-primary save">%s</button>', esc_html__( 'Save Changes' ) ),
		);

		// ajax action to save options
		add_action( 'wp_ajax_pll_save_options', array( $this, 'save_options' ) );
	}

	/**
	 * tells if the module is active
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_active() {
		return empty( $this->active_option ) || ! empty( $this->options[ $this->active_option ] );
	}

	/**
	 * activates the module
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
	 * deactivates the module
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
	 * protected method to display a configuration form
	 *
	 * @since 1.8
	 *
	 */
	protected function form() {
		// child classes can provide a form
	}

	/**
	 * public method returning the form if any
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function get_form() {
		static $form = false;

		// read the form only once
		if ( false === $form ) {
			ob_start();
			$this->form();
			$form = ob_get_clean();
		}

		return $form;
	}

	/**
	 * allows child classes to validate their options before saving
	 *
	 * @since 1.8
	 *
	 * @param array $options raw options
	 * @param array options
	 */
	protected function update( $options ) {
		return array(); // it's responsibility of the child class to decide what is saved
	}

	/**
	 * ajax method to save the options
	 *
	 * @since 1.8
	 */
	public function save_options() {
		check_ajax_referer( 'pll_options', '_pll_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( $this->module == $_POST['module'] ) {
			// it's up to the child class to decide which options are saved, whether there are errors or not
			$post = array_diff_key( $_POST, array_flip( array( 'action', 'module', 'pll_ajax_backend', '_pll_nonce' ) ) );
			$options = $this->update( $post );
			$this->options = array_merge( $this->options, $options );
			update_option( 'polylang', $this->options );

			// refresh language cache in case home urls have been modified
			$this->model->clean_languages_cache();

			// refresh rewrite rules in case rewrite,  hide_default, post types or taxonomies options have been modified
			// don't use flush_rewrite_rules as we don't have the right links model and permastruct
			delete_option( 'rewrite_rules' );

			ob_start();

			if ( ! get_settings_errors() ) {
				// send update message
				add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'updated' );
				settings_errors();
				$x = new WP_Ajax_Response( array( 'what' => 'success', 'data' => ob_get_clean() ) );
				$x->send();
			} else {
				// send error messages
				settings_errors();
				$x = new WP_Ajax_Response( array( 'what' => 'error', 'data' => ob_get_clean() ) );
				$x->send();
			}
		}
	}

	/**
	 * get the row actions
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	protected function get_actions() {
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
	 * get the actions links
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function get_action_links() {
		return array_intersect_key( $this->action_links, array_flip( $this->get_actions() ) );
	}

	/**
	 * default upgrade message ( to pro version )
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function default_upgrade_message() {
		return sprintf(
			'%s <a href="%s">%s</a>',
			__( 'You need Polylang Pro to enable this feature.', 'polylang' ),
			'https://polylang.pro',
			__( 'Upgrade now.', 'polylang' )
		);
	}

	/**
	 * allows child classes to display an upgrade message
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function get_upgrade_message() {
		return '';
	}

	/**
	 * get the buttons
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_buttons() {
		return $this->buttons;
	}
}
