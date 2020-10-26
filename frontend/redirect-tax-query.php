<?php


class PLL_Redirect_Tax_Query {

	/**
	 * @var WP_Tax_Query
	 */
	private $query;

	/**
	 * @var PLL_Model
	 */
	private $model;

	/**
	 * PLL_Redirect_Tax_Query constructor.
	 *
	 * @param WP_Query $wp_query
	 */
	public function __construct( $wp_query, $model ) {
		$this->query = $wp_query->tax_query;
		$this->model = $model;
	}

	/**
	 * Find the taxonomy being queried.
	 *
	 * @return string A taxonomy slug
	 */
	public function get_queried_taxonomy() {
		$queried_terms = $this->query->queried_terms;
		unset( $queried_terms['language'] );

		return key( $queried_terms );
	}

	/**
	 * @return mixed
	 */
	public function is_translated_taxonomy() {
		return $this->model->is_translated_taxonomy( $this->get_queried_taxonomy() );
	}

	/**
	 * Returns the term_id of the requested term.
	 *
	 * @return int
	 * @since 2.8.4
	 */
	public function get_queried_term_id() {
		$queried_terms = $this->query->queried_terms;
		$taxonomy      = $this->get_queried_taxonomy();

		$field = $queried_terms[ $taxonomy ]['field'];
		$term  = reset( $queried_terms[ $taxonomy ]['terms'] );

		// We can get a term_id when requesting a plain permalink, eg /?cat=1.
		if ( 'term_id' === $field ) {
			return $term;
		}

		// We get a slug when requesting a pretty permalink with the wrong language.
		$args  = array(
			'lang'       => '',
			'taxonomy'   => $taxonomy,
			$field       => $term,
			'hide_empty' => false,
			'fields'     => 'ids',
		);
		$terms = get_terms( $args );

		return reset( $terms );
	}

	/**
	 * @param $term_id
	 *
	 * @return mixed
	 */
	public function get_queried_term_language( $term_id ) {
		return $this->model->term->get_language( $term_id );
	}

	/**
	 * @param $term_id
	 *
	 * @return array|false|int|object|string|WP_Error|WP_Term|null
	 */
	public function get_queried_term_url( $term_id ) {
		$redirect_url = get_term_link( $term_id );

		return $redirect_url;
	}


}
