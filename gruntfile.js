module.exports = function (grunt) {
	require('jit-grunt')(grunt);

	grunt.initConfig({
		copy: {
			main: {
				// copy src/images, src/fonts, src/vendor to assets
				files: [
					{
						expand: true,
						cwd: 'src/images/',
						src: '**',
						dest: 'assets/images/',
					},
					{
						expand: true,
						cwd: 'src/fonts/',
						src: '**',
						dest: 'assets/fonts/',
					},
					{
						expand: true,
						cwd: 'src/vendor/',
						src: '**',
						dest: 'assets/vendor/',
					},
					// {
					// 	expand: false,
					// 	src: 'node_modules/uikit/dist/js/uikit.min.js',
					// 	dest: 'assets/js/bdt-uikit.min.js',
					// },
				],
			},
		},

		less: {
			development: {
				options: {
					compress: true,
					yuicompress: true,
					optimization: 2,
				},
				files: [
					{
						'assets/css/bdt-uikit.css': 'src/less/bdt-uikit.less',
						'assets/css/prime-slider-site.css': 'src/less/prime-slider-site.less',
						'assets/css/prime-slider-editor.css': 'src/less/prime-slider-editor.less',
						'assets/css/prime-slider-preview.css': 'src/less/prime-slider-preview.less',
						'assets/css/tippy.css': 'src/less/tippy.less',
						'assets/css/prime-slider-font.css': 'src/less/prime-slider-font.less',
						'assets/css/prime-slider-product-feed.css': 'src/less/prime-slider-product-feed.less',

						'admin/assets/css/ps-admin.css': 'src/less/admin.less',
					},
					// all widgets files
					{
						expand: true,
						cwd: 'src/less/widgets/',
						src: ['**/*.less', '!**/*.rtl.less'],
						dest: 'assets/css/',
						ext: '.css',
						rename: function (dest, src) {
							return dest + src.replace(/(.+)\.css$/, 'ps-$1.css');
						},
					},
				],
			},
		},

		terser: {
			options: {
				mangle: true,
			},
			my_target: {
				files: [
					{
						'assets/js/bdt-uikit.min.js': ['src/js/bdt-uikit.min.js'], // To-do: remove this file and use uikit from node_modules
						'assets/js/prime-slider-admin.min.js': ['src/js/prime-slider-admin.js'],
						'assets/js/prime-slider-editor.min.js': ['src/js/prime-slider-editor.js'],
						'assets/js/prime-slider-site.min.js': ['src/js/prime-slider-site.js'],
						'admin/assets/js/ps-admin.min.js': ['src/admin/js/ps-admin.js'],
					},
					{
						expand: true,
						cwd: 'src/js/modules/',
						src: ['**/*.js', '!src/js/**/*.min.js'],
						dest: 'assets/js/modules/',
						ext: '.min.js',
					},
				],
			},
		},

		rtlcss: {
			siteRTL: {
				// task options
				options: {
					// rtlcss options
					opts: {
						clean: true,
					},
					// rtlcss plugins
					plugins: [],
					// save unmodified files
					saveUnmodified: true,
					useCalc: true,
				},
				expand: true,
				cwd: 'assets/css/',
				dest: 'assets/css/',
				src: ['**/*.css', '!**/*.rtl.css'],
				ext: '.rtl.css',
			},
			adminRTL: {
				// task options
				options: {
					// rtlcss options
					opts: {
						clean: true,
					},
					// rtlcss plugins
					plugins: [],
					// save unmodified files
					saveUnmodified: true,
				},
				expand: true,
				cwd: 'admin/assets/css/',
				dest: 'admin/assets/css/',
				src: ['**/*.css', '!**/*.rtl.css'],
				ext: '.rtl.css',
			},
		},

		watch: {
			styles: {
				files: ['src/less/**/*.less'], // which files to watch
				tasks: ['less', 'rtlcss'],
				options: {
					nospawn: true,
				},
			},
			scripts: {
				files: ['src/js/**/*.js'], // which files to watch
				tasks: ['terser'],
				options: {
					spawn: false,
				},
			},
		},
	});

	grunt.loadNpmTasks('grunt-terser');
	grunt.loadNpmTasks('grunt-rtlcss');
	grunt.loadNpmTasks('grunt-contrib-copy');

	if (process.env.NODE_ENV === 'development') {
		grunt.registerTask('default', ['less', 'terser', 'copy', 'rtlcss', 'watch']);
	} else {
		grunt.registerTask('default', ['less', 'terser', 'copy', 'rtlcss']);
	}
};
