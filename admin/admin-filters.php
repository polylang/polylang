<?php

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

		// Upgrades languages files after a core upgrade ( timing is important )
		// Backward compatibility WP < 4.0 *AND* Polylang < 1.6
		add_action( '_core_updated_successfully', array( $this, 'upgrade_languages' ), 1 ); // since WP 3.3

		// Upgrades plugins and themes translations files
		add_filter( 'themes_update_check_locales', array( $this, 'update_check_locales' ) );
		add_filter( 'plugins_update_check_locales', array( $this, 'update_check_locales' ) );

		// We need specific filters for German and Danish
		$specific_locales = array( 'da_DK', 'de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'ca', 'sr_RS', 'bs_BA' );
		if ( array_intersect( $this->model->get_languages_list( array( 'fields' => 'locale' ) ), $specific_locales ) ) {
			add_filter( 'sanitize_title', array( $this, 'sanitize_title' ), 10, 3 );
			add_filter( 'sanitize_user', array( $this, 'sanitize_user' ), 10, 3 );
		}

		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * Modifies the widgets forms to add our language dropdown list
	 *
	 * @since 0.3
	 *
	 * @param object $widget   Widget instance
	 * @param null   $return   Not used
	 * @param array  $instance Widget settings
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
	 * Upgrades languages files after a core upgrade
	 * only for backward compatibility WP < 4.0 *AND* Polylang < 1.6
	 *
	 * @since 0.6
	 *
	 * @param string $version new WP version
	 */
	public function upgrade_languages( $version ) {
		// $GLOBALS['wp_version'] is the old WP version
		if ( version_compare( $version, '4.0', '>=' ) && version_compare( $GLOBALS['wp_version'], '4.0', '<' ) ) {

			/** This filter is documented in wp-admin/includes/update-core.php */
			apply_filters( 'update_feedback', __( 'Upgrading language files&#8230;', 'polylang' ) );
			PLL_Upgrade::download_language_packs();
		}
	}

	/**
	 * Allows to update translations files for plugins and themes
	 *
	 * @since 1.6
	 *
	 * @param array $locales Not used
	 * @return array list of locales to update
	 */
	public function update_check_locales( $locales ) {
		return $this->model->get_languages_list( array( 'fields' => 'locale' ) );
	}

	/**
	 * Filters the locale according to the current language instead of the language
	 * of the admin interface
	 *
	 * @since 2.0
	 *
	 * @param string $locale
	 * @return string
	 */
	public function get_locale( $locale ) {
		if ( isset( $_POST['post_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['post_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( isset( $_POST['term_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( isset( $_POST['inline_lang_choice'] ) && $lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$locale = $lang->locale;
		} elseif ( ! empty( $this->curlang ) ) {
			$locale = $this->curlang->locale;
		}

		return $locale;
	}

	/**
	 * Maybe fix the result of sanitize_title() in case the languages include German or Danish
	 * Without this filter, if language of the title being sanitized is different from the language
	 * used for the admin interface and if one this language is German or Danish, some specific
	 * characters such as ä, ö, ü, ß are incorrectly sanitized.
	 *
	 * @since 2.0
	 *
	 * @param string $title     Sanitized title.
	 * @param string $raw_title The title prior to sanitization.
	 * @param string $context   The context for which the title is being sanitized.
	 * @return string
	 */
	public function sanitize_title( $title, $raw_title, $context ) {
		static $once = false;

		if ( ! $once && 'save' == $context && ! empty( $title ) ) {
			$once = true;
			add_filter( 'locale', array( $this, 'get_locale' ), 20 ); // After the filter for the admin interface
			$title = sanitize_title( $raw_title, '', $context );
			remove_filter( 'locale', array( $this, 'get_locale' ), 20 );
			$once = false;
		}
		return $title;
	}

	/**
	 * Maybe fix the result of sanitize_user() in case the languages include German or Danish
	 *
	 * @since 2.0
	 *
	 * @param string $username     Sanitized username.
	 * @param string $raw_username The username prior to sanitization.
	 * @param bool   $strict       Whether to limit the sanitization to specific characters. Default false.
	 * @return string
	 */
	public function sanitize_user( $username, $raw_username, $strict ) {
		static $once = false;

		if ( ! $once ) {
			$once = true;
			add_filter( 'locale', array( $this, 'get_locale' ), 20 ); // After the filter for the admin interface
			$username = sanitize_user( $raw_username, '', $strict );
			remove_filter( 'locale', array( $this, 'get_locale' ), 20 );
			$once = false;
		}
		return $username;
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
}
