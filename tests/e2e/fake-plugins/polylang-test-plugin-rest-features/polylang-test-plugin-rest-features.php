<?php

/**
 * Polylang test plugin for E2E.
 *
 * @package           Polylang
 * @author            WP SYNTEX
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       polylang-test-plugin-rest-features
 * Plugin URI:        https://polylang.pro
 * Description:       Adds multilingual capability to WordPress
 * Version:           1.0
 * Requires at least: 5.8
 * Requires PHP:      5.6
 * Author:            WP SYNTEX
 * Author URI:        https://polylang.pro
 * Text Domain:       polylang
 * Domain Path:       /languages
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Copyright 2011-2019 Frédéric Demarle
 * Copyright 2019-2023 WP SYNTEX
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * This plugin is for end-to-end test purpose only.
 */

if ( ! defined( PLL_TEST_NAMESPACE ) ) {
	define( 'PLL_TEST_NAMESPACE', 'pll-test/v1' );
}

/**
 * Registers both languages and options routes.
 *
 * @since 1.0
 *
 * @return void
 */
function pll_test_register_rest_routes() {
	register_rest_route(
		PLL_TEST_NAMESPACE,
		'/languages',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pll_test_get_languages',
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'description'       => 'Slug of the retrieved language. If none is provided, all languages are returned',
						'type'              => 'string',
						'required'          => false,
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'pll_test_create_languages',
				'permission_callback' => '__return_true',
				'args'                => array(
					'locale' => array(
						'description'       => 'Locale of the created language.',
						'type'              => 'string',
						'required'          => true,
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => 'pll_test_delete_languages',
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'description'       => 'Slug of the deleted language. If none is provided, all languages are deleted',
						'type'              => 'string',
						'required'          => false,
					),
				),
			),
		)
	);

	register_rest_route(
		PLL_TEST_NAMESPACE,
		'/options',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pll_test_get_options',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'pll_test_edit_options',
				'permission_callback' => '__return_true',
				'args'                => array(
					'key' => array(
						'description'       => 'Option key.',
						'type'              => 'string',
						'required'          => true,
					),
					'value' => array(
						'description'       => 'Option value.',
						'type'              => 'string',
						'required'          => true,
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => 'pll_test_delete_options',
				'permission_callback' => '__return_true',
			),
		)
	);
}
add_action( 'rest_api_init', 'pll_test_register_rest_routes' );

/**
 * Returns one or all languages.
 *
 * @since 1.0
 *
 * @param WP_REST_Request $request Current REST request.
 * @return array Language object as array, or array of languages.
 */
function pll_test_get_languages( $request ) {
	$slug = $request->get_param( 'slug' );

	if ( ! empty( $slug ) ) {
		$language = PLL()->model->get_language( $slug );

		return $language->to_array();
	}

	$languages = PLL()->model->get_languages_list();
	array_walk(
		$languages,
		function( $language ) {
			return $language->to_array();
		}
	);

	return $languages;
}

/**
 * Creates a languages.
 *
 * @since 1.0
 *
 * @param WP_REST_Request $request Current REST request.
 * @return array|WP_Error Array of language props on success, `WP_Error` on failure.
 */
function pll_test_create_languages( $request ) {
	$locale               = $request->get_param( 'locale' );
	$languages            = require POLYLANG_DIR . '/settings/languages.php';
	$values               = $languages[ $locale ];
	$values['slug']       = $values['code'];
	$values['rtl']        = (int) ( 'rtl' === $values['dir'] );
	$values['term_group'] = 0;
	$admin_model          = new PLL_Admin_Model( PLL()->options );
	$errors               = $admin_model->add_language( $values );

	if ( is_wp_error( $errors ) ) {
		return $errors;
	}

	$admin_model->clean_languages_cache();

	return PLL()->model->get_language( $locale );
}

/**
 * Deletes all languages.
 *
 * @since 1.0
 *
 * @return WP_Error|true `WP_Error` on failure, `true` on success.
 */
function pll_test_delete_languages() {
	$languages = PLL()->model->get_languages_list();
	if ( ! is_array( $languages ) ) {
		return new WP_Error( 'bad_request', 'No languages exist.' );
	}
	// Delete the default categories first.
	$tt = wp_get_object_terms( get_option( 'default_category' ), 'term_translations' );
	$terms = PLL()->model->term->get_translations( get_option( 'default_category' ) );

	wp_delete_term( $tt, 'term_translations' );

	foreach ( $terms as $t ) {
		wp_delete_term( $t, 'category' );
	}

	foreach ( $languages as $lang ) {
		$admin_model = new PLL_Admin_Model( PLL()->options );
		$admin_model->delete_language( $lang->term_id );
	}

	return true;
}

/**
 * Returns all Polylang's options.
 *
 * @since 1.0
 *
 * @return array Array of options.
 */
function pll_test_get_options() {
	return PLL()->options;
}

/**
 * Updates an option.
 *
 * @since 1.0
 *
 * @param WP_REST_Request $request Current REST request.
 * @return WP_Error|true `WP_Error` on failure, `true` on success.
 */
function pll_test_edit_options( $request ) {
	$key   = $request->get_param( 'key' );
	$value = $request->get_param( 'value' );

	if ( empty( PLL()->options[ $key ] ) ) {
		return new WP_Error( 'bad_request', 'GIven option key does not exist.' );
	}

	PLL()->options[ $key ] = $value;

	return true;
}

/**
 * Resets Polylang's options to default values.
 *
 * @since 1.0
 *
 * @return void
 */
function pll_test_delete_options() {
	$default = PLL_Install::get_default_options();
	PLL()->options = $default;
}
