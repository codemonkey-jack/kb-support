const path = require("path");
const webpack = require("webpack");
// Browser sync uses proxy.
const BrowserSyncPlugin = require("browser-sync-webpack-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const CleanWebpackPlugin = require("clean-webpack-plugin");
// Vital css extractor.
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const UglifyJsPlugin = require("uglifyjs-webpack-plugin");
const NodePolyfillPlugin = require("node-polyfill-webpack-plugin")
// Minimization.
const TerserPlugin = require("terser-webpack-plugin");


const config = require("./config.json");

const webpackConfig = {
	entry: ["./src/index.js"],
	output: {
		filename: "bundle.js",
		path: path.resolve(__dirname, "dist"),
		publicPath: "/",
	},
	resolve: {
		fallback: {
			fs: false,
			tls: false,
			net: false,
			path: false,
			zlib: false,
			http: false,
			https: false,
			stream: false,
			crypto: false,
		},
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader",
					options: {
						presets: ["@babel/preset-env"],
					},
				},
			},
			{
				test: /\.css$/i,
				use: [
					MiniCssExtractPlugin.loader,
					"css-loader",
					"postcss-loader",
				],
			},
			{
				test: /\.scss$/i,
				exclude: /node_modules/,
				use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
			},
			{
				test: /\.(png|svg|jpg|gif)$/,
				use: ["file-loader"],
			},
		],
	},
	devtool: "source-map",
	plugins: [
		new BrowserSyncPlugin({
			proxy: {
				target: config.proxyURL,
			},
			files: ["**/*.php"],
			cors: true,
			reloadDelay: 0,
		}),
		new MiniCssExtractPlugin({
			filename: "style.css",
			chunkFilename: "style.css",
		}),
		new NodePolyfillPlugin(),
	],
	optimization: {
		minimize: true,
		minimizer: [new TerserPlugin({
			minify: TerserPlugin.uglifyJsMinify,
			include: /dist/,
		})],
	},
};

if (process.env.NODE_ENV === "production") {
	const buildFolder = path.resolve(__dirname, "wp-react-boilerplate-built");
	webpackConfig.plugins.push(
		new webpack.optimize.UglifyJsPlugin({
			mangle: {
				screw_ie8: true,
			},
			compress: {
				screw_ie8: true,
				warnings: false,
			},
			sourceMap: false,
		})
	);

	webpackConfig.plugins.push(new CleanWebpackPlugin([buildFolder]));

	webpackConfig.plugins.push(
		new CopyWebpackPlugin(
			[
				{
					from: path.resolve(__dirname, "server") + "/**",
					to: buildFolder,
				},
				{ from: path.resolve(__dirname, "*.php"), to: buildFolder },
			],
			{
				// By default, we only copy modified files during
				// a watch or webpack-dev-server build. Setting this
				// to `true` copies all files.
				copyUnmodified: true,
			}
		)
	);

	webpackConfig.output.path = buildFolder + "/dist";
}

module.exports = webpackConfig;
