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
	const jsSourceFileNames = glob( '**/*.src.js', { 'ignore': jsFoldersToIgnore } ).map( filename => `./${ filename }` );
	console.log( 'js files to build:', jsSourceFileNames );

	const jsFileNames = glob(
		'**/*.js',
		{
			'ignore': [
				...jsFoldersToIgnore,
				...jsFileNamesToIgnore,
				// Glob ignore pathes cannot use the '.' special character
				...jsSourceFileNames.map( filename => computeBuildFilename( filename ).substr( 2 ) )
			]
		}
	).map( filename => `./${ filename }` );
	console.log( 'js files to minify:', jsFileNames );

	function computeBuildFilename( filename, suffix ) {
		const nameWithoutSuffix = path.parse( filename ).name.split( '.' )[0];
		suffix = suffix ? '.' + suffix : '';

		return `${ path.parse( filename ).dir }/${ nameWithoutSuffix + suffix }.js`; // phpcs:ignore WordPress.WhiteSpace.OperatorSpacing
	}

	// Prepare webpack configuration to minify js files to source folder as target folder and suffix file name with .min.js extension.
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
	const jsFileNamesEntries = [
		...mapJsFiles( [ ...jsFileNames, ...jsSourceFileNames ], true ),
		...mapJsFiles( jsSourceFileNames )
	];
	return jsFileNamesEntries;
}

module.exports = getJsFileNamesEntries;
