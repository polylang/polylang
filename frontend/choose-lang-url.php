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
			$this->set_language();
		}

		add_filter( 'request', array( $this, 'request' ) );
	}

	/**
	 * Returns the language according to information found in the url.
	 *
	 * @since 1.2
	 * @since 3.8 Renamed from `get_language_from_url()`.
	 *
	 * @return PLL_Language|false
	 */
	protected function get_current_language() {
		$host      = str_replace( 'www.', '', (string) wp_parse_url( $this->links_model->home, PHP_URL_HOST ) ); // Remove www. for the comparison.
		$home_path = (string) wp_parse_url( $this->links_model->home, PHP_URL_PATH );

		$requested_url   = pll_get_requested_url();
		$requested_host  = str_replace( 'www.', '', (string) wp_parse_url( $requested_url, PHP_URL_HOST ) ); // Remove www. for the comparison.
		$requested_path  = rtrim( str_replace( $this->index, '', (string) wp_parse_url( $requested_url, PHP_URL_PATH ) ), '/' ); // Some PHP setups turn requests for / into /index.php in REQUEST_URI.
		$requested_query = wp_parse_url( $requested_url, PHP_URL_QUERY );

		// Home is requested.
		if ( $requested_host === $host && $requested_path === $home_path && empty( $requested_query ) ) {
			add_action( 'setup_theme', array( $this, 'home_requested' ) );
			return $this->get_home_language();
		}

		// Take care to post & page preview http://wordpress.org/support/topic/static-frontpage-url-parameter-url-language-information.
		if ( isset( $_GET['preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			foreach ( array( 'p', 'page_id' ) as $var ) {
				if ( empty( $_GET[ $var ] ) || ! is_numeric( $_GET[ $var ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					continue;
				}
				$lang = $this->model->post->get_language( (int) $_GET[ $var ] ); // phpcs:ignore WordPress.Security.NonceVerification
				return $lang ?: $this->model->get_default_language();
			}
		}

		// Take care to (unattached) attachments.
		if ( ! empty( $_GET['attachment_id'] ) && is_numeric( $_GET['attachment_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $this->model->post->get_language( (int) $_GET['attachment_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$slug = $this->links_model->get_language_from_url();

		if ( ! empty( $slug ) ) {
			return $this->model->get_language( $slug );
		}

		if ( $this->options['hide_default'] ) {
			return $this->model->get_default_language();
		}

		return false;
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
