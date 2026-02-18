/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const {
	getVanillaConfig,
	getReactifiedConfig,
} = require( '@wpsyntex/polylang-build-scripts' );

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

	const vanillaConfig = getVanillaConfig( {
		workingDirectory,
		jsPatterns: [ '**/*.js' ],
		jsIgnorePatterns: [ ...commonFoldersToIgnore, ...jsFileNamesToIgnore ],
		cssPatterns: [ '**/*.css' ],
		cssIgnorePatterns: [ ...commonFoldersToIgnore, '**/*.min.css' ],
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
		additionalExternals: { lodash: 'lodash' },
		sassLoadPaths: [
			path.resolve(
				workingDirectory,
				'./css/src/blocks/navigation-language-switcher-editor-style.css'
			),
		],
	} );

	return [
		...vanillaConfig,
		...reactifiedConfig,
	];
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
};

