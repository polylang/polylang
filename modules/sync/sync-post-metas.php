<?php

/**
 * A class to manage copy and synchronization of post metas
 *
 * @since 2.3
 */
class PLL_Sync_Post_Metas extends PLL_Sync_Metas {
	public $options;

	/**
	 * Constructor
	 *
	 * @since 2.3
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->meta_type = 'post';

		parent::__construct( $polylang );

		$this->options = &$polylang->options;

		add_filter( 'pll_translate_post_meta', array( $this, 'translate_thumbnail_id' ), 10, 3 );
	}

	/**
	 * Get the custom fields to copy or synchronize
	 *
	 * @since 2.3
	 *
	 * @param int    $from Id of the post from which we copy informations
	 * @param int    $to   Id of the post to which we paste informations
	 * @param string $lang Language slug
	 * @param bool   $sync True if it is synchronization, false if it is a copy
	 * @return array List of meta keys
	 */
	protected function get_metas_to_copy( $from, $to, $lang, $sync = false ) {
		// Copy or synchronize post metas and allow plugins to do the same
		$metas = get_post_custom( $from );
		$keys = array();

		// Get public meta keys ( including from translated post in case we just deleted a custom field )
		if ( ! $sync || in_array( 'post_meta', $this->options['sync'] ) ) {
			foreach ( $keys = array_unique( array_merge( array_keys( $metas ), array_keys( get_post_custom( $to ) ) ) ) as $k => $meta_key ) {
				if ( is_protected_meta( $meta_key ) ) {
					unset( $keys[ $k ] );
				}
			}
		}

		// Add page template and featured image
		foreach ( array( '_wp_page_template', '_thumbnail_id' ) as $meta ) {
			if ( ! $sync || in_array( $meta, $this->options['sync'] ) ) {
				$keys[] = $meta;
			}
		}

		if ( $this->options['media_support'] ) {
			$keys[] = '_wp_attached_file';
			$keys[] = '_wp_attachment_metadata';
			$keys[] = '_wp_attachment_backup_sizes';
			$keys[] = '_wp_attachment_is_custom_header'; // Random header image
		}

		/** This filter is documented in modules/sync/sync-metas.php */
		return array_unique( apply_filters( 'pll_copy_post_metas', $keys, $sync, $from, $to, $lang ) );
	}

	/**
	 * Translates the thumbnail id
	 *
	 * @since 2.3
	 *
	 * @param int    $value Thumbnail id
	 * @param string $key   Meta key
	 * @param string $lang  Language code
	 * @return int
	 */
	public function translate_thumbnail_id( $value, $key, $lang ) {
		return ( $this->options['media_support'] && '_thumbnail_id' === $key && $to_value = $this->model->post->get_translation( $value, $lang ) ) ? $to_value : $value;
	}
}
