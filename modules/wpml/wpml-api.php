<?php
/**
 * @package Polylang
 */

/**
 * A class to handle the WPML API based on hooks, introduced since WPML 3.2
 * It partly relies on the legacy API
 *
 * @see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/
 *
 * @since 2.0
 */
class PLL_WPML_API {
	/**
	 * Stores the original language when the language is switched.
	 *
	 * @var PLL_Language|null
	 */
	private static $original_language = null;

	/**
	 * Constructor
	 *
	 * @since 2.0
	 */
	public function __construct() {
		// Site Wide Language informations

		add_filter( 'wpml_active_languages', array( $this, 'wpml_active_languages' ), 10, 2 );
		add_filter( 'wpml_display_language_names', array( $this, 'wpml_display_language_names' ), 10, 5 );
		// wpml_translated_language_name           => not applicable
		add_filter( 'wpml_current_language', 'pll_current_language', 10, 0 );
		add_filter( 'wpml_default_language', 'pll_default_language', 10, 0 );
		// wpml_add_language_selector              => not implemented
		// wpml_footer_language_selector           => not applicable
		add_action( 'wpml_add_language_form_field', array( $this, 'wpml_add_language_form_field' ) );
		add_filter( 'wpml_language_is_active', array( $this, 'wpml_language_is_active' ), 10, 2 );
		add_filter( 'wpml_is_rtl', array( $this, 'wpml_is_rtl' ) );
		// wpml_language_form_input_field          => See wpml_add_language_form_field
		// wpml_language_has_switched              => See wpml_switch_language
		// wpml_element_trid                       => not implemented
		// wpml_get_element_translations           => not implemented
		// wpml_language_switcher                  => not implemented
		// wpml_browser_redirect_language_params   => not implemented
		// wpml_enqueue_browser_redirect_language  => not applicable
		// wpml_enqueued_browser_redirect_language => not applicable
		// wpml_encode_string                      => not applicable
		// wpml_decode_string                      => not applicable

		// Retrieving Language Information for Content

		add_filter( 'wpml_post_language_details', 'wpml_get_language_information', 10, 2 );
		add_action( 'wpml_switch_language', array( __CLASS__, 'wpml_switch_language' ), 10, 2 );
		add_filter( 'wpml_element_language_code', array( $this, 'wpml_element_language_code' ), 10, 3 );
		// wpml_element_language_details           => not applicable

		// Retrieving Localized Content

		add_filter( 'wpml_home_url', 'pll_home_url', 10, 0 );
		add_filter( 'wpml_element_link', 'icl_link_to_element', 10, 7 );
		add_filter( 'wpml_object_id', 'icl_object_id', 10, 4 );
		add_filter( 'wpml_translate_single_string', array( $this, 'wpml_translate_single_string' ), 10, 4 );
		// wpml_translate_string                   => not applicable
		// wpml_unfiltered_admin_string            => not implemented
		add_filter( 'wpml_permalink', array( $this, 'wpml_permalink' ), 10, 2 );
		// wpml_elements_without_translations      => not implemented
		add_filter( 'wpml_get_translated_slug', array( $this, 'wpml_get_translated_slug' ), 10, 3 );

		// Finding the Translation State of Content

		// wpml_element_translation_type           => not implemented
		add_filter( 'wpml_element_has_translations', array( $this, 'wpml_element_has_translations' ), 10, 3 );
		// wpml_master_post_from_duplicate         => not applicable
		// wpml_post_duplicates                    => not applicable

		// Inserting Content

		// wpml_admin_make_post_duplicates         => not applicable
		// wpml_make_post_duplicates               => not applicable
		add_action( 'wpml_register_single_string', 'icl_register_string', 10, 3 );
		// wpml_register_string                    => not applicable
		// wpml_register_string_packages           => not applicable
		// wpml_delete_package_action              => not applicable
		// wpml_show_package_language_ui           => not applicable
		// wpml_set_element_language_details       => not implemented
		// wpml_multilingual_options               => not applicable

		// Miscellaneous

		// wpml_element_type                       => not applicable
		// wpml_setting                            => not applicable
		// wpml_sub_setting                        => not applicable
		// wpml_editor_cf_to_display               => not applicable
		// wpml_tm_save_translation_cf             => not implemented
		// wpml_tm_xliff_export_translated_cf      => not applicable
		// wpml_tm_xliff_export_original_cf        => not applicable
		// wpml_duplicate_generic_string           => not applicable
		// wpml_translatable_user_meta_fields      => not implemented
		// wpml_cross_domain_language_data         => not applicable
		// wpml_get_cross_domain_language_data     => not applicable
		// wpml_loaded                             => not applicable
		// wpml_st_loaded                          => not applicable
		// wpml_tm_loaded                          => not applicable
		// wpml_hide_management_column (3.4.1)     => not applicable
		// wpml_ls_directories_to_scan             => not applicable
		// wpml_ls_model_css_classes               => not applicable
		// wpml_ls_model_language_css_classes      => not applicable
		// wpml_tf_feedback_open_link              => not applicable
		// wpml_sync_custom_field                  => not implemented
		// wpml_sync_all_custom_fields             => not implemented
		// wpml_is_redirected                      => not implemented

		// Updating Content

		// wpml_set_translation_mode_for_post_type => not implemented
	}

	/**
	 * Get a list of the languages enabled for a site
	 *
	 * @since 2.0
	 *
	 * @param mixed         $null Not used
	 * @param array| string $args See arguments of icl_get_languages()
	 * @return array Array of arrays per language
	 */
	public function wpml_active_languages( $null, $args = '' ) {
		return icl_get_languages( $args );
	}

	/**
	 * In WPML, get a language's native and translated name for display in a custom language switcher
	 * Since Polylang does not implement the translated name, always returns only the native name,
	 * so the 3rd, 4th and 5th parameters are not used.
	 *
	 * @since 2.2
	 *
	 * @param mixed  $null        Not used.
	 * @param string $native_name The language native name.
	 * @return string
	 */
	public function wpml_display_language_names( $null, $native_name ) {
		return $native_name;
	}

	/**
	 * Returns an HTML hidden input field with name=”lang” and as value the current language
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function wpml_add_language_form_field() {
		$lang = pll_current_language();
		$field = sprintf( '<input type="hidden" name="lang" value="%s" />', esc_attr( $lang ) );
		$field = apply_filters( 'wpml_language_form_input_field', $field, $lang );
		echo $field; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Find out if a specific language is enabled for the site
	 *
	 * @since 2.0
	 *
	 * @param mixed  $null Not used
	 * @param string $slug Language code
	 * @return bool
	 */
	public function wpml_language_is_active( $null, $slug ) {
		$language = PLL()->model->get_language( $slug );
		return empty( $language->active ) || true === $language->active;
	}

	/**
	 * Find out whether the current language text direction is RTL or not
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function wpml_is_rtl() {
		return pll_current_language( 'is_rtl' );
	}

	/**
	 * Switches whole site to the given language or restores the language that was set when first calling this function.
	 * Unlike the WPML original action, it is not possible to set the current language and the cookie to different values.
	 *
	 * @since 2.7
	 *
	 * @param null|string $lang   Language code to switch into, restores the original language if null.
	 * @param bool|string $cookie Optionally also switches the cookie.
	 * @return void
	 */
	public static function wpml_switch_language( $lang = null, $cookie = false ) {
		if ( null === self::$original_language ) {
			self::$original_language = PLL()->curlang;
		}

		if ( empty( $lang ) ) {
			PLL()->curlang = self::$original_language;
		} elseif ( 'all' === $lang ) {
			PLL()->curlang = null;
		} elseif ( in_array( $lang, pll_languages_list() ) ) {
			PLL()->curlang = PLL()->model->get_language( $lang );
		}

		if ( $cookie && isset( PLL()->choose_lang ) ) {
			PLL()->choose_lang->maybe_setcookie();
		}

		do_action( 'wpml_language_has_switched', $lang, $cookie, self::$original_language );
	}

	/**
	 * Get the language code for a translatable element
	 *
	 * @since 2.0
	 *
	 * @param mixed $language_code
	 * @param array $args          An array with two keys element_id => post_id or term_taxonomy_id, element_type => post type or taxonomy
	 * @return string
	 */
	public function wpml_element_language_code( $language_code, $args ) {
		$type = $args['element_type'];
		$id   = $args['element_id'];

		if ( 'post' === $type || pll_is_translated_post_type( $type ) ) {
			return pll_get_post_language( $id );
		}

		if ( 'term' === $type || pll_is_translated_taxonomy( $type ) ) {
			$term = get_term_by( 'term_taxonomy_id', $id );
			if ( $term instanceof WP_Term ) {
				$id = $term->term_id;
			}
			return pll_get_term_language( $id );
		}

		return $language_code;
	}

	/**
	 * Translates a string
	 *
	 * @since 2.0
	 *
	 * @param string      $string  The string's original value
	 * @param string      $context The string's registered context
	 * @param string      $name    The string's registered name
	 * @param null|string $lang    Optional, return the translation in this language, defaults to current language
	 * @return string The translated string
	 */
	public function wpml_translate_single_string( $string, $context, $name, $lang = null ) {
		$has_translation = null; // Passed by reference
		return icl_translate( $context, $name, $string, false, $has_translation, $lang );
	}

	/**
	 * Converts a permalink to a language specific permalink
	 *
	 * @since 2.2
	 *
	 * @param string      $url  The url to filter
	 * @param null|string $lang Langage code, optional, defaults to the current language
	 * @return string
	 */
	public function wpml_permalink( $url, $lang = '' ) {
		$lang = PLL()->model->get_language( $lang );

		if ( empty( $lang ) && ! empty( PLL()->curlang ) ) {
			$lang = PLL()->curlang;
		}

		return empty( $lang ) ? $url : PLL()->links_model->switch_language_in_link( $url, $lang );
	}

	/**
	 * Translates a post type slug
	 *
	 * @since 2.2
	 *
	 * @param string $slug      Post type slug
	 * @param string $post_type Post type name
	 * @param string $lang      Optional language code (defaults to current language)
	 * @return string
	 */
	public function wpml_get_translated_slug( $slug, $post_type, $lang = null ) {
		if ( isset( PLL()->translate_slugs ) ) {
			if ( empty( $lang ) ) {
				$lang = pll_current_language();
			}

			$slug = PLL()->translate_slugs->slugs_model->get_translated_slug( $post_type, $lang );
		}
		return $slug;
	}

	/**
	 * Find out whether a post type or a taxonomy term is translated
	 *
	 * @since 2.0
	 *
	 * @param mixed  $null
	 * @param int    $id   The post_id or term_id
	 * @param string $type The post type or taxonomy
	 * @return bool
	 */
	public function wpml_element_has_translations( $null, $id, $type ) {
		if ( 'post' === $type || pll_is_translated_post_type( $type ) ) {
			return count( pll_get_post_translations( $id ) ) > 1;
		} elseif ( 'term' === $type || pll_is_translated_taxonomy( $type ) ) {
			return count( pll_get_term_translations( $id ) ) > 1;
		}

		return false;
	}
}
