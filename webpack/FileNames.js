/**
 * @package Polylang
 */

/**
 * External dependencies
 */
const path = require( 'path' );
const glob = require( 'glob' ).sync;

/**
 * Retrieves the javascript filenames to build and minify.
 * 
 * @param {string[]} jsFoldersToIgnore 
 * @param {string[]} jsFileNamesToIgnore 
 */
function getJsFileNamesEntries( jsFoldersToIgnore , jsFileNamesToIgnore ) {
	const jsFileNames = glob(
		'**/*.js',
		{
			'ignore': [
				...jsFoldersToIgnore,
				...jsFileNamesToIgnore,
			]
		}
	).map( filename => `./${ filename }` );
	console.log( 'js files to minify:', jsFileNames );

	const jsFileNamesEntries = [
		...mapJsFiles( jsFileNames, true ),
		...mapJsFiles( jsFileNames )
	];
	return jsFileNamesEntries;
}

/**
 * @param {string} filename Source file's path and name.
 * @param {string} suffix To add before file extension.
 */
function computeBuildFilename( filename, suffix ) {
	const nameWithoutSuffix = path.parse( filename ).name;
	suffix = suffix ? '.' + suffix : '';

	return `js/build/${ nameWithoutSuffix + suffix }.js`; // phpcs:ignore WordPress.WhiteSpace.OperatorSpacing
}

/**
 * Prepare webpack configuration to minify js files to source folder as target folder and suffix file name with .min.js extension.
 * @param {string[]} jsFileNames Source files to build.
 * @param {boolean} minimize True to generate minified files.
 */
 function mapJsFiles( jsFileNames, minimize = false ) {
	return jsFileNames.map( 
		( filename ) => {
			const entry = {};
			entry[ path.parse( filename ).name ] = filename;
			const output = {
				filename: computeBuildFilename( filename, minimize ? 'min' : '' ),
				path: process.cwd(),
				iife: false, // Avoid Webpack to wrap files into a IIFE which is not needed for this kind of javascript files.
			};
			const config = {
				entry: entry,
				output: output,
				optimization: { minimize: minimize }
			};
			return config;
		},
		{}
	);
}

module.exports = getJsFileNamesEntries;
