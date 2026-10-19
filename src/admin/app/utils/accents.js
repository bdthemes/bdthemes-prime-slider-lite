/**
 * Category accent styles from the mockup palette. Complete class strings —
 * never build Tailwind classes by concatenation (they'd be purged).
 */
const ACCENTS = {
	violet: 'bg-[#f3f0ff] text-[#8b5cf6] dark:bg-[#241d3a]',
	// The brand accent (Element Pack's blue): Prime Slider orange on its tint.
	blue: 'bg-[#fff4ee] text-[#fc6a2a] dark:bg-[#2a1a12]',
	teal: 'bg-[#e6fffb] text-[#14b8a6] dark:bg-[#0e2b28]',
	rose: 'bg-[#fff1f3] text-[#f43f5e] dark:bg-[#2b141a]',
	amber: 'bg-[#fff7ed] text-[#f59e0b] dark:bg-[#2a1c0e]',
	woo: 'bg-[#f5f0fb] text-[#7f54b3] dark:text-[#c4a5ec] dark:bg-[#241d3a]',
	green: 'bg-[#ecfdf3] text-[#1a7d3d] dark:text-[#4ade80] dark:bg-[#0f2318]',
	indigo: 'bg-[#eef1ff] text-[#6366f1] dark:bg-[#1b1e3a]',
	sky: 'bg-[#eef6ff] text-[#0ea5e9] dark:bg-[#0e2334]',
};

/** Text-only accent colors (category labels etc.). */
const ACCENT_TEXT = {
	violet: 'text-[#8b5cf6]',
	blue: 'text-[#fc6a2a]',
	teal: 'text-[#14b8a6]',
	rose: 'text-[#f43f5e]',
	amber: 'text-[#f59e0b]',
	woo: 'text-[#7f54b3] dark:text-[#c4a5ec]',
	green: 'text-[#1a7d3d] dark:text-[#4ade80]',
	indigo: 'text-[#6366f1]',
	sky: 'text-[#0ea5e9]',
};

const ORDER = Object.keys( ACCENTS );

export function accentClass( name ) {
	return ACCENTS[ name ] || ACCENTS.blue;
}

export function accentTextClass( name ) {
	return ACCENT_TEXT[ name ] || ACCENT_TEXT.blue;
}

/**
 * Deterministic accent for an arbitrary slug/label so grids get stable,
 * varied tinting without per-item config.
 *
 * @param {string} text Any string (widget slug, category name…).
 * @return {string} Tailwind class string.
 */
export function accentFor( text ) {
	let hash = 0;
	const value = String( text || '' );
	for ( let i = 0; i < value.length; i++ ) {
		hash = ( hash * 31 + value.charCodeAt( i ) ) % 997;
	}
	return ACCENTS[ ORDER[ hash % ORDER.length ] ];
}

export default ACCENTS;
