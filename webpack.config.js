const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const path = require('path');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const isProduction = process.env.NODE_ENV === 'production';
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

class TailwindExtractor {
    static extract(content) {
        return content.match(/[A-z0-9-:\/]+/g);
    }
}

let plugins = [
    new CleanWebpackPlugin(),
    new MiniCssExtractPlugin({
        filename: isProduction ? 'styles.[hash].css' : 'styles.css'
    }),
    new WebpackManifestPlugin({
        fileName: '../_data/manifest.yml',
        publicPath: '/dist/',
    }),
];

let isProd = process.env.NODE_ENV === 'production';

module.exports = {
    mode: isProd ? 'production' : 'development',
    entry: {
        docs: './assets/index.js'
    },
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: isProduction ? '[name].[hash].js' : '[name].js',
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader',
                ]
            }
        ]
    },
    optimization: {
        minimizer: [
            `...`,
            new CssMinimizerPlugin(),
        ]
    },
    watchOptions: {
        ignored: ['**/node_modules/', '/_site/'],
    },
    plugins: plugins
};
