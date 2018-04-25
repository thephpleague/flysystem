const ExtractTextPlugin = require('extract-text-webpack-plugin');
const PurgecssPlugin = require('purgecss-webpack-plugin');
const glob = require('glob-all');
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const path = require('path');

class TailwindExtractor {
    static extract(content) {
        return content.match(/[A-z0-9-:\/]+/g);
    }
}

let plugins = [
    new ExtractTextPlugin('styles.css'),
    new OptimizeCssAssetsPlugin(),
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
    entry: './index.js',
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: '[name].js',
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [
                        { loader: 'css-loader', options: { importLoaders: 1 } },
                        'postcss-loader'
                    ]
                })
            }
        ]
    },
    plugins: plugins,
};