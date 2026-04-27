<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Switcher;

interface Visitor {
	/**
	 * @param Element[] $elements
	 */
	public function walk( array $elements ): string;

	public function visit_current_language( Current_Language_Element $element ): string;

	public function visit_without_translations( No_Translations_Element $element ): string;

	public function visit_without_content( No_Content_Element $element ): string;
}
