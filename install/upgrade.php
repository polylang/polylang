<?php
/**
 * @package Polylang
 */

/**
 * Manages Polylang upgrades
 *
 * @since 1.2
 */
class PLL_Upgrade {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param array $options Polylang options
	 */
	public function __construct( &$options ) {
		$this->options = &$options;
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
	 * Displays a notice when ugrading from a too old version
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
		foreach ( array( '2.0.8', '2.1', '2.7', '2.8.1' ) as $version ) {
			if ( version_compare( $this->options['version'], $version, '<' ) ) {
				$method_to_call = array( $this, 'upgrade_' . str_replace( '.', '_', $version ) );
				if ( is_callable( $method_to_call ) ) {
					call_user_func( $method_to_call );
				}               
			}
		}

		$this->options['previous_version'] = $this->options['version']; // Remember the previous version of Polylang since v1.7.7
		$this->options['version'] = POLYLANG_VERSION;
		update_option( 'polylang', $this->options );
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
	 * Upgrades if the previous version is < 2.8.1
	 *
	 * Deletes language cache due to:
	 * - 'redirect_lang' option removed for subdomains and multiple domains in 2.2
	 * - W3C and Facebook locales added to PLL_Language objects in 2.3
	 * - flags moved to a different directory in Polylang Pro 2.8
	 * - bug of flags url returning html fixed in 2.8.1
	 *
	 * @since 2.8.1
	 *
	 * @return void
	 */
	protected function upgrade_2_8_1() {
		delete_transient( 'pll_languages_list' );
	}
}
