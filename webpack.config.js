/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const {
	getVanillaConfig,
	getReactifiedConfig,
} = require( '@wpsyntex/polylang-build-scripts' );

/**
 * Gets the configuration for the CSS library copy.
 * Required for development mode to resolve the CSS library in files with @import statements.
 *
 * @param {string}  workingDirectory  The working directory.
 * @param {string}  cssBuildDirectory The directory to build the CSS library.
 * @param {boolean} isProduction      Whether the build is in production mode.
 * @return {Object} The configuration for the CSS library copy.
 */
function getCssLibCopyConfig(
	workingDirectory,
	cssBuildDirectory,
	isProduction
) {
	return {
		mode: isProduction ? 'production' : 'development',
		entry: path.join( workingDirectory, 'css/src/lib/switcher-flags.css' ),
		output: {
			path: cssBuildDirectory,
			filename: '[name].work',
		},
		plugins: [
			new CopyPlugin( {
				patterns: [
					{
						from: path.join( workingDirectory, 'css/src/lib' ),
						to: path.join( cssBuildDirectory, 'lib' ),
					},
				],
			} ),
			new CleanWebpackPlugin( {
				dry: false,
				verbose: false,
				cleanOnceBeforeBuildPatterns: [],
				cleanAfterEveryBuildPatterns: [
					path.join( cssBuildDirectory, '**/*.work' ),
				],
			} ),
		],
		module: {
			rules: [
				{
					test: /\.css$/i,
					use: [ 'css-loader' ],
				},
			],
		},
	};
}

function configureWebpack( options ) {
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	const workingDirectory = path.resolve( __dirname );
	const jsBuildDirectory = path.join( workingDirectory, 'js/build' );
	const cssBuildDirectory = path.join( workingDirectory, 'css/build' );

	console.log( 'Webpack mode:', mode );
	console.log( 'Working directory:', workingDirectory );

	/*
	 * Prepare vanilla config for Polylang Core.
	 */
	const commonFoldersToIgnore = [
		'node_modules/**',
		'coverage/**',
		'vendor/**',
		'tmp/**',
		'tests/**',
		'webpack/**',
		'**/build/**',
	];

	const jsFileNamesToIgnore = [
		'js/src/lib/**',
		'js/src/packages/**',
		'js/src/blocks/**',
		'js/src/block-editor.js',
		'**/*.config.js',
		'**/*.min.js',
	];

	const cssFileNamesToIgnore = [ 'css/src/lib/**', '**/*.min.css' ];

	const vanillaConfig = getVanillaConfig( {
		workingDirectory,
		jsPatterns: [ '**/*.js' ],
		jsIgnorePatterns: [ ...commonFoldersToIgnore, ...jsFileNamesToIgnore ],
		cssPatterns: [ '**/*.css' ],
		cssIgnorePatterns: [
			...commonFoldersToIgnore,
			...cssFileNamesToIgnore,
		],
		jsBuildDirectory,
		cssBuildDirectory,
		isProduction,
	} );

	/*
	 * Prepare Reactified config for Blocks bundle.
	 */
	const wpDependencies = [
		'api-fetch',
		'block-editor',
		'blocks',
		'components',
		'data',
		'editor',
		'element',
		'hooks',
		'i18n',
		'primitives',
		'notices',
		'core-data',
	];

	const reactifiedConfig = getReactifiedConfig( {
		entryPoints: {
			blocks: './js/src/blocks/index.js',
			'block-editor': './js/src/block-editor.js',
		},
		outputPath: workingDirectory,
		libraryName: 'polylang',
		isProduction,
		wpDependencies,
		sassLoadPaths: [
			path.resolve(
				workingDirectory,
				'./css/src/navigation-language-switcher-editor.css'
			),
		],
	} );

	return [
		...vanillaConfig,
		getCssLibCopyConfig(
			workingDirectory,
			cssBuildDirectory,
			isProduction
		),
		...reactifiedConfig,
	];
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
};
