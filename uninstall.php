<?php
/**
 * @package Polylang
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { // If uninstall not called from WordPress exit
	exit;
}

/**
 * Manages Polylang uninstallation
 * The goal is to remove ALL Polylang related data in db
 *
 * @since 0.5
 */
class PLL_Uninstall {

	/**
	 * Constructor: manages uninstall for multisite
	 *
	 * @since 0.5
	 */
	public function __construct() {
		global $wpdb;

		// Don't do anything except if the constant PLL_REMOVE_ALL_DATA is explicitely defined and true.
		if ( ! defined( 'PLL_REMOVE_ALL_DATA' ) || ! PLL_REMOVE_ALL_DATA ) {
			return;
		}

		// Check if it is a multisite uninstall - if so, run the uninstall function for each blog id
		if ( is_multisite() ) {
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->uninstall();
			}
			restore_current_blog();
		}
		else {
			$this->uninstall();
		}
	}

	/**
	 * Removes ALL plugin data
	 * only when the relevant option is active
	 *
	 * @since 0.5
	 */
	public function uninstall() {
		global $wpdb;

		do_action( 'pll_uninstall' );

		// Need to register the taxonomies
		$pll_taxonomies = array( 'language', 'term_language', 'post_translations', 'term_translations' );
		foreach ( $pll_taxonomies as $taxonomy ) {
			register_taxonomy( $taxonomy, null, array( 'label' => false, 'public' => false, 'query_var' => false, 'rewrite' => false ) );
		}

		$languages = get_terms( 'language', array( 'hide_empty' => false ) );

		// Delete users options
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
			delete_user_meta( $user_id, 'pll_filter_content' );
			delete_user_meta( $user_id, 'pll_dismissed_notices' ); // Legacy meta.
			foreach ( $languages as $lang ) {
				delete_user_meta( $user_id, 'description_' . $lang->slug );
			}
		}

		// Delete menu language switchers
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

		// Delete the strings translations.
		register_post_type( 'polylang_mo', array( 'rewrite' => false, 'query_var' => false ) );
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

		// Delete all what is related to languages and translations
		$term_ids = array();
		$tt_ids   = array();

		foreach ( get_terms( $pll_taxonomies, array( 'hide_empty' => false ) ) as $term ) {
			$term_ids[] = (int) $term->term_id;
			$tt_ids[] = (int) $term->term_taxonomy_id;
		}

		if ( ! empty( $term_ids ) ) {
			$term_ids = array_unique( $term_ids );
			$wpdb->query( "DELETE FROM {$wpdb->terms} WHERE term_id IN ( " . implode( ',', $term_ids ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ( " . implode( ',', $term_ids ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		if ( ! empty( $tt_ids ) ) {
			$tt_ids = array_unique( $tt_ids );
			$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', $tt_ids ) . ' )' ); // PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Delete options
		delete_option( 'polylang' );
		delete_option( 'widget_polylang' ); // Automatically created by WP
		delete_option( 'polylang_wpml_strings' ); // Strings registered with icl_register_string
		delete_option( 'polylang_licenses' );
		delete_option( 'pll_dismissed_notices' );

		// Delete transients
		delete_transient( 'pll_languages_list' );
	}
}

new PLL_Uninstall();
