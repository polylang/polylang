/**
 * External dependencies
 */
const path = require( 'path' );
const glob = require( 'glob' ).sync;
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

function configureWebpack( options){
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log('Webpack mode:', mode);
	console.log('isProduction:', isProduction);
	console.log('dirname:', __dirname);


	const jsFileNamesToIgnore = [
		'*.config.js',
		'node_modules/**/*.js',
		'vendor/**/*.js',
		'**/*.min.js'
	];
	const jsFileNames = glob( '**/*.js', { 'ignore': jsFileNamesToIgnore } ).map( filename => `./${ filename }`);
	console.log( 'js files to minify:', jsFileNames );

	const cssFileNamesToIgnore = [
		'node_modules/**/*.css',
		'vendor/**/*.css',
		'**/*.min.css'
	];
	const cssFileNames = glob( '**/*.css', { 'ignore': cssFileNamesToIgnore } ).map( filename => `./${ filename }`);
	console.log( 'css files to minify:', cssFileNames );

	// Prepare webpack configuration to minify js files to source folder as target folder and suffix file name with .min.js extension.
	const jsFileNamesEntries = jsFileNames.map( ( filename ) => {
			const entry = {};
			entry[ path.parse( filename).name ] = filename;
			const output = {
				filename: `${path.parse( filename).dir}/[name].min.js`,
				path: path.resolve( __dirname ), // Output folder as project root to put files in the same folder as source files.
				iife: false, // Avoid Webpack to wrap files into a IIFE which is not needed for this kind of javascript files.
			}
			const config = {
				entry: entry,
				output: output,
			};
			return config;
		},
		{}
	);

	// Prepare webpack configuration to minify css files to source folder as target folder and suffix file name with .min.js extension.
	const cssFileNamesEntries = cssFileNames.map( ( filename ) => {
			const entry = {};
			entry[ path.parse( filename).name ] = filename;
			const output = {
				filename: `${path.parse( filename).dir}/[name].work`,
				path: path.resolve( __dirname ), // Output folder as project root to put files in the same folder as source files.
			}
			const config = {
				entry: entry,
				output: output,
				plugins: [
					new MiniCssExtractPlugin(
						{
							filename: `${path.parse( filename).dir}/[name].min.css`
						}
					),
					new CleanWebpackPlugin(
						{
							dry: false,
							verbose: false,
							cleanOnceBeforeBuildPatterns: [], // Disable to clean nothing before build.
							cleanAfterEveryBuildPatterns: ['**/*.work'],
						}
					)
				],
				module: {
					rules: [
						{
							test: /\.css$/i,
							use: [ MiniCssExtractPlugin.loader, 'css-loader'],
						},
					],
				},
				optimization: {
					minimize: true,
					minimizer: [
						new CssMinimizerPlugin(),
					],
				},
			};
			return config;
		},
		{}
	);

	// Make webpack configuration.
	const config = [];
	// Add config for js files.
	jsFileNamesEntries.forEach( element => {
		config.push( element );
	});

	// Add config for css files.
	cssFileNamesEntries.forEach( element => {
		config.push( element );
	});
	return config;
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
}
