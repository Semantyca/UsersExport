const {VueLoaderPlugin} = require('vue-loader');
const webpack = require('webpack');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const path = require('path');
const {BundleAnalyzerPlugin} = require('webpack-bundle-analyzer');

require('dotenv').config();

console.log('NODE_ENV:', process.env.NODE_ENV);
console.log('__dirname: ', __dirname);

const outputDir = process.env.BUILD_OUTPUT_DIR;
const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    optimization: {
        splitChunks: false,
        runtimeChunk: false,
    },
    mode: process.env.NODE_ENV || 'development',
    devtool: isProduction ? false : 'eval-source-map',
    entry: path.resolve(__dirname, 'src/main.js'),
    output: {
        path: path.resolve(__dirname, outputDir),
        filename: 'bundle-[fullhash].js'
    },
    watch: process.env.NODE_ENV === 'development',
    watchOptions: {
        ignored: /node_modules/,
        aggregateTimeout: 300,
        poll: 1000
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader'],
            },
        ],
    },
    plugins: [
        new VueLoaderPlugin(),
        new CleanWebpackPlugin(),
        new webpack.DefinePlugin({
            __VUE_OPTIONS_API__: JSON.stringify(true),
            __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false)
        }),
        new BundleAnalyzerPlugin({
            analyzerMode: 'static',
            reportFilename: path.resolve(__dirname, 'bundle-report.html'),
            openAnalyzer: false,
        }),
    ],
    resolve: {
        alias: {
            vue$: 'vue/dist/vue.esm-bundler.js',
        },
        extensions: ['.js', '.vue', '.json'],
        fallback: {
            "fs": false,
            "path": require.resolve("path-browserify"),
        }
    },
};
