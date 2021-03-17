<?php


class Save_Translations_Test extends PLL_UnitTestCase {
	/**
	 * @var PLL_Translated_Post|WP_UnitTest_Factory|null
	 */
	private $translated_post;
	/**
	 * @var PLL_Admin_Filters_Post|WP_UnitTest_Factory|null
	 */
	private $filters_post;
	/**
	 * @var ReflectionMethod|WP_UnitTest_Factory|null
	 */
	private $inline_save_language;

	public static function wpSetUpBeforeClass( $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		self::create_language( 'en_GB' );
		self::create_language( 'fr_FR' );
		self::create_language( 'de_DE_formal' );
	}

	public function setUp() {
		parent::setUp();

		$options = PLL_Install::get_default_options();
		$model = new PLL_Admin_Model( $options );
		$this->translated_post = new PLL_Translated_Post( $model );

		$links_model = new PLL_Links_Default( $model );
		$polylang = new PLL_Admin( $links_model );
		$this->filters_post = new PLL_Admin_Filters_Post( $polylang );
		$this->inline_save_language = new ReflectionMethod( PLL_Admin_Filters_Post::class, 'inline_save_language' );
		$this->inline_save_language->setAccessible( true );

	}

	/**
	 * @dataProvider update_language_provider
	 * @param string $to Language slug.
	 *
	 * @throws ReflectionException
	 */
	public function test_inline_save_equivalent_to_update_translations( $to ) {
		$en1 = $this->factory()->attachment->create();
		self::$model->post->set_language( $en1, 'en' );
		$fr1 = $this->factory()->post->create();
		self::$model->post->set_language( $fr1, 'fr' );
		self::$model->post->save_translations( $en1, array( 'fr' => $fr1 ) );

		$this->translated_post->update_language( $en1, $to );

		$en2 = $this->factory()->attachment->create();
		self::$model->post->set_language( $en2, 'en' );
		$fr2 = $this->factory()->post->create();
		self::$model->post->set_language( $fr2, 'fr' );
		self::$model->post->save_translations( $en2, array( 'fr' => $fr2 ) );

		$this->inline_save_language->invokeArgs( $this->filters_post, array( $en2, $to ) );

		$updated_language_translations_group = array_keys( $this->translated_post->get_translations( $en1 ) );
		$inline_saved_language_translations_group = array_keys( $this->translated_post->get_translations( $en2 ) );
		$this->assertEquals( $updated_language_translations_group, $inline_saved_language_translations_group );
	}

	public function test_save_translations_from_filter_post_equivalent_to_update_translations() {
		$this->markTestIncomplete();
	}

	public function test_dave_translations_from_filter_post_equivalent_to_save_inline_post() {
		$this->markTestIncomplete();
	}

	public function update_language_provider() {
		return array(
			'Update to same language' => array(
				'to' => 'en',
			),
			'Update to language already in translations group' => array(
				'to' => 'fr',
			),
			'Update to language not in translations group' => array(
				'to' => 'de',
			),
		);
	}
}
