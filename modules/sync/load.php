<?php
/**
 * Loads the module for general synchronization such as metas and taxonomies.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( $polylang->model->get_languages_list() ) {
	$polylang->add_shared( 'sync_tax', PLL_Sync_Tax::class )
		->withArgument( $polylang->model )
		->withArgument( $polylang->options );
	$polylang->add_shared( 'sync_post_metas', PLL_Sync_Post_Metas::class )
		->withArgument( $polylang->model )
		->withArgument( $polylang->options );
	$polylang->add_shared( 'sync_term_metas', PLL_Sync_Term_Metas::class )
		->withArgument( $polylang->model );

	$polylang->add_shared( 'sync', $polylang instanceof PLL_Admin_Base ? PLL_Admin_Sync::class : PLL_Sync::class )
		->withArguments(
			array(
				$polylang->model,
				$polylang->options,
				'sync_tax',
				'sync_post_metas',
				'sync_term_metas',
			)
		);

	$polylang->get( 'sync' )->init();
}
