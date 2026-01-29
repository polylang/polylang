/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const glob = require( 'glob' ).sync;
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const { merge } = require( 'lodash' );
const {
	transformCssEntry,
	transformJsEntry,
} = require( '@wpsyntex/polylang-build-scripts' );

function configureWebpack( options ){
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log('Webpack mode:', mode);
	console.log('isProduction:', isProduction);
	console.log('dirname:', __dirname);

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
	let config = [
		...jsFileNamesEntries, // Add config for js files.
		...cssFileNamesEntries, // Add config for css files.
	];

	/**
	 *  Prepare Blocks bundle
	 */

	// Reference to external library dependencies.
	const externals = {
		react: 'React',
		lodash: 'lodash',
	};

	const blockEditorEntryPointFilenamesEntries = './js/src/blocks/index.js';
	const blockEditorEntryPointCssFilenameEntries = './css/src/blocks/navigation-language-switcher-editor-style.css';

	console.log(
		'Building Blocks bundle with entry point:',
		blockEditorEntryPointFilenamesEntries,
		'and CSS file:',
		blockEditorEntryPointCssFilenameEntries,
	);

	/**
	 * Given a string, returns a new string with dash separators converted to
	 * camelCase equivalent. This is not as aggressive as `_.camelCase` in
	 * converting to uppercase, where Lodash will also capitalize letters
	 * following numbers.
	 *
	 * @param {string} string Input dash-delimited string.
	 * @return {string} Camel-cased string.
	 */
	function camelCaseDash( string ) {
		return string.replace( /-([a-z])/g, ( match, letter ) =>
			letter.toUpperCase()
		);
	}

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
	wpDependencies.forEach( ( name ) => {
		externals[ `@wordpress/${ name }` ] = {
			this: [ 'wp', camelCaseDash( name ) ],
		};
	} );

	/**
	 *  Prepare Block editor css file generation preprocess with sass and js file transpilation with babel.
	 */

	// Active source maps only for development mode.
	const devtool = ! isProduction ? 'source-map' : false;

	// Where to find external packages.
	const resolve = {
		modules: [ __dirname, 'node_modules' ],
	};

	// Common Output configuration.
	const blockEditorOutputConfig = {
		path: __dirname,
		library: [ 'polylang-pro' ],
		libraryTarget: 'this',
	};

	// Common configuration to minify js files.
	const blockEditorMinimizeOptimizationConfig = {
		minimize: true,
		minimizer: [
			new TerserPlugin( {
				terserOptions: {
					format: {
						comments: false,
					},
				},
				extractComments: false,
			} ),
		],
	};

	// Common configuration not to minify js files.
	const blockEditorUnMinimizeOptimizationConfig = {
		minimize: false,
	};

	// Common configuration to transpile js files with babel.
	const blockEditorModuleTranspilationConfig = [
		{
			test: /\.js$/,
			exclude: /node_modules/,
			use: 'babel-loader',
		},
	];

	// Prepare Block editor both entry point sidebar and blocks config for minified files.
	const blockEditorMinifiedEntryPointConfig = [
		{
			entry: { blocks: blockEditorEntryPointFilenamesEntries },
			output: Object.assign(
				{},
				{ filename: './js/build/[name].min.js' },
				blockEditorOutputConfig
			),
			externals,
			resolve,
			module: {
				rules: [
					...blockEditorModuleTranspilationConfig,
				],
			},
		plugins: [
			new MiniCssExtractPlugin( {
				filename: './css/build/style.min.css',
			} ),
		],
		devtool,
		optimization: blockEditorMinimizeOptimizationConfig,
		},
	];

	// Prepare Block editor both entry point sidebar and blocks config for unminified files.
	const blockEditorUnminifiedEntryPointConfig = [
		{
			entry: { blocks: blockEditorEntryPointFilenamesEntries },
			output: Object.assign(
				{},
				{ filename: './js/build/[name].js' },
				blockEditorOutputConfig
			),
			externals,
			resolve,
			module: {
				rules: [
					...blockEditorModuleTranspilationConfig,
				],
			},
		plugins: [
			new MiniCssExtractPlugin( {
				filename: './css/build/style.css',
			} ),
		],
		devtool,
		optimization: blockEditorUnMinimizeOptimizationConfig,
		},
	];

	// Concatenate Block editor both entry point sidebar and blocks config for both minified and unminified files.
	const blockEditorEntryPointConfig = [
		...blockEditorMinifiedEntryPointConfig,
		...blockEditorUnminifiedEntryPointConfig,
	];

	const workingDirectory = path.resolve( __dirname );
	const CssBuildDirectory = workingDirectory + '/css/build';

	// Make webpack configuration.
	config = [
		...config,
		...[ blockEditorEntryPointCssFilenameEntries ].map( transformCssEntry( CssBuildDirectory, isProduction ) ),
		...blockEditorEntryPointConfig,
	];

	return config;
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
}

