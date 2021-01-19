/**
 * @package Polylang
 */

 /**
 * External dependencies
 */

const path = require( 'path' );
const glob = require( 'glob' ).sync;
const { transformJsEntry, transformCssEntry } = require( '@wpsyntex/polylang-build-scripts' );

function configureWebpack( options ){
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log('Webpack mode:', mode);
	console.log('isProduction:', isProduction);
	console.log('dirname:', __dirname);

	const commonFoldersToIgnore = [
		'node_modules/**',
		'vendor/**',
		'tmp/**',
		'webpack/**',
		'**/build/**',
	];

	const jsFileNamesToIgnore = [
		'js/lib/**',
		'*.config.js',
		'*.min.js',
	];

	const jsFileNames = glob( '**/*.js', { 'ignore': [ ...commonFoldersToIgnore, ...jsFileNamesToIgnore ] } ).map( filename => `./${ filename }`);
	console.log( 'js files to minify:', jsFileNames );

	const jsFileNamesEntries = [
		...jsFileNames.map( transformJsEntry( path.resolve( __dirname ) + '/js/build', true ) ),
		...jsFileNames.map( transformJsEntry( path.resolve( __dirname ) + '/js/build', false ) )
	]

	const cssFileNames = glob( '**/*.css', { 'ignore': [ ...commonFoldersToIgnore, '**/*.min.css' ] } ).map( filename => `./${ filename }`);
	console.log( 'css files to minify:', cssFileNames );

	// Prepare webpack configuration to minify css files to source folder as target folder and suffix file name with .min.js extension.
	const cssFileNamesEntries = cssFileNames.map( transformCssEntry( path.resolve( __dirname ) + '/css/build', isProduction ) );

	// Make webpack configuration.
	const config = [
		...jsFileNamesEntries, // Add config for js files.
		...cssFileNamesEntries, // Add config for css files.
	];

	return config;
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
}

