<?php

/**
 * @group links
 * @group domain
 * @group directory
 */
class Links_Subomain_Sub_Dir_Test extends PLL_Domain_UnitTestCase {
	use PLL_Links_Trait;

	protected $is_directory = true;

	public function set_up() {
		parent::set_up();

		$this->hosts = array(
			'en' => 'http://example.org',
			'fr' => 'http://fr.example.org',
			'de' => 'http://de.example.org',
		);

		self::$model->options['default_lang'] = 'en';
		self::$model->options['hide_default'] = 1;
		self::$model->options['force_lang']   = 2;
		self::$model->options['domains']      = $this->hosts;

		$this->init_links_model();
	}
}
