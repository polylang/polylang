<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

class Switcher {
	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Visitor|null
	 */
	private $visitor;

	/**
	 * @var Element[]
	 */
	private $elements = array();

	public function __construct( Settings $settings ) {}

	public function print(): string {}

	public function to_array(): array {}
}
