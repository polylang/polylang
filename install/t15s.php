<?php

/**
 * Allows to download translations from TranslationsPress
 * This is a modified version of the library available at https://github.com/WP-Translations/t15s-registry
 * This version aims to be compatible with PHP 5.2, and supports only plugins.
 *
 * @since 2.6
 */
class PLL_T15S {

	const TRANSIENT_KEY_PLUGIN = 't15s-registry-plugins';

	private $type    = 'plugin';
	private $slug    = '';
	private $api_url = '';

	/**
	 * Adds a new project to load translations for.
	 *
	 * @since 2.6
	 *
	 * @param string $slug    Project directory slug.
	 * @param string $api_url Full GlotPress API URL for the project.
	 */
	public function __construct( $slug, $api_url ) {
		$this->slug    = $slug;
		$this->api_url = $api_url;

		add_action( 'init', array( __CLASS__, 'register_clean_translations_cache' ), 9999 );
		add_filter( 'translations_api', array( $this, 'translations_api' ), 10, 3 );
		add_filter( 'site_transient_update_' . $this->type . 's', array( $this, 'site_transient_update_plugins' ) );
	}

	/**
	 * Short-circuits translations API requests for private projects.
	 *
	 * @since 2.6
	 *
	 * @param bool|array $result         The result object. Default false.
	 * @param string     $requested_type The type of translations being requested.
	 * @param object     $args           Translation API arguments.
	 * @return bool|array
	 */
	public function translations_api( $result, $requested_type, $args ) {
		if ( $this->type . 's' === $requested_type && $this->slug === $args['slug'] ) {
			return self::get_translations( $this->type, $args['slug'], $this->api_url );
		}

		return $result;
	}

	/**
	 * Filters the translations transients to include the private plugin or theme.
	 *
	 * @see wp_get_translation_updates()
	 *
	 * @since 2.6
	 *
	 * @param bool|array $value The transient value.
	 */
	public function site_transient_update_plugins( $value ) {
		if ( ! $value ) {
			$value = new stdClass();
		}

		if ( ! isset( $value->translations ) ) {
			$value->translations = array();
		}

		$translations = self::get_translations( $this->type, $this->slug, $this->api_url );

		if ( ! isset( $translations['translations'] ) ) {
			return $value;
		}

		$installed_translations = wp_get_installed_translations( $this->type . 's' );

		foreach ( (array) $translations['translations'] as $translation ) {
			if ( in_array( $translation['language'], get_available_languages() ) ) {
				if ( isset( $installed_translations[ $this->slug ][ $translation['language'] ] ) && $translation['updated'] ) {
					$local  = new DateTime( $installed_translations[ $this->slug ][ $translation['language'] ]['PO-Revision-Date'] );
					$remote = new DateTime( $translation['updated'] );

					if ( $local >= $remote ) {
						continue;
					}
				}

				$translation['type'] = $this->type;
				$translation['slug'] = $this->slug;

				$value->translations[] = $translation;
			}
		}

		return $value;
	}

	/**
	 * Registers actions for clearing translation caches.
	 *
	 * @since 2.6
	 */
	public static function register_clean_translations_cache() {
		add_action( 'set_site_transient_update_plugins', array( __CLASS__, 'clean_translations_cache' ) );
		add_action( 'delete_site_transient_update_plugins', array( __CLASS__, 'clean_translations_cache' ) );
	}

	/**
	 * Clears existing translation cache.
	 *
	 * @since 2.6
	 */
	public static function clean_translations_cache() {
		$translations = get_site_transient( self::TRANSIENT_KEY_PLUGIN );

		if ( ! is_object( $translations ) ) {
			return;
		}

		/*
		 * Don't delete the cache if the transient gets changed multiple times
		 * during a single request. Set cache lifetime to maximum 15 seconds.
		 */
		$cache_lifespan   = 15;
		$time_not_changed = isset( $translations->_last_checked ) && ( time() - $translations->_last_checked ) > $cache_lifespan;

		if ( ! $time_not_changed ) {
			return;
		}

		delete_site_transient( self::TRANSIENT_KEY_PLUGIN );
	}

	/**
	 * Gets the translations for a given project.
	 *
	 * @since 2.6
	 *
	 * @param string $type Project type. Either plugin or theme.
	 * @param string $slug Project directory slug.
	 * @param string $url  Full GlotPress API URL for the project.
	 * @return array Translation data.
	 */
	private static function get_translations( $type, $slug, $url ) {
		$translations = get_site_transient( self::TRANSIENT_KEY_PLUGIN );

		if ( ! is_object( $translations ) ) {
			$translations = new stdClass();
		}

		if ( isset( $translations->{$slug} ) && is_array( $translations->{$slug} ) ) {
			return $translations->{$slug};
		}

		$result = json_decode( wp_remote_retrieve_body( wp_remote_get( $url, array( 'timeout' => 3 ) ) ), true );

		// Nothing found.
		if ( ! is_array( $result ) ) {
			$result = array();
		}

		$translations->{$slug}       = $result;
		$translations->_last_checked = time();

		set_site_transient( self::TRANSIENT_KEY_PLUGIN, $translations );
		return $result;
	}
}
