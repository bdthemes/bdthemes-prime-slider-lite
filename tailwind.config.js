/**
 * Tailwind config for the Prime Slider React admin dashboard (src/admin/app).
 *
 * - `important: '#ps-admin-app'` scopes every utility under the app's mount
 *   node so nothing leaks into the rest of wp-admin. Utilities placed on the
 *   mount node itself will not match; the app renders an inner `.ps-root`
 *   wrapper that carries the root-level utilities.
 * - Preflight is disabled (a global reset would restyle wp-admin); a scoped
 *   mini-reset lives in src/admin/app/styles/index.scss.
 * - Layout and design tokens follow the Element Pack dashboard; the accent is
 *   Prime Slider's own gradient.
 */
module.exports = {
	content: [ './src/admin/app/**/*.js' ],
	important: '#ps-admin-app',
	corePlugins: { preflight: false },
	darkMode: [ 'selector', '[data-ps-theme="dark"]' ],
	theme: {
		extend: {
			colors: {
				brand: {
					DEFAULT: '#fc6a2a',
					pink: '#fe506c',
					soft: '#fff1ea',
				},
				ink: {
					page: '#0b0e13',
					card: '#161a21',
					subtle: '#12161c',
					border: '#262c36',
					text: '#edeef0',
					muted: '#9aa0a8',
				},
			},
			fontFamily: {
				sans: [
					'"IBM Plex Sans"',
					'system-ui',
					'-apple-system',
					'BlinkMacSystemFont',
					'"Segoe UI"',
					'Roboto',
					'sans-serif',
				],
			},
			borderRadius: {
				btn: '10px',
				tile: '11px',
				panel: '16px',
				card: '18px',
				sheet: '22px',
			},
			boxShadow: {
				// Cards render flat (border only), as in Element Pack.
				resting: 'none',
				card: 'none',
				pop: '0 24px 60px -16px rgba(0,0,0,.32)',
				sheet: '0 30px 80px -20px rgba(0,0,0,.6)',
				glow: '0 6px 18px rgba(252,106,42,.32)',
				'glow-lg': '0 8px 24px rgba(252,106,42,.44)',
			},
			backgroundImage: {
				'gradient-brand': 'linear-gradient(135deg,#fe506c,#fc6a2a)',
			},
			transitionTimingFunction: {
				ps: 'cubic-bezier(.16,1,.3,1)',
			},
			keyframes: {
				psPop: {
					from: { opacity: '0', transform: 'translateY(6px) scale(.98)' },
					to: { opacity: '1', transform: 'none' },
				},
				psFadeUp: {
					from: { opacity: '0', transform: 'translateY(10px)' },
					to: { opacity: '1', transform: 'none' },
				},
				psSpin: {
					to: { transform: 'rotate(360deg)' },
				},
				psShimmer: {
					from: { backgroundPosition: '200% 0' },
					to: { backgroundPosition: '-200% 0' },
				},
			},
			animation: {
				'ps-pop': 'psPop .25s cubic-bezier(.16,1,.3,1) both',
				'ps-fade-up': 'psFadeUp .34s cubic-bezier(.16,1,.3,1) both',
				'ps-spin': 'psSpin .8s linear infinite',
				'ps-shimmer': 'psShimmer 1.6s linear infinite',
			},
		},
	},
};
