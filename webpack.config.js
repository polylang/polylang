const path                                    = require( 'path' );
const glob                                    = require( 'glob' ).sync;
const defaultConfig                           = require( '@wordpress/scripts/config/webpack.config' );
const { transformJsEntry, transformCssEntry } = require( '@wpsyntex/polylang-build-scripts' );

const jsFiles = glob( './public/js/*.js' );
console.log( 'Javascript files to minify:', jsFiles );

const cssFiles = glob( './public/css/*.css' );
console.log( 'CSS files to minify:', cssFiles );

const config = [
	...jsFiles.map( transformJsEntry( path.resolve( __dirname ) + '/build/js', true ) ),
	...jsFiles.map( transformJsEntry( path.resolve( __dirname ) + '/build/js', false ) ),
	...cssFiles.map( transformCssEntry( path.resolve( __dirname ) + '/build/css', true ) ),
]

module.exports = config;
