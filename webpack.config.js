const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	// Add any custom webpack configurations here if needed
	// For example, if you need multiple entry points or specific loaders.
	// The default config handles src/index.js -> build/index.js
};