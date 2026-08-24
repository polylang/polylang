/* eslint-disable import/no-extraneous-dependencies */
const AutomatticPlugin = require( '@automattic/eslint-plugin-wpvip' );
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
	// Compose explicitly: `recommended` only auto-loads React when `react` is a
	// declared dependency; we get it transitively via `@wordpress/element`.
	...AutomatticPlugin.configs.javascript,
	...AutomatticPlugin.configs.formatting,
	...AutomatticPlugin.configs.react,
	// WordPress-specific rules only (not the full WP recommended stack).
	...wpPlugin.configs.custom,
	...wpPlugin.configs.i18n,
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
			// Same as the WPVIP config, but tolerates the blank lines introduced by
			// the WordPress `/** … dependencies */` import blocks.
			'import/order': [
				'error',
				{
					'newlines-between': 'always-and-inside-groups',
					alphabetize: {
						order: 'asc',
					},
					groups: [
						[ 'builtin', 'external' ],
						[ 'index', 'internal', 'object', 'parent', 'sibling' ],
						[ 'type' ],
					],
				},
			],
			camelcase: [
				2,
				{
					allow: [
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
				ajaxurl: 'readonly',
				pll_data: 'readonly',
				pllDefaultLanguage: 'readonly',
			},
		},
	},
	{
		files: [ 'tests/e2e/**' ],
		rules: {
			// Specs import root devDependencies; the nested stub is not an npm package.
			'import/no-extraneous-dependencies': [
				'error',
				{
					devDependencies: true,
					packageDir: __dirname,
				},
			],
		},
	},
	// Last: Prettier turns off conflicting stylistic ESLint rules.
	...AutomatticPlugin.configs.prettier,
];
