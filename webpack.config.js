/**
 * @package Polylang
 */

 /**
 * External dependencies
 */

const path = require( 'path' );
const glob = require( 'glob' ).sync;
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const CopyPlugin = require( 'copy-webpack-plugin' );

/**
 * Internal dependencies
 */

 const getJsFileNamesEntries = require( './webpack/FileNames' );

function configureWebpack( options ){
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log('Webpack mode:', mode);
	console.log('isProduction:', isProduction);
	console.log('dirname:', __dirname);

	const commonFileNamesToIgnore = [
		'node_modules/**',
		'vendor/**',
		'tmp/**',
		'webpack/**',
		'**/build/**',
		'js/lib/**',
		'**/*.min.*',
	];
	
	const jsFileNamesEntries = getJsFileNamesEntries( [ ...commonFileNamesToIgnore, '*.config.js' ] );

	const cssFileNames = glob( '**/*.css', { 'ignore': commonFileNamesToIgnore } ).map( filename => `./${ filename }`);
	console.log( 'css files to minify:', cssFileNames );

	// Prepare webpack configuration to minify css files to source folder as target folder and suffix file name with .min.js extension.
	const cssFileNamesEntries = [
		...cssFileNames.map( mapCssMinifiedFiles(), {} ),
		...cssFileNames.map( mapCssFiles(), {} )
	];

	// Make webpack configuration.
	const config = [
		...jsFileNamesEntries, // Add config for js files.
		...cssFileNamesEntries, // Add config for css files.
	];

	return config;

	function mapCssMinifiedFiles() {
		return ( filename ) => {
			const entry = {};
			entry[ path.parse( filename ).name ] = filename;
			const config = {
				entry: entry,
				output: {
					filename: `css/build/[name].work`,
					path: path.resolve( __dirname ), // Output folder as project root to put files in the same folder as source files.
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

	function mapCssFiles() {
		return ( filename ) => {
			const entry = {};
			entry[ path.parse( filename ).name ] = filename;
			const config = {
				entry: entry,
				output: {
					filename: `css/build/[name].css`,
					path: path.resolve( __dirname ), // Output folder as project root to put files in the same folder as source files.
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
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
}

