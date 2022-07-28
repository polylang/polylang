<?php
/**
 * @package Polylang
 */

/**
 * Manages canonical redirect on frontend.
 *
 * @since 3.3
 */
class PLL_Canonical {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * @var PLL_Model
	 */
	protected $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	protected $links_model;

	/**
	 * Current language.
	 *
	 * @var PLL_Language
	 */
	protected $curlang;

	/**
	 * Constructor.
	 *
	 * @since 3.3
	 *
	 * @param object $polylang Main Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->links_model = &$polylang->links_model;
		$this->model       = &$polylang->model;
		$this->options     = &$polylang->options;
		$this->curlang     = &$polylang->curlang;
	}

	/**
	 * If the language code is not in agreement with the language of the content,
	 * redirects incoming links to the proper URL to avoid duplicate content.
	 *
	 * @since 0.9.6
	 *
	 * @global WP_Query $wp_query WordPress Query object.
	 * @global bool     $is_IIS
	 *
	 * @param string $requested_url Optional, defaults to requested url.
	 * @param bool   $do_redirect   Optional, whether to perform the redirect or not.
	 * @return string|void Returns if redirect is not performed.
	 */
	public function check_canonical_url( $requested_url = '', $do_redirect = true ) {
		global $wp_query;

		// Don't redirect in same cases as WP.
		if ( is_trackback() || is_search() || is_admin() || is_preview() || is_robots() || ( $GLOBALS['is_IIS'] && ! iis7_supports_permalinks() ) ) {
			return;
		}

		// Don't redirect mysite.com/?attachment_id= to mysite.com/en/?attachment_id=.
		if ( 1 == $this->options['force_lang'] && is_attachment() && isset( $_GET['attachment_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		/*
		 * If the default language code is not hidden and the static front page url contains the page name,
		 * the customizer lands here and the code below would redirect to the list of posts.
		 */
		if ( is_customize_preview() ) {
			return;
		}

		if ( empty( $requested_url ) ) {
			$requested_url = pll_get_requested_url();
		}

		if ( ( is_single() || is_page() ) && ! is_front_page() ) {
			$post = get_post();
			if ( $post instanceof WP_Post && $this->model->is_translated_post_type( $post->post_type ) ) {
				$language = $this->model->post->get_language( (int) $post->ID );
			}
		}

		if ( ! empty( $wp_query->tax_query ) ) {
			if ( $this->model->is_translated_taxonomy( $this->get_queried_taxonomy( $wp_query->tax_query ) ) ) {
				$term_id = $this->get_queried_term_id( $wp_query->tax_query );
				if ( $term_id ) {
					$language = $this->model->term->get_language( $term_id );
				}
			}
		}

		if ( $wp_query->is_posts_page ) {
			$page_id = get_query_var( 'page_id' );
			if ( ! $page_id ) {
				$page_id = get_queried_object_id();
			}
			if ( $page_id && is_numeric( $page_id ) ) {
				$language = $this->model->post->get_language( (int) $page_id );
			}
		}

		if ( 3 === $this->options['force_lang'] ) {
			$requested_host = wp_parse_url( $requested_url, PHP_URL_HOST );
			foreach ( $this->options['domains'] as $lang => $domain ) {
				$host = wp_parse_url( $domain, PHP_URL_HOST );
				if ( $requested_host && $host && ltrim( $requested_host, 'w.' ) === ltrim( $host, 'w.' ) ) {
					$language = $this->model->get_language( $lang );
				}
			}
		}

		if ( empty( $language ) ) {
			$language = $this->curlang;
			$redirect_url = $requested_url;
		} else {
			$redirect_url = $this->redirect_canonical( $requested_url, $language );
			$redirect_url = $this->options['force_lang'] ?
				$this->links_model->switch_language_in_link( $redirect_url, $language ) :
				$this->links_model->remove_language_from_link( $redirect_url ); // Works only for default permalinks.
		}


		/**
		 * Filters the canonical url detected by Polylang.
		 *
		 * @since 1.6
		 *
		 * @param string|false $redirect_url False or the url to redirect to.
		 * @param PLL_Language $language The language detected.
		 */
		$redirect_url = apply_filters( 'pll_check_canonical_url', $redirect_url, $language );

		if ( ! $redirect_url || $requested_url === $redirect_url ) {
			return $requested_url;
		}

		if ( ! $do_redirect ) {
			return $redirect_url;
		}

		// Protect against chained redirects.
		if ( $redirect_url === $this->check_canonical_url( $redirect_url, false ) && wp_validate_redirect( $redirect_url ) ) {
			wp_safe_redirect( $redirect_url, 301, POLYLANG );
			exit;
		}
	}

	/**
	 * Returns the term_id of the requested term.
	 *
	 * @since 2.9
	 *
	 * @param WP_Tax_Query $tax_query An instance of WP_Tax_Query.
	 * @return int
	 */
	protected function get_queried_term_id( $tax_query ) {
		$queried_terms = $tax_query->queried_terms;
		$taxonomy = $this->get_queried_taxonomy( $tax_query );

		if ( ! is_array( $queried_terms[ $taxonomy ]['terms'] ) ) {
			return 0;
		}
		$field = $queried_terms[ $taxonomy ]['field'];
		$term  = reset( $queried_terms[ $taxonomy ]['terms'] );
		$lang  = isset( $queried_terms['language']['terms'] ) ? reset( $queried_terms['language']['terms'] ) : '';

		// We can get a term_id when requesting a plain permalink, eg /?cat=1.
		if ( 'term_id' === $field ) {
			return $term;
		}

		// We get a slug when requesting a pretty permalink. Let's query all corresponding terms.
		$args = array(
			'lang'       => '',
			'taxonomy'   => $taxonomy,
			$field       => $term,
			'hide_empty' => false,
			'fields'     => 'ids',
		);
		$term_ids = get_terms( $args );

		if ( ! is_array( $term_ids ) || empty( $term_ids ) ) {
			return 0;
		}

		$term_ids = array_filter( $term_ids, 'is_numeric' );

		$filtered_terms_by_lang = array_filter(
			$term_ids,
			function ( $term_id ) use ( $lang ) {
				$term_lang = $this->model->term->get_language( (int) $term_id );

				return ! empty( $term_lang ) && $term_lang->slug === $lang;
			}
		);

		$tr_term = (int) reset( $filtered_terms_by_lang );

		if ( ! empty( $tr_term ) ) {
			// The queried term exists in the desired language.
			return $tr_term;
		}

		// The queried term doesn't exist in the desired language, let's return the first one retrieved.
		return (int) reset( $term_ids );
	}

	/**
	 * Find the taxonomy being queried.
	 *
	 * @since 2.9
	 *
	 * @param WP_Tax_Query $tax_query An instance of WP_Tax_Query.
	 * @return string A taxonomy slug
	 */
	protected function get_queried_taxonomy( $tax_query ) {
		$queried_terms = $tax_query->queried_terms;
		unset( $queried_terms['language'] );

		return (string) key( $queried_terms );
	}

	/**
	 * Evaluates the canonical redirect url through the deidcated WP function.
	 *
	 * @since 3.3
	 *
	 * @global WP_Query $wp_query WordPress Query object.
	 *
	 * @param string       $url      Requested url.
	 * @param PLL_Language $language Language of the queried object.
	 * @return string
	 */
	protected function redirect_canonical( $url, $language ) {
		global $wp_query;

		$this->curlang = $language; // Hack to filter the `page_for_posts` option in the correct language.

		$backup_wp_query = $wp_query;

		if ( isset( $wp_query->tax_query ) ) {
			unset( $wp_query->tax_query->queried_terms['language'] );
			unset( $wp_query->query['lang'] );
		}

		$redirect_url = redirect_canonical( $url, false );

		$wp_query = $backup_wp_query;

		return $redirect_url ? $redirect_url : $url;
	}
}
