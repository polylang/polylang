<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use Countable;
use ArrayIterator;
use IteratorAggregate;

/**
 * Collection aggregate root that stores multiple Translatable entities.
 *
 * @since 3.8
 */
class Collection implements IteratorAggregate, Countable {
	/**
	 * The translatables stored in this collection.
	 *
	 * @var Translatable[]
	 */
	private $translatables = array();

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Translatable[] $translatables Optional. Initial translatables to add to the collection.
	 */
	public function __construct( array $translatables = array() ) {
		foreach ( $translatables as $translatable ) {
			$this->add( $translatable );
		}
	}

	/**
	 * Adds a translatable to the collection.
	 *
	 * @since 3.8
	 *
	 * @param Translatable $translatable The translatable to add.
	 * @return void
	 */
	public function add( Translatable $translatable ): void {
		$this->translatables[ $translatable->get_id() ] = $translatable;
	}

	/**
	 * Removes a translatable from the collection by ID.
	 *
	 * @since 3.8
	 *
	 * @param string $id The identifier.
	 * @return void
	 */
	public function remove( string $id ): void {
		unset( $this->translatables[ $id ] );
	}

	/**
	 * Gets a translatable by ID.
	 *
	 * @since 3.8
	 *
	 * @param string $id The identifier.
	 * @return Translatable|null The translatable if found, null otherwise.
	 */
	public function get( string $id ): ?Translatable {
		return $this->translatables[ $id ] ?? null;
	}

	/**
	 * Checks if a translatable exists in the collection.
	 *
	 * @since 3.8
	 *
	 * @param string $id The identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->translatables[ $id ] );
	}

	/**
	 * Gets the count of translatables in the collection.
	 *
	 * @since 3.8
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->translatables );
	}

	/**
	 * Filters translatables by context.
	 *
	 * @since 3.8
	 *
	 * @param string $context The context to filter by.
	 * @return Collection A new collection with filtered translatables.
	 */
	public function filter_by_context( string $context ): Collection {
		$filtered = array();

		foreach ( $this->translatables as $translatable ) {
			if ( $translatable->get_context() === $context ) {
				$filtered[ $translatable->get_id() ] = $translatable;
			}
		}

		return new Collection( $filtered );
	}

	/**
	 * Gets an iterator for the translatables.
	 *
	 * @since 3.8
	 *
	 * @return \ArrayIterator<string, Translatable>
	 */
	public function getIterator(): \ArrayIterator {
		return new ArrayIterator( $this->translatables );
	}
}
