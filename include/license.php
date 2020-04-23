<?php
/**
 * @package Polylang
 */

/**
 * A class to easily manage licenses for Polylang Pro and addons
 *
 * @since 1.9
 */
class PLL_License {
	public $id, $name, $license_key, $license_data;
	private $file, $version, $author;
	private $api_url = 'https://polylang.pro';

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param string $file
	 * @param string $item_name
	 * @param string $version
	 * @param string $author
	 * @param string $api_url optional
	 */
	public function __construct( $file, $item_name, $version, $author, $api_url = null ) {
		$this->id      = sanitize_title( $item_name );
		$this->file    = $file;
		$this->name    = $item_name;
		$this->version = $version;
		$this->author  = $author;
		$this->api_url = empty( $api_url ) ? $this->api_url : $api_url;

		$licenses = get_option( 'polylang_licenses' );
		$this->license_key = empty( $licenses[ $this->id ]['key'] ) ? '' : $licenses[ $this->id ]['key'];
		if ( ! empty( $licenses[ $this->id ]['data'] ) ) {
			$this->license_data = $licenses[ $this->id ]['data'];
		}

		// Updater
		add_action( 'admin_init', array( $this, 'auto_updater' ), 0 );
		add_action( 'cli_init', array( $this, 'auto_updater' ), 0 ); // For WP CLI.

		// Register settings
		add_filter( 'pll_settings_licenses', array( $this, 'settings' ) );

		// Weekly schedule
		if ( ! wp_next_scheduled( 'polylang_check_licenses' ) ) {
			wp_schedule_event( time(), 'weekly', 'polylang_check_licenses' );
		}

		add_action( 'polylang_check_licenses', array( $this, 'check_license' ) );
	}

	/**
	 * Auto updater
	 *
	 * @since 1.9
	 */
	public function auto_updater() {
		$args = array(
			'version'   => $this->version,
			'license'   => $this->license_key,
			'author'    => $this->author,
			'item_name' => $this->name,
		);

		// Setup the updater
		new PLL_Plugin_Updater( $this->api_url, $this->file, $args );
	}

	/**
	 * Registers the licence in the Settings
	 *
	 * @since 1.9
	 *
	 * @param array $items
	 * @return  array
	 */
	public function settings( $items ) {
		$items[ $this->id ] = $this;
		return $items;
	}

	/**
	 * Activate the license key
	 *
	 * @since 1.9
	 *
	 * @param string $license_key activation key
	 * @return object updated $this
	 */
	public function activate_license( $license_key ) {
		$this->license_key = $license_key;
		$this->api_request( 'activate_license' );

		// Tell WordPress to look for updates
		set_site_transient( 'update_plugins', null );
		return $this;
	}


	/**
	 * Deactivate the license key
	 *
	 * @since 1.9
	 *
	 * @return object updated $this
	 */
	public function deactivate_license() {
		$this->api_request( 'deactivate_license' );
		return $this;
	}

	/**
	 * Check if license key is valid
	 *
	 * @since 1.9
	 *
	 * @return object updated $this
	 */
	public function check_license() {
		$this->api_request( 'check_license' );
		return $this;
	}

	/**
	 * Sends an api request to check, activate or deactivate the license
	 * Updates the licenses option according to the status
	 *
	 * @since 1.9
	 *
	 * @param string $request check_license | activate_license | deactivate_license
	 */
	private function api_request( $request ) {
		$licenses = get_option( 'polylang_licenses' );

		if ( is_array( $licenses ) ) {
			unset( $licenses[ $this->id ] );
		} else {
			$licenses = array();
		}
		unset( $this->license_data );

		if ( ! empty( $this->license_key ) ) {
			// Data to send in our API request
			$api_params = array(
				'edd_action' => $request,
				'license'    => $this->license_key,
				'item_name'  => urlencode( $this->name ),
				'url'        => home_url(),
			);

			// Call the API
			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 3,
					'sslverify' => false,
					'body'      => $api_params,
				)
			);

			// Update the option only if we got a response
			if ( is_wp_error( $response ) ) {
				return;
			}

			// Save new license info
			$licenses[ $this->id ] = array( 'key' => $this->license_key );
			$data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( 'deactivated' !== $data->license ) {
				$licenses[ $this->id ]['data'] = $this->license_data = $data;
			}
		}

		update_option( 'polylang_licenses', $licenses ); // FIXME called multiple times when saving all licenses
	}

	/**
	 * Get the html form field in a table row (one per license key) for display
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	public function get_form_field() {
		if ( ! empty( $this->license_data ) ) {
			$license = $this->license_data;
		}

		$class = 'license-null';

		$out = sprintf(
			'<td><label for="pll-licenses[%1$s]">%2$s</label></td>' .
			'<td><input name="licenses[%1$s]" id="pll-licenses[%1$s]" type="text" value="%3$s" class="regular-text code" />',
			esc_attr( $this->id ),
			esc_attr( $this->name ),
			esc_html( $this->license_key )
		);

		if ( ! empty( $license ) && is_object( $license ) ) {
			$now = time();
			$expiration = isset( $license->expires ) ? strtotime( $license->expires ) : false;

			// Special case: the license expired after the last check
			if ( $license->success && $expiration && $expiration < $now ) {
				$license->success = false;
				$license->error = 'expired';
			}

			if ( false === $license->success ) {
				$class = 'notice-error notice-alt';

				switch ( $license->error ) {
					case 'expired':
						$message = sprintf(
							/* translators: %1$s is a date, %2$s is link start tag, %3$s is link end tag. */
							esc_html__( 'Your license key expired on %1$s. Please %2$srenew your license key%3$s.', 'polylang' ),
							esc_html( date_i18n( get_option( 'date_format' ), $expiration ) ),
							sprintf( '<a href="%s" target="_blank">', esc_url( 'https://polylang.pro/checkout/?edd_license_key=' . $this->license_key ) ),
							'</a>'
						);
						break;

					case 'disabled':
					case 'revoked':
						$message = esc_html__( 'Your license key has been disabled.', 'polylang' );
						break;

					case 'missing':
						$message = sprintf(
							/* translators: %1$s is link start tag, %2$s is link end tag. */
							esc_html__( 'Invalid license. Please %1$svisit your account page%2$s and verify it.', 'polylang' ),
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/account' ),
							'</a>'
						);
						break;

					case 'invalid':
					case 'site_inactive':
						$message = sprintf(
							/* translators: %1$s is a product name, %2$s is link start tag, %3$s is link end tag. */
							esc_html__( 'Your %1$s license key is not active for this URL. Please %2$svisit your account page%3$s to manage your license key URLs.', 'polylang' ),
							esc_html( $this->name ),
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/account' ),
							'</a>'
						);
						break;

					case 'item_name_mismatch':
						/* translators: %s is a product name */
						$message = sprintf( esc_html__( 'This is not a %s license key.', 'polylang' ), esc_html( $this->name ) );
						break;

					case 'no_activations_left':
						$message = sprintf(
							/* translators: %1$s is link start tag, %2$s is link end tag */
							esc_html__( 'Your license key has reached its activation limit. %1$sView possible upgrades%2$s now.', 'polylang' ),
							sprintf( '<a href="%s" target="_blank">', 'https://polylang.pro/account' ),
							'</a>'
						);
						break;
				}
			} else {
				$class = 'license-valid';

				$out .= sprintf( '<button id="deactivate_%s" type="button" class="button button-secondary pll-deactivate-license">%s</button>', esc_attr( $this->id ), esc_html__( 'Deactivate', 'polylang' ) );

				if ( 'lifetime' === $license->expires ) {
					$message = esc_html__( 'The license key never expires.', 'polylang' );
				} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {
					$class = 'notice-warning notice-alt';
					$message = sprintf(
						/* translators: %1$s is a date, %2$s is link start tag, %3$s is link end tag. */
						esc_html__( 'Your license key will expire soon! Precisely, it will expire on %1$s. %2$sRenew your license key today!%3$s.', 'polylang' ),
						esc_html( date_i18n( get_option( 'date_format' ), $expiration ) ),
						sprintf( '<a href="%s" target="_blank">', esc_url( 'https://polylang.pro/checkout/?edd_license_key=' . $this->license_key ) ),
						'</a>'
					);
				} else {
					$message = sprintf(
						/* translators: %s is a date */
						esc_html__( 'Your license key expires on %s.', 'polylang' ),
						esc_html( date_i18n( get_option( 'date_format' ), $expiration ) )
					);
				}
			}
		}

		if ( ! empty( $message ) ) {
			$out .= '<p>' . $message . '</p>';
		}

		return sprintf( '<tr id="pll-license-%s" class="%s">%s</tr>', esc_attr( $this->id ), $class, $out );
	}
}
