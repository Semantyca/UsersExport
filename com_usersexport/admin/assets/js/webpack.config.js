const { VueLoaderPlugin } = require('vue-loader');
const webpack = require('webpack');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const path = require('path');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

require('dotenv').config();

console.log('NODE_ENV:', process.env.NODE_ENV);
console.log('__dirname: ', __dirname);

const outputDir = process.env.BUILD_OUTPUT_DIR || 'dist';
const isProduction = process.env.NODE_ENV === 'production';

const plugins = [
    new VueLoaderPlugin(),
    new CleanWebpackPlugin(),
    new webpack.DefinePlugin({
        __VUE_OPTIONS_API__: JSON.stringify(true),
        __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false)
    }),
    new WebpackManifestPlugin({
        fileName: 'manifest.json',
        publicPath: '', // You can set your public path here
        writeToFileEmit: true,
    }),
];

console.log('Output directory:', path.resolve(__dirname, outputDir));

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
        filename: 'bundle-[fullhash].js',
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
    plugins: plugins,
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
    plugins: plugins.concat([
        function() {
            this.hooks.done.tap('BuildInfoPlugin', (stats) => {
                console.log('Build complete.');
                console.log('Assets generated:');
                const assets = stats.toJson().assets.map(asset => asset.name);
                assets.forEach(asset => console.log(asset));
            });
        }
    ])
};
