<?php

class PLL_UnitTest_Factory_For_Language extends WP_UnitTest_Factory_For_Thing {
	/**
	 * @var PLL_Admin_Model
	 */
	protected $pll_model;

	/**
	 * @var PLL_Language[]
	 */
	protected $default_languages = array(
		'en_US',
		'fr_FR',
		'de_DE_formal',
		'es_ES',
	);

	public function __construct( PLL_UnitTest_Factory $factory ) {
		$this->pll_model = $factory->pll_model;
	}

	/**
	 * Creates a language and returns its term ID.
	 *
	 * @param array $args                   The arguments for the object to create.
	 *                                      Default empty array.
	 * @param null  $generation_definitions Not used.
	 *
	 * @return int|WP_Error The object ID on success, WP_Error object on failure.
	 *
	 * @throws InvalidArgumentException Throw exception when badly used.
	 */
	public function create( $args = array(), $generation_definitions = null ) {
		if ( empty( $args['locale'] ) ) {
			throw new InvalidArgumentException( 'A locale is required to create a language.' );
		}

		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$values    = $languages[ $args['locale'] ];

		$values['slug']       = $values['code'];
		$values['rtl']        = (int) ( 'rtl' === $values['dir'] );
		$values['term_group'] = 0;

		$args = array_merge( $values, $args );

		$result = $this->create_object( $args );

		$this->pll_model->clean_languages_cache();

		return $result;
	}

	/**
	 * Creates multiple objects.
	 *
	 * @param int   $count                  Amount of objects to create.
	 * @param array $args                   Not used.
	 * @param null  $generation_definitions Not Used.
	 *
	 * @return array
	 *
	 * @throws InvalidArgumentException Throw exception when badly used.
	 */
	public function create_many( $count, $args = array(), $generation_definitions = null ) {
		if ( $count > count( $this->default_languages ) ) {
			throw new InvalidArgumentException( "Don't be so greedy." );
		}

		$results = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$results[] = $this->create( array( 'locale' => $this->default_languages[ $i ] ), $generation_definitions );
		}

		return $results;
	}

	/**
	 * Creates a language object.
	 *
	 * @param array $args Language data.
	 * @return int|WP_Error Language term ID on success, `WP_Error` on failure.
	 */
	public function create_object( $args ) {
		$errors = $this->pll_model->add_language( $args );

		if ( is_wp_error( $errors ) ) {
			return $errors;
		}

		$language = $this->pll_model->get_language( $args['slug'] );

		if ( ! $language instanceof PLL_Language ) {
			return new WP_Error( 'Could not get the created language.' );
		}

		return $language->term_id;
	}

	/**
	 * Returns a language object for a givern term ID.
	 *
	 * @param int $object_id Term ID.
	 * @return PLL_Language|WP_Error
	 */
	public function get_object_by_id( $object_id ) {
		$language = $this->pll_model->get_language( $object_id );

		if ( $language instanceof PLL_Language ) {
			return $language;
		}

		return new WP_Error( 'Could not find a language for the given term ID.' );
	}

	/**
	 * Does nothing because it's only used in `WP_UnitTest_Factory_For_Thing::create()` and this method has been overridden.
	 *
	 * @param int   $object_id
	 * @param array $fields
	 * @return void
	 */
	public function update_object( $object_id, $fields ) {} // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
}
