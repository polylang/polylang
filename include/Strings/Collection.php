<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use Countable;
use PLL_Language;
use ArrayIterator;
use IteratorAggregate;

/**
 * Collection aggregate root that stores multiple Translatable entities.
 *
 * @since 3.8
 *
 * @implements IteratorAggregate<string, Translatable>
 */
class Collection implements IteratorAggregate, Countable {
	/**
	 * The translatables stored in this collection.
	 *
	 * @var Translatable[]
	 */
	private array $translatables = array();

	/**
	 * The target language of the collection.
	 *
	 * @var PLL_Language
	 */
	private PLL_Language $language;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Translatable[] $translatables Initial translatables to add to the collection.
	 * @param PLL_Language   $language      The target language of the collection.
	 */
	public function __construct( array $translatables, PLL_Language $language ) {
		$this->language = $language;
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
	 * Gets the target language of the collection.
	 *
	 * @since 3.8
	 *
	 * @return PLL_Language The target language.
	 */
	public function target_language(): PLL_Language {
		return $this->language;
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
