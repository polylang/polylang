<?php
/**
 * @package Polylang
 */

/**
 * Registers and translates strings in an option.
 * When a string is updated in an original option, the translations of the old string are assigned to the new original string.
 *
 * @since 2.9
 */
class PLL_Translate_Option {

	/**
	 * Array of option keys to translate.
	 *
	 * @var array
	 */
	private $keys;

	/**
	 * Used to prevent filtering when retrieving the raw value of the option.
	 *
	 * @var bool
	 */
	private static $raw = false;

	/**
	 * Array of updated strings.
	 *
	 * @var array
	 */
	private $updated_strings = array();

	/**
	 * @var PLL_MO[]
	 */
	private $translations;

	/**
	 * Cache for the translated values.
	 *
	 * @var PLL_Cache<array|string>
	 */
	private $cache;

	/**
	 * Constructor
	 *
	 * @since 2.9
	 *
	 * @param string $name Option name.
	 * @param array  $keys Recursive array of option keys to translate in the form:
	 *    @example array(
	 *      'option_key_to_translate_1' => 1,
	 *      'option_key_to_translate_2' => 1,
	 *      'my_group' => array(
	 *        'sub_key_to_translate_1' => 1,
	 *        'sub_key_to_translate_2' => 1,
	 *      ),
	 *    )
	 *
	 *    Note: only keys are interpreted. Any scalar can be used as values.
	 * @param array  $args {
	 *    Optional. Array of arguments for registering the option.
	 *
	 *    @type string $context           The group in which the strings will be registered.
	 *    @type string $sanitize_callback A callback function that sanitizes the option's value.
	 * }
	 */
	public function __construct( $name, $keys = array(), $args = array() ) {
		$this->cache = new PLL_Cache();

		// Registers the strings.
		$context = isset( $args['context'] ) ? $args['context'] : 'Polylang';
		$this->register_string_recursive( $context, $name, get_option( $name ), $keys );

		// Translates the strings.
		$this->keys = $keys;
		add_filter( 'option_' . $name, array( $this, 'translate' ) ); // Make sure to add this filter after options are registered.

		// Filters updated values.
		add_filter( 'pre_update_option_' . $name, array( $this, 'pre_update_option' ), 10, 3 );
		add_action( 'update_option_' . $name, array( $this, 'update_option' ) );

		// Sanitizes translated strings.
		if ( empty( $args['sanitize_callback'] ) ) {
			add_filter( 'pll_sanitize_string_translation', array( $this, 'sanitize_option' ), 10, 2 );
		} else {
			add_filter( 'pll_sanitize_string_translation', $args['sanitize_callback'], 10, 3 );
		}
	}

	/**
	 * Translates the strings registered for an option.
	 *
	 * @since 1.0
	 *
	 * @param mixed $value Either a string to translate or a list of strings to translate.
	 * @return mixed Translated string(s).
	 */
	public function translate( $value ) {
		if ( self::$raw ) {
			return $value;
		}

		if ( empty( $GLOBALS['l10n']['pll_string'] ) || ! $GLOBALS['l10n']['pll_string'] instanceof PLL_MO ) {
			return $value;
		}

		$lang = $GLOBALS['l10n']['pll_string']->get_header( 'Language' );

		if ( ! is_string( $lang ) || '' === $lang ) {
			return $value;
		}

		$cache = $this->cache->get( $lang );
		if ( false === $cache ) {
			$cache = $this->translate_string_recursive( $value, $this->keys );
			$this->cache->set( $lang, $cache );
		}

		return $cache;
	}

	/**
	 * Recursively translates the strings registered for an option.
	 *
	 * @since 1.0
	 *
	 * @param mixed      $values Either a string to translate or a list of strings to translate.
	 * @param array|bool $key    Array of option keys to translate.
	 * @return array|string Translated string(s)
	 */
	protected function translate_string_recursive( $values, $key ) {
		$children = is_array( $key ) ? $key : array();

		if ( is_array( $values ) || is_object( $values ) ) {
			if ( count( $children ) ) {
				foreach ( $children as $name => $child ) {
					if ( is_array( $values ) && isset( $values[ $name ] ) ) {
						$values[ $name ] = $this->translate_string_recursive( $values[ $name ], $child );
						continue;
					}

					if ( is_object( $values ) && isset( $values->$name ) ) {
						$values->$name = $this->translate_string_recursive( $values->$name, $child );
						continue;
					}

					$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';

					foreach ( $values as $n => &$value ) {
						// The first case could be handled by the next one, but we avoid calls to preg_match here.
						if ( '*' === $name || ( false !== strpos( $name, '*' ) && preg_match( $pattern, $n ) ) ) {
							$value = $this->translate_string_recursive( $value, $child );
						}
					}
				}
			} else {
				// Parent key is a wildcard and no sub-key has been whitelisted.
				foreach ( $values as &$value ) {
					$value = $this->translate_string_recursive( $value, $key );
				}
			}
		} else {
			$values = pll__( $values );
		}

		return $values;
	}

	/**
	 * Recursively registers strings for an option.
	 *
	 * @since 1.0
	 * @since 2.7 Signature modified
	 *
	 * @param string     $context The group in which the strings will be registered.
	 * @param string     $option  Option name.
	 * @param mixed      $values  Option value.
	 * @param array|bool $key     Array of option keys to translate.
	 * @return void
	 */
	protected function register_string_recursive( $context, $option, $values, $key ) {
		if ( is_object( $values ) ) {
			$values = (array) $values;
		}

		if ( is_array( $values ) ) {
			$children = is_array( $key ) ? $key : array();

			if ( count( $children ) ) {
				foreach ( $children as $name => $child ) {
					if ( isset( $values[ $name ] ) ) {
						$this->register_string_recursive( $context, $name, $values[ $name ], $child );
						continue;
					}

					$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';

					foreach ( $values as $n => $value ) {
						// The first case could be handled by the next one, but we avoid calls to preg_match here.
						if ( '*' === $name || ( false !== strpos( $name, '*' ) && preg_match( $pattern, $n ) ) ) {
							$this->register_string_recursive( $context, $n, $value, $child );
						}
					}
				}
			} else {
				foreach ( $values as $n => $value ) {
					// Parent key is a wildcard and no sub-key has been whitelisted.
					$this->register_string_recursive( $context, $n, $value, $key );
				}
			}
		} else {
			PLL_Admin_Strings::register_string( $option, $values, $context, true );
		}
	}

	/**
	 * Returns the raw value of an option (without this class' filter).
	 *
	 * A static property is used to make sure that the option is not filtered
	 * whatever the number of instances of this class filtering the option.
	 *
	 * @since 3.3
	 *
	 * @param string $option_name Option name.
	 * @return mixed
	 */
	protected function get_raw_option( $option_name ) {
		self::$raw    = true;
		$option_value = get_option( $option_name );
		self::$raw    = false;

		return $option_value;
	}

	/**
	 * Filters an option before it is updated.
	 *
	 * This is the step 1 in the update process, in which we prevent the update of
	 * strings to their translations by filtering them out, and we store the updated strings
	 * for the next step.
	 *
	 * @since 2.9
	 *
	 * @param mixed  $value     The new, unserialized option value.
	 * @param mixed  $old_value The old (filtered) option value.
	 * @param string $name      Option name.
	 * @return mixed
	 */
	public function pre_update_option( $value, $old_value, $name ) {
		// Stores the unfiltered old option value before it is updated in DB.
		$unfiltered_old_value = $this->get_raw_option( $name );

		$languages = PLL()->model->get_languages_list();

		if ( empty( $languages ) ) {
			return $value;
		}

		// Load translations in all languages.
		foreach ( $languages as $language ) {
			$this->translations[ $language->slug ] = new PLL_MO();
			$this->translations[ $language->slug ]->import_from_db( $language );
		}

		$lang = pll_current_language();
		if ( empty( $lang ) ) {
			$lang = pll_default_language();
		}

		if ( empty( $lang ) ) {
			return $value; // Something's wrong.
		}

		// Filters out the strings which would be updated to their translations and stores the updated strings.
		$value = $this->check_value_recursive( $unfiltered_old_value, $value, $this->keys, $this->translations[ $lang ] );

		return $value;
	}

	/**
	 * Updates the string translations to keep the same translated value when updating the original option.
	 *
	 * This is the step 2 in the update process. Knowing all strings that have been updated,
	 * we remove the old strings from the strings translations and replace them by
	 * the new strings with the old translations.
	 *
	 * @since 2.9
	 *
	 * @return void
	 */
	public function update_option() {
		$curlang = pll_current_language();

		if ( ! empty( $this->updated_strings ) ) {
			foreach ( PLL()->model->get_languages_list() as $language ) {

				$mo = &$this->translations[ $language->slug ];

				foreach ( $this->updated_strings as $old_string => $string ) {
					$translation = $mo->translate( $old_string );
					if ( ( empty( $curlang ) && $translation === $old_string ) || $language->slug === $curlang ) {
						$translation = $string;
					}

					// Add new entry with new string and old translation.
					$mo->add_entry( $mo->make_entry( $string, $translation ) );
				}

				$mo->export_to_db( $language );
			}
		}

		$this->cache->clean();
	}

	/**
	 * Recursively compares the updated strings to the translation of the old string.
	 *
	 * This is the heart of the update process. If an updated string is found to be
	 * the same as the translation of the old string, we restore the old string to
	 * prevent the update in {@see PLL_Translate_Option::pre_update_option()}, otherwise
	 * the updated string is stored in {@see PLL_Translate_Option::updated_strings} to be able to
	 * later assign the translations to the new value in {@see PLL_Translate_Option::update_option()}.
	 *
	 * @since 2.9
	 * @since 3.5 Added $mo parameter.
	 *
	 * @param mixed      $old_values The old option value.
	 * @param mixed      $values     The new option value.
	 * @param array|bool $key        Array of option keys to translate.
	 * @param PLL_MO     $mo         Translations used to compare the updated string to the translated old string.
	 * @return mixed
	 */
	protected function check_value_recursive( $old_values, $values, $key, $mo ) {
		$children = is_array( $key ) ? $key : array();

		if ( is_array( $values ) || is_object( $values ) ) {
			if ( count( $children ) ) {
				foreach ( $children as $name => $child ) {
					if ( is_array( $values ) && is_array( $old_values ) && isset( $old_values[ $name ], $values[ $name ] ) ) {
						$values[ $name ] = $this->check_value_recursive( $old_values[ $name ], $values[ $name ], $child, $mo );
						continue;
					}

					if ( is_object( $values ) && is_object( $old_values ) && isset( $old_values->$name, $values->$name ) ) {
						$values->$name = $this->check_value_recursive( $old_values->$name, $values->$name, $child, $mo );
						continue;
					}

					$pattern = '#^' . str_replace( '*', '(?:.+)', $name ) . '$#';

					foreach ( $values as $n => $value ) {
						// The first case could be handled by the next one, but we avoid calls to preg_match here.
						if ( '*' === $name || ( false !== strpos( $name, '*' ) && preg_match( $pattern, $n ) ) ) {
							if ( is_array( $values ) && is_array( $old_values ) && isset( $old_values[ $n ] ) ) {
								$values[ $n ] = $this->check_value_recursive( $old_values[ $n ], $value, $child, $mo );
							}

							if ( is_object( $values ) && is_object( $old_values ) && isset( $old_values->$n ) ) {
								$values->$n = $this->check_value_recursive( $old_values->$n, $value, $child, $mo );
							}
						}
					}
				}
			} else {
				// Parent key is a wildcard and no sub-key has been whitelisted.
				foreach ( $values as $n => $value ) {
					if ( is_array( $values ) && is_array( $old_values ) && isset( $old_values[ $n ] ) ) {
						$values[ $n ] = $this->check_value_recursive( $old_values[ $n ], $value, $key, $mo );
					}

					if ( is_object( $values ) && is_object( $old_values ) && isset( $old_values->$n ) ) {
						$values->$n = $this->check_value_recursive( $old_values->$n, $value, $key, $mo );
					}
				}
			}
		} elseif ( $old_values !== $values ) {
			if ( $mo->translate( $old_values ) === $values ) {
				$values = $old_values; // Prevents updating the value to its translation.
			} else {
				$this->updated_strings[ $old_values ] = $values; // Stores the updated strings.
			}
		}

		return $values;
	}

	/**
	 * Sanitizes the option value.
	 *
	 * @since 2.9
	 *
	 * @param string $value The unsanitised value.
	 * @param string $name  The name of the option.
	 * @return string Sanitized value.
	 */
	public function sanitize_option( $value, $name ) {
		return sanitize_option( $name, $value );
	}
}
