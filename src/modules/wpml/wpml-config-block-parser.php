<?php
/**
 * @package Polylang
 */

/**
 * Extracts the parsing rules from a `gutenberg-block` node (`wpml-config.xml`).
 *
 * @since 4.0
 *
 * @phpstan-import-type BlockName from PLL_WPML_Config
 * @phpstan-import-type NodeName from PLL_WPML_Config
 * @phpstan-import-type Rules from PLL_WPML_Config
 * @phpstan-import-type TradTypes from PLL_WPML_Config
 *
 * @phpstan-import-type BlockXPath from PLL_WPML_Config
 * @phpstan-import-type BlockKey from PLL_WPML_Config
 * @phpstan-import-type BlockEncoding from PLL_WPML_Config
 * @phpstan-import-type BlockIdsXPath from PLL_WPML_Config
 * @phpstan-import-type BlockIdsKey from PLL_WPML_Config
 * @phpstan-import-type ParsingRules from PLL_WPML_Config
 */
class PLL_WPML_Config_Block_Parser {
	/**
	 * @var SimpleXMLElement
	 */
	private SimpleXMLElement $block_node;

	/**
	 * @var string
	 *
	 * @phpstan-var BlockName
	 */
	private string $block_name;

	/**
	 * @var string[]
	 *
	 * @phpstan-var list<NodeName>
	 */
	private array $curpath = array();

	/**
	 * @var array
	 *
	 * @phpstan-var ParsingRules
	 */
	private array $data = array();

	/**
	 * @var bool
	 */
	private bool $translate_text = false;

	/**
	 * Constructor.
	 *
	 * @since 4.0
	 *
	 * @param SimpleXMLElement $block_node The XML node to parse. The node must correspond to a block.
	 */
	public function __construct( SimpleXMLElement $block_node ) {
		$this->block_node = $block_node;
	}

	/**
	 * Parses the block node and its children, and merges the result to the given rules.
	 *
	 * @since 4.0
	 *
	 * @param array[] $parsing_rules Parsing rules to merge the new rules to.
	 * @return array[]
	 *
	 * @phpstan-param ParsingRules $parsing_rules
	 * @phpstan-return ParsingRules
	 */
	public function merge_to( array $parsing_rules ): array {
		$this->data    = array();
		$this->curpath = array();
		$block_name    = $this->get_node_attribute( $this->block_node, 'type' );

		if ( empty( $block_name ) ) {
			return $parsing_rules;
		}

		// The `translate` attribute is at the block level.
		$this->translate_text = '1' === $this->get_node_attribute( $this->block_node, 'translate' );
		$this->block_name     = $block_name;

		foreach ( $this->block_node->children() as $child ) {
			switch ( $child->getName() ) {
				case 'xpath':
					$rule = trim( (string) $child );

					if ( empty( $rule ) ) {
						break;
					}

					$translation_type = $this->get_translation_type( $child );

					// Text node.
					if ( 'text' === $translation_type ) {
						$this->data[ PLL_WPML_Config::TEXT_IN_CONTENT ][ $this->block_name ][] = $rule;
						break;
					}

					// Post IDs, Term IDs.
					if ( ! empty( $translation_type ) ) {
						$this->data[ PLL_WPML_Config::IDS_IN_CONTENT ][ $this->block_name ][ $translation_type ][] = $rule;
						break;
					}
					break;

				case 'key':
					$this->recurse( $child );
					$this->maybe_add_encoding( $child );
					break;
			}
		}

		return $this->array_merge_recursive( $parsing_rules, $this->data );
	}

	/**
	 * Recursively parses the given node and its children.
	 *
	 * @since 4.0
	 *
	 * @param SimpleXMLElement $node A XML node.
	 * @return void
	 */
	private function recurse( SimpleXMLElement $node ): void {
		$name = $this->get_node_attribute( $node, 'name' );

		if ( '' === $name ) {
			// Mandatory attribute.
			return;
		}

		$children = $node->children();

		if ( $children->count() > 0 ) {
			// Recurse into the sub-nodes.
			$this->curpath[] = $name;

			foreach ( $children as $child ) {
				$this->recurse( $child );
			}

			array_pop( $this->curpath );
			return;
		}

		// Last node of this branch.
		$translation_type = $this->get_translation_type( $node );

		// Text node.
		if ( 'text' === $translation_type ) {
			$this->data[ PLL_WPML_Config::TEXT_IN_ATTRIBUTES ][ $this->block_name ] = $this->array_merge_recursive(
				$this->data[ PLL_WPML_Config::TEXT_IN_ATTRIBUTES ][ $this->block_name ] ?? array(),
				$this->build_path_array( $name )
			);
			return;
		}

		// Post IDs, Term IDs.
		if ( ! empty( $translation_type ) ) {
			$this->data[ PLL_WPML_Config::IDS_IN_ATTRIBUTES ][ $this->block_name ][ $translation_type ] = $this->array_merge_recursive(
				$this->data[ PLL_WPML_Config::IDS_IN_ATTRIBUTES ][ $this->block_name ][ $translation_type ] ?? array(),
				$this->build_path_array( $name )
			);
		}
	}

	/**
	 * Returns the value of an attribute.
	 *
	 * @since 4.0
	 *
	 * @param  SimpleXMLElement $node           A XML node.
	 * @param  string           $attribute_name Node of the attribute.
	 * @return string
	 *
	 * @phpstan-param $attribute_name non-empty-string
	 */
	private function get_node_attribute( SimpleXMLElement $node, $attribute_name ) {
		$attributes = $node->attributes();

		if ( empty( $attributes ) || ! isset( $attributes[ $attribute_name ] ) ) {
			return '';
		}

		return trim( (string) $attributes[ $attribute_name ] );
	}

	/**
	 * Returns the translation type of the given node.
	 *
	 * @since 4.0
	 *
	 * @param SimpleXMLElement $node A XML node.
	 * @return string
	 *
	 * @phpstan-return TradTypes|'text'|''
	 */
	private function get_translation_type( SimpleXMLElement $node ): string {
		switch ( $this->get_node_attribute( $node, 'type' ) ) {
			case 'taxonomy-ids':
				// Taxonomy IDs.
				$sub_type = $this->get_node_attribute( $node, 'sub-type' );
				return ! empty( $sub_type ) ? 'term' : '';

			case 'post-ids':
				// Post IDs.
				$sub_type = $this->get_node_attribute( $node, 'sub-type' );

				if ( in_array( $sub_type, array( 'attachment', 'wp_block' ), true ) ) {
					return $sub_type;
				}

				return ! empty( $sub_type ) ? 'post' : '';

			case '':
				// Text.
				if ( ! $this->translate_text ) {
					return '';
				}

				switch ( $node->getName() ) {
					case 'xpath':
						return 'text';

					case 'key':
						// The only supported `search-method` is `wildcards` (which is the default value).
						if ( in_array( $this->get_node_attribute( $node, 'search-method' ), array( '', 'wildcards' ), true ) ) {
							return 'text';
						}
				} // eo switch
		} // eo switch

		return '';
	}

	/**
	 * Builds the current path as an array.
	 *
	 * Given `$this->curpath` being `array( 'a', 'b' )` and the method receiving `'c'`, this will output:
	 * `array( 'a' => array( 'b' => array( 'c' => true ) ) )`.
	 *
	 * @since 4.0
	 *
	 * @param string $name Current node's name.
	 * @return array
	 *
	 * @phpstan-param NodeName $name
	 * @phpstan-return Rules
	 */
	private function build_path_array( string $name ): array {
		$temp = array( $name => true );

		foreach ( array_reverse( $this->curpath ) as $key ) {
			$temp = array( $key => $temp );
		}

		return $temp;
	}

	/**
	 * Maybe adds the encoding to the data.
	 *
	 * @since 4.0
	 *
	 * @param SimpleXMLElement $node A XML node.
	 * @return void
	 */
	private function maybe_add_encoding( SimpleXMLElement $node ): void {
		$name = $this->get_node_attribute( $node, 'name' );

		if ( '' === $name ) {
			return;
		}

		$encoding = $this->get_node_attribute( $node, 'encoding' );

		if ( 'json' !== $encoding ) {
			// Only `json` is supported.
			return;
		}

		// For WPML, `json` means `json,urlencode` (and is the only format supported in this context).
		$this->data[ PLL_WPML_Config::TEXT_ENCODING ][ $this->block_name ][ $name ] = 'json,urlencode';
	}

	/**
	 * Merges two arrays recursively.
	 * Unlike `array_merge_recursive()`, this method doesn't change the type of the values.
	 *
	 * @since 3.6
	 * @since 4.0 Moved from PLL_WPML_Config
	 *
	 * @param array $array1 Array to merge into.
	 * @param array $array2 Array to merge.
	 * @return array
	 */
	private function array_merge_recursive( array $array1, array $array2 ): array {
		foreach ( $array2 as $key => $value ) {
			if ( is_array( $value ) && isset( $array1[ $key ] ) && is_array( $array1[ $key ] ) ) {
				$array1[ $key ] = $this->array_merge_recursive( $array1[ $key ], $value );
			} else {
				$array1[ $key ] = $value;
			}
		}

		return $array1;
	}
}
