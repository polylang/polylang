<?php
/**
 * @package Polylang
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { // If uninstall not called from WordPress exit.
	exit;
}

/**
 * Manages Polylang uninstallation.
 * The goal is to remove **all** Polylang related data in db.
 *
 * @since 0.5
 */
class PLL_Uninstall {

	/**
	 * Constructor: manages uninstall for multisite.
	 *
	 * @since 0.5
	 */
	public function __construct() {
		global $wpdb;

		// Don't do anything except if the constant PLL_REMOVE_ALL_DATA is explicitly defined and true.
		if ( ! defined( 'PLL_REMOVE_ALL_DATA' ) || ! PLL_REMOVE_ALL_DATA ) {
			return;
		}

		// Check if it is a multisite uninstall - if so, run the uninstall function for each blog id.
		if ( is_multisite() ) {
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->uninstall();
			}
			restore_current_blog();
		} else {
			$this->uninstall();
		}
	}

	/**
	 * Removes **all** plugin data.
	 *
	 * @since 0.5
	 */
	public function uninstall() {
		global $wpdb;

		do_action( 'pll_uninstall' );

		// We need to register the taxonomies.
		$pll_taxonomies = array(
			'language',
			'term_language',
			'post_translations',
			'term_translations',
		);

		foreach ( $pll_taxonomies as $taxonomy ) {
			register_taxonomy(
				$taxonomy,
				null,
				array(
					'label'     => false,
					'public'    => false,
					'query_var' => false,
					'rewrite'   => false,
				)
			);
		}

		$languages = get_terms(
			array(
				'taxonomy'   => 'language',
				'hide_empty' => false,
			)
		);

		// Delete users options.
		delete_metadata( 'user', 0, 'pll_filter_content', '', true );
		delete_metadata( 'user', 0, 'pll_dismissed_notices', '', true ); // Legacy meta.
		foreach ( $languages as $lang ) {
			delete_metadata( 'user', 0, "description_{$lang->slug}", '', true );
		}

		// Delete menu language switchers.
		$ids = get_posts(
			array(
				'post_type'   => 'nav_menu_item',
				'numberposts' => -1,
				'nopaging'    => true,
				'fields'      => 'ids',
				'meta_key'    => '_pll_menu_item',
			)
		);

		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}

		/*
		 * Backward compatibility with Polylang < 3.4.
		 * Delete the legacy strings translations.
		 */
		register_post_type(
			'polylang_mo',
			array(
				'rewrite'   => false,
				'query_var' => false,
			)
		);
		$ids = get_posts(
			array(
				'post_type'   => 'polylang_mo',
				'post_status' => 'any',
				'numberposts' => -1,
				'nopaging'    => true,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}

		// Delete all what is related to languages and translations.
		$term_ids = array();
		$tt_ids   = array();

		$terms = get_terms(
			array(
				'taxonomy'   => $pll_taxonomies,
				'hide_empty' => false,
			)
		);

		foreach ( $terms as $term ) {
			$term_ids[] = (int) $term->term_id;
			$tt_ids[]   = (int) $term->term_taxonomy_id;
		}

		if ( ! empty( $term_ids ) ) {
			$term_ids = array_unique( $term_ids );
			$wpdb->query(
				$wpdb->prepare(
					sprintf(
						"DELETE FROM {$wpdb->terms} WHERE term_id IN (%s)",
						implode( ',', array_fill( 0, count( $term_ids ), '%d' ) )
					),
					$term_ids
				)
			);
				$wpdb->query(
					$wpdb->prepare(
						sprintf(
							"DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN (%s)",
							implode( ',', array_fill( 0, count( $term_ids ), '%d' ) )
						),
						$term_ids
					)
				);
			$wpdb->query(
				$wpdb->prepare(
					sprintf(
						"DELETE FROM {$wpdb->termmeta} WHERE term_id IN (%s) AND meta_key='_pll_strings_translations'",
						implode( ',', array_fill( 0, count( $term_ids ), '%d' ) )
					),
					$term_ids
				)
			);
		}

		if ( ! empty( $tt_ids ) ) {
			$tt_ids = array_unique( $tt_ids );
			$wpdb->query( $wpdb->prepare( sprintf( "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (%s)", implode( ',', array_fill( 0, count( $tt_ids ), '%d' ) ) ), $tt_ids ) );
		}

		// Delete options.
		delete_option( 'polylang' );
		delete_option( 'widget_polylang' ); // Automatically created by WP.
		delete_option( 'polylang_wpml_strings' ); // Strings registered with icl_register_string.
		delete_option( 'polylang_licenses' );
		delete_option( 'pll_dismissed_notices' );
		delete_option( 'pll_language_from_content_available' );
		delete_option( 'pll_language_taxonomies' );

		// Delete transients.
		delete_option( '_transient_pll_languages_list' ); // Force deletion of the transient from the options table even in case external object cache is used.
	}
}

new PLL_Uninstall();
