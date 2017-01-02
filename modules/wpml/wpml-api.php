<?php

/**
 * A class to handle the WPML API based on hooks, introduced since WPML 3.2
 * It partly relies on the legacy API
 * @see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/
 *
 * @since 2.0
 */
class PLL_WPML_API {

	/**
	 * Constructor
	 *
	 * @since 2.0
	 */
	public function __construct() {
		// Site Wide Language informations

		add_filter( 'wpml_active_languages', array( $this, 'wpml_active_languages' ), 10, 2 );
		// wpml_display_language_names        => not implemented
		// wpml_translated_language_name      => not applicable
		add_filter( 'wpml_current_language', 'pll_current_language', 10, 0 );
		add_filter( 'wpml_default_language', 'pll_default_language', 10, 0 );
		// wpml_add_language_selector         => not implemented
		// wpml_footer_language_selector      => not applicable
		add_action( 'wpml_add_language_form_field', array( $this, 'wpml_add_language_form_field' ) );
		add_filter( 'wpml_language_is_active', array( $this, 'wpml_language_is_active' ), 10, 2 );
		add_filter( 'wpml_is_rtl' , array( $this, 'wpml_is_rtl' ) );
		// wpml_language_form_input_field     => See wpml_add_language_form_field
		// wpml_language_has_switched         => not implemented

		// Retrieving Language Information for Content

		add_filter( 'wpml_post_language_details', 'wpml_get_language_information', 10, 2 );
		// wpml_switch_language               => not implemented
		add_filter( 'wpml_element_language_code', array( $this, 'wpml_element_language_code' ), 10, 3 );
		// wpml_element_language_details      => not applicable

		// Retrieving Localized Content

		add_filter( 'wpml_home_url', 'pll_home_url', 10, 0 );
		add_filter( 'wpml_element_link', 'icl_link_to_element' , 10, 7 );
		add_filter( 'wpml_object_id', 'icl_object_id', 10, 4 );
		add_filter( 'wpml_translate_single_string', array( $this, 'wpml_translate_single_string' ), 10, 4 );
		// wpml_translate_string              => not applicable
		// wpml_unfiltered_admin_string       => not implemented
		add_filter( 'wpml_permalink', array( PLL()->links_model, 'switch_language_in_link' ), 10, 2 );
		// wpml_elements_without_translations => not implemented
		// wpml_get_translated_slug           => not implemented

		// Finding the Translation State of Content

		// wpml_element_translation_type
		add_filter( 'wpml_element_has_translations', array( $this, 'wpml_element_has_translations' ), 10, 3 );
		// wpml_master_post_from_duplicate    => not applicable
		// wpml_post_duplicates               => not applicable

		// Inserting Content

		// wpml_admin_make_post_duplicates    => not applicable
		// wpml_make_post_duplicates          => not applicable
		add_action( 'wpml_register_single_string', 'icl_register_string', 10, 3 );
		// wpml_register_string               => not applicable
		// wpml_register_string_packages      => not applicable
		// wpml_delete_package_action         => not applicable
		// wpml_show_package_language_ui      => not applicable
		// wpml_set_element_language_details  => not implemented

		// Miscellaneous

		// wpml_element_type                   => not applicable
		// wpml_setting                        => not applicable
		// wpml_sub_setting                    => not applicable
		// wpml_editor_cf_to_display           => not applicable
		// wpml_tm_save_translation_cf         => not implemented
		// wpml_tm_xliff_export_translated_cf  => not applicable
		// wpml_tm_xliff_export_original_cf    => not applicable
		// wpml_duplicate_generic_string       => not applicable
		// wpml_translatable_user_meta_fields  => not implemented
		// wpml_cross_domain_language_data     => not applicable
		// wpml_get_cross_domain_language_data => not applicable
		// wpml_loaded                         => not applicable
		// wpml_st_loaded                      => not applicable
		// wpml_tm_loaded                      => not applicable
	}

	/**
	 * Get a list of the languages enabled for a site
	 *
	 * @since 2.0
	 *
	 * @param mixed         $null not used
	 * @param array| string $args see arguments of icl_get_languages()
	 * @return array array of arrays per language
	 */
	public function wpml_active_languages( $null, $args = '' ) {
		return icl_get_languages( $args );
	}

	/**
	 * Returns an HTML hidden input field with name=”lang” and as value the current language
	 *
	 * @since 2.0
	 */
	public function wpml_add_language_form_field() {
		$lang = pll_current_language();
		$field = sprintf( '<input type="hidden" name="lang" value="%s" />', esc_attr( $lang ) );
		$field = apply_filters( 'wpml_language_form_input_field', $field, $lang );
		echo $field;
	}

	/**
	 * Find out if a specific language is enabled for the site
	 *
	 * @since 2.0
	 *
	 * @param mixed  $null not used
	 * @param string $slug language code
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
	 * @param mixed  $null not used
	 * @return bool
	 */
	public function wpml_is_rtl( $null ) {
		return pll_current_language( 'is_rtl' );
	}

	/**
	 * Get the language code for a translatable element
	 *
	 * @since 2.0
	 *
	 * @param mixed $language_code
	 * @param array $args          an array with two keys element_id => post_id or term_taxonomy_id, element_type => post type or taxonomy
	 * @return string
	 */
	public function wpml_element_language_code( $language_code, $args ) {
		$type = $args['element_type'];
		$id = $args['element_id'];
		$pll_type = ( 'post' == $type || pll_is_translated_post_type( $type ) ) ? 'post' : ( 'term' == $type || pll_is_translated_taxonomy( $type ) ? 'term' : false );
		if ( 'term' === $pll_type && $term = wpcom_vip_get_term_by( 'term_taxonomy_id', $id ) ) {
			$id = $term->term_id;
		}
		return $pll_type ? call_user_func( "pll_get_{$pll_type}_language", $id ) : $language_code;
	}

	/**
	 * Translates a string
	 *
	 * @since 2.0
	 *
	 * @param string      $string  the string's original value
	 * @param string      $context the string's registered context
	 * @param string      $name    the string's registered name
	 * @param null|string $lang    optional, return the translation in this language, defaults to current language
	 * @return string the translated string
	 */
	public function wpml_translate_single_string( $string, $context, $name, $lang = null ) {
		$has_translation = null; // Passed by reference
		return icl_translate( $context, $name, $string, false, $has_translation, $lang );
	}

	/**
	 * Find out whether a post type or a taxonomy term is translated
	 *
	 * @since 2.0
	 *
	 * @param mixed  $null
	 * @param int    $id   post_id or term_id
	 * @param string $type post type or taxonomy
	 * @return bool
	 */
	public function wpml_element_has_translations( $null, $id, $type ) {
		$pll_type = ( 'post' == $type || pll_is_translated_post_type( $type ) ) ? 'post' : ( 'term' == $type || pll_is_translated_taxonomy( $type ) ? 'term' : false );
		return ( $pll_type && $translations = call_user_func( "pll_get_{$pll_type}_translations", $id ) ) ? count( $translations ) > 1 : false;
	}
}
