<?php

/**
 * Abstract class to manage the copy and synchronization of metas
 *
 * @since 2.3
 */
abstract class PLL_Sync_Metas {
	public $model;
	protected $meta_type;

	/**
	 * Constructor
	 *
	 * @since 2.3
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;

		add_filter( "update_{$this->meta_type}_metadata", array( $this, 'update_metadata' ), 999, 5 ); // Very late in case a filter prevents the meta to be updated

		add_filter( "add_{$this->meta_type}_meta", array( $this, 'add_meta' ), 10, 3 );
		add_filter( "update_{$this->meta_type}_meta", array( $this, 'update_meta' ), 10, 4 );
		add_action( "delete_{$this->meta_type}_meta", array( $this, 'delete_meta' ), 10, 4 );
	}

	/**
	 * Maybe modify ("translate") a meta value when it is copied or synchronized
	 *
	 * @since 2.3
	 *
	 * @param mixed  $value Meta value
	 * @param string $key   Meta key
	 * @param int    $from  Id of the source
	 * @param int    $to    Id of the target
	 * @param string $lang  Language of target
	 * @return mixed
	 */
	protected function maybe_translate_value( $value, $key, $from, $to, $lang ) {
		/**
		 * Filter a meta value before is copied or synchronized
		 *
		 * @since 2.3
		 *
		 * @param mixed  $value Meta value
		 * @param string $key   Meta key
		 * @param string $lang  Language of target
		 * @param int    $from  Id of the source
		 * @param int    $to    Id of the target
		 */
		return apply_filters( "pll_translate_{$this->meta_type}_meta", maybe_unserialize( $value ), $key, $lang, $from, $to );
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
		/**
		 * Filter the custom fields to copy or synchronize
		 *
		 * @since 0.6
		 * @since 1.9.2 The `$from`, `$to`, `$lang` parameters were added.
		 *
		 * @param array  $keys List of custom fields names
		 * @param bool   $sync True if it is synchronization, false if it is a copy
		 * @param int    $from Id of the post from which we copy informations
		 * @param int    $to   Id of the post to which we paste informations
		 * @param string $lang Language slug
		 */
		return array_unique( apply_filters( "pll_copy_{$this->meta_type}_metas", array(), $sync, $from, $to, $lang ) );
	}

	/**
	 * Stores the previous value when
	 *
	 * @since 2.3
	 *
	 * @param null|bool $r          Not used
	 * @param int       $id         Object ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
	 * @param mixed     $prev_value If specified, only update existing metadata entries with the specified value.
	 * @return null|bool Unchanged
	 */
	public function update_metadata( $r, $id, $meta_key, $meta_value, $prev_value ) {
		if ( null === $r ) {
			$this->prev_value[ "$id|$meta_key|$meta_value" ] = $prev_value;
		}
		return $r;
	}

	/**
	 * Synchronize added metas across translations
	 *
	 * @since 2.3
	 *
	 * @param int    $id         Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 */
	public function add_meta( $id, $meta_key, $meta_value ) {
		static $avoid_recursion = false;

		if ( ! $avoid_recursion ) {
			$avoid_recursion = true;
			$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id !== $id ) {
					$to_copy = $this->get_metas_to_copy( $id, $tr_id, $lang, true );
					if ( in_array( $meta_key, $to_copy ) ) {
						$meta_value = $this->maybe_translate_value( $meta_value, $meta_key, $id, $tr_id, $lang );
						add_metadata( $this->meta_type, $tr_id, $meta_key, $meta_value );
					}
				}
			}

			$avoid_recursion = false;
		}
	}

	/**
	 * Synchronize updated metas across translations
	 *
	 * @since 2.3
	 *
	 * @param int    $mid        Meta id.
	 * @param int    $id         Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 */
	public function update_meta( $mid, $id, $meta_key, $meta_value ) {
		static $avoid_recursion = false;

		if ( ! $avoid_recursion ) {
			$avoid_recursion = true;

			$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id != $id ) {
					$to_copy = $this->get_metas_to_copy( $id, $tr_id, $lang, true );
					if ( in_array( $meta_key, $to_copy ) ) {
						$meta_value = $this->maybe_translate_value( $meta_value, $meta_key, $id, $tr_id, $lang );
						$prev_meta = get_metadata_by_mid( $this->meta_type, $mid );
						if ( empty( $this->prev_value[ "$id|$meta_key|$meta_value" ] ) || $this->prev_value[ "$id|$meta_key|$meta_value" ] === $prev_meta->meta_value ) {
							$prev_value = $this->maybe_translate_value( $prev_meta->meta_value, $meta_key, $id, $tr_id, $lang );
							update_metadata( $this->meta_type, $tr_id, $meta_key, $meta_value, $prev_value );
						}
					}
				}
			}

			unset( $this->prev_value[ "$id|$meta_key|$meta_value" ] );
			$avoid_recursion = false;
		}
	}

	/**
	 * Synchronize deleted meta across translations
	 *
	 * @since 2.3
	 *
	 * @param array  $mids  Not used
	 * @param int    $id    Object ID.
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 */
	public function delete_meta( $mids, $id, $key, $value ) {
		static $avoid_recursion = false;

		if ( ! $avoid_recursion ) {
			$avoid_recursion = true;

			$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id !== $id ) {
					$to_copy = $this->get_metas_to_copy( $id, $tr_id, $lang, true );
					if ( in_array( $key, $to_copy ) ) {
						if ( '' !== $value && null !== $value && false !== $value ) { // Same test as WP
							$value = $this->maybe_translate_value( $value, $key, $id, $tr_id, $lang );
						}
						delete_metadata( $this->meta_type, $tr_id, $key, $value );
					}
				}
			}
		}

		$avoid_recursion = false;
	}

	/**
	 * Copy metas when creating a new translations
	 * Don't use to synchronize
	 *
	 * @since 2.3
	 *
	 * @param int    $from Id of the source object
	 * @param int    $to   Id of the target object
	 * @param string $lang Language code
	 */
	public function copy( $from, $to, $lang ) {
		// We don't need to sync back the new metas
		remove_filter( "add_{$this->meta_type}_meta", array( $this, 'add_meta' ), 10, 4 );

		$to_copy = $this->get_metas_to_copy( $from, $to, $lang );
		$metas = get_metadata( $this->meta_type, $from );

		foreach ( $metas as $key => $values ) {
			if ( in_array( $key, $to_copy ) ) {
				foreach ( $values as $value ) {
					$to_value = $this->maybe_translate_value( $value, $key, $from, $to, $lang );
					add_metadata( $this->meta_type, $to, $key, $to_value );
				}
			}
		}

		add_filter( "add_{$this->meta_type}_meta", array( $this, 'add_meta' ), 10, 4 );
	}
}
