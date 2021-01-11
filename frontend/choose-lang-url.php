<?php
/**
 * @package Polylang
 */

/**
 * Choose the language when the language code is added to all urls
 * The language is set in plugins_loaded with priority 1 as done by WPML
 * Some actions have to be delayed to wait for $wp_rewrite availability
 *
 * @since 1.2
 */
class PLL_Choose_Lang_Url extends PLL_Choose_Lang {
	/**
	 * The name of the index file which is the entry point to all requests.
	 * We need this before the global $wp_rewrite is created.
	 * Also hardcoded in WP_Rewrite.
	 *
	 * @var string
	 */
	protected $index = 'index.php';

	/**
	 * Sets the language
	 *
	 * @since 1.8
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		if ( ! did_action( 'pll_language_defined' ) ) {
			$this->set_language_from_url();
		}

		add_action( 'request', array( $this, 'request' ) );
	}

	/**
	 * Finds the language according to information found in the url
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function set_language_from_url() {
		$host      = str_replace( 'www.', '', wp_parse_url( $this->links_model->home, PHP_URL_HOST ) ); // Remove www. for the comparison
		$home_path = (string) wp_parse_url( $this->links_model->home, PHP_URL_PATH );

		$requested_url   = pll_get_requested_url();
		$requested_host  = str_replace( 'www.', '', wp_parse_url( $requested_url, PHP_URL_HOST ) ); // Remove www. for the comparison
		$requested_path  = rtrim( str_replace( $this->index, '', wp_parse_url( $requested_url, PHP_URL_PATH ) ), '/' ); // Some PHP setups turn requests for / into /index.php in REQUEST_URI
		$requested_query = wp_parse_url( $requested_url, PHP_URL_QUERY );

		// Home is requested
		if ( $requested_host === $host && $requested_path === $home_path && empty( $requested_query ) ) {
			$this->home_language();
			add_action( 'setup_theme', array( $this, 'home_requested' ) );
		}

		// Take care to post & page preview http://wordpress.org/support/topic/static-frontpage-url-parameter-url-language-information
		elseif ( isset( $_GET['preview'] ) && ( ( isset( $_GET['p'] ) && $id = (int) $_GET['p'] ) || ( isset( $_GET['page_id'] ) && $id = (int) $_GET['page_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$curlang = ( $lg = $this->model->post->get_language( $id ) ) ? $lg : $this->model->get_language( $this->options['default_lang'] );
		}

		// Take care to ( unattached ) attachments
		elseif ( isset( $_GET['attachment_id'] ) && $id = (int) $_GET['attachment_id'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$curlang = ( $lg = $this->model->post->get_language( $id ) ) ? $lg : $this->get_preferred_language();
		}

		elseif ( $slug = $this->links_model->get_language_from_url() ) {
			$curlang = $this->model->get_language( $slug );
		}

		elseif ( $this->options['hide_default'] ) {
			$curlang = $this->model->get_language( $this->options['default_lang'] );
		}

		// If no language found, check_language_code_in_url() will attempt to find one and redirect to the correct url
		// Otherwise a 404 will be fired in the preferred language
		$this->set_language( empty( $curlang ) ? $this->get_preferred_language() : $curlang );
	}


	/**
	 * Adds the current language in query vars
	 * useful for subdomains and multiple domains
	 *
	 * @since 1.8
	 *
	 * @param array $qv main request query vars
	 * @return array modified query vars
	 */
	public function request( $qv ) {
		// FIXME take care not to break untranslated content
		// FIXME media ?

		// Untranslated post types
		if ( isset( $qv['post_type'] ) && ! $this->model->is_translated_post_type( $qv['post_type'] ) ) {
			return $qv;
		}

		// Untranslated taxonomies
		$tax_qv = array_filter( wp_list_pluck( get_taxonomies( array(), 'objects' ), 'query_var' ) ); // Get all taxonomies query vars
		$tax_qv = array_intersect( $tax_qv, array_keys( $qv ) ); // Get all queried taxonomies query vars

		if ( ! $this->model->is_translated_taxonomy( array_keys( $tax_qv ) ) ) {
			return $qv;
		}

		if ( isset( $this->curlang ) && empty( $qv['lang'] ) ) {
			$qv['lang'] = $this->curlang->slug;
		}

		return $qv;
	}
}
