/* eslint-disable import/no-extraneous-dependencies */
/**
 * External dependencies.
 */
import { isEmpty, isNil } from 'lodash';

/**
 * Internal dependencies.
 */
import { translation } from '@wpsyntex/polylang-pro-icons';

/**
 * Displays a flag icon for a given language.
 *
 * @since 3.1
 * @since 3.2 Now its own component.
 *
 * @param {Object} props          LanguageFlag props.
 * @param {Object} props.language Language object for the flag.
 *
 * @return {React.Component} Flag component.
 */
function LanguageFlag( { language } ) {
	if ( ! isNil( language ) ) {
		return ! isEmpty( language.flag_url ) ? (
			<span className="pll-select-flag">
				<img
					src={ language.flag_url }
					alt={ language.name }
					title={ language.name }
					className="flag"
				/>
			</span>
		) : (
			<abbr>
				{ language.slug }
				<span className="screen-reader-text">{ language.name }</span>
			</abbr>
		);
	}

	return <span className="pll-translation-icon">{ translation }</span>;
}

export default LanguageFlag;
