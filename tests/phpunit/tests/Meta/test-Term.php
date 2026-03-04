<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta;

use PLL_UnitTest_Factory;

/**
 * @group meta
 */
class Term extends TestCase {
	use Add;
	use Copy;
	use Delete;
	use Update;

	/**
	 * @var string
	 */
	protected static $type = 'term';

	/**
	 * @var int[]
	 */
	protected static $objects;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		self::$objects = $factory->term->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);
	}

	public function set_up() {
		parent::set_up();

		add_filter(
			'pll_copy_term_metas',
			static function ( $metas ) {
				$metas[] = 'the_key';
				return $metas;
			}
		);
	}

	protected function get_metas_object() {
		return $this->pll_env->sync->term_metas;
	}
}
