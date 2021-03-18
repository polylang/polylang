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
	 * @param $original_group
	 * @param $to
	 * @param $post_type
	 * @param $expected_new_group
	 * @param array              $expected_former_group
	 *
	 * @throws Exception
	 */
	public function test_update_language( $original_group, $to, $expected_new_group, $expected_former_group = array() ) {
		wp_set_current_user( 1 ); // Needs edit_post capability.

		list( $en1, $translations1 ) = $this->create_post_and_translations( $original_group );

		$this->translated_post->update_language( $en1, $to, 'post' );

		$updated_language_translations_group = array_keys( $this->translated_post->get_translations( $en1 ) );
		$updated_language_old_translations_group = array_keys( $this->translated_post->get_translations( array_values( $translations1 )[0] ) );
		$this->assertEqualSets( $expected_new_group, $updated_language_translations_group );
		if ( ! empty( $expected_former_group ) ) {
			$this->assertEqualSets( $expected_former_group, $updated_language_old_translations_group );
		}
	}

	/**
	 * @dataProvider update_language_provider
	 * @param string $to Language slug.
	 *
	 * @throws ReflectionException
	 */
	public function test_inline_save_language( $original_group, $to, $expected_new_group, $expected_former_group = array() ) {
		wp_set_current_user( 1 ); // Needs edit_post capability.

		list( $en2, $translations2 ) = $this->create_post_and_translations( $original_group );

		$this->inline_save_language->invokeArgs( $this->filters_post, array( $en2, self::$model->get_language( $to ) ) );

		$inline_saved_language_translations_group = array_keys( $this->translated_post->get_translations( $en2 ) );
		$inline_saved_language_old_translations_group = array_keys( $this->translated_post->get_translations( array_values( $translations2 )[0] ) );
		$this->assertEqualSets( $expected_new_group, $inline_saved_language_translations_group );
		if ( ! empty( $expected_former_group ) ) {
			$this->assertEqualSets( $expected_former_group, $inline_saved_language_old_translations_group );
		}
	}

	public function update_language_provider() {
		return array(
			'Update to same language' => array(
				'original_group' => array( 'en', 'fr' ),
				'to' => 'en',
				'expected_new_group' => array( 'en', 'fr' ),
			),
			'Update to language not in translations group' => array(
				'original_group' => array( 'en', 'fr' ),
				'to' => 'de',
				'expected_new_group' => array( 'fr', 'de' ),
			),
			'Update to language already in translations group' => array(
				'original_group' => array( 'en', 'fr', 'de' ),
				'to' => 'fr',
				'expected_new_group' => array( 'fr' ),
				'expected_former_group' => array( 'fr', 'de' ),
			),
		);
	}

	/**
	 * @param string[] $languages
	 *
	 * @return array
	 */
	public function create_post_and_translations( $languages ) {
		$translations = array();
		foreach ( $languages as $language ) {
			$new_post = $this->factory()->attachment->create();
			self::$model->post->set_language( $new_post, $language );
			$translations[ $language ] = $new_post;
		}
		$post_id = array_shift( $translations );
		self::$model->post->save_translations( $post_id, $translations );

		return array( $post_id, $translations );
	}
}
