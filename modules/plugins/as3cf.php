<?php

/**
 * A class to manage the integration with WP Offload Media Lite.
 * Version tested: 2.1.1
 *
 * @since 2.6
 */
class PLL_AS3CF {
	private $is_media_translated;

	/**
	 * Initializes filters and actions.
	 *
	 * @since 2.6
	 */
	public function init() {
		add_filter( 'pll_copy_post_metas', array( $this, 'copy_post_metas' ) );
		add_action( 'delete_attachment', array( $this, 'check_translated_media' ), 5 ); // Before Polylang deletes the translations information.
		add_action( 'delete_attachment', array( $this, 'prevent_file_deletion' ), 15 ); // Between Polylang and WP Offload Media.
	}

	/**
	 * Synchronizes post metas
	 *
	 * @since 2.6
	 *
	 * @param array $metas List of custom fields names.
	 * @return array
	 */
	public function copy_post_metas( $metas ) {
		$metas[] = 'amazonS3_info';
		$metas[] = 'as3cf_filesize_total';
		return $metas;
	}

	/**
	 * Checks if the deleted attachment was translated and stores the information.
	 *
	 * @since 2.6
	 *
	 * @param int $post_id Id of the attachment being deleted.
	 */
	public function check_translated_media( $post_id ) {
		$this->is_media_translated[ $post_id ] = ( count( pll_get_post_translations( $post_id ) ) > 1 );
	}

	/**
	 * Deletes the WP Offload Media information from the attachment being deleted.
	 * That way WP Offload Media won't delete the file stored in the cloud.
	 * Done after Polylang has deleted the translations informations, to avoid the synchronization of the deletion
	 * and of course before WP Offload Media deletes the file, normally at priority 20.
	 *
	 * @since 2.6
	 *
	 * @param int $post_id Id of the attachment being deleted.
	 */
	public function prevent_file_deletion( $post_id ) {
		if ( ! empty( $this->is_media_translated[ $post_id ] ) ) {
			delete_post_meta( $post_id, 'amazonS3_info' );
			delete_post_meta( $post_id, 'as3cf_filesize_total' );
		}
	}
}
