/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RadioControl,
	RangeControl,
	ToolbarGroup,
	ToolbarButton,
	ToolbarDropdownMenu,
	// ToggleGroupControl is the recommended replacement for deprecated ButtonGroup.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const MARGIN_RIGHT_UNITS = [ 'px', 'em', 'rem', 'vh', 'vw' ];

const MARGIN_RIGHT_UNIT_LABELS = {
	px: __( 'px', 'polylang' ),
	em: __( 'em', 'polylang' ),
	rem: __( 'rem', 'polylang' ),
	vh: __( 'vh', 'polylang' ),
	vw: __( 'vw', 'polylang' ),
};

/**
 * Switcher controls component for toolbar and inspector controls.
 *
 * @param {Object}   props               The component props.
 * @param {Object}   props.attributes    The block attributes.
 * @param {Function} props.setAttributes The function to set the block attributes.
 * @return {React.ReactNode} The switcher controls component.
 */
export const SwitcherControls = ( { attributes, setAttributes } ) => {
	const [ marginRightUnit, setMarginRightUnit ] = useState( 'px' );
	const [ marginRightValue, setMarginRightValue ] = useState( 0 );

	const {
		layout,
		show_labels,
		show_flags,
		force_home,
		hide_current,
		flag_aspect_ratio,
		flag_border_radius,
		flag_width,
		flag_margin_right,
		hide_if_no_translation,
	} = attributes;

	const { createWarningNotice } = useDispatch( noticesStore );

	const updateMarginRightUnit = ( unit ) => {
		setMarginRightUnit( unit );
		setAttributes( {
			flag_margin_right: getMarginRightValueFromUnit(
				flag_margin_right,
				unit
			),
		} );
	};

	const updateMarginRightValue = ( value ) => {
		setMarginRightValue( value );
		setAttributes( {
			flag_margin_right: getMarginRightValueFromValue(
				flag_margin_right,
				value
			),
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display', 'polylang' ) }>
					<SelectControl
						label={ __( 'Layout', 'polylang' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Horizontal', 'polylang' ),
								value: 'horizontal',
							},
							{
								label: __( 'Vertical', 'polylang' ),
								value: 'vertical',
							},
							{
								label: __( 'Dropdown', 'polylang' ),
								value: 'dropdown',
							},
							{
								label: __( 'Select', 'polylang' ),
								value: 'select',
							},
						] }
						onChange={ ( value ) => {
							setAttributes( { layout: value } );

							// Hide flags if the layout is select.
							if ( 'select' === value ) {
								setAttributes( { show_flags: false } );

								// Forcing labels to show names if there are no labels.
								if ( '' === show_labels ) {
									setAttributes( { show_labels: 'names' } );
								}
							}
						} }
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Labels', 'polylang' ) }
						value={ show_labels }
						options={ [
							{
								label: __( 'Names', 'polylang' ),
								value: 'names',
							},
							{
								label: __( 'Codes', 'polylang' ),
								value: 'codes',
							},
							{ label: __( 'None', 'polylang' ), value: '' },
						] }
						onChange={ ( value ) => {
							if ( '' === value ) {
								if ( ! show_flags ) {
									createWarningNotice(
										__(
											'Labels cannot be hidden if flags are not shown.',
											'polylang'
										)
									);
									return;
								}

								// Reset margin right unit and value if labels are hidden.
								updateMarginRightUnit( 'px' );
								updateMarginRightValue( 0 );
							}

							setAttributes( { show_labels: value } );
						} }
						__next40pxDefaultSize
					/>
					<ToggleControl
						label={ __( 'Show flags', 'polylang' ) }
						checked={ show_flags }
						onChange={ ( value ) => {
							if ( '' === show_labels && ! value ) {
								createWarningNotice(
									__(
										'Flags cannot be hidden if labels are not displayed.',
										'polylang'
									)
								);

								return;
							}

							if ( 'select' === layout && value ) {
								createWarningNotice(
									__(
										'Flags cannot be shown for the select layout.',
										'polylang'
									)
								);

								return;
							}

							setAttributes( { show_flags: value } );
						} }
					/>
					{ show_flags && (
						<PanelBody title={ __( 'Flags Settings', 'polylang' ) }>
							<RadioControl
								label={ __( 'Aspect', 'polylang' ) }
								selected={ flag_aspect_ratio }
								options={ [
									{
										label: __(
											'Landscape (3:2)',
											'polylang'
										),
										value: '3:2',
									},
									{
										label: __( 'Square (1:1)', 'polylang' ),
										value: '1:1',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( {
										flag_aspect_ratio: value,
									} )
								}
							/>
							<RangeControl
								label={ __( 'Border radius', 'polylang' ) }
								initialPosition={ 0 }
								value={ flag_border_radius }
								min={ 0 }
								max={ 100 }
								step={ 1 }
								allowReset={ true }
								resetFallbackValue={ 0 }
								onChange={ ( value ) =>
									setAttributes( {
										flag_border_radius: value,
									} )
								}
								__next40pxDefaultSize
							/>
							<RangeControl
								label={ __( 'Size', 'polylang' ) }
								initialPosition={ 18 }
								value={ flag_width }
								min={ 1 }
								max={ 1000 }
								step={ 1 }
								allowReset={ true }
								resetFallbackValue={ 18 }
								onChange={ ( value ) =>
									setAttributes( {
										flag_width: value,
									} )
								}
								__next40pxDefaultSize
							/>
							{ '' !== show_labels && (
								<>
									<ToggleGroupControl
										__next40pxDefaultSize
										label={ __(
											'Margin right unit',
											'polylang'
										) }
										value={ marginRightUnit }
										onChange={ updateMarginRightUnit }
										isBlock
									>
										{ MARGIN_RIGHT_UNITS.map( ( unit ) => (
											<ToggleGroupControlOption
												key={ unit }
												value={ unit }
												label={
													MARGIN_RIGHT_UNIT_LABELS[
														unit
													]
												}
											/>
										) ) }
									</ToggleGroupControl>
									<RangeControl
										label={ __(
											'Margin right value',
											'polylang'
										) }
										initialPosition={ 0 }
										value={ marginRightValue }
										min={ 0 }
										max={ 100 }
										step={ 1 }
										onChange={ ( value ) => {
											updateMarginRightValue( value );
										} }
										__next40pxDefaultSize
									/>
								</>
							) }
						</PanelBody>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Behavior', 'polylang' ) }>
					<ToggleControl
						label={ __( 'Force home', 'polylang' ) }
						checked={ force_home }
						onChange={ ( value ) =>
							setAttributes( { force_home: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Hide current', 'polylang' ) }
						checked={ hide_current }
						onChange={ ( value ) =>
							setAttributes( { hide_current: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Hide if no translation', 'polylang' ) }
						checked={ hide_if_no_translation }
						onChange={ ( value ) =>
							setAttributes( { hide_if_no_translation: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						label={ __( 'Layout', 'polylang' ) }
						controls={ [
							{
								title: __( 'Horizontal', 'polylang' ),
								onClick: () =>
									setAttributes( { layout: 'horizontal' } ),
							},
							{
								title: __( 'Vertical', 'polylang' ),
								onClick: () =>
									setAttributes( { layout: 'vertical' } ),
							},
							{
								title: __( 'Dropdown', 'polylang' ),
								onClick: () =>
									setAttributes( { layout: 'dropdown' } ),
							},
						] }
					/>
					<ToolbarButton
						icon={
							<span className="dashicons dashicons-flag"></span>
						}
						label={
							show_flags
								? __( 'Hide flags', 'polylang' )
								: __( 'Show flags', 'polylang' )
						}
						onClick={ () =>
							setAttributes( { show_flags: ! show_flags } )
						}
					/>
					<ToolbarDropdownMenu
						icon={
							<span className="dashicons dashicons-editor-textcolor"></span>
						}
						label={ __( 'Labels', 'polylang' ) }
						controls={ [
							{
								title: __( 'Names', 'polylang' ),
								onClick: () =>
									setAttributes( { show_labels: 'names' } ),
							},
							{
								title: __( 'Codes', 'polylang' ),
								onClick: () =>
									setAttributes( { show_labels: 'codes' } ),
							},
							{
								title: __( 'None', 'polylang' ),
								onClick: () =>
									setAttributes( { show_labels: '' } ),
							},
						] }
					/>
				</ToolbarGroup>
			</BlockControls>
		</>
	);
};

/**
 * Gets the margin right value from the unit.
 *
 * @param {string} currentValue The current margin right value.
 * @param {string} nextUnit     The next unit.
 * @return {string} The margin right value.
 */
const getMarginRightValueFromUnit = ( currentValue, nextUnit ) => {
	const regex = new RegExp(
		`^(-?\\d+(?:\\.\\d+)?)(?:${ MARGIN_RIGHT_UNITS.map( ( unit ) =>
			unit.replace( /([.*+?^${}()|\[\]\/\\])/g, '\\$1' )
		).join( '|' ) })?$`
	);
	const match = currentValue.match( regex );

	if ( match ) {
		return `${ match[ 1 ] }${ nextUnit }`;
	}

	return currentValue;
};

/**
 * Gets the margin right value from the value.
 *
 * @param {string} currentValue The current margin right value.
 * @param {string} nextValue    The next value.
 * @return {string} The margin right value.
 */
const getMarginRightValueFromValue = ( currentValue, nextValue ) => {
	const unitRegex = MARGIN_RIGHT_UNITS.map( ( unit ) =>
		unit.replace( /([.*+?^${}()|\[\]\/\\])/g, '\\$1' )
	).join( '|' );
	const regex = new RegExp( `^(-?\\d+(?:\\.\\d+)?)(?:(${ unitRegex }))?$` );
	const match = currentValue.match( regex );

	if ( match ) {
		const unit = match[ 2 ] || '';
		return `${ nextValue }${ unit }`;
	}

	return nextValue;
};
