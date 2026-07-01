/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RadioControl,
	RangeControl,
	ToolbarGroup,
	ToolbarButton,
	ToolbarDropdownMenu,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch } from '@wordpress/data';

const CSS_LENGTH_UNITS = [
	{ value: 'px', label: 'px' },
	{ value: 'em', label: 'em' },
	{ value: 'rem', label: 'rem' },
	{ value: 'vh', label: 'vh' },
	{ value: 'vw', label: 'vw' },
];

const LABEL_SPACING_DEFAULT_VALUE = '0.3em';

const ALL_LAYOUT_OPTIONS = [
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
];

const ALL_LAYOUT_TOOLBAR_CONTROLS = [
	{
		title: __( 'Horizontal', 'polylang' ),
		layout: 'horizontal',
	},
	{
		title: __( 'Vertical', 'polylang' ),
		layout: 'vertical',
	},
	{
		title: __( 'Dropdown', 'polylang' ),
		layout: 'dropdown',
	},
];

/**
 * Switcher controls component for toolbar and inspector controls.
 *
 * @param {Object}        props                       The component props.
 * @param {Object}        props.attributes            The block attributes.
 * @param {Function}      props.setAttributes         The function to set the block attributes.
 * @param {Array<string>} props.layoutOptions         Optional layout values to expose.
 * @param {boolean}       props.showToolbar           Whether to render block toolbar controls.
 * @param {boolean}       props.hideCurrentInDropdown Whether to hide the hide-current control in dropdown layout.
 * @return {React.ReactNode} The switcher controls component.
 */
export const SwitcherControls = ( {
	attributes,
	setAttributes,
	layoutOptions = [ 'horizontal', 'vertical', 'dropdown', 'select' ],
	showToolbar = true,
	hideCurrentInDropdown = false,
} ) => {
	const {
		layout,
		show_labels,
		show_flags,
		force_home,
		hide_current,
		flag_aspect_ratio,
		flag_border_radius,
		flag_width,
		flag_label_spacing,
		hide_if_no_translation,
	} = attributes;

	const { createWarningNotice } = useDispatch( noticesStore );

	const layoutSelectOptions = ALL_LAYOUT_OPTIONS.filter( ( option ) =>
		layoutOptions.includes( option.value )
	);

	const layoutToolbarControls = ALL_LAYOUT_TOOLBAR_CONTROLS.filter(
		( control ) => layoutOptions.includes( control.layout )
	).map( ( control ) => ( {
		title: control.title,
		onClick: () => setAttributes( { layout: control.layout } ),
	} ) );

	const labelOptions = [
		{
			label: __( 'Names', 'polylang' ),
			value: 'names',
		},
		{
			label: __( 'Codes', 'polylang' ),
			value: 'codes',
		},
	];

	if ( show_flags ) {
		labelOptions.push( {
			label: __( 'None', 'polylang' ),
			value: '',
		} );
	}

	const toolbarLabelControls = [
		{
			title: __( 'Names', 'polylang' ),
			onClick: () => setAttributes( { show_labels: 'names' } ),
		},
		{
			title: __( 'Codes', 'polylang' ),
			onClick: () => setAttributes( { show_labels: 'codes' } ),
		},
	];

	if ( show_flags ) {
		toolbarLabelControls.push( {
			title: __( 'None', 'polylang' ),
			onClick: () => {
				if ( ! show_flags ) {
					createWarningNotice(
						__(
							'Labels cannot be hidden if flags are not shown.',
							'polylang'
						)
					);

					return;
				}

				setAttributes( {
					show_labels: '',
					flag_label_spacing: LABEL_SPACING_DEFAULT_VALUE,
				} );
			},
		} );
	}

	const hideCurrentVisible =
		'select' !== layout &&
		( ! hideCurrentInDropdown || 'dropdown' !== layout );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display', 'polylang' ) }>
					<SelectControl
						label={ __( 'Layout', 'polylang' ) }
						value={ layout }
						options={ layoutSelectOptions }
						onChange={ ( value ) => {
							setAttributes( { layout: value } );

							if ( 'select' === value ) {
								setAttributes( { show_flags: false } );

								if ( '' === show_labels ) {
									setAttributes( { show_labels: 'names' } );
								}
							}
						} }
						__next40pxDefaultSize
					/>
					{ 'select' !== layout && (
						<SelectControl
							label={ __( 'Labels', 'polylang' ) }
							value={ show_labels }
							options={ labelOptions }
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

									setAttributes( {
										show_labels: value,
										flag_label_spacing:
											LABEL_SPACING_DEFAULT_VALUE,
									} );

									return;
								}

								setAttributes( { show_labels: value } );
							} }
							__next40pxDefaultSize
						/>
					) }
					{ 'select' !== layout && (
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

								setAttributes( { show_flags: value } );
							} }
						/>
					) }
					{ show_flags && 'select' !== layout && (
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
							<UnitControl
								__next40pxDefaultSize
								label={ __( 'Size', 'polylang' ) }
								value={ flag_width }
								units={ CSS_LENGTH_UNITS }
								onChange={ ( value ) =>
									setAttributes( {
										flag_width: value,
									} )
								}
							/>
							{ '' !== show_labels && (
								<UnitControl
									__next40pxDefaultSize
									label={ __( 'Label spacing', 'polylang' ) }
									value={ flag_label_spacing }
									units={ CSS_LENGTH_UNITS }
									onChange={ ( value ) =>
										setAttributes( {
											flag_label_spacing: value,
										} )
									}
								/>
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
					{ hideCurrentVisible && (
						<ToggleControl
							label={ __( 'Hide current', 'polylang' ) }
							checked={ hide_current }
							onChange={ ( value ) =>
								setAttributes( { hide_current: value } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Hide if no translation', 'polylang' ) }
						checked={ hide_if_no_translation }
						onChange={ ( value ) =>
							setAttributes( { hide_if_no_translation: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			{ showToolbar && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarDropdownMenu
							label={ __( 'Layout', 'polylang' ) }
							controls={ layoutToolbarControls }
						/>
						{ 'select' !== layout && (
							<ToolbarButton
								icon={
									<span className="dashicons dashicons-flag"></span>
								}
								label={
									show_flags
										? __( 'Hide flags', 'polylang' )
										: __( 'Show flags', 'polylang' )
								}
								onClick={ () => {
									if ( show_flags && '' === show_labels ) {
										createWarningNotice(
											__(
												'Flags cannot be hidden if labels are not displayed.',
												'polylang'
											)
										);

										return;
									}

									setAttributes( {
										show_flags: ! show_flags,
									} );
								} }
							/>
						) }
						{ 'select' !== layout && (
							<ToolbarDropdownMenu
								icon={
									<span className="dashicons dashicons-editor-textcolor"></span>
								}
								label={ __( 'Labels', 'polylang' ) }
								controls={ toolbarLabelControls }
							/>
						) }
					</ToolbarGroup>
				</BlockControls>
			) }
		</>
	);
};
