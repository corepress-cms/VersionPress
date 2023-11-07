const path = require('path');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserPlugin = require("terser-webpack-plugin");

module.exports = () => {
    let config = {
        mode: 'production',
        entry: {
            app: path.join(process.cwd(), './src/main.tsx'),
        },
        output: {
            publicPath: './',
        },
        optimization: {
            minimizer: [
                new CssMinimizerPlugin({}),
                new TerserPlugin({
                  parallel: true,
                }),
            ],

        },
    };

    return require('./webpack.base')(false, config);
};
