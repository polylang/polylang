<?php
/**
 * @package Polylang
 */

/**
 * Setup miscellaneous admin filters as well as filters common to admin and frontend
 *
 * @since 1.2
 */
class PLL_Admin_Filters extends PLL_Filters {

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		// Widgets languages filter
		add_action( 'in_widget_form', array( $this, 'in_widget_form' ), 10, 3 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), 10, 4 );

		// Language management for users
		add_action( 'personal_options_update', array( $this, 'personal_options_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'personal_options_update' ) );
		add_action( 'personal_options', array( $this, 'personal_options' ) );

		// Upgrades plugins and themes translations files
		add_filter( 'themes_update_check_locales', array( $this, 'update_check_locales' ) );
		add_filter( 'plugins_update_check_locales', array( $this, 'update_check_locales' ) );

		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		// Add post state for translations of the privacy policy page
		add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );
	}

	/**
	 * Modifies the widgets forms to add our language dropdown list
	 *
	 * @since 0.3
	 *
	 * @param object $widget   Widget instance
	 * @param null   $return   Not used
	 * @param array  $instance Widget settings
	 * @return void
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$screen = get_current_screen();

		// Test the Widgets screen and the Customizer to avoid displaying the option in page builders
		// Saving the widget reloads the form. And curiously the action is in $_REQUEST but neither in $_POST, nor in $_GET.
		if ( ( isset( $screen ) && 'widgets' === $screen->base ) || ( isset( $_REQUEST['action'] ) && 'save-widget' === $_REQUEST['action'] ) || isset( $GLOBALS['wp_customize'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$dropdown = new PLL_Walker_Dropdown();

			$dropdown_html = $dropdown->walk(
				array_merge(
					array( (object) array( 'slug' => 0, 'name' => __( 'All languages', 'polylang' ) ) ),
					$this->model->get_languages_list()
				),
				-1,
				array(
					'name'     => $widget->id . '_lang_choice',
					'class'    => 'tags-input pll-lang-choice',
					'selected' => empty( $instance['pll_lang'] ) ? '' : $instance['pll_lang'],
				)
			);

			printf(
				'<p><label for="%1$s">%2$s %3$s</label></p>',
				esc_attr( $widget->id . '_lang_choice' ),
				esc_html__( 'The widget is displayed for:', 'polylang' ),
				$dropdown_html // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}
	}

	/**
	 * Called when widget options are saved
	 * saves the language associated to the widget
	 *
	 * @since 0.3
	 *
	 * @param array  $instance     Widget options
	 * @param array  $new_instance Not used
	 * @param array  $old_instance Not used
	 * @param object $widget       WP_Widget object
	 * @return array Widget options
	 */
	public function widget_update_callback( $instance, $new_instance, $old_instance, $widget ) {
		$key = $widget->id . '_lang_choice';

		if ( ! empty( $_POST[ $key ] ) && $lang = $this->model->get_language( sanitize_key( $_POST[ $key ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$instance['pll_lang'] = $lang->slug;
		} else {
			unset( $instance['pll_lang'] );
		}

		return $instance;
	}

	/**
	 * Updates language user preference set in user profile
	 *
	 * @since 0.4
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function personal_options_update( $user_id ) {
		// Biography translations
		foreach ( $this->model->get_languages_list() as $lang ) {
			$meta = $lang->slug == $this->options['default_lang'] ? 'description' : 'description_' . $lang->slug;
			$description = empty( $_POST[ 'description_' . $lang->slug ] ) ? '' : trim( $_POST[ 'description_' . $lang->slug ] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

			/** This filter is documented in wp-includes/user.php */
			$description = apply_filters( 'pre_user_description', $description ); // Applies WP default filter wp_filter_kses
			update_user_meta( $user_id, $meta, $description );
		}
	}

	/**
	 * Outputs hidden information to modify the biography form with js
	 *
	 * @since 0.4
	 *
	 * @param object $profileuser
	 * @return void
	 */
	public function personal_options( $profileuser ) {
		foreach ( $this->model->get_languages_list() as $lang ) {
			$meta = $lang->slug == $this->options['default_lang'] ? 'description' : 'description_' . $lang->slug;

			/** This filter is documented in wp-includes/user.php */
			$description = apply_filters( 'user_description', get_user_meta( $profileuser->ID, $meta, true ) ); // Applies WP default filter wp_kses_data

			printf(
				'<input type="hidden" class="biography" name="%s___%s" value="%s" />',
				esc_attr( $lang->slug ),
				esc_attr( $lang->name ),
				esc_attr( $description )
			);
		}
	}

	/**
	 * Allows to update translations files for plugins and themes.
	 *
	 * @since 1.6
	 *
	 * @param string[] $locales List of locales to update for plugins and themes.
	 * @return string[]
	 */
	public function update_check_locales( $locales ) {
		return array_merge( $locales, $this->model->get_languages_list( array( 'fields' => 'locale' ) ) );
	}

	/**
	 * Adds a text direction dependent class to the body
	 *
	 * @since 2.2
	 *
	 * @param string $classes Space-separated list of CSS classes.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		if ( ! empty( $this->curlang ) ) {
			$classes .= ' pll-dir-' . ( $this->curlang->is_rtl ? 'rtl' : 'ltr' );
		}
		return $classes;
	}

	/**
	 * Adds post state for translations of the privacy policy page.
	 *
	 * @since 2.7
	 *
	 * @param string[] $post_states An array of post display states.
	 * @param WP_Post  $post        The current post object.
	 * @return string[]
	 */
	public function display_post_states( $post_states, $post ) {
		$page_for_privacy_policy = get_option( 'wp_page_for_privacy_policy' );

		if ( $page_for_privacy_policy && in_array( $post->ID, $this->model->post->get_translations( $page_for_privacy_policy ) ) ) {
			$post_states['page_for_privacy_policy'] = __( 'Privacy Policy Page', 'polylang' );
		}

		return $post_states;
	}
}
