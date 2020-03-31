<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Jetpack
 *
 * @since 2.3
 */
class PLL_Jetpack {
	/**
	 * Constructor
	 *
	 * @since 2.3
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'jetpack_init' ) );
		add_action( 'jetpack_widget_get_top_posts', array( $this, 'jetpack_widget_get_top_posts' ), 10, 3 );
		add_filter( 'grunion_contact_form_field_html', array( $this, 'grunion_contact_form_field_html_filter' ), 10, 3 );
		add_filter( 'jetpack_open_graph_tags', array( $this, 'jetpack_ogp' ) );
		add_filter( 'jetpack_relatedposts_filter_filters', array( $this, 'jetpack_relatedposts_filter_filters' ), 10, 2 );

		// Jetpack infinite scroll
		if ( isset( $_GET['infinity'], $_POST['action'] ) && 'infinite_scroll' == $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_filter( 'pll_is_ajax_on_front', '__return_true' );
		}
	}

	/**
	 * Add filters
	 *
	 * @since 2.1
	 */
	public function jetpack_init() {
		if ( ! defined( 'JETPACK__VERSION' ) ) {
			return;
		}

		// Infinite scroll ajax url must be on the right domain
		if ( did_action( 'pll_init' ) && PLL()->options['force_lang'] > 1 ) {
			add_filter( 'infinite_scroll_ajax_url', array( PLL()->links_model, 'site_url' ) );
			add_filter( 'infinite_scroll_js_settings', array( $this, 'jetpack_infinite_scroll_js_settings' ) );
		}
	}

	/**
	 * Filter the Top Posts and Pages by language.
	 * Adapted from the same function in jetpack-3.0.2/3rd-party/wpml.php
	 *
	 * @since 1.5.4
	 *
	 * @param array  $posts    Array of the most popular posts.
	 * @param array  $post_ids Array of Post IDs.
	 * @param string $count    Number of Top Posts we want to display.
	 * @return array
	 */
	public function jetpack_widget_get_top_posts( $posts, $post_ids, $count ) {
		foreach ( $posts as $k => $post ) {
			if ( pll_current_language() !== pll_get_post_language( $post['post_id'] ) ) {
				unset( $posts[ $k ] );
			}
		}

		return $posts;
	}

	/**
	 * Filter the HTML of the Contact Form and output the one requested by language.
	 * Adapted from the same function in jetpack-3.0.2/3rd-party/wpml.php
	 * Keeps using 'icl_translate' as the function registers the string
	 *
	 * @since 1.5.4
	 *
	 * @param string   $r           Contact Form HTML output.
	 * @param string   $field_label Field label.
	 * @param int|null $id          Post ID.
	 * @return string
	 */
	public function grunion_contact_form_field_html_filter( $r, $field_label, $id ) {
		if ( function_exists( 'icl_translate' ) ) {
			if ( pll_current_language() !== pll_default_language() ) {
				$label_translation = icl_translate( 'jetpack ', $field_label . '_label', $field_label );
				$r = str_replace( $field_label, $label_translation, $r );
			}
		}

		return $r;
	}

	/**
	 * Adds opengraph support for locale and translations
	 *
	 * @since 1.6
	 *
	 * @param array $tags opengraph tags to output
	 * @return array
	 */
	public function jetpack_ogp( $tags ) {
		if ( did_action( 'pll_init' ) ) {
			foreach ( PLL()->model->get_languages_list() as $language ) {
				if ( PLL()->curlang->slug !== $language->slug && PLL()->links->get_translation_url( $language ) && isset( $language->facebook ) ) {
					$tags['og:locale:alternate'][] = $language->facebook;
				}
				if ( PLL()->curlang->slug === $language->slug && isset( $language->facebook ) ) {
					$tags['og:locale'] = $language->facebook;
				}
			}
		}
		return $tags;
	}

	/**
	 * Allows to make sure that related posts are in the correct language
	 *
	 * @since 1.8
	 *
	 * @param array  $filters Array of ElasticSearch filters based on the post_id and args.
	 * @param string $post_id Post ID of the post for which we are retrieving Related Posts.
	 * @return array
	 */
	public function jetpack_relatedposts_filter_filters( $filters, $post_id ) {
		$slug = sanitize_title( pll_get_post_language( $post_id, 'slug' ) );
		$filters[] = array( 'term' => array( 'taxonomy.language.slug' => $slug ) );
		return $filters;
	}

	/**
	 * Fixes the settings history host for infinite scroll when using subdomains or multiple domains
	 *
	 * @since 2.1
	 *
	 * @param array $settings
	 * @return array
	 */
	public function jetpack_infinite_scroll_js_settings( $settings ) {
		$settings['history']['host'] = wp_parse_url( pll_home_url(), PHP_URL_HOST ); // Jetpack uses get_option( 'home' )
		return $settings;
	}
}
