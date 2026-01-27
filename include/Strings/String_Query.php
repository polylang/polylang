<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Strings;

use WP_Syntex\Polylang\Model\Languages;

/**
 * Query builder for filtering and sorting string collections.
 *
 * @since 3.8
 */
class String_Query {
	/**
	 * Context filter.
	 *
	 * @var string|null
	 */
	private ?string $context = null;

	/**
	 * Fragment filter for searching in source and translations.
	 *
	 * @var string|null
	 */
	private ?string $fragment = null;

	/**
	 * Field to sort by.
	 *
	 * @var string
	 * @phpstan-var 'string'|'name'|'context'
	 */
	private string $order_by = 'name';

	/**
	 * Sort order (asc or desc).
	 *
	 * @var string
	 * @phpstan-var 'asc'|'desc'
	 */
	private string $order = 'asc';

	/**
	 * The repository instance.
	 *
	 * @var Database_Repository
	 */
	private Database_Repository $repository;

	/**
	 * The languages model.
	 *
	 * @var Languages
	 */
	private Languages $languages;

	/**
	 * Constructor.
	 *
	 * @since 3.8
	 *
	 * @param Database_Repository $repository The repository instance.
	 * @param Languages           $languages  The languages model.
	 */
	public function __construct( Database_Repository $repository, Languages $languages ) {
		$this->repository = $repository;
		$this->languages  = $languages;
	}

	/**
	 * Filters by context.
	 *
	 * @since 3.8
	 *
	 * @param string $context The context to filter by.
	 * @return self For method chaining.
	 */
	public function by_context( string $context ): self {
		$this->context = $context;

		return $this;
	}

	/**
	 * Filters by fragment (searches in both source and translations).
	 *
	 * @since 3.8
	 *
	 * @param string $fragment The fragment to search for.
	 * @return self For method chaining.
	 */
	public function by_fragment( string $fragment ): self {
		$this->fragment = $fragment;

		return $this;
	}

	/**
	 * Sets the sorting field and order.
	 *
	 * @since 3.8
	 *
	 * @param string $field The field to sort by ('string', 'name', or 'context').
	 * @param string $order The sort order ('asc' or 'desc'). Default 'asc'.
	 * @return self For method chaining.
	 */
	public function order_by( string $field = 'name', string $order = 'asc' ): self {
		$allowed_fields = array( 'string', 'name', 'context' );

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			/* translators: %s: Field name. */
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: Field name. */
					esc_html__( 'Invalid field: %s', 'polylang' ),
					esc_html( $field )
				),
				'3.8'
			);

			return $this;
		}

		$this->order_by = $field;

		$order       = strtolower( $order );
		$this->order = in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'asc';

		return $this;
	}

	/**
	 * Executes the query and returns the filtered/sorted collection.
	 *
	 * @since 3.8
	 *
	 * @return Collection The resulting collection.
	 */
	public function get(): Collection {
		$collection     = $this->repository->find_all();
		$translatables = iterator_to_array( $collection );

		if ( $this->context ) {
			$translatables = $this->filter_by_context( $translatables );
		}

		if ( $this->fragment ) {
			$translatables = $this->filter_by_fragment( $translatables );
		}

		if ( 'none' !== $this->order_by ) {
			$translatables = $this->sort( $translatables );
		}

		$this->context  = null;
		$this->fragment = null;
		$this->order_by = 'name';
		$this->order    = 'asc';

		return new Collection( $translatables );
	}

	/**
	 * Filters translatables by context.
	 *
	 * @since 3.8
	 *
	 * @param Translatable[] $translatables The translatables to filter.
	 * @return Translatable[] The filtered translatables.
	 */
	private function filter_by_context( array $translatables ): array {
		return array_filter(
			$translatables,
			function ( Translatable $translatable ) {
				return $translatable->get_context() === $this->context;
			}
		);
	}

	/**
	 * Filters translatables by fragment in source, name, or translations.
	 *
	 * @since 3.8
	 *
	 * @param Translatable[] $translatables The translatables to filter.
	 * @return Translatable[] The filtered translatables.
	 */
	private function filter_by_fragment( array $translatables ): array {
		if ( ! $this->fragment ) {
			return $translatables;
		}

		$fragment = $this->fragment;
		return array_filter(
			$translatables,
			function ( Translatable $translatable ) use ( $fragment ) {
				if (
					false !== stripos( $translatable->get_source(), $fragment ) ||
					false !== stripos( $translatable->get_name(), $fragment )
				) {
					return true;
				}

				foreach ( $this->languages->get_list() as $language ) {
					if ( false !== stripos( $translatable->get_translation( $language ), $fragment ) ) {
						return true;
					}
				}

				return false;
			}
		);
	}

	/**
	 * Sorts the translatables by a field.
	 *
	 * @since 3.8
	 *
	 * @param Translatable[] $translatables The translatables to sort.
	 * @return Translatable[] The sorted translatables.
	 */
	private function sort( array $translatables ): array {
		$getter_map = array(
			'string'  => 'get_source',
			'name'    => 'get_name',
			'context' => 'get_context',
		);

		$getter = $getter_map[ $this->order_by ];
		$order  = $this->order;

		uasort(
			$translatables,
			static function ( Translatable $a, Translatable $b ) use ( $getter, $order ) {
				$result = strcmp( $a->$getter(), $b->$getter() );

				return 'desc' === $order ? -$result : $result;
			}
		);

		return $translatables;
	}
}
