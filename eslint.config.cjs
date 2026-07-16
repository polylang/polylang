/* eslint-disable import/no-extraneous-dependencies */
const wpPlugin = require( '@wordpress/eslint-plugin' );
const globals = require( 'globals' );

module.exports = [
	{
		ignores: [
			'**/build/**',
			'**/node_modules/**',
			'**/vendor/**',
			'dependencies/**',
			'tmp/**',
			'dist/**',
			'coverage/**',
			'playwright-report/**',
			'test-results/**',
			'artifacts/**',
			'downloads/**',
			'**/*.min.js',
			'**/*.map',
		],
	},
	...wpPlugin.configs.recommended,
	{
		settings: {
			'import/core-modules': [
				'@wordpress/block-editor',
				'@wordpress/blocks',
				'@wordpress/components',
				'@wordpress/element',
				'@wordpress/hooks',
				'@wordpress/i18n',
				'@wordpress/notices',
				'@wordpress/data',
			],
		},
		rules: {
			'jsdoc/no-undefined-types': [
				1,
				{
					definedTypes: [ 'React', 'ReactElement', 'APIFetchOptions' ],
				},
			],
			camelcase: [
				2,
				{
					allow: [
						'pll_block_editor_blocks_settings',
						'show_flags',
						'show_names',
						'flag_aspect_ratio',
						'flag_border_radius',
						'hide_if_no_translation',
						'hide_current',
						'force_home',
						'layout',
						'show_labels',
						'flag_width',
						'flag_label_spacing',
						'pll_data',
					],
					properties: 'never',
					ignoreDestructuring: true,
				},
			],
		},
		languageOptions: {
			globals: {
				...globals.browser,
				pll_block_editor_blocks_settings: 'readonly',
				pll_data: 'readonly',
				pllDefaultLanguage: 'readonly',
			},
			parserOptions: {
				requireConfigFile: false,
			},
		},
	},
];
