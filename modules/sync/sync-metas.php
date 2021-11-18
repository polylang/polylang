<?php
/**
 * @package Polylang
 */

/**
 * Abstract class to manage the copy and synchronization of metas
 *
 * @since 2.3
 */
abstract class PLL_Sync_Metas {
	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Meta type. Typically 'post' or 'term'.
	 *
	 * @var string
	 */
	protected $meta_type;

	/**
	 * Stores the previous values when updating a meta.
	 *
	 * @var array
	 */
	protected $prev_value;

	/**
	 * Stores the metas to synchronize before deleting them.
	 *
	 * @var array
	 */
	protected $to_copy;

	/**
	 * Constructor
	 *
	 * @since 2.3
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->model = &$polylang->model;

		add_filter( "add_{$this->meta_type}_metadata", array( $this, 'can_synchronize_metadata' ), 1, 3 );
		add_filter( "update_{$this->meta_type}_metadata", array( $this, 'can_synchronize_metadata' ), 1, 3 );
		add_filter( "delete_{$this->meta_type}_metadata", array( $this, 'can_synchronize_metadata' ), 1, 3 );

		$this->add_all_meta_actions();

		add_action( "pll_save_{$this->meta_type}", array( $this, 'save_object' ), 10, 3 );
	}

	/**
	 * Removes "added_{$this->meta_type}_meta" action
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	protected function remove_add_meta_action() {
		remove_action( "added_{$this->meta_type}_meta", array( $this, 'add_meta' ), 10, 4 );
	}

	/**
	 * Removes all meta synchronization actions and filters
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	protected function remove_all_meta_actions() {
		$this->remove_add_meta_action();

		remove_filter( "update_{$this->meta_type}_metadata", array( $this, 'update_metadata' ), 999, 5 );
		remove_action( "update_{$this->meta_type}_meta", array( $this, 'update_meta' ), 10, 4 );

		remove_action( "delete_{$this->meta_type}_meta", array( $this, 'store_metas_to_sync' ), 10, 2 );
		remove_action( "deleted_{$this->meta_type}_meta", array( $this, 'delete_meta' ), 10, 4 );
	}

	/**
	 * Adds "added_{$this->meta_type}_meta" action
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	protected function restore_add_meta_action() {
		add_action( "added_{$this->meta_type}_meta", array( $this, 'add_meta' ), 10, 4 );
	}

	/**
	 * Adds meta synchronization actions and filters
	 *
	 * @since 2.3
	 *
	 * @return void
	 */
	protected function add_all_meta_actions() {
		$this->restore_add_meta_action();

		add_filter( "update_{$this->meta_type}_metadata", array( $this, 'update_metadata' ), 999, 5 ); // Very late in case a filter prevents the meta to be updated
		add_action( "update_{$this->meta_type}_meta", array( $this, 'update_meta' ), 10, 4 );

		add_action( "delete_{$this->meta_type}_meta", array( $this, 'store_metas_to_sync' ), 10, 2 );
		add_action( "deleted_{$this->meta_type}_meta", array( $this, 'delete_meta' ), 10, 4 );
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
	 * Get the custom fields to copy or synchronize.
	 *
	 * @since 2.3
	 *
	 * @param int    $from Id of the post from which we copy informations.
	 * @param int    $to   Id of the post to which we paste informations.
	 * @param string $lang Language slug.
	 * @param bool   $sync True if it is synchronization, false if it is a copy.
	 * @return string[] List of meta keys.
	 */
	protected function get_metas_to_copy( $from, $to, $lang, $sync = false ) {
		/**
		 * Filters the custom fields to copy or synchronize.
		 *
		 * @since 0.6
		 * @since 1.9.2 The `$from`, `$to`, `$lang` parameters were added.
		 *
		 * @param string[] $keys List of custom fields names.
		 * @param bool     $sync True if it is synchronization, false if it is a copy.
		 * @param int      $from Id of the post from which we copy informations.
		 * @param int      $to   Id of the post to which we paste informations.
		 * @param string   $lang Language slug.
		 */
		return array_unique( apply_filters( "pll_copy_{$this->meta_type}_metas", array(), $sync, $from, $to, $lang ) );
	}

	/**
	 * Disallow modifying synchronized meta if the current user can not modify translations
	 *
	 * @since 2.6
	 *
	 * @param null|bool $check    Whether to allow adding/updating/deleting metadata.
	 * @param int       $id       Object ID.
	 * @param string    $meta_key Meta key.
	 * @return null|bool
	 */
	public function can_synchronize_metadata( $check, $id, $meta_key ) {
		if ( ! $this->model->{$this->meta_type}->current_user_can_synchronize( $id ) ) {
			$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id != $id ) {
					$to_copy = $this->get_metas_to_copy( $id, $tr_id, $lang, true );
					if ( in_array( $meta_key, $to_copy ) ) {
						return false;
					}
				}
			}
		}
		return $check;
	}

	/**
	 * Synchronize added metas across translations
	 *
	 * @since 2.3
	 *
	 * @param int    $mid        Meta id.
	 * @param int    $id         Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 * @return void
	 */
	public function add_meta( $mid, $id, $meta_key, $meta_value ) {
		static $avoid_recursion = false;

		if ( ! $avoid_recursion ) {
			$avoid_recursion = true;
			$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id != $id ) {
					$to_copy = $this->get_metas_to_copy( $id, $tr_id, $lang, true );
					if ( in_array( $meta_key, $to_copy ) ) {
						$meta_value = $this->maybe_translate_value( $meta_value, $meta_key, $id, $tr_id, $lang );
						add_metadata( $this->meta_type, $tr_id, wp_slash( $meta_key ), is_object( $meta_value ) ? $meta_value : wp_slash( $meta_value ) );
					}
				}
			}

			$avoid_recursion = false;
		}
	}

	/**
	 * Stores the previous value when updating metas
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
			$hash = md5( "$id|$meta_key|" . maybe_serialize( $meta_value ) );
			$this->prev_value[ $hash ] = $prev_value;
		}
		return $r;
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
	 * @return void
	 */
	public function update_meta( $mid, $id, $meta_key, $meta_value ) {
		static $avoid_recursion = false;

		if ( ! $avoid_recursion ) {
			$avoid_recursion = true;
			$hash = md5( "$id|$meta_key|" . maybe_serialize( $meta_value ) );

			$prev_meta = get_metadata_by_mid( $this->meta_type, $mid );

			if ( $prev_meta ) {
				$this->remove_add_meta_action(); // We don't want to sync back the new metas
				$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

				foreach ( $tr_ids as $lang => $tr_id ) {
					if ( $tr_id != $id && in_array( $meta_key, $this->get_metas_to_copy( $id, $tr_id, $lang, true ) ) ) {
						if ( empty( $this->prev_value[ $hash ] ) || $this->prev_value[ $hash ] === $prev_meta->meta_value ) {
							$prev_value = $this->maybe_translate_value( $prev_meta->meta_value, $meta_key, $id, $tr_id, $lang );
							$meta_value = $this->maybe_translate_value( $meta_value, $meta_key, $id, $tr_id, $lang );
							update_metadata( $this->meta_type, $tr_id, wp_slash( $meta_key ), is_object( $meta_value ) ? $meta_value : wp_slash( $meta_value ), $prev_value );
						}
					}
				}
				$this->restore_add_meta_action();
			}

			unset( $this->prev_value[ $hash ] );
			$avoid_recursion = false;
		}
	}

	/**
	 * Store metas to synchronize before deleting them.
	 *
	 * @since 2.3
	 *
	 * @param int[] $mids  Not used.
	 * @param int   $id    Object ID.
	 * @return void
	 */
	public function store_metas_to_sync( $mids, $id ) {
		$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

		foreach ( $tr_ids as $lang => $tr_id ) {
			$this->to_copy[ $id ][ $tr_id ] = $this->get_metas_to_copy( $id, $tr_id, $lang, true );
		}
	}

	/**
	 * Synchronizes deleted meta across translations.
	 *
	 * @since 2.3
	 *
	 * @param int[]  $mids  Not used.
	 * @param int    $id    Object ID.
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 * @return void
	 */
	public function delete_meta( $mids, $id, $key, $value ) {
		static $avoid_recursion = false;

		if ( ! $avoid_recursion ) {
			$avoid_recursion = true;

			$tr_ids = $this->model->{$this->meta_type}->get_translations( $id );

			foreach ( $tr_ids as $lang => $tr_id ) {
				if ( $tr_id != $id ) {
					if ( in_array( $key, $this->to_copy[ $id ][ $tr_id ] ) ) {
						if ( '' !== $value && null !== $value && false !== $value ) { // Same test as WP
							$value = $this->maybe_translate_value( $value, $key, $id, $tr_id, $lang );
						}
						delete_metadata( $this->meta_type, $tr_id, wp_slash( $key ), is_object( $value ) ? $value : wp_slash( $value ) );
					}
				}
			}
		}

		$avoid_recursion = false;
	}

	/**
	 * Copy or synchronize metas
	 *
	 * @since 2.3
	 *
	 * @param int    $from Id of the source object
	 * @param int    $to   Id of the target object
	 * @param string $lang Language code of the target object
	 * @param bool   $sync Optional, defaults to true. True if it is synchronization, false if it is a copy
	 * @return void
	 */
	public function copy( $from, $to, $lang, $sync = false ) {
		$this->remove_all_meta_actions();

		$to_copy = $this->get_metas_to_copy( $from, $to, $lang, $sync );
		$metas = get_metadata( $this->meta_type, $from );
		$tr_metas = get_metadata( $this->meta_type, $to );

		foreach ( $to_copy as $key ) {
			if ( empty( $metas[ $key ] ) ) {
				if ( ! empty( $tr_metas[ $key ] ) ) {
					// If the meta key is not present in the source object, delete all values
					delete_metadata( $this->meta_type, $to, wp_slash( $key ) );
				}
			} else {
				if ( ! empty( $tr_metas[ $key ] ) && 1 === count( $metas[ $key ] ) && 1 === count( $tr_metas[ $key ] ) ) {
					// One custom field to update
					$value = reset( $metas[ $key ] );
					$value = maybe_unserialize( $value );
					$to_value = $this->maybe_translate_value( $value, $key, $from, $to, $lang );
					update_metadata( $this->meta_type, $to, wp_slash( $key ), is_object( $to_value ) ? $to_value : wp_slash( $to_value ) );
				} else {
					// Multiple custom fields, either in the source or the target
					if ( ! empty( $tr_metas[ $key ] ) ) {
						// The synchronization of multiple values custom fields is easier if we delete all metas first
						delete_metadata( $this->meta_type, $to, wp_slash( $key ) );
					}

					foreach ( $metas[ $key ] as $value ) {
						$value = maybe_unserialize( $value );
						$to_value = $this->maybe_translate_value( $value, $key, $from, $to, $lang );
						add_metadata( $this->meta_type, $to, wp_slash( $key ), is_object( $to_value ) ? $to_value : wp_slash( $to_value ) );
					}
				}
			}
		}

		$this->add_all_meta_actions();
	}

	/**
	 * If synchronized custom fields were previously not synchronized, it is expected
	 * that saving a post (or term) will synchronize them.
	 *
	 * @since 2.3
	 *
	 * @param int    $object_id    Id of the object being saved.
	 * @param object $obj          Not used.
	 * @param int[]  $translations The list of translations object ids.
	 * @return void
	 */
	public function save_object( $object_id, $obj, $translations ) {
		foreach ( $translations as $tr_lang => $tr_id ) {
			if ( $tr_id != $object_id ) {
				$this->copy( $object_id, $tr_id, $tr_lang, true );
			}
		}
	}
}
