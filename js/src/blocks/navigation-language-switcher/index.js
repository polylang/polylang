/**
 * Register navigation language switcher block.
 */

/**
 * WordPress Dependencies
 */
import { registerBlockType, createBlock } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { translation as translationIcon } from '@wpsyntex/polylang-pro-icons';
import { Edit } from './edit';
import { menuItemsToBlocksFilter } from './menu-items-converter';
import metadata from '../../../../modules/blocks/language-switcher/navigation/block.json';

registerBlockType( metadata.name, {
	icon: translationIcon,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/navigation-link' ],
				transform: () => createBlock( metadata.name ),
			},
		],
	},
	edit: Edit,
} );

/**
 * Hooks to the classic menu conversion to core/navigation block to be able to convert
 * the language switcher menu item to its corresponding block.
 */
addFilter(
	'blocks.navigation.__unstableMenuItemsToBlocks',
	'polylang/include-language-switcher',
	menuItemsToBlocksFilter
);
