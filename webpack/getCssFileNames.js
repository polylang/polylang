/**
 * @package Polylang
 */

/**
 * External dependencies.
 */
const path = require( 'path' );
const glob = require( 'glob' ).sync;
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );

/**
 * Internal dependecies
 */
const commonFileNamesToIgnore = require( './commonFileNamesToIgnore' );

/**
 * Retrieves the CSS filenames to copy and minify.
 *
 * @param {string} URI of the plugin's root path to output files.
 */
function getCssFileNamesEntries( root, isProduction = false ) {
	const cssFileNames = glob(
		'**/*.css', 
		{ 
			cwd: root,
			ignore: commonFileNamesToIgnore
		}
	).map(filename => `./${filename}`);
	console.log('css files to minify:', cssFileNames);

	// Prepare webpack configuration to minify css files to source folder as target folder and suffix file name with .min.js extension.
	const cssFileNamesEntries = [
		...cssFileNames.map( mapCssMinifiedFiles( root, isProduction ), {}),
		...cssFileNames.map( mapCssFiles( root, isProduction ), {})
	];
	return cssFileNamesEntries;
}

function mapCssMinifiedFiles( root, isProduction ) {
	return ( filename ) => {
		const entry = {};
		entry[ path.parse( filename ).name ] = filename;
		const config = {
			entry: entry,
			output: {
				filename: `css/[name].work`,
				path: root, // Output path from the plugin's root folder, passed as parameter.
			},
			plugins: [
				new MiniCssExtractPlugin(
					{
						filename: `css/build/[name].min.css`
					}
				),
				new CleanWebpackPlugin(
					{
						dry: false,
						verbose: false,
						cleanOnceBeforeBuildPatterns: [],
						cleanAfterEveryBuildPatterns: [
							'**/*.work',
							'**/*.LICENSE.txt'
						],
					}
				),
			],
			module: {
				rules: [
					{
						test: /\.css$/i,
						use: [ MiniCssExtractPlugin.loader, 'css-loader' ],
					},
				],
			},
			devtool: !isProduction ? 'source-map' : false,
			optimization: {
				minimize: true,
				minimizer: [
					new CssMinimizerPlugin(),
				],
			},
		};
		return config;
	};
}

function mapCssFiles( root, isProduction ) {
	return ( filename ) => {
		const entry = {};
		entry[ path.parse( filename ).name ] = filename;
		const config = {
			entry: entry,
			output: {
				filename: `css/build/[name].css`,
				path: root, // Output path from the plugin's root folder, passed as parameter.
			},
			devtool: !isProduction ? 'source-map' : false,
			optimization: {
				minimize: false
			},
			module: {
				rules: [
					{
						test: /\.css$/i,
						use: ['css-loader' ],
					},
				],
			}
		};
		return config;
	};
}

module.exports = getCssFileNamesEntries;