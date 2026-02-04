<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Options\Options;
/**
 * Manages Polylang upgrades
 *
 * @since 1.2
 */
class PLL_Upgrade {
	/**
	 * Stores the plugin options.
	 *
	 * @var Options
	 */
	public $options;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 * @since 3.7 The `$options` parameter is an instance of `Options`.
	 *
	 * @param Options $options Polylang options.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Check if upgrade is possible otherwise die to avoid activation
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function can_activate() {
		if ( ! $this->can_upgrade() ) {
			ob_start();
			$this->admin_notices(); // FIXME the error message is displayed two times
			die( ob_get_contents() ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Upgrades if possible otherwise returns false to stop Polylang loading
	 *
	 * @since 1.2
	 *
	 * @return bool true if upgrade is possible, false otherwise
	 */
	public function upgrade() {
		if ( ! $this->can_upgrade() ) {
			add_action( 'all_admin_notices', array( $this, 'admin_notices' ) );
			return false;
		}

		add_action( 'admin_init', array( $this, '_upgrade' ) );
		return true;
	}


	/**
	 * Check if we the previous version is not too old
	 * Upgrades if OK
	 * /!\ never start any upgrade before admin_init as it is likely to conflict with some other plugins
	 *
	 * @since 1.2
	 *
	 * @return bool true if upgrade is possible, false otherwise
	 */
	public function can_upgrade() {
		// Don't manage upgrade from version < 1.8
		return version_compare( $this->options['version'], '1.8', '>=' );
	}

	/**
	 * Displays a notice when upgrading from a too old version
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function admin_notices() {
		load_plugin_textdomain( 'polylang' );
		printf(
			'<div class="error"><p>%s</p><p>%s</p></div>',
			esc_html__( 'Polylang has been deactivated because you upgraded from a too old version.', 'polylang' ),
			sprintf(
				/* translators: %1$s and %2$s are Polylang version numbers */
				esc_html__( 'Before upgrading to %2$s, please upgrade to %1$s.', 'polylang' ),
				'<strong>2.9</strong>',
				POLYLANG_VERSION // phpcs:ignore WordPress.Security.EscapeOutput
			)
		);
	}

	/**
	 * Upgrades the plugin depending on the previous version
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function _upgrade() {
		/*
		 * Delete the transient precisely at this point (`admin_init`)
		 * to ensure `Model\Languages` callbacks do their job.
		 * i.e. delete the transient from the options table when external object cache is used.
		 */
		delete_transient( 'pll_languages_list' );

		foreach ( array( '2.0.8', '2.1', '2.7', '3.4', '3.7', '3.8' ) as $version ) {
			if ( version_compare( $this->options['version'], $version, '<' ) ) {
				$method_to_call = array( $this, 'upgrade_' . str_replace( '.', '_', $version ) );
				if ( is_callable( $method_to_call ) ) {
					call_user_func( $method_to_call );
				}
			}
		}

		/**
		 * Fires after Polylang has been upgraded and before the new version is saved in options.
		 *
		 * @since 3.7
		 */
		do_action( 'pll_upgrade' );

		$this->options['previous_version'] = $this->options['version']; // Remember the previous version of Polylang since v1.7.7
		$this->options['version'] = POLYLANG_VERSION;
	}

	/**
	 * Upgrades if the previous version is < 2.0.8
	 * Changes the user meta 'user_lang' to 'locale' to match WP 4.7 choice
	 *
	 * @since 2.0.8
	 *
	 * @return void
	 */
	protected function upgrade_2_0_8() {
		global $wpdb;
		$wpdb->update( $wpdb->usermeta, array( 'meta_key' => 'locale' ), array( 'meta_key' => 'user_lang' ) );
	}

	/**
	 * Upgrades if the previous version is < 2.1.
	 * Moves strings translations from polylang_mo post_content to post meta _pll_strings_translations.
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	protected function upgrade_2_1() {
		$posts = get_posts(
			array(
				'post_type'   => 'polylang_mo',
				'post_status' => 'any',
				'numberposts' => -1,
				'nopaging'    => true,
			)
		);

		if ( is_array( $posts ) ) {
			foreach ( $posts as $post ) {
				$meta = get_post_meta( $post->ID, '_pll_strings_translations', true );

				if ( empty( $meta ) ) {
					$strings = maybe_unserialize( $post->post_content );
					if ( is_array( $strings ) ) {
						update_post_meta( $post->ID, '_pll_strings_translations', $strings );
					}
				}
			}
		}
	}

	/**
	 * Upgrades if the previous version is < 2.7
	 * Replace numeric keys by hashes in WPML registered strings
	 * Dismiss the wizard notice for existing sites
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	protected function upgrade_2_7() {
		$strings = get_option( 'polylang_wpml_strings' );
		if ( is_array( $strings ) ) {
			$new_strings = array();

			foreach ( $strings as $string ) {
				$context = $string['context'];
				$name    = $string['name'];

				$key = md5( "$context | $name" );
				$new_strings[ $key ] = $string;
			}
			update_option( 'polylang_wpml_strings', $new_strings );
		}

		PLL_Admin_Notices::dismiss( 'wizard' );
	}

	/**
	 * Upgrades if the previous version is < 3.4.0.
	 *
	 * @since 3.4
	 *
	 * @return void
	 */
	protected function upgrade_3_4() {
		$this->migrate_locale_fallback_to_language_description();

		$this->migrate_strings_translations();
	}

	/**
	 * Upgrades if the previous version is < 3.7.
	 * Hides the "The language is set from content" option if it isn't the one selected.
	 * Cleans up strings translations so we don't store translations duplicated from the source.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	protected function upgrade_3_7() {
		$this->allow_to_hide_language_from_content();
		$this->empty_duplicated_strings_translations();
	}

	/**
	 * Upgrades if the previous version is < 3.8.
	 * Migrates language taxonomies from `polylang` option to `pll_language_taxonomies` option.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	protected function upgrade_3_8() {
		$options = get_option( 'polylang' );

		$language_taxonomies = array();
		if ( is_array( $options ) && isset( $options['language_taxonomies'] ) && is_array( $options['language_taxonomies'] ) ) {
			$language_taxonomies = $options['language_taxonomies'];
		}

		update_option( 'pll_language_taxonomies', $language_taxonomies );
	}

	/**
	 * Moves strings translations from post meta to term meta _pll_strings_translations.
	 *
	 * @since 3.4
	 *
	 * @return void
	 */
	protected function migrate_strings_translations() {
		$posts = get_posts(
			array(
				'post_type' => 'polylang_mo',
				'post_status' => 'any',
				'numberposts' => -1,
				'nopaging' => true,
			)
		);

		if ( ! is_array( $posts ) ) {
			return;
		}

		foreach ( $posts as $post ) {
			$meta = get_post_meta( $post->ID, '_pll_strings_translations', true );

			$term_id = (int) substr( $post->post_title, 12 );

			// Do not delete post metas in case a user needs to rollback to Polylang < 3.4.

			if ( empty( $meta ) || ! is_array( $meta ) ) {
				continue;
			}

			update_term_meta( $term_id, '_pll_strings_translations', wp_slash( $meta ) );
		}
	}

	/**
	 * Migrate locale fallback to language term description.
	 *
	 * @since 3.4
	 *
	 * @return void
	 */
	protected function migrate_locale_fallback_to_language_description() {
		// Migrate locale fallbacks from term metas to language term description.
		$terms = get_terms(
			array(
				'taxonomy'   => 'language',
				'hide_empty' => false,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'         => 'fallback',
						'compare_key' => 'EXISTS',
					),
				),
			)
		);

		if ( ! is_array( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$fallbacks = get_term_meta( $term->term_id, 'fallback', true );

			delete_term_meta( $term->term_id, 'fallback' );

			if ( empty( $fallbacks ) || ! is_array( $fallbacks ) ) {
				// Empty or invalid value, should not happen.
				continue;
			}

			$description = maybe_unserialize( $term->description );
			$description = is_array( $description ) ? $description : array();

			$description['fallbacks'] = $fallbacks;
			/** @var string */
			$description = maybe_serialize( $description );

			wp_update_term( $term->term_id, 'language', array( 'description' => $description ) );
		}
	}

	/**
	 * Hides the language from content option if it is not the one selected.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	private function allow_to_hide_language_from_content() {
		update_option(
			'pll_language_from_content_available',
			0 === $this->options['force_lang'] ? 'yes' : 'no'
		);
	}

	/**
	 * Cleans up strings translations so we don't store translations duplicated from the source.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	private function empty_duplicated_strings_translations() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'language',
				'hide_empty' => false,
			)
		);

		if ( ! is_array( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$strings = get_term_meta( $term->term_id, '_pll_strings_translations', true );

			if ( empty( $strings ) || ! is_array( $strings ) ) {
				continue;
			}

			foreach ( $strings as $i => $tuple ) {
				if ( $tuple[0] !== $tuple[1] ) {
					continue;
				}

				$strings[ $i ][1] = '';
			}

			update_term_meta( $term->term_id, '_pll_strings_translations', $strings );
		}
	}
}
