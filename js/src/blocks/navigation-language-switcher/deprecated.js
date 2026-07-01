/**
 * Deprecated navigation language switcher block.
 */

const V2Attributes = {
	layout: 'horizontal',
	show_labels: 'names',
	show_flags: false,
	force_home: false,
	hide_current: false,
	hide_if_no_translation: false,
	flag_aspect_ratio: '3:2',
	flag_border_radius: 0,
	flag_width: '18px',
	flag_label_spacing: '0.3em',
	className: '',
};

const v1 = {
	attributes: {
		dropdown: {
			type: 'boolean',
			default: false,
		},
		show_names: {
			type: 'boolean',
			default: true,
		},
		show_flags: {
			type: 'boolean',
			default: false,
		},
		force_home: {
			type: 'boolean',
			default: false,
		},
		hide_current: {
			type: 'boolean',
			default: false,
		},
		hide_if_no_translation: {
			type: 'boolean',
			default: false,
		},
		className: {
			type: 'string',
			default: '',
		},
	},

	isEligible: ( attributes ) =>
		'dropdown' in attributes || 'show_names' in attributes,

	migrate( attributes ) {
		const {
			dropdown,
			show_names,
			show_flags,
			force_home,
			hide_current,
			hide_if_no_translation,
			className,
		} = attributes;

		return {
			...V2Attributes,
			layout: dropdown ? 'dropdown' : 'horizontal',
			show_labels: show_names ? 'names' : '',
			show_flags: show_names ? show_flags : true,
			force_home,
			hide_current,
			hide_if_no_translation,
			className: className || '',
		};
	},
};

export default [ v1 ];
