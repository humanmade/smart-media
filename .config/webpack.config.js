const ManifestPlugin = require('webpack-manifest-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const path = require('path');

const env = process.env.NODE_ENV || 'production';

const commonConfig = {
	mode: env,
	entry: {
		cropper: path.resolve( 'inc/cropper/src/cropper.js' ),
	},
	output: {
		path: path.resolve( __dirname, '..' ),
		filename: 'inc/[name]/build/[name].[hash:8].js',
		chunkFilename: 'inc/[name]/build/chunk.[id].[chunkhash:8].js',
		publicPath: '/',
		libraryTarget: 'this',
		jsonpFunction: 'HMSmartMedia'
	},
	target: 'web',
	resolve: { extensions: [ '.js', '.css', '.scss' ] },
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				loader: 'babel-loader',
				exclude: /(node_modules|bower_components)/,
				options: {
					babelrc: false,
					presets: [
						[ require( '@babel/preset-env' ), {
							modules: false,
							targets: { browsers: [ ' > 0.01%' ] },
						} ],
					],
					plugins: [
						require( '@babel/plugin-proposal-object-rest-spread' ),
						require( '@babel/polyfill' ),
					],
				},
			},
			{
				test: /\.(png|jpg|jpeg|gif|ttf|otf|eot|svg|woff(2)?)(\??[#a-z0-9]+)?$/,
				loader: 'url-loader',
				options: {
					name: '[name].[ext]',
					limit: 10000,
					fallback: 'file-loader',
					publicPath: '/',
				},
			},
			{
				test: /\.s?css$/,
				use: [ 'style-loader', 'css-loader', 'sass-loader' ],
			},
		],
	},
	externals: {
		'HM': 'HM',
		'@wordpress/backbone': { this: [ 'wp', 'BackBone' ] },
		'@wordpress/media': { this: [ 'wp', 'media' ] },
		'@wordpress/ajax': { this: [ 'wp', 'ajax' ] },
		'@wordpress/template': { this: [ 'wp', 'template' ] },
		'@wordpress/i18n': { this: [ 'wp', 'i18n' ] },
		'@wordpress/hooks': { this: [ 'wp', 'hooks' ] },
		'jQuery': 'jQuery',
		lodash: '_',
		wp: 'wp',
	},
	optimization: {
		noEmitOnErrors: true
	},
	performance: {
		assetFilter: function assetFilter(assetFilename) {
			return !(/\.map$/.test(assetFilename));
		},
	},
	plugins: [
		new ManifestPlugin( {
			writeToFileEmit: true,
		} ),
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: ['inc/*/build/**/*']
		} ),
	],
};

const devConfig = Object.assign( {}, commonConfig, {
	devtool: 'cheap-module-eval-source-map',
	devServer: {
		port: 9022,
		//hot: true,
		allowedHosts: [
			'.local',
			'.localhost',
			'.test',
		],
		overlay: {
			warnings: true,
			errors: true
		}
	},
} );

//devConfig.plugins.push( new webpack.HotModuleReplacementPlugin() );

const productionConfig = Object.assign( {}, commonConfig, {} );

const config = env === 'production' ? productionConfig : devConfig;

module.exports = config;
