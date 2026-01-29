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
import { translation as translationIcon } from '@wpsyntex/polylang-pro-icons';
import { Edit } from './edit';
import metadata from '../../../../modules/blocks/language-switcher/standard/block.json';

registerBlockType( metadata.name, {
	icon: translationIcon,
	edit: Edit,
} );
