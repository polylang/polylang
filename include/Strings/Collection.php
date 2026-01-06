<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

/**
 * Collection aggregate root that stores multiple Translatable entities.
 *
 * @since 3.8
 */
class Collection {
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
	 * Gets all translatables in the collection.
	 *
	 * @since 3.8
	 *
	 * @return Translatable[]
	 */
	public function all(): array {
		return $this->translatables;
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
	 * Gets all unique contexts from the translatables.
	 *
	 * @since 3.8
	 *
	 * @return string[]
	 */
	public function get_contexts(): array {
		$contexts = array();

		foreach ( $this->translatables as $translatable ) {
			$contexts[] = $translatable->get_context();
		}

		return array_unique( $contexts );
	}

	/**
	 * Converts the collection to an array representation.
	 *
	 * @since 3.8
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function to_array(): array {
		$result = array();

		foreach ( $this->translatables as $translatable ) {
			$result[] = $translatable->to_array();
		}

		return $result;
	}
}
