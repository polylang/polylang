<?php

use Yoast\WP\SEO\Presenters\Abstract_Indexable_Presenter;

/**
 * Creates an Opengraph alternate locale meta tag to be consumed by Yoast SEO
 * Requires Yoast SEO 14.0 or newer.
 *
 * @since 2.7
 */
final class PLL_WPSEO_OGP extends Abstract_Indexable_Presenter {
	/**
	 * Facebook locale
	 *
	 * @var string
	 */
	private $lang;

	/**
	 * Constructor
	 *
	 * @since 2.7
	 *
	 * @param string Facebook locale.
	 */
	public function __construct( $lang ) {
		$this->lang = $lang;
	}

	/**
	 * Returns the meta Opengraph alternate locale meta tag
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
  public function present() {
    return sprintf( '<meta property="og:locale:alternate" content="%s" />', esc_attr( $this->lang ) );
  }
}

