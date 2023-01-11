<?php
/**
 * @package Polylang
 */

/**
 * A class to import languages and translations information form a WXR file
 *
 * @since 1.2
 */
class PLL_WP_Import extends WP_Import {
	/**
	 * Stores post_translations terms.
	 *
	 * @var array
	 */
	public $post_translations = array();

	/**
	 * Overrides WP_Import::process_terms to remap terms translations.
	 *
	 * @since 1.2
	 */
	public function process_terms() {
		$term_translations = array();

		// Store this for future usage as parent function unsets $this->terms.
		foreach ( $this->terms as $term ) {
			if ( 'post_translations' == $term['term_taxonomy'] ) {
				$this->post_translations[] = $term;
			}
			if ( 'term_translations' == $term['term_taxonomy'] ) {
				$term_translations[] = $term;
			}
		}

		parent::process_terms();

		// First reset the core terms cache as WordPress Importer calls wp_suspend_cache_invalidation( true );
		wp_cache_set( 'last_changed', microtime(), 'terms' );

		// Assign the default language in case the importer created the first language.
		if ( empty( PLL()->options['default_lang'] ) ) {
			$languages = get_terms( array( 'taxonomy' => 'language', 'hide_empty' => false, 'orderby' => 'term_id' ) );
			$default_lang = reset( $languages );
			PLL()->options['default_lang'] = $default_lang->slug;
			update_option( 'polylang', PLL()->options );
		}

		// Clean languages cache in case some of them were created during import.
		PLL()->model->clean_languages_cache();

		$this->remap_terms_relations( $term_translations );
		$this->remap_translations( $term_translations, $this->processed_terms );
	}

	/**
	 * Overrides WP_Import::process_post to remap posts translations
	 * Also merges strings translations from the WXR file to the existing ones
	 *
	 * @since 1.2
	 */
	public function process_posts() {
		$menu_items = $mo_posts = array();

		// Store this for future usage as parent function unset $this->posts
		foreach ( $this->posts as $post ) {
			if ( 'nav_menu_item' == $post['post_type'] ) {
				$menu_items[] = $post;
			}

			if ( 0 === strpos( $post['post_title'], 'polylang_mo_' ) ) {
				$mo_posts[] = $post;
			}
		}

		if ( ! empty( $mo_posts ) ) {
			new PLL_MO(); // Just to register the polylang_mo post type before processing posts
		}

		parent::process_posts();

		PLL()->model->clean_languages_cache(); // To update the posts count in ( cached ) languages list

		$this->remap_translations( $this->post_translations, $this->processed_posts );
		unset( $this->post_translations );

		// Language switcher menu items
		foreach ( $menu_items as $item ) {
			foreach ( $item['postmeta'] as $meta ) {
				if ( '_pll_menu_item' == $meta['key'] ) {
					update_post_meta( $this->processed_menu_items[ $item['post_id'] ], '_pll_menu_item', maybe_unserialize( $meta['value'] ) );
				}
			}
		}

		// Merge strings translations
		foreach ( $mo_posts as $post ) {
			$lang_id = (int) substr( $post['post_title'], 12 );

			if ( ! empty( $this->processed_terms[ $lang_id ] ) ) {
				if ( $strings = maybe_unserialize( $post['post_content'] ) ) {
					$mo = new PLL_MO();
					$mo->import_from_db( $this->processed_terms[ $lang_id ] );
					foreach ( $strings as $msg ) {
						$mo->add_entry_or_merge( $mo->make_entry( $msg[0], $msg[1] ) );
					}
					$mo->export_to_db( $this->processed_terms[ $lang_id ] );
				}
			}
			// Delete the now useless imported post
			wp_delete_post( $this->processed_posts[ $post['post_id'] ], true );
		}
	}

	/**
	 * Remaps terms languages
	 *
	 * @since 1.2
	 *
	 * @param array $terms array of terms in 'term_translations' taxonomy
	 */
	protected function remap_terms_relations( &$terms ) {
		global $wpdb;

		$trs = array();

		foreach ( $terms as $term ) {
			$translations = maybe_unserialize( $term['term_description'] );
			foreach ( $translations as $slug => $old_id ) {
				if ( $old_id && ! empty( $this->processed_terms[ $old_id ] ) && $lang = PLL()->model->get_language( $slug ) ) {
					// Language relationship
					$trs[] = $wpdb->prepare( '( %d, %d )', $this->processed_terms[ $old_id ], $lang->get_tax_prop( 'term_language', 'term_taxonomy_id' ) );

					// Translation relationship
					$trs[] = $wpdb->prepare( '( %d, %d )', $this->processed_terms[ $old_id ], get_term( $this->processed_terms[ $term['term_id'] ], 'term_translations' )->term_taxonomy_id );
				}
			}
		}

		// Insert term_relationships
		if ( ! empty( $trs ) ) {
			$trs = array_unique( $trs );

			// Make sure we don't attempt to insert already existing term relationships
			$existing_trs = $wpdb->get_results(
				"SELECT tr.object_id, tr.term_taxonomy_id FROM {$wpdb->term_relationships} AS tr
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tt.taxonomy IN ( 'term_language', 'term_translations' )"
			);

			foreach ( $existing_trs as $key => $tr ) {
				$existing_trs[ $key ] = $wpdb->prepare( '( %d, %d )', $tr->object_id, $tr->term_taxonomy_id );
			}

			$trs = array_diff( $trs, $existing_trs );

			if ( ! empty( $trs ) ) {
				// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( "INSERT INTO {$wpdb->term_relationships} ( object_id, term_taxonomy_id ) VALUES " . implode( ',', $trs ) );
			}
		}
	}

	/**
	 * Remaps translations for both posts and terms
	 *
	 * @since 1.2
	 *
	 * @param array $terms array of terms in 'post_translations' or 'term_translations' taxonomies
	 * @param array $processed_objects array of posts or terms processed by WordPress Importer
	 */
	protected function remap_translations( &$terms, &$processed_objects ) {
		global $wpdb;

		$u = array();

		foreach ( $terms as $term ) {
			$translations = maybe_unserialize( $term['term_description'] );
			$new_translations = array();

			foreach ( $translations as $slug => $old_id ) {
				if ( $old_id && ! empty( $processed_objects[ $old_id ] ) ) {
					$new_translations[ $slug ] = $processed_objects[ $old_id ];
				}
			}

			if ( ! empty( $new_translations ) ) {
				$u['case'][] = $wpdb->prepare( 'WHEN %d THEN %s', $this->processed_terms[ $term['term_id'] ], maybe_serialize( $new_translations ) );
				$u['in'][] = (int) $this->processed_terms[ $term['term_id'] ];
			}
		}

		if ( ! empty( $u ) ) {
			// PHPCS:disable WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query(
				"UPDATE {$wpdb->term_taxonomy}
				SET description = ( CASE term_id " . implode( ' ', $u['case'] ) . ' END )
				WHERE term_id IN ( ' . implode( ',', $u['in'] ) . ' )'
			);
			// PHPCS:enable
		}
	}
}
