/**
 * Register language switcher block.
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { translation as translationIcon } from '@wpsyntex/polylang-react-library';
import { Edit } from './edit';
import metadata from '../../../../src/modules/Blocks/Language_Switcher/Standard/block.json';

registerBlockType( metadata.name, {
	icon: translationIcon,
	edit: Edit,
} );
