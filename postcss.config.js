/**
 * PostCSS config for the React admin build (wp-scripts).
 *
 * Presence of this file replaces the default @wordpress/postcss-plugins-preset,
 * so autoprefixer is listed explicitly. The Grunt pipeline (LESS) does not use
 * PostCSS and is unaffected.
 */
module.exports = {
	plugins: {
		tailwindcss: {},
		autoprefixer: {},
	},
};
