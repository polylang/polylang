/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const {
	transformCssEntry,
	getVanillaConfig,
	getReactifiedConfig,
} = require( '@wpsyntex/polylang-build-scripts' );

function configureWebpack( options ) {
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log( 'Webpack mode:', mode );
	console.log( 'isProduction:', isProduction );
	console.log( 'dirname:', __dirname );

	const workingDirectory = path.resolve( __dirname );
	const jsBuildDirectory = path.join( workingDirectory, 'js/build' );
	const cssBuildDirectory = path.join( workingDirectory, 'css/build' );

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
		'**/*.config.js',
		'**/*.min.js',
	];

	// Generate vanilla JS and CSS configs
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

	/**
	 * Prepare Blocks bundle
	 */
	const blockEditorEntryPointCssFilenameEntries = './css/src/blocks/navigation-language-switcher-editor-style.css';

	console.log(
		'Building Blocks bundle with entry point: ./js/src/blocks/index.js',
		'and CSS file:',
		blockEditorEntryPointCssFilenameEntries
	);

	// WordPress package dependencies declared as externals.
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

	// Generate reactified config for blocks
	const reactifiedConfig = getReactifiedConfig( {
		entryPoints: { blocks: './js/src/blocks/index.js' },
		outputPath: workingDirectory,
		libraryName: 'polylang-pro',
		isProduction,
		wpDependencies,
		additionalExternals: { lodash: 'lodash' },
	} );

	// Handle block editor CSS separately
	const blockEditorCssConfig = [ blockEditorEntryPointCssFilenameEntries ].map(
		transformCssEntry( cssBuildDirectory, isProduction )
	);

	// Make webpack configuration.
	return [
		...vanillaConfig,
		...blockEditorCssConfig,
		...reactifiedConfig,
	];
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
};

