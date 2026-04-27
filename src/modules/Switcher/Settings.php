<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

class Settings {
	/**
	 * @var array
	 */
	private $options = array();

	public function __construct( array $options = array() ) {}

	public function get_visitor(): Visitor {}
}
