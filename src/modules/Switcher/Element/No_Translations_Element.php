<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

class No_Translations_Element implements Element {
	/**
	 * @var array
	 */
	private $row = array();

	public function __construct( array $row = array() ) {}

	public function accept( Visitor $visitor ): string {}

	public function get_url(): string {}

	public function to_array(): array {}
}
