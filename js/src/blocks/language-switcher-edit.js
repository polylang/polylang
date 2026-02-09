/**
 * Language switcher block edit.
 */

/**
 * External dependencies
 */
import { assign } from 'lodash';

/**
 * WordPress dependencies
 */
import { ToggleControl } from '@wordpress/components';

const i18nAttributeStrings = pll_block_editor_blocks_settings;

export function createLanguageSwitcherEdit( props ) {
	const createToggleAttribute = function ( propName ) {
		return () => {
			const value = props.attributes[ propName ];
			const { setAttributes } = props;
			let updatedAttributes = { [ propName ]: ! value };
			let forcedAttributeName;
			let forcedAttributeUnchecked;

			// Both show_names and show_flags attributes can't be unchecked together.
			switch ( propName ) {
				case 'show_names':
					forcedAttributeName = 'show_flags';
					forcedAttributeUnchecked =
						! props.attributes[ forcedAttributeName ];
					break;
				case 'show_flags':
					forcedAttributeName = 'show_names';
					forcedAttributeUnchecked =
						! props.attributes[ forcedAttributeName ];
					break;
			}

			if ( 'show_names' === propName || 'show_flags' === propName ) {
				if ( value && forcedAttributeUnchecked ) {
					updatedAttributes = assign( updatedAttributes, {
						[ forcedAttributeName ]: forcedAttributeUnchecked,
					} );
				}
			}
			setAttributes( updatedAttributes );
		};
	};
	const toggleDropdown = createToggleAttribute( 'dropdown' );
	const toggleShowNames = createToggleAttribute( 'show_names' );
	const toggleShowFlags = createToggleAttribute( 'show_flags' );
	const toggleForceHome = createToggleAttribute( 'force_home' );
	const toggleHideCurrent = createToggleAttribute( 'hide_current' );
	const toggleHideIfNoTranslation = createToggleAttribute(
		'hide_if_no_translation'
	);
	const {
		dropdown,
		show_names,
		show_flags,
		force_home,
		hide_current,
		hide_if_no_translation,
	} = props.attributes;

	function ToggleControlDropdown() {
		return (
			<ToggleControl
				label={ i18nAttributeStrings.dropdown }
				checked={ dropdown }
				onChange={ toggleDropdown }
			/>
		);
	}

	function ToggleControlShowNames() {
		return (
			<ToggleControl
				label={ i18nAttributeStrings.show_names }
				checked={ show_names } // eslint-disable-line camelcase
				onChange={ toggleShowNames }
			/>
		);
	}

	function ToggleControlShowFlags() {
		return (
			<ToggleControl
				label={ i18nAttributeStrings.show_flags }
				checked={ show_flags } // eslint-disable-line camelcase
				onChange={ toggleShowFlags }
			/>
		);
	}

	function ToggleControlForceHome() {
		return (
			<ToggleControl
				label={ i18nAttributeStrings.force_home }
				checked={ force_home } // eslint-disable-line camelcase
				onChange={ toggleForceHome }
			/>
		);
	}

	function ToggleControlHideCurrent() {
		return (
			<ToggleControl
				label={ i18nAttributeStrings.hide_current }
				checked={ hide_current } // eslint-disable-line camelcase
				onChange={ toggleHideCurrent }
			/>
		);
	}

	function ToggleControlHideIfNoTranslations() {
		return (
			<ToggleControl
				label={ i18nAttributeStrings.hide_if_no_translation }
				checked={ hide_if_no_translation } // eslint-disable-line camelcase
				onChange={ toggleHideIfNoTranslation }
			/>
		);
	}

	return {
		ToggleControlDropdown,
		ToggleControlShowNames,
		ToggleControlShowFlags,
		ToggleControlForceHome,
		ToggleControlHideCurrent,
		ToggleControlHideIfNoTranslations,
	};
}
