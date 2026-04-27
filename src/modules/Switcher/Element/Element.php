<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

interface Element {
	public function accept( Visitor $visitor ): string;

	public function get_url(): string;

	public function to_array(): array;
}
