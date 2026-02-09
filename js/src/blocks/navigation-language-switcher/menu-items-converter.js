/**
 * Menu items converter
 */

/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from '../../../../src/modules/Blocks/Language_Switcher/Navigation/block.json';

/**
 * Apply a callback function on each block of the blocks list.
 *
 * @param {Array}  blocks        The list of blocks to process.
 * @param {Array}  menuItems     The initial menu items from where the blocks are converted to.
 * @param {Object} blocksMapping The mapping between the menu items and their corresponding blocks.
 * @param {mapper} mapper        A callback to change the converted block by another one if necessary
 * @return {Array} Array of blocks updated.
 */
function mapBlockTree( blocks, menuItems, blocksMapping, mapper ) {
	/**
	 * A function to apply to each block to convert it if necessary by applying the `mapper` filter.
	 *
	 * @param {Object} block The block to replace or not.
	 * @return {Object} The new block potentially replaced by the `mapper`.
	 */
	const convertBlock = ( block ) => ( {
		...mapper( block, menuItems, blocksMapping ),
		innerBlocks: mapBlockTree(
			block.innerBlocks,
			menuItems,
			blocksMapping,
			mapper
		),
	} );

	return blocks.map( convertBlock );
}

/**
 * A filter to detect the `core/navigation-link` block not correctly converted from the language switcher menu item
 * and convert it to its corresponding `polylang/navigation-language-switcher` block.
 *
 * @callback mapper
 * @param {Object} block         The block converted from the menu item.
 * @param {Array}  menuItems     The initial menu items from where the blocks are converted to.
 * @param {Object} blocksMapping The mapping between the menu items and their corresponding blocks.
 * @return {Object} The block correctly converted.
 */
const blocksFilter = ( block, menuItems, blocksMapping ) => {
	if (
		block.name === 'core/navigation-link' &&
		block.attributes?.url === '#pll_switcher'
	) {
		const menuItem = menuItems.find(
			( item ) => item.url === '#pll_switcher'
		); // Get the corresponding menu item.
		const attributes = menuItem.meta._pll_menu_item; // Get its options.
		const newBlock = createBlock( metadata.name, attributes );
		blocksMapping[ menuItem.id ] = newBlock.clientId; // Update the blocks mapping.
		return newBlock;
	}
	return block;
};

/**
 * A filter callback hooked to `blocks.navigation.__unstableMenuItemsToBlocks`.
 *
 * @param {Array} blocks    The list of blocks to process.
 * @param {Array} menuItems The initial menu items from where the blocks are converted to.
 * @return {Array} Array of blocks updated.
 */
export const menuItemsToBlocksFilter = ( blocks, menuItems ) => ( {
	...blocks,
	innerBlocks: mapBlockTree(
		blocks.innerBlocks,
		menuItems,
		blocks.mapping,
		blocksFilter
	),
} );
