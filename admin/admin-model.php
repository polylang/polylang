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
	 *   @type string $name           Language name ( used only for display ).
	 *   @type string $slug           Language code ( ideally 2-letters ISO 639-1 language code ).
	 *   @type string $locale         WordPress locale. If something wrong is used for the locale, the .mo files will not be loaded...
	 *   @type int    $rtl            1 if rtl language, 0 otherwise.
	 *   @type int    $term_group     Language order when displayed.
	 *   @type string $no_default_cat Optional, if set, no default category will be created for this language.
	 *   @type string $flag           Optional, country code, @see flags.php.
	 * }
	 * @return WP_Error|true true if success / WP_Error if failed.
	 */
	public function add_language( $args ) {
		$errors = $this->validate_lang( $args );
		if ( $errors->get_error_code() ) { // Using has_errors() would be more meaningful but is available only since WP 5.0
			return $errors;
		}

		// First the language taxonomy
		$description = maybe_serialize( array( 'locale' => $args['locale'], 'rtl' => (int) $args['rtl'], 'flag_code' => empty( $args['flag'] ) ? '' : $args['flag'] ) );
		$r = wp_insert_term( $args['name'], 'language', array( 'slug' => $args['slug'], 'description' => $description ) );
		if ( is_wp_error( $r ) ) {
			// Avoid an ugly fatal error if something went wrong ( reported once in the forum )
			return new WP_Error( 'pll_add_language', __( 'Impossible to add the language.', 'polylang' ) );
		}
		wp_update_term( (int) $r['term_id'], 'language', array( 'term_group' => (int) $args['term_group'] ) ); // can't set the term group directly in wp_insert_term

		// The term_language taxonomy
		// Don't want shared terms so use a different slug
		wp_insert_term( $args['name'], 'term_language', array( 'slug' => 'pll_' . $args['slug'] ) );

		$this->clean_languages_cache(); // Update the languages list now !

		if ( ! isset( $this->options['default_lang'] ) ) {
			// If this is the first language created, set it as default language
			$this->options['default_lang'] = $args['slug'];
			update_option( 'polylang', $this->options );
		}

		// Init a mo_id for this language
		$mo = new PLL_MO();
		$mo->export_to_db( $this->get_language( $args['slug'] ) );

		/**
		 * Fires when a language is added.
		 *
		 * @since 1.9
		 *
		 * @param array $args Arguments used to create the language. @see PLL_Admin_Model::add_language().
		 */
		do_action( 'pll_add_language', $args );

		$this->clean_languages_cache(); // Again to set add mo_id in the cached languages list
		flush_rewrite_rules(); // Refresh rewrite rules
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
		if ( $this->options['default_lang'] == $lang->slug ) {
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

		// Delete the string translations
		$post = wpcom_vip_get_page_by_title( 'polylang_mo_' . $lang->term_id, OBJECT, 'polylang_mo' );
		if ( $post instanceof WP_Post ) {
			wp_delete_post( $post->ID );
		}

		// Delete domain
		unset( $this->options['domains'][ $lang->slug ] );

		// Delete the language itself
		wp_delete_term( $lang->term_id, 'language' );
		wp_delete_term( $lang->tl_term_id, 'term_language' );

		// Update languages list
		$this->clean_languages_cache();

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
			if ( $this->options['default_lang'] == $old_slug ) {
				$this->options['default_lang'] = $slug;
			}
		}

		update_option( 'polylang', $this->options );

		// And finally update the language itself
		$description = maybe_serialize( array( 'locale' => $args['locale'], 'rtl' => (int) $args['rtl'], 'flag_code' => empty( $args['flag'] ) ? '' : $args['flag'] ) );
		wp_update_term( (int) $lang->term_id, 'language', array( 'slug' => $slug, 'name' => $args['name'], 'description' => $description, 'term_group' => (int) $args['term_group'] ) );
		if ( empty( $lang->tl_term_id ) ) {
			// Attempt to repair the term_language if it has been deleted by a database cleaning tool.
			wp_insert_term( $args['name'], 'term_language', array( 'slug' => 'pll_' . $slug ) );
		} else {
			wp_update_term( (int) $lang->tl_term_id, 'term_language', array( 'slug' => 'pll_' . $slug, 'name' => $args['name'] ) );
		}

		/**
		 * Fires when a language is updated.
		 *
		 * @since 1.9
		 *
		 * @param array $args Arguments used to modify the language. @see PLL_Admin_Model::update_language().
		 */
		do_action( 'pll_update_language', $args );

		$this->clean_languages_cache();
		flush_rewrite_rules(); // Refresh rewrite rules
		return true;
	}

	/**
	 * Validates data entered when creating or updating a language.
	 *
	 * @see PLL_Admin_Model::add_language().
	 *
	 * @since 0.4
	 *
	 * @param array        $args Parameters of {@see PLL_Admin_Model::add_language() or @see PLL_Admin_Model::update_language()}.
	 * @param PLL_Language $lang Optional the language currently updated, the language is created if not set.
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
		if ( ! empty( $args['flag'] ) && ! file_exists( POLYLANG_DIR . '/flags/' . $args['flag'] . '.png' ) ) {
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
	 * Assigns a language to posts or terms in mass.
	 *
	 * @since 1.2
	 *
	 * @param string              $type Either 'post' or 'term'.
	 * @param int[]               $ids  Array of post ids or term ids.
	 * @param PLL_Language|string $lang Language to assign to the posts or terms.
	 * @return void
	 */
	public function set_language_in_mass( $type, $ids, $lang ) {
		global $wpdb;

		$lang = $this->get_language( $lang );

		if ( empty( $lang ) ) {
			return;
		}

		$tt_id  = 'term' === $type ? $lang->tl_term_taxonomy_id : $lang->term_taxonomy_id;
		$values = array();
		$ids    = array_map( 'intval', $ids );

		foreach ( $ids as $id ) {
			$values[] = $wpdb->prepare( '( %d, %d )', $id, $tt_id );
		}

		if ( ! empty( $values ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->term_relationships} ( object_id, term_taxonomy_id ) VALUES " . implode( ',', array_unique( $values ) ) );
			$lang->update_count(); // Updating term count is mandatory ( thanks to AndyDeGroo )
		}

		if ( 'term' === $type ) {
			clean_term_cache( $ids, 'term_language' );
			$translations = array();

			foreach ( $ids as $id ) {
				$translations[] = array( $lang->slug => $id );
			}

			if ( ! empty( $translations ) ) {
				$this->set_translation_in_mass( 'term', $translations );
			}
		} else {
			clean_term_cache( $ids, 'language' );
		}
	}

	/**
	 * Creates translations groups in mass.
	 *
	 * @since 1.6.3
	 *
	 * @param string $type         Either 'post' or 'term'
	 * @param array  $translations Array of translations arrays.
	 * @return void
	 */
	public function set_translation_in_mass( $type, $translations ) {
		global $wpdb;

		$taxonomy    = $type . '_translations';
		$terms       = array();
		$slugs       = array();
		$description = array();
		$count       = array();

		foreach ( $translations as $t ) {
			$term = uniqid( 'pll_' ); // the term name
			$terms[] = $wpdb->prepare( '( %s, %s )', $term, $term );
			$slugs[] = $wpdb->prepare( '%s', $term );
			$description[ $term ] = maybe_serialize( $t );
			$count[ $term ] = count( $t );
		}

		// Insert terms
		if ( ! empty( $terms ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->terms} ( slug, name ) VALUES " . implode( ',', array_unique( $terms ) ) );
		}

		// Get all terms with their term_id
		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		$terms    = $wpdb->get_results( "SELECT term_id, slug FROM {$wpdb->terms} WHERE slug IN ( " . implode( ',', $slugs ) . ' )' );
		$term_ids = array();
		$tts      = array();

		// Prepare terms taxonomy relationship
		foreach ( $terms as $term ) {
			$term_ids[] = $term->term_id;
			$tts[] = $wpdb->prepare( '( %d, %s, %s, %d )', $term->term_id, $taxonomy, $description[ $term->slug ], $count[ $term->slug ] );
		}

		// Insert term_taxonomy
		if ( ! empty( $tts ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->term_taxonomy} ( term_id, taxonomy, description, count ) VALUES " . implode( ',', array_unique( $tts ) ) );
		}

		// Get all terms with term_taxonomy_id
		$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
		$trs   = array();

		// Prepare objects relationships.
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$t = maybe_unserialize( $term->description );
				if ( in_array( $t, $translations ) ) {
					foreach ( $t as $object_id ) {
						if ( ! empty( $object_id ) ) {
							$trs[] = $wpdb->prepare( '( %d, %d )', $object_id, $term->term_taxonomy_id );
						}
					}
				}
			}
		}

		// Insert term_relationships
		if ( ! empty( $trs ) ) {
			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->term_relationships} ( object_id, term_taxonomy_id ) VALUES " . implode( ',', $trs ) );
			$trs = array_unique( $trs );
		}

		clean_term_cache( $term_ids, $taxonomy );
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

		$terms    = get_terms( array( 'post_translations', 'term_translations' ) );
		$term_ids = array();
		$dr       = array();
		$dt       = array();
		$ut       = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_ids[ $term->taxonomy ][] = $term->term_id;
				$tr = maybe_unserialize( $term->description );
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
