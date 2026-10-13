<?php
/**
 * @package Polylang
 */

/**
 * Extracts the parsing rules from a `gutenberg-block` node (`wpml-config.xml`).
 *
 * @since 3.9
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
	private bool $translate = false;

	/**
	 * @var bool
	 */
	private bool $is_recursing = false;

	/**
	 * Constructor.
	 *
	 * @since 3.9
	 *
	 * @param SimpleXMLElement $block_node The XML node to parse. The node must correspond to a block.
	 */
	public function __construct( SimpleXMLElement $block_node ) {
		$this->block_node = $block_node;
	}

	/**
	 * Parses the block node and its children, and merges the result to the given rules.
	 *
	 * @since 3.9
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
		$this->translate  = '1' === $this->get_node_attribute( $this->block_node, 'translate' );
		$this->block_name = $block_name;

		foreach ( $this->block_node->children() as $child ) {
			switch ( $child->getName() ) {
				case 'xpath':
					$rule = trim( (string) $child );

					if ( empty( $rule ) ) {
						break;
					}

					// Post IDs, Term IDs.
					$translation_type = $this->get_ids_translation_type( $child );

					if ( ! empty( $translation_type ) ) {
						$this->data['ids_in_content'][ $this->block_name ][ $translation_type ][] = $rule;
						// No need to go further, since a `xpath` tag cannot have children, and we cannot have
						// a tag that is a "IDs" one and a text one at the same time.
						break;
					}

					// Text node.
					if ( $this->is_supported_text_node( $child ) ) {
						$this->data['xpath'][ $this->block_name ][] = $rule;
					}
					break;

				case 'key':
					$this->recurse( $child );
					break;
			}
		}

		return $this->array_merge_recursive( $parsing_rules, $this->data );
	}

	/**
	 * Recursively parses the given node and its children.
	 *
	 * @since 3.9
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
			$this->curpath[]    = $name;
			$this->is_recursing = true;

			foreach ( $children as $child ) {
				$this->recurse( $child );
			}

			$this->is_recursing = false;
			array_pop( $this->curpath );

			$this->maybe_add_encoding( $node, $name );
			return;
		}

		// Last node of this branch.
		$translation_type = $this->get_ids_translation_type( $node );

		if ( ! empty( $translation_type ) ) {
			// Post IDs, Term IDs.
			$this->data['ids_in_attributes'][ $this->block_name ][ $translation_type ] = $this->array_merge_recursive(
				$this->data['ids_in_attributes'][ $this->block_name ][ $translation_type ] ?? array(),
				$this->build_path_array( $name )
			);
			return;
		}

		if ( $this->is_supported_text_node( $node ) ) {
			// Text node.
			$this->data['key'][ $this->block_name ] = $this->array_merge_recursive(
				$this->data['key'][ $this->block_name ] ?? array(),
				$this->build_path_array( $name )
			);

			$this->maybe_add_encoding( $node, $name );
		}
	}

	/**
	 * Returns the value of an attribute.
	 *
	 * @since 3.9
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
	 * Tells if the given type is a "IDs" one.
	 * These types are found on nodes: `type="post-ids"`.
	 *
	 * @since 3.9
	 *
	 * @param string $type The type.
	 * @return bool
	 *
	 * @phpstan-assert-if-true 'post-ids'|'taxonomy-ids' $type
	 */
	private function is_ids_type( string $type ): bool {
		return in_array( $type, array( 'post-ids', 'taxonomy-ids' ), true );
	}

	/**
	 * Returns the translation type of the given node, for IDs translation.
	 * All post types are inferred to `post` except for `attachment` and `wp_block`.
	 * If the post type is not specified or empty, the type is `post_mixed`.
	 *
	 * @since 3.9
	 *
	 * @param SimpleXMLElement $node A XML node.
	 * @return string
	 *
	 * @phpstan-return TradTypes|''
	 */
	private function get_ids_translation_type( SimpleXMLElement $node ): string {
		$type = $this->get_node_attribute( $node, 'type' );

		if ( ! $this->is_ids_type( $type ) ) {
			return '';
		}

		if ( 'taxonomy-ids' === $type ) {
			return 'term';
		}

		$sub_type = $this->get_node_attribute( $node, 'sub-type' );

		if ( in_array( $sub_type, array( 'attachment', 'wp_block' ), true ) ) {
			return $sub_type;
		}

		return ! empty( $sub_type ) ? 'post' : 'post_mixed';
	}

	/**
	 * Tells if the given text node is supported.
	 *
	 * @since 3.9
	 *
	 * @param SimpleXMLElement $node A XML node.
	 * @return bool
	 */
	private function is_supported_text_node( SimpleXMLElement $node ): bool {
		if ( ! $this->translate || $this->get_node_attribute( $node, 'type' ) !== '' ) {
			return false;
		}

		if ( $node->getName() === 'key' ) {
			// The only supported `search-method` is `wildcards` (which is the default value).
			return in_array( $this->get_node_attribute( $node, 'search-method' ), array( '', 'wildcards' ), true );
		}

		return true;
	}

	/**
	 * Builds the current path as an array.
	 *
	 * Given `$this->curpath` being `array( 'a', 'b' )` and the method receiving `'c'`, this will output:
	 * `array( 'a' => array( 'b' => array( 'c' => true ) ) )`.
	 *
	 * @since 3.9
	 *
	 * @param string $name Current node's name.
	 * @return array
	 *
	 * @phpstan-param NodeName $name
	 * @phpstan-return Rules
	 */
	private function build_path_array( string $name ): array {
		$this->curpath[] = $name;
		$temp            = true;

		foreach ( array_reverse( $this->curpath ) as $key ) {
			$temp = array( $key => $temp );
		}
		array_pop( $this->curpath );

		return $temp;
	}

	/**
	 * Maybe adds the encoding to the data.
	 *
	 * @since 3.9
	 *
	 * @param SimpleXMLElement $node A XML node.
	 * @param string           $name The node's name.
	 * @return void
	 *
	 * @phpstan-param NodeName $name
	 */
	private function maybe_add_encoding( SimpleXMLElement $node, string $name ): void {
		if ( $this->is_recursing ) {
			// The encoding is present only on the first level.
			return;
		}

		$encoding = $this->get_node_attribute( $node, 'encoding' );

		if ( 'json' !== $encoding ) {
			// Only `json` is supported.
			return;
		}

		// For WPML, `json` means `json,urlencode` (and is the only format supported in this context).
		$this->data['encoding'][ $this->block_name ][ $name ] = 'json,urlencode';
	}

	/**
	 * Merges two arrays recursively.
	 * Unlike `array_merge_recursive()`, this method doesn't change the type of the values.
	 *
	 * @since 3.6
	 * @since 3.9 Moved from PLL_WPML_Config
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
