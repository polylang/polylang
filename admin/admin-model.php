<?php
/**
 * @package Polylang
 */

/**
 * Extends the PLL_Model class with methods needed only in Polylang settings pages.
 *
 * @since 1.2
 */
class PLL_Admin_Model extends PLL_Model {

	/**
	 * Adds a new language
	 * and creates a default category for this language.
	 *
	 * @since 1.2
	 *
	 * @param array $args {
	 *   @type string $name           Language name (used only for display).
	 *   @type string $slug           Language code (ideally 2-letters ISO 639-1 language code).
	 *   @type string $locale         WordPress locale. If something wrong is used for the locale, the .mo files will
	 *                                not be loaded...
	 *   @type int    $rtl            1 if rtl language, 0 otherwise.
	 *   @type int    $term_group     Language order when displayed.
	 *   @type string $no_default_cat Optional, if set, no default category will be created for this language.
	 *   @type string $flag           Optional, country code, {@see settings/flags.php}.
	 * }
	 * @return WP_Error|true true if success / WP_Error if failed.
	 */
	public function add_language( $args ) {
		$errors = $this->validate_lang( $args );
		if ( $errors->has_errors() ) {
			return $errors;
		}

		// First the language taxonomy
		$r = wp_insert_term(
			$args['name'],
			'language',
			array(
				'slug'        => $args['slug'],
				'description' => $this->build_language_metas( $args ),
			)
		);
		if ( is_wp_error( $r ) ) {
			// Avoid an ugly fatal error if something went wrong ( reported once in the forum )
			return new WP_Error( 'pll_add_language', __( 'Impossible to add the language.', 'polylang' ) );
		}
		wp_update_term( (int) $r['term_id'], 'language', array( 'term_group' => (int) $args['term_group'] ) ); // can't set the term group directly in wp_insert_term

		// The other language taxonomies.
		$this->update_secondary_language_terms( $args['slug'], $args['name'] );

		if ( ! isset( $this->options['default_lang'] ) ) {
			// If this is the first language created, set it as default language
			$this->options['default_lang'] = $args['slug'];
			update_option( 'polylang', $this->options );
		}

		// Refresh languages.
		$this->clean_languages_cache();
		$this->get_languages_list();

		flush_rewrite_rules(); // Refresh rewrite rules.

		/**
		 * Fires when a language is added.
		 *
		 * @since 1.9
		 *
		 * @param array $args Arguments used to create the language. @see PLL_Admin_Model::add_language().
		 */
		do_action( 'pll_add_language', $args );

		return true;
	}

	/**
	 * Delete a language.
	 *
	 * @since 1.2
	 *
	 * @param int $lang_id Language term_id.
	 * @return bool
	 */
	public function delete_language( $lang_id ) {
		$lang = $this->get_language( (int) $lang_id );

		if ( empty( $lang ) ) {
			return false;
		}

		// Oops ! we are deleting the default language...
		// Need to do this before loosing the information for default category translations
		if ( $lang->is_default ) {
			$slugs = $this->get_languages_list( array( 'fields' => 'slug' ) );
			$slugs = array_diff( $slugs, array( $lang->slug ) );

			if ( ! empty( $slugs ) ) {
				$this->update_default_lang( reset( $slugs ) ); // Arbitrary choice...
			} else {
				unset( $this->options['default_lang'] );
			}
		}

		// Delete the translations
		$this->update_translations( $lang->slug );

		// Delete language option in widgets
		foreach ( $GLOBALS['wp_registered_widgets'] as $widget ) {
			if ( ! empty( $widget['callback'][0] ) && ! empty( $widget['params'][0]['number'] ) ) {
				$obj = $widget['callback'][0];
				$number = $widget['params'][0]['number'];
				if ( is_object( $obj ) && method_exists( $obj, 'get_settings' ) && method_exists( $obj, 'save_settings' ) ) {
					$settings = $obj->get_settings();
					if ( isset( $settings[ $number ]['pll_lang'] ) && $settings[ $number ]['pll_lang'] == $lang->slug ) {
						unset( $settings[ $number ]['pll_lang'] );
						$obj->save_settings( $settings );
					}
				}
			}
		}

		// Delete menus locations
		if ( ! empty( $this->options['nav_menus'] ) ) {
			foreach ( $this->options['nav_menus'] as $theme => $locations ) {
				foreach ( array_keys( $locations ) as $location ) {
					unset( $this->options['nav_menus'][ $theme ][ $location ][ $lang->slug ] );
				}
			}
		}

		// Delete users options
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
			delete_user_meta( $user_id, 'pll_filter_content', $lang->slug );
			delete_user_meta( $user_id, 'description_' . $lang->slug );
		}

		// Delete domain
		unset( $this->options['domains'][ $lang->slug ] );

		/*
		 * Delete the language itself.
		 *
		 * Reverses the language taxonomies order is required to make sure 'language' is deleted in last.
		 *
		 * The initial order with the 'language' taxonomy at the beginning of 'PLL_Language::term_props' property
		 * is done by {@see PLL_Model::filter_language_terms_orderby()}
		 */
		foreach ( array_reverse( $lang->get_tax_props( 'term_id' ) ) as $taxonomy_name => $term_id ) {
			wp_delete_term( $term_id, $taxonomy_name );
		}

		// Refresh languages.
		$this->clean_languages_cache();
		$this->get_languages_list();

		update_option( 'polylang', $this->options );
		flush_rewrite_rules(); // refresh rewrite rules
		return true;
	}

	/**
	 * Updates language properties.
	 *
	 * @since 1.2
	 *
	 * @param array $args {
	 *   @type int    $lang_id        Id of the language to modify.
	 *   @type string $name           Language name ( used only for display ).
	 *   @type string $slug           Language code ( ideally 2-letters ISO 639-1 language code ).
	 *   @type string $locale         WordPress locale. If something wrong is used for the locale, the .mo files will not be loaded...
	 *   @type int    $rtl            1 if rtl language, 0 otherwise.
	 *   @type int    $term_group     Language order when displayed.
	 *   @type string $flag           Optional, country code, @see flags.php.
	 * }
	 * @return WP_Error|true true if success / WP_Error if failed.
	 */
	public function update_language( $args ) {
		$lang = $this->get_language( (int) $args['lang_id'] );

		if ( empty( $lang ) ) {
			return new WP_Error( 'pll_invalid_language_id', __( 'The language does not seem to exist.', 'polylang' ) );
		}

		$errors = $this->validate_lang( $args, $lang );
		if ( $errors->get_error_code() ) { // Using has_errors() would be more meaningful but is available only since WP 5.0
			return $errors;
		}

		// Update links to this language in posts and terms in case the slug has been modified
		$slug = $args['slug'];
		$old_slug = $lang->slug;

		if ( $old_slug != $slug ) {
			// Update the language slug in translations
			$this->update_translations( $old_slug, $slug );

			// Update language option in widgets
			foreach ( $GLOBALS['wp_registered_widgets'] as $widget ) {
				if ( ! empty( $widget['callback'][0] ) && ! empty( $widget['params'][0]['number'] ) ) {
					$obj = $widget['callback'][0];
					$number = $widget['params'][0]['number'];
					if ( is_object( $obj ) && method_exists( $obj, 'get_settings' ) && method_exists( $obj, 'save_settings' ) ) {
						$settings = $obj->get_settings();
						if ( isset( $settings[ $number ]['pll_lang'] ) && $settings[ $number ]['pll_lang'] == $old_slug ) {
							$settings[ $number ]['pll_lang'] = $slug;
							$obj->save_settings( $settings );
						}
					}
				}
			}

			// Update menus locations
			if ( ! empty( $this->options['nav_menus'] ) ) {
				foreach ( $this->options['nav_menus'] as $theme => $locations ) {
					foreach ( array_keys( $locations ) as $location ) {
						if ( ! empty( $this->options['nav_menus'][ $theme ][ $location ][ $old_slug ] ) ) {
							$this->options['nav_menus'][ $theme ][ $location ][ $slug ] = $this->options['nav_menus'][ $theme ][ $location ][ $old_slug ];
							unset( $this->options['nav_menus'][ $theme ][ $location ][ $old_slug ] );
						}
					}
				}
			}

			// Update domains
			if ( ! empty( $this->options['domains'][ $old_slug ] ) ) {
				$this->options['domains'][ $slug ] = $this->options['domains'][ $old_slug ];
				unset( $this->options['domains'][ $old_slug ] );
			}

			// Update the default language option if necessary
			if ( $lang->is_default ) {
				$this->options['default_lang'] = $slug;
			}
		}

		update_option( 'polylang', $this->options );

		// And finally update the language itself.
		$this->update_secondary_language_terms( $args['slug'], $args['name'], $lang );

		$description = $this->build_language_metas( $args );
		wp_update_term( $lang->get_tax_prop( 'language', 'term_id' ), 'language', array( 'slug' => $slug, 'name' => $args['name'], 'description' => $description, 'term_group' => (int) $args['term_group'] ) );

		// Refresh languages.
		$this->clean_languages_cache();
		$this->get_languages_list();

		// Refresh rewrite rules.
		flush_rewrite_rules();

		/**
		 * Fires after a language is updated.
		 *
		 * @since 1.9
		 * @since 3.2 Added $lang parameter.
		 *
		 * @param array $args {
		 *   Arguments used to modify the language. @see PLL_Admin_Model::update_language().
		 *
		 *   @type string $name           Language name (used only for display).
		 *   @type string $slug           Language code (ideally 2-letters ISO 639-1 language code).
		 *   @type string $locale         WordPress locale.
		 *   @type int    $rtl            1 if rtl language, 0 otherwise.
		 *   @type int    $term_group     Language order when displayed.
		 *   @type string $no_default_cat Optional, if set, no default category has been created for this language.
		 *   @type string $flag           Optional, country code, @see flags.php.
		 * }
		 * @param PLL_Language $lang Previous value of the language beeing edited.
		 */
		do_action( 'pll_update_language', $args, $lang );

		return true;
	}

	/**
	 * Builds the language metas into an array and serializes it, to be stored in the term description.
	 *
	 * @since 3.4
	 *
	 * @param array $args {
	 *   @type string $name       Language name (used only for display).
	 *   @type string $slug       Language code (ideally 2-letters ISO 639-1 language code).
	 *   @type string $locale     WordPress locale. If something wrong is used for the locale, the .mo files will not be
	 *                            loaded...
	 *   @type int    $rtl        1 if rtl language, 0 otherwise.
	 *   @type int    $term_group Language order when displayed.
	 *   @type int    $lang_id    Optional, ID of the language to modify. An empty value means the language is being
	 *                            created.
	 *   @type string $flag       Optional, country code, {@see settings/flags.php}.
	 * }
	 * @return string The serialized description array updated.
	 */
	protected function build_language_metas( array $args ) {
		if ( ! empty( $args['lang_id'] ) ) {
			$language_term = get_term( (int) $args['lang_id'] );

			if ( $language_term instanceof WP_Term ) {
				$old_data = maybe_unserialize( $language_term->description );
			}
		}

		if ( empty( $old_data ) || ! is_array( $old_data ) ) {
			$old_data = array();
		}

		$new_data = array(
			'locale'    => $args['locale'],
			'rtl'       => ! empty( $args['rtl'] ) ? 1 : 0,
			'flag_code' => empty( $args['flag'] ) ? '' : $args['flag'],
		);

		/**
		 * Allow to add data to store for a language.
		 * `$locale`, `$rtl`, and `$flag_code` cannot be overwriten.
		 *
		 * @since 3.4
		 *
		 * @param mixed[] $add_data Data to add.
		 * @param mixed[] $args     {
		 *     Arguments used to create the language.
		 *
		 *     @type string $name       Language name (used only for display).
		 *     @type string $slug       Language code (ideally 2-letters ISO 639-1 language code).
		 *     @type string $locale     WordPress locale. If something wrong is used for the locale, the .mo files will
		 *                              not be loaded...
		 *     @type int    $rtl        1 if rtl language, 0 otherwise.
		 *     @type int    $term_group Language order when displayed.
		 *     @type int    $lang_id    Optional, ID of the language to modify. An empty value means the language is
		 *                              being created.
		 *     @type string $flag       Optional, country code, {@see settings/flags.php}.
		 * }
		 * @param mixed[] $new_data New data.
		 * @param mixed[] $old_data {
		 *     Original data. Contains at least the following:
		 *
		 *     @type string $locale    WordPress locale.
		 *     @type int    $rtl       1 if rtl language, 0 otherwise.
		 *     @type string $flag_code Country code.
		 * }
		 */
		$add_data = apply_filters( 'pll_language_metas', array(), $args, $new_data, $old_data );
		// Don't allow to overwrite `$locale`, `$rtl`, and `$flag_code`.
		$new_data = array_merge( $old_data, $add_data, $new_data );

		/** @var non-empty-string $serialized maybe_serialize() cannot return anything else than a string when feeded by an array. */
		$serialized = maybe_serialize( $new_data );
		return $serialized;
	}

	/**
	 * Validates data entered when creating or updating a language.
	 *
	 * @see PLL_Admin_Model::add_language().
	 *
	 * @since 0.4
	 *
	 * @param array             $args Parameters of {@see PLL_Admin_Model::add_language() or @see PLL_Admin_Model::update_language()}.
	 * @param PLL_Language|null $lang Optional the language currently updated, the language is created if not set.
	 * @return WP_Error
	 */
	protected function validate_lang( $args, $lang = null ) {
		$errors = new WP_Error();

		// Validate locale with the same pattern as WP 4.3. See #28303
		if ( ! preg_match( '#^[a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?$#', $args['locale'], $matches ) ) {
			$errors->add( 'pll_invalid_locale', __( 'Enter a valid WordPress locale', 'polylang' ) );
		}

		// Validate slug characters
		if ( ! preg_match( '#^[a-z_-]+$#', $args['slug'] ) ) {
			$errors->add( 'pll_invalid_slug', __( 'The language code contains invalid characters', 'polylang' ) );
		}

		// Validate slug is unique
		foreach ( $this->get_languages_list() as $language ) {
			if ( $language->slug === $args['slug'] && ( null === $lang || $lang->term_id !== $language->term_id ) ) {
				$errors->add( 'pll_non_unique_slug', __( 'The language code must be unique', 'polylang' ) );
			}
		}

		// Validate name
		// No need to sanitize it as wp_insert_term will do it for us
		if ( empty( $args['name'] ) ) {
			$errors->add( 'pll_invalid_name', __( 'The language must have a name', 'polylang' ) );
		}

		// Validate flag
		if ( ! empty( $args['flag'] ) && ! is_readable( POLYLANG_DIR . '/flags/' . $args['flag'] . '.png' ) ) {
			$flag = PLL_Language::get_flag_informations( $args['flag'] );

			if ( ! empty( $flag['url'] ) ) {
				$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( esc_url_raw( $flag['url'] ) ) : wp_remote_get( esc_url_raw( $flag['url'] ) );
			}

			if ( empty( $response ) || is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$errors->add( 'pll_invalid_flag', __( 'The flag does not exist', 'polylang' ) );
			}
		}

		return $errors;
	}

	/**
	 * Updates the translations when a language slug has been modified in settings
	 * or deletes them when a language is removed.
	 *
	 * @since 0.5
	 *
	 * @param string $old_slug The old language slug.
	 * @param string $new_slug Optional, the new language slug, if not set it means that the language has been deleted.
	 * @return void
	 */
	public function update_translations( $old_slug, $new_slug = '' ) {
		global $wpdb;

		$term_ids = array();
		$dr       = array();
		$dt       = array();
		$ut       = array();

		$taxonomies = $this->translatable_objects->get_taxonomy_names( array( 'translations' ) );
		$terms      = get_terms( array( 'taxonomy' => $taxonomies ) );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_ids[ $term->taxonomy ][] = $term->term_id;
				$tr = maybe_unserialize( $term->description );

				/**
				 * Filters the unserialized translation group description before it is
				 * updated when a language is deleted or a language slug is changed.
				 *
				 * @since 3.2
				 *
				 * @param (int|string[])[] $tr {
				 *     List of translations with lang codes as array keys and IDs as array values.
				 *     Also in this array:
				 *
				 *     @type string[] $sync List of synchronized translations with lang codes as array keys and array values.
				 * }
				 * @param string           $old_slug The old language slug.
				 * @param string           $new_slug The new language slug.
				 * @param WP_Term          $term     The term containing the post or term translation group.
				 */
				$tr = apply_filters( 'update_translation_group', $tr, $old_slug, $new_slug, $term );

				if ( ! empty( $tr[ $old_slug ] ) ) {
					if ( $new_slug ) {
						$tr[ $new_slug ] = $tr[ $old_slug ]; // Suppress this for delete
					} else {
						$dr['id'][] = (int) $tr[ $old_slug ];
						$dr['tt'][] = (int) $term->term_taxonomy_id;
					}
					unset( $tr[ $old_slug ] );

					if ( empty( $tr ) || 1 == count( $tr ) ) {
						$dt['t'][] = (int) $term->term_id;
						$dt['tt'][] = (int) $term->term_taxonomy_id;
					} else {
						$ut['case'][] = $wpdb->prepare( 'WHEN %d THEN %s', $term->term_id, maybe_serialize( $tr ) );
						$ut['in'][] = (int) $term->term_id;
					}
				}
			}
		}

		// Delete relationships
		if ( ! empty( $dr ) ) {
			// PHPCS:disable WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query(
				"DELETE FROM $wpdb->term_relationships
				WHERE object_id IN ( " . implode( ',', $dr['id'] ) . ' )
				AND term_taxonomy_id IN ( ' . implode( ',', $dr['tt'] ) . ' )'
			);
			// PHPCS:enable
		}

		// Delete terms
		if ( ! empty( $dt ) ) {
			$wpdb->query( "DELETE FROM $wpdb->terms WHERE term_id IN ( " . implode( ',', $dt['t'] ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id IN ( " . implode( ',', $dt['tt'] ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Update terms
		if ( ! empty( $ut ) ) {
			// PHPCS:disable WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query(
				"UPDATE $wpdb->term_taxonomy
				SET description = ( CASE term_id " . implode( ' ', $ut['case'] ) . ' END )
				WHERE term_id IN ( ' . implode( ',', $ut['in'] ) . ' )'
			);
			// PHPCS:enable
		}

		if ( ! empty( $term_ids ) ) {
			foreach ( $term_ids as $taxonomy => $ids ) {
				clean_term_cache( $ids, $taxonomy );
			}
		}
	}

	/**
	 * Updates the default language
	 * taking care to update the default category & the nav menu locations.
	 *
	 * @since 1.8
	 *
	 * @param string $slug New language slug.
	 * @return void
	 */
	public function update_default_lang( $slug ) {
		// The nav menus stored in theme locations should be in the default language
		$theme = get_stylesheet();
		if ( ! empty( $this->options['nav_menus'][ $theme ] ) ) {
			$menus = array();

			foreach ( $this->options['nav_menus'][ $theme ] as $key => $loc ) {
				$menus[ $key ] = empty( $loc[ $slug ] ) ? 0 : $loc[ $slug ];
			}
			set_theme_mod( 'nav_menu_locations', $menus );
		}

		/**
		 * Fires when a default language is updated.
		 *
		 * @since 3.1
		 *
		 * @param string $slug Slug.
		 */
		do_action( 'pll_update_default_lang', $slug );

		// Update options
		$this->options['default_lang'] = $slug;
		update_option( 'polylang', $this->options );

		$this->clean_languages_cache();
		flush_rewrite_rules();
	}
}
