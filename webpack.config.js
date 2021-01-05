/**
 * @package Polylang
 */

 /**
  * External dependencies
  */
 const path = require( 'path' );

 /**
 * Internal dependencies
 */
const getJsFileNamesEntries = require( './webpack/getJsFileNames' );
const getCssFileNamesEntries = require('./webpack/getCssFileNames' );

function configureWebpack( options ){
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log('Webpack mode:', mode);
	console.log('isProduction:', isProduction);
	console.log('dirname:', __dirname);
	
	const jsFileNamesEntries = getJsFileNamesEntries( path.resolve( __dirname ) );

	const cssFileNamesEntries = getCssFileNamesEntries( path.resolve( __dirname ), isProduction );

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

