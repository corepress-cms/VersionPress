const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const webpack = require('webpack');
const autoprefixer = require('autoprefixer');

const baseDir = process.cwd();

const postCssLoader = {
    loader: 'postcss-loader',
    options: {
      postcssOptions: {
        plugins: [autoprefixer({})],
      }
    },
};

module.exports = (isDevelopment, options) => ({
    mode: options.mode,
    optimization: {...options.optimization, moduleIds: 'named'},
    entry: options.entry,
    output: {
      hashFunction: 'sha256',
        filename: '[name].js',
        path: path.join(baseDir, 'build'),
        publicPath: options.output && options.output.publicPath || '/static/',
    },
    cache: isDevelopment,
    devtool: 'source-map',
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].css'
        }),
        new webpack.IgnorePlugin({
          resourceRegExp: /^\.\/locale$/,
          contextRegExp: /moment$/
        }),
    ].concat(isDevelopment
        ?  [
            new webpack.HotModuleReplacementPlugin(),
        ] : []
    ).concat(options.plugins || []),
    resolve: {
        extensions: ['.ts', '.tsx', '.js'],
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules/,
                use: isDevelopment
                    ? [
                          {
                              loader: 'babel-loader',
                              options: {
                                  babelrc: true,
                              },
                          },
                          'ts-loader',
                    ]
                    : ['ts-loader'],
            },
            {
                test: /\.woff2?$|\.ttf$|\.eot$/,
                use: [{
                    loader: 'file-loader',
                    options: {
                        name: 'fonts/[name].[ext]',
                    },
                }],
            },
            {
                test: /\.(gif|jpg|png|svg)$/,
                use: [{
                    loader: 'file-loader',
                    options: {
                        name: 'images/[name].[ext]',
                    },
                }],
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader'],
            },
            {
                test: /\.less$/,
                use: [
                    isDevelopment ? {
                      loader: 'style-loader',
                    } : MiniCssExtractPlugin.loader,
                    {
                      loader: 'css-loader',
                      options: {
                          sourceMap: true,
                      },
                    },
                    postCssLoader,
                    {
                        loader: 'less-loader',
                        options: {
                            sourceMap: true,
                        },
                    },
                ],
            },
        ].concat(options.module && options.module.rules || []),
    },
    devServer: options.devServer,
});
