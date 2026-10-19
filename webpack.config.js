/**
 * Webpack configuration for the Prime Slider React admin dashboard.
 *
 * Extends the default @wordpress/scripts config (Babel, SCSS, JSX, dependency
 * extraction) and only overrides entry/output, so the bundle is emitted to
 * /build/admin. The Grunt pipeline (src/ -> assets/) is untouched; this build
 * covers src/admin/app only.
 */

const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src/admin/app/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build/admin' ),
	},
};
