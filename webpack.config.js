const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(__dirname, 'src', 'index.js'),
        frontend: path.resolve(__dirname, 'assets/js', 'frontend.js'),
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve?.alias,
            '@': path.resolve(__dirname, 'src'),
        },
    },
    // Enable source maps for development
    devtool: process.env.NODE_ENV === 'development' ? 'source-map' : false,
};