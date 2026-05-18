<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher\Element;

use WP_Post;
use PLL_Links;
use PLL_Language;
use PLL_Frontend_Links;
use PLL_Translated_Post;
use WP_Syntex\Polylang\Switcher\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Data representing an item.
 *
 * @since 3.9
 */
abstract class Abstract_Element {
	/**
	 * @since 3.9
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * @since 3.9
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public string $slug;

	/**
	 * @since 3.9
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public string $locale;

	/**
	 * @since 3.9
	 *
	 * @var string
	 */
	public string $label = '';

	/**
	 * @since 3.9
	 *
	 * @var string
	 */
	public string $flag = '';

	/**
	 * @since 3.9
	 *
	 * @var string
	 *
	 * @phpstan-var 'ltr'|'rtl'
	 */
	public string $direction;

	/**
	 * @since 3.9
	 *
	 * @var int
	 */
	public int $order;

	/**
	 * @since 3.9
	 *
	 * @var bool
	 */
	public bool $has_translations;

	/**
	 * @since 3.9
	 *
	 * @var bool
	 */
	public bool $is_empty;

	/**
	 * @since 3.9
	 *
	 * @var bool
	 */
	public bool $is_current;

	/**
	 * @since 3.9
	 *
	 * @var string[]
	 */
	public array $item_classes;

	/**
	 * @since 3.9
	 *
	 * @var string[]
	 */
	public array $link_classes;

	/**
	 * @var PLL_Language
	 */
	private PLL_Language $language;

	/**
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * @var PLL_Links
	 */
	private PLL_Links $links;

	/**
	 * @var PLL_Translated_Post
	 */
	private PLL_Translated_Post $post_model;

	/**
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param PLL_Language $language Instance of `PLL_Language`.
	 * @param Settings     $settings Instance of `Settings`.
	 * @param PLL_Links    $links    Instance of `PLL_Links`.
	 */
	public function __construct( PLL_Language $language, Settings $settings, PLL_Links $links ) {
		$this->language   = $language;
		$this->settings   = $settings;
		$this->links      = $links;
		$this->post_model = $links->model->post;

		$this->id               = (int) $language->term_id;
		$this->slug             = $language->slug;
		$this->locale           = $language->get_locale( 'display' );
		$this->direction        = $language->is_rtl ? 'rtl' : 'ltr';
		$this->order            = (int) $language->term_group;
		$this->has_translations = true;
		$this->is_empty         = $language->get_tax_prop( 'language', 'count' ) <= 0;
		$this->is_current       = $this->get_current_language_slug() === $language->slug;
		$this->item_classes     = array_merge(
			array( 'lang-item', "lang-item-{$language->term_id}", "lang-item-{$language->slug}" ),
			$settings->item_classes
		);
		$this->link_classes     = $settings->link_classes;

		if ( $this->is_current ) {
			$this->item_classes[] = 'current-lang';
		}
		if ( $this->settings->show_labels ) {
			$this->label = 'codes' === $this->settings->show_labels ? strtoupper( $this->language->slug ) : $this->language->name;
		}
		if ( $this->settings->show_flags ) {
			$this->flag = $this->language->get_display_flag( $this->settings->show_labels ? 'no-alt' : 'alt' );
		}
	}

	/**
	 * Returns the markup of a row.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	abstract public function get(): string;

	/**
	 * Returns the markup of the label of a row.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html( $this->label );
	}

	/**
	 * Returns the URL of the element.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_url(): string {
		$url = $this->get_original_url();

		if ( empty( $url ) ) {
			$this->has_translations = false;
			$this->item_classes[]   = 'no-translation';
		}

		/**
		 * Filter the link in the language switcher.
		 *
		 * @since 0.7
		 * @since 3.9 Return an empty string instead of `null`.
		 *
		 * @param string $url    The link URL, an empty string if no translation was found.
		 * @param string $slug   The language code.
		 * @param string $locale The language locale.
		 */
		$url = apply_filters( 'pll_the_language_link', $url, $this->language->slug, $this->language->locale );

		if ( empty( $url ) || ! is_string( $url ) || $this->settings->force_home ) {
			return $this->links->get_home_url( $this->language );
		}

		return $url;
	}

	/**
	 * Returns the current language code.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	private function get_current_language_slug(): string {
		if ( ! empty( $this->links->curlang ) ) {
			return $this->links->curlang->slug;
		}

		return $this->links->options['default_lang'];
	}

	/**
	 * Returns the original URL of the element.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	private function get_original_url(): string {
		global $post;

		// Priority to the post passed in parameters.
		if ( $this->settings->post_id ) {
			$tr_id = $this->post_model->get( $this->settings->post_id, $this->language );

			if ( $tr_id && $this->post_model->current_user_can_read( $tr_id ) ) {
				return (string) get_permalink( $tr_id );
			}
		}

		// If we are on frontend.
		if ( $this->links instanceof PLL_Frontend_Links ) {
			return $this->links->get_translation_url( $this->language );
		}

		// For blocks in posts in REST requests.
		if ( $post instanceof WP_Post ) {
			$tr_id = $this->post_model->get( $post->ID, $this->language );

			if ( $tr_id && $this->post_model->current_user_can_read( $tr_id ) ) {
				return (string) get_permalink( $tr_id );
			}
		}

		return '';
	}
}
