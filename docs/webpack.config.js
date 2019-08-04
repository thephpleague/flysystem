const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const PurgecssPlugin = require('purgecss-webpack-plugin');
const glob = require("glob-all");
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const path = require('path');
const ManifestPlugin = require('webpack-manifest-plugin');
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
    new OptimizeCssAssetsPlugin(),
    new ManifestPlugin({
        fileName: '../_data/manifest.yml',
        publicPath: '/dist/',
    }),
];

let isProd = process.env.NODE_ENV === 'production';

if (isProd) {
    plugins.push(new PurgecssPlugin({
        paths: glob.sync([
            path.join(__dirname, "_site/**/*.html"),
        ]),
        extractors: [{
            extractor: TailwindExtractor,
            extensions: ["html"]
        }]
    }));
}

module.exports = {
    mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
    entry: {
        docs: './index.js'
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
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            hmr: process.env.NODE_ENV === 'development',
                        },
                    },
                    'css-loader',
                    'postcss-loader',
                ]
            }
        ]
    },
    plugins: plugins
};
