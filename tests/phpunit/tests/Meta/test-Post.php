<?php

namespace WP_Syntex\Polylang_Pro\Tests\Integration\modules\Meta;

use PLL_UnitTest_Factory;

/**
 * @group meta
 */
class Post extends TestCase {
	use Add;
	use Copy;
	use Delete;
	use Update;

	/**
	 * @var string
	 */
	protected static $type = 'post';

	/**
	 * @var int[]
	 */
	protected static $objects;

	public static function pllSetUpBeforeClass( PLL_UnitTest_Factory $factory ) {
		parent::pllSetUpBeforeClass( $factory );

		$factory->language->create_many( 2 );

		self::$objects = $factory->post->create_translated(
			array( 'lang' => 'en' ),
			array( 'lang' => 'fr' )
		);
	}

	public function set_up() {
		parent::set_up();

		add_filter(
			'pll_copy_post_metas',
			static function ( $metas ) {
				$metas[] = 'the_key';
				return $metas;
			}
		);
	}

	protected function get_metas_object() {
		return $this->pll_env->sync->post_metas;
	}
}
