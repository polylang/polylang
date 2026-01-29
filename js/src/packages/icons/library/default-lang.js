/**
 * WordPress dependencies
 */
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { star } from '..';

const DefaultLangIcon = () => (
	<>
		<Icon icon={ star } className="pll-default-lang-icon" />
		<span className="screen-reader-text">
			{ __( 'Default language.', 'polylang-pro' ) }
		</span>
	</>
);

export default DefaultLangIcon;
