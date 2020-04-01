<?php
/**
 * Main class for Polylang wizard.
 *
 * @since 2.7
 */
class PLL_Wizard {
	/**
	 * Reference to PLL_Model object
	 *
	 * @var object $model
	 */
	protected $model;

	/**
	 * Reference to Polylang options array
	 *
	 * @var array $options
	 */
	protected $options;

	/**
	 * List of steps
	 *
	 * @var array $steps
	 */
	protected $steps = array();

	/**
	 * List of WordPress CSS file handles
	 *
	 * @var array $styles
	 */
	protected $styles = array();

	/**
	 * Constructor
	 *
	 * @param object $polylang Reference to Polylang global object.
	 * @since 2.7
	 */
	public function __construct( &$polylang ) {
		$this->options = &$polylang->options;
		$this->model   = &$polylang->model;

		// Display Wizard page before any other action to ensure displaying it outside the WordPress admin context.
		// Hooked on admin_init with priority 40 to ensure PLL_Wizard_Pro is corretly initialized.
		add_action( 'admin_init', array( $this, 'setup_wizard_page' ), 40 );
		// Add Wizard submenu.
		add_filter( 'pll_settings_tabs', array( $this, 'settings_tabs' ), 10, 1 );
		// Add filter to select screens where to display the notice.
		add_filter( 'pll_can_display_notice', array( $this, 'can_display_notice' ), 10, 2 );

		// Default steps.
		add_filter( 'pll_wizard_steps', array( $this, 'add_step_licenses' ), 100 );
		add_filter( 'pll_wizard_steps', array( $this, 'add_step_languages' ), 200 );
		add_filter( 'pll_wizard_steps', array( $this, 'add_step_media' ), 300 );
		add_filter( 'pll_wizard_steps', array( $this, 'add_step_untranslated_contents' ), 400 );
		add_filter( 'pll_wizard_steps', array( $this, 'add_step_home_page' ), 500 );
		add_filter( 'pll_wizard_steps', array( $this, 'add_step_last' ), 999 );
	}

	/**
	 * Save an activation transient when Polylang is activating to redirect to the wizard
	 *
	 * @param bool $network_wide if activated for all sites in the network.
	 * @since 2.7
	 */
	public static function start_wizard( $network_wide ) {
		$options = get_option( 'polylang' );

		if ( wp_doing_ajax() || $network_wide || ! empty( $options ) ) {
			return;
		}
		set_transient( 'pll_activation_redirect', 1, 30 );
	}

	/**
	 * Redirect to the wizard depending on the context
	 *
	 * @since 2.7
	 */
	public function redirect_to_wizard() {
		if ( get_transient( 'pll_activation_redirect' ) ) {
			$do_redirect = true;
			if ( ( isset( $_GET['page'] ) && 'mlang_wizard' === sanitize_key( $_GET['page'] ) || isset( $_GET['activate-multi'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				delete_transient( 'pll_activation_redirect' );
				$do_redirect = false;
			}

			if ( $do_redirect ) {
				wp_safe_redirect(
					esc_url_raw(
						add_query_arg(
							array(
								'page' => 'mlang_wizard',
							),
							admin_url( 'admin.php' )
						)
					)
				);
				exit;
			}
		}
	}

	/**
	 * Add an admin Polylang submenu to access the wizard
	 *
	 * @param array $tabs Submenus list.
	 * @return array Submenus list updated.
	 * @since 2.7
	 */
	public function settings_tabs( $tabs ) {
		$tabs['wizard'] = __( 'Setup', 'polylang' );
		return $tabs;
	}

	/**
	 * Return if the media step is displayable
	 *
	 * @param array $languages List of language objects.
	 * @return bool
	 * @since 2.7
	 */
	public function is_media_step_displayable( $languages ) {
		$media = array();
		// If there is no language or only one the media step is displayable.
		if ( ! $languages || count( $languages ) < 2 ) {
			return true;
		}
		foreach ( $languages as $language ) {
			$media[ $language->slug ] = $this->model->count_posts(
				$language,
				array(
					'post_type'   => array( 'attachment' ),
					'post_status' => 'inherit',
				)
			);
		}
		return count( array_filter( $media ) ) === 0;
	}

	/**
	 * Check if the licenses step is displayable
	 *
	 * @return bool
	 * @since 2.7
	 */
	public function is_licenses_step_displayable() {
		$licenses = apply_filters( 'pll_settings_licenses', array() );
		return count( $licenses ) > 0;
	}

	/**
	 * Setup the wizard page
	 *
	 * @since 2.7
	 */
	public function setup_wizard_page() {

		PLL_Admin_Notices::add_notice( 'wizard', $this->wizard_notice() );

		$this->redirect_to_wizard();
		if ( ! Polylang::is_wizard() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage options for this site.', 'polylang' ) );
		}

		// Enqueue scripts and styles especially for the wizard.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->steps = apply_filters( 'pll_wizard_steps', $this->steps );
		$step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification

		$this->step = $step && array_key_exists( $step, $this->steps ) ? $step : current( array_keys( $this->steps ) );

		$languages = $this->model->get_languages_list();

		if ( count( $languages ) === 0 && ! in_array( $this->step, array( 'licenses', 'languages' ) ) ) {
			wp_safe_redirect( esc_url_raw( $this->get_step_link( 'languages' ) ) );
			exit;
		}

		if ( count( $languages ) > 0 && $this->model->get_objects_with_no_lang( 1 ) && ! in_array( $this->step, array( 'licenses', 'languages', 'media', 'untranslated-contents' ) ) ) {
			wp_safe_redirect( esc_url_raw( $this->get_step_link( 'untranslated-contents' ) ) );
			exit;
		}

		// Call the handler of the step for going to the next step.
		// Be careful nonce verification with check_admin_referer must be done in each handler.
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		$this->display_wizard_page();
		// Ensure nothing is done after including the page.
		exit;
	}

	/**
	 * Adds some admin screens where to display the wizard notice
	 *
	 * @param bool   $can_display_notice Whether the notice can be displayed.
	 * @param string $notice             The notice name.
	 * @return bool
	 * @since 2.7
	 */
	public function can_display_notice( $can_display_notice, $notice ) {
		if ( ! $can_display_notice && 'wizard' === $notice ) {
			$screen = get_current_screen();
			$can_display_notice = in_array(
				$screen->base,
				array(
					'edit',
					'upload',
					'options-general',
				)
			);
		}
		return $can_display_notice;
	}

	/**
	 * Return html code of the wizard notice
	 *
	 * @since 2.7
	 */
	public function wizard_notice() {
		ob_start();
		include PLL_MODULES_INC . '/wizard/html-wizard-notice.php';
		return ob_get_clean();
	}

	/**
	 * Display the wizard page
	 *
	 * @since 2.7
	 */
	public function display_wizard_page() {
		set_current_screen();
		include PLL_MODULES_INC . '/wizard/view-wizard-page.php';
	}

	/**
	 * Enqueue scripts and styles for the wizard
	 *
	 * @since 2.7
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'polylang_admin', plugins_url( '/css/admin' . $this->get_suffix() . '.css', POLYLANG_FILE ), array(), POLYLANG_VERSION );
		wp_enqueue_style( 'pll-wizard', plugins_url( '/modules/wizard/css/wizard' . $this->get_suffix() . '.css', POLYLANG_FILE ), array( 'dashicons', 'install', 'common', 'forms' ), POLYLANG_VERSION );

		$this->styles = array( 'polylang_admin', 'pll-wizard' );
	}

	/**
	 * Get the suffix to enqueue non minified files in a Debug context
	 *
	 * @return string Empty when SCRIPT_DEBUG equal to true
	 *                otherwise .min
	 * @since 2.7
	 */
	public function get_suffix() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Get the URL for the step's screen.
	 *
	 * @param string $step  slug (default: current step).
	 * @return string       URL for the step if it exists.
	 *                      Empty string on failure.
	 * @since 2.7
	 */
	public function get_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index ], remove_query_arg( 'activate_error' ) );
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string $step  slug (default: current step).
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 * @since 2.7
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
	}

	/**
	 * Add licenses step to the wizard
	 *
	 * @param array $steps List of steps.
	 * @return array List of steps updated.
	 * @since 2.7
	 */
	public function add_step_licenses( $steps ) {
		// Add ajax action on deactivate button in licenses step.
		add_action( 'wp_ajax_pll_deactivate_license', array( $this, 'deactivate_license' ) );

		wp_enqueue_script( 'pll_admin', plugins_url( '/js/admin' . $this->get_suffix() . '.js', POLYLANG_FILE ), array( 'jquery', 'jquery-ui-selectmenu' ), POLYLANG_VERSION, true );
		if ( $this->is_licenses_step_displayable() ) {
			$steps['licenses'] = array(
				'name'    => __( 'Licenses', 'polylang' ),
				'view'    => array( $this, 'display_step_licenses' ),
				'handler' => array( $this, 'save_step_licenses' ),
				'scripts' => array( 'pll_admin' ), // Polylang admin script used by deactivate license button.
				'styles'  => array(),
			);
		}
		return $steps;
	}

	/**
	 * Display the languages step form
	 *
	 * @since 2.7
	 */
	public function display_step_licenses() {
		include PLL_MODULES_INC . '/wizard/view-wizard-step-licenses.php';
	}

	/**
	 * Execute the languages step
	 *
	 * @since 2.7
	 */
	public function save_step_licenses() {
		check_admin_referer( 'pll-wizard', '_pll_nonce' );

		$redirect = $this->get_next_step_link();
		$licenses = apply_filters( 'pll_settings_licenses', array() );

		foreach ( $licenses as $license ) {
			if ( ! empty( $_POST['licenses'][ $license->id ] ) ) {
				$updated_license = $license->activate_license( sanitize_key( $_POST['licenses'][ $license->id ] ) );
				if ( ! empty( $updated_license->license_data ) && false === $updated_license->license_data->success ) {
					// Stay on this step with an error.
					$redirect = add_query_arg(
						array(
							'step'           => $this->step,
							'activate_error' => 'i18n_license_key_error',
						)
					);
				}
			}
		}

		wp_safe_redirect( esc_url_raw( $redirect ) );
		exit;
	}

	/**
	 * Ajax method to deactivate a license
	 *
	 * @since 2.7
	 */
	public function deactivate_license() {
		check_ajax_referer( 'pll-wizard', '_pll_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_POST['id'] ) ) {
			wp_die( 0 );
		}

		$id = substr( sanitize_text_field( wp_unslash( $_POST['id'] ) ), 11 );
		$licenses = apply_filters( 'pll_settings_licenses', array() );
		$license = $licenses[ $id ];
		$license->deactivate_license();

		wp_send_json(
			array(
				'id'   => $id,
				'html' => $license->get_form_field(),
			)
		);
	}

	/**
	 * Add languages step to the wizard
	 *
	 * @param array $steps List of steps.
	 * @return array List of steps updated.
	 * @since 2.7
	 */
	public function add_step_languages( $steps ) {
		wp_enqueue_script( 'pll-wizard-language-choice', plugins_url( '/js/admin' . $this->get_suffix() . '.js', POLYLANG_FILE ), array( 'jquery', 'jquery-ui-selectmenu' ), POLYLANG_VERSION, true );
		wp_register_script( 'pll-wizard-languages', plugins_url( '/modules/wizard/js/languages-step' . $this->get_suffix() . '.js', POLYLANG_FILE ), array( 'jquery', 'jquery-ui-dialog' ), POLYLANG_VERSION, true );
		wp_localize_script(
			'pll-wizard-languages',
			'pll_wizard_params',
			array(
				'i18n_no_language_selected'   => __( 'You need to select a language to be added.', 'polylang' ),
				'i18n_language_already_added' => __( 'You already added this language.', 'polylang' ),
				'i18n_no_language_added'      => __( 'You need to add at least one language.', 'polylang' ),
				'i18n_add_language_needed'    => __( 'You selected a language, however, to be able to continue, you need to add it.', 'polylang' ),
				'i18n_pll_add_language'       => __( 'Impossible to add the language.', 'polylang' ),
				'i18n_pll_invalid_locale'     => __( 'Enter a valid WordPress locale', 'polylang' ),
				'i18n_pll_invalid_slug'       => __( 'The language code contains invalid characters', 'polylang' ),
				'i18n_pll_non_unique_slug'    => __( 'The language code must be unique', 'polylang' ),
				'i18n_pll_invalid_name'       => __( 'The language must have a name', 'polylang' ),
				'i18n_pll_invalid_flag'       => __( 'The flag does not exist', 'polylang' ),
				'i18n_dialog_title'           => __( "A language wasn't added.", 'polylang' ),
				'i18n_dialog_yes_button'      => __( 'Yes', 'polylang' ),
				'i18n_dialog_no_button'       => __( 'No', 'polylang' ),
				'i18n_dialog_ignore_button'   => __( 'Ignore', 'polylang' ),
				'i18n_remove_language_icon'   => __( 'Remove this language', 'polylang' ),
			)
		);
		wp_enqueue_script( 'pll-wizard-languages' );
		wp_enqueue_style( 'pll-wizard-selectmenu', plugins_url( '/css/selectmenu' . $this->get_suffix() . '.css', POLYLANG_FILE ), array( 'dashicons', 'install', 'common', 'wp-jquery-ui-dialog' ), POLYLANG_VERSION );
		$steps['languages'] = array(
			'name'    => __( 'Languages', 'polylang' ),
			'view'    => array( $this, 'display_step_languages' ),
			'handler' => array( $this, 'save_step_languages' ),
			'scripts' => array( 'pll-wizard-languages', 'pll-wizard-language-choice' ),
			'styles'  => array( 'pll-wizard-selectmenu' ),
		);
		return $steps;
	}

	/**
	 * Display the languages step form
	 *
	 * @since 2.7
	 */
	public function display_step_languages() {
		include PLL_MODULES_INC . '/wizard/view-wizard-step-languages.php';
	}

	/**
	 * Execute the languages step
	 *
	 * @since 2.7
	 */
	public function save_step_languages() {
		check_admin_referer( 'pll-wizard', '_pll_nonce' );

		$existing_languages = $this->model->get_languages_list();

		$all_languages = include PLL_SETTINGS_INC . '/languages.php';
		$languages = isset( $_POST['languages'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['languages'] ) ) : false;
		$saved_languages = array();

		// If there is no language added or defined.
		if ( empty( $languages ) && empty( $existing_languages ) ) {
			// Stay on this step with an error.
			wp_safe_redirect(
				esc_url_raw(
					add_query_arg(
						array(
							'step'           => $this->step,
							'activate_error' => 'i18n_no_language_added',
						)
					)
				)
			);
			exit;
		}

		// Otherwise process the languages to add or skip the step if no language has been added.
		if ( ! empty( $languages ) ) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			// Remove duplicate values.
			$languages = array_unique( $languages );
			// For each language add it in Polylang settings.
			foreach ( $languages as $locale ) {
				$saved_languages = $all_languages[ $locale ];

				$saved_languages['slug'] = $saved_languages['code'];
				$saved_languages['rtl'] = (int) ( 'rtl' === $saved_languages['dir'] );
				$saved_languages['term_group'] = 0; // Default term_group.

				$language_added = $this->model->add_language( $saved_languages );

				if ( $language_added instanceof WP_Error && array_key_exists( 'pll_non_unique_slug', $language_added->errors ) ) {
					// Get the slug from the locale : lowercase and dash instead of underscore.
					$saved_languages['slug'] = strtolower( str_replace( '_', '-', $saved_languages['locale'] ) );
					$language_added = $this->model->add_language( $saved_languages );
				}

				if ( $language_added instanceof WP_Error ) {
					// Stay on this step with an error.
					$error_keys = array_keys( $language_added->errors );
					wp_safe_redirect(
						esc_url_raw(
							add_query_arg(
								array(
									'step'           => $this->step,
									'activate_error' => 'i18n_' . reset( $error_keys ),
								)
							)
						)
					);
					exit;
				}
				if ( 'en_US' !== $locale ) {
					wp_download_language_pack( $locale );
				}
			}
		}
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Add media step to the wizard
	 * Add media step to the wizard
	 *
	 * @param array $steps List of steps.
	 * @return array List of steps updated.
	 * @since 2.7
	 */
	public function add_step_media( $steps ) {
		$languages = $this->model->get_languages_list();

		if ( $this->is_media_step_displayable( $languages ) ) {
			$steps['media'] = array(
				'name'    => __( 'Media', 'polylang' ),
				'view'    => array( $this, 'display_step_media' ),
				'handler' => array( $this, 'save_step_media' ),
				'scripts' => array(),
				'styles'  => array(),
			);
		}
		return $steps;
	}

	/**
	 * Display the media step form
	 *
	 * @since 2.7
	 */
	public function display_step_media() {
		include PLL_MODULES_INC . '/wizard/view-wizard-step-media.php';
	}

	/**
	 * Execute the media step
	 *
	 * @since 2.7
	 */
	public function save_step_media() {
		check_admin_referer( 'pll-wizard', '_pll_nonce' );

		$media_support = isset( $_POST['media_support'] ) ? sanitize_key( $_POST['media_support'] ) === 'yes' : false;

		$this->options['media_support'] = $media_support;

		update_option( 'polylang', $this->options );

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Add untranslated contents step to the wizard
	 *
	 * @param array $steps List of steps.
	 * @return array List of steps updated.
	 * @since 2.7
	 */
	public function add_step_untranslated_contents( $steps ) {
		if ( ! $this->model->get_languages_list() || $this->model->get_objects_with_no_lang( 1 ) ) {
			wp_enqueue_script( 'pll-wizard-language-choice', plugins_url( '/js/admin' . $this->get_suffix() . '.js', POLYLANG_FILE ), array( 'jquery', 'jquery-ui-selectmenu' ), POLYLANG_VERSION, true );
			wp_enqueue_style( 'pll-wizard-selectmenu', plugins_url( '/css/selectmenu' . $this->get_suffix() . '.css', POLYLANG_FILE ), array( 'dashicons', 'install', 'common' ), POLYLANG_VERSION );
			$steps['untranslated-contents'] = array(
				'name'    => __( 'Content', 'polylang' ),
				'view'    => array( $this, 'display_step_untranslated_contents' ),
				'handler' => array( $this, 'save_step_untranslated_contents' ),
				'scripts' => array( 'pll-wizard-language-choice' ),
				'styles'  => array( 'pll-wizard-selectmenu' ),
			);
		}
		return $steps;
	}

	/**
	 * Display the untranslated contents step form
	 *
	 * @since 2.7
	 */
	public function display_step_untranslated_contents() {
		include PLL_MODULES_INC . '/wizard/view-wizard-step-untranslated-contents.php';
	}

	/**
	 * Execute the untranslated contents step
	 *
	 * @since 2.7
	 */
	public function save_step_untranslated_contents() {
		check_admin_referer( 'pll-wizard', '_pll_nonce' );

		$lang = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : false;

		if ( empty( $lang ) ) {
			$lang = $this->options['default_lang'];
		}

		$language = $this->model->get_language( $lang );

		while ( $nolang = $this->model->get_objects_with_no_lang( 1000 ) ) {
			if ( ! empty( $nolang['posts'] ) ) {
				$this->model->set_language_in_mass( 'post', $nolang['posts'], $language->slug );
			}
			if ( ! empty( $nolang['terms'] ) ) {
				$this->model->set_language_in_mass( 'term', $nolang['terms'], $language->slug );
			}
		}

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Add home page step to the wizard
	 *
	 * @param array $steps List of steps.
	 * @return array List of steps updated.
	 * @since 2.7
	 */
	public function add_step_home_page( $steps ) {
		$languages = $this->model->get_languages_list();
		$home_page_id = get_option( 'page_on_front' );

		$translations = $this->model->post->get_translations( $home_page_id );

		if ( $home_page_id > 0 && ( ! $languages || count( $languages ) === 1 || count( $translations ) !== count( $languages ) ) ) {
			$steps['home-page'] = array(
				'name'    => __( 'Homepage', 'polylang' ),
				'view'    => array( $this, 'display_step_home_page' ),
				'handler' => array( $this, 'save_step_home_page' ),
				'scripts' => array(),
				'styles'  => array(),
			);
		}
		return $steps;
	}

	/**
	 * Display the home page step form
	 *
	 * @since 2.7
	 */
	public function display_step_home_page() {
		include PLL_MODULES_INC . '/wizard/view-wizard-step-home-page.php';
	}

	/**
	 * Execute the home page step
	 *
	 * @since 2.7
	 */
	public function save_step_home_page() {
		check_admin_referer( 'pll-wizard', '_pll_nonce' );

		$languages = $this->model->get_languages_list();

		$default_language = count( $languages ) > 0 ? $this->options['default_lang'] : null;
		$home_page = isset( $_POST['home_page'] ) ? sanitize_key( $_POST['home_page'] ) : false;
		$home_page_title = isset( $_POST['home_page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['home_page_title'] ) ) : esc_html__( 'Homepage', 'polylang' );
		$home_page_language = isset( $_POST['home_page_language'] ) ? sanitize_key( $_POST['home_page_language'] ) : false;

		$untranslated_languages = isset( $_POST['untranslated_languages'] ) ? array_map( 'sanitize_key', $_POST['untranslated_languages'] ) : array();

		call_user_func(
			apply_filters( 'pll_wizard_create_home_page_translations', array( $this, 'create_home_page_translations' ) ),
			$default_language,
			$home_page,
			$home_page_title,
			$home_page_language,
			$untranslated_languages
		);

		$this->model->clean_languages_cache();

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Create home page translations for each language defined.
	 *
	 * @since 2.7
	 *
	 * @param string $default_language       slug of the default language; null if no default language is defined.
	 * @param int    $home_page              post_id of the home page if it's defined, false otherwise.
	 * @param string $home_page_title        home page title if it's defined, 'Homepage' otherwise.
	 * @param string $home_page_language     slug of the home page if it's defined, false otherwise.
	 * @param array  $untranslated_languages array of languages which needs to have a home page translated.
	 */
	public function create_home_page_translations( $default_language, $home_page, $home_page_title, $home_page_language, $untranslated_languages ) {
		$translations = $this->model->post->get_translations( $home_page );

		foreach ( $untranslated_languages as $language ) {
			$language_properties = $this->model->get_language( $language );
			$id = wp_insert_post(
				array(
					'post_title'  => $home_page_title . ' - ' . $language_properties->name,
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);
			$translations[ $language ] = $id;
			pll_set_post_language( $id, $language );
		}
		pll_save_post_translations( $translations );
	}

	/**
	 * Add last step to the wizard
	 *
	 * @param array $steps List of steps.
	 * @return array List of steps updated.
	 * @since 2.7
	 */
	public function add_step_last( $steps ) {
		$steps['last'] = array(
			'name'    => __( 'Ready!', 'polylang' ),
			'view'    => array( $this, 'display_step_last' ),
			'handler' => array( $this, 'save_step_last' ),
			'scripts' => array(),
			'styles'  => array(),
		);
		return $steps;
	}

	/**
	 * Display the last step form
	 *
	 * @since 2.7
	 */
	public function display_step_last() {
		// We ran the wizard once. So we can dismiss its notice.
		PLL_Admin_Notices::dismiss( 'wizard' );
		include PLL_MODULES_INC . '/wizard/view-wizard-step-last.php';
	}

	/**
	 * Execute the last step
	 *
	 * @since 2.7
	 */
	public function save_step_last() {
		check_admin_referer( 'pll-wizard', '_pll_nonce' );

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}
}
