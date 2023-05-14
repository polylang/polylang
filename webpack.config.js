/**
 * WordPress dependencies.
 */
const defaultConfig                     = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

/**
 * External dependencies.
 */
const glob                              = require( 'glob' ).sync;
const path                              = require( 'path' );
const TerserPlugin                      = require("terser-webpack-plugin");
const MiniCssExtractPlugin              = require( 'mini-css-extract-plugin' );

/**
 * Returns configurations objects for both optimized and expended bundles.
 *
 * @returns {Object}
 */
function getConfig () {
	const jsFiles = glob( './public/js/*.js' );
	console.log( 'Scripts to bundle:', jsFiles );

	const cssFiles = glob( './public/css/*.scss' );
	console.log( 'Stylesheets to bundle:', cssFiles );

	const OptimizedEentries = {};

	jsFiles.map( ( filename ) => {
		if ( undefined === OptimizedEentries[ path.parse( filename ).name ] && undefined === OptimizedEentries[ path.parse( filename ).name + '.min' ] ) {
			OptimizedEentries[ path.parse( filename ).name ] = [ filename ];
			OptimizedEentries[ path.parse( filename ).name + '.min' ] = [ filename ];
			return;
		}
		OptimizedEentries[ path.parse( filename ).name ].push( filename );
		OptimizedEentries[ path.parse( filename ).name + '.min' ].push( filename );
	} );

	cssFiles.map( ( filename ) => {
		if ( undefined === OptimizedEentries[ path.parse( filename ).name ] && undefined === OptimizedEentries[ path.parse( filename ).name + '.min' ] ) {
			OptimizedEentries[ path.parse( filename ).name + '.min' ] = [ filename ];
			return;
		}
		OptimizedEentries[ path.parse( filename ).name + '.min' ].push( filename );
	} );

	const expandedEntries = {};

	cssFiles.map( ( filename ) => {
		expandedEntries[ path.parse( filename ).name ] = [ filename ];
	} );

	console.log(defaultConfig.mode)

	const optimizedConfig = {
		...defaultConfig,
		name: 'optimized',
		entry: OptimizedEentries,
		output: {
			...defaultConfig.output,
		},
		module: {
			rules: [
				{
					test: /\.s?css$/,
					use: [
						MiniCssExtractPlugin.loader,
						'css-loader',
						{
							loader: 'sass-loader',
							options:{
								sassOptions: {
									includePaths: [
										path.resolve(__dirname, './public/css')
									],
									outputStyle: 'compressed',
									sourceMap: true,
								},
							},
						}
					]
				},
			]
		},
		plugins: [
			new DependencyExtractionWebpackPlugin( {
				combineAssets: true,
			} ),
			new MiniCssExtractPlugin(),
		],
		optimization: {
			...defaultConfig.optimization,
			minimize: true,
			minimizer: [
				new TerserPlugin( {
					test: /\.min\.js$/i,
				} ),

			],
		},
	};

	const expandedConfig = {
		name: 'expanded',
		mode: defaultConfig.mode,
		entry:expandedEntries,
		output: {
			...defaultConfig.output,
			clean: true,
		},
		module: {
			rules: [
				{
					test: /\.s?css$/,
					use: [
						MiniCssExtractPlugin.loader,
						'css-loader',
						{
							loader: 'sass-loader',
							options:{
								sassOptions: {
									includePaths: [
										path.resolve(__dirname, './public/css')
									],
									outputStyle: 'expanded',
									sourceMap: true,
								},
							},
						}
					]
				},
			]
		},
		plugins: [ new MiniCssExtractPlugin() ],
	}

	return [ optimizedConfig, expandedConfig ];
}

module.exports = () => {
	return getConfig();
};
