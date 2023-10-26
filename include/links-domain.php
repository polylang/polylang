<?php
/**
 * @package Polylang
 */

/**
 * Links model for use when using one domain per language
 * for example mysite.com/something and mysite.fr/quelquechose.
 *
 * @since 1.2
 */
class PLL_Links_Domain extends PLL_Links_Abstract_Domain {

	/**
	 * An array with language code as keys and the host as values.
	 *
	 * @var string[]
	 */
	protected $hosts;

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 *
	 * @param object $model PLL_Model instance.
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		$this->hosts = $this->get_hosts();

		// Filters the site url (mainly to get the correct login form).
		add_filter( 'site_url', array( $this, 'site_url' ) );
	}


	/**
	 * Switches the primary domain to a secondary domain in the url.
	 *
	 * @since 1.2
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param string                    $url      The url to modify.
	 * @param PLL_Language|string|false $language Language object or slug.
	 * @return string The modified url.
	 */
	public function add_language_to_link( $url, $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		if ( ! empty( $language ) && ! empty( $this->hosts[ $language ] ) ) {
			$url = preg_replace( '#://(' . wp_parse_url( $this->home, PHP_URL_HOST ) . ')($|/.*)#', '://' . $this->hosts[ $language ] . '$2', $url );
		}
		return $url;
	}

	/**
	 * Returns the url with the primary domain.
	 *
	 * @since 1.2
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_language_from_link( $url ) {
		if ( ! empty( $this->hosts ) ) {
			$url = preg_replace( '#://(' . implode( '|', $this->hosts ) . ')($|/.*)#', '://' . wp_parse_url( $this->home, PHP_URL_HOST ) . '$2', $url );
		}
		return $url;
	}

	/**
	 * Returns the home url in a given language.
	 *
	 * @since 1.3.1
	 * @since 3.4 Accepts now a language slug.
	 *
	 * @param PLL_Language|string $language Language object or slug.
	 * @return string
	 */
	public function home_url( $language ) {
		if ( $language instanceof PLL_Language ) {
			$language = $language->slug;
		}

		return trailingslashit( empty( $this->options['domains'][ $language ] ) ? $this->home : $this->options['domains'][ $language ] );
	}

	/**
	 * Get the hosts managed on the website.
	 *
	 * @since 1.5
	 *
	 * @return string[] List of hosts.
	 */
	public function get_hosts() {
		$hosts = array();
		foreach ( $this->options['domains'] as $lang => $domain ) {
			$host = wp_parse_url( $domain, PHP_URL_HOST );

			if ( ! is_string( $host ) ) {
				continue;
			}

			// The function idn_to_ascii() is much faster than the WordPress method.
			if ( function_exists( 'idn_to_ascii' ) && defined( 'INTL_IDNA_VARIANT_UTS46' ) ) {
				$hosts[ $lang ] = idn_to_ascii( $host, 0, INTL_IDNA_VARIANT_UTS46 );
			} elseif ( class_exists( 'WpOrg\Requests\IdnaEncoder' ) ) {
				// Since WP 6.2.
				$hosts[ $lang ] = \WpOrg\Requests\IdnaEncoder::encode( $host );
			} else {
				// Backward compatibility with WP < 6.2.
				$hosts[ $lang ] = Requests_IDNAEncoder::encode( $host );
			}
		}

		return $hosts;
	}
}
