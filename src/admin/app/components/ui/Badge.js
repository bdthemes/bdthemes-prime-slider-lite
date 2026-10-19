/**
 * Tiny status badges: PRO tag, status pills (mockup widget cards / list rows).
 */
export function ProBadge() {
	return (
		<span className="text-[9px] font-extrabold tracking-[.04em] text-[#1d4ed8] dark:text-[#93c5fd] bg-[rgba(252,106,42,.1)] py-[2px] px-[6px] rounded-full flex-none">
			PRO
		</span>
	);
}

const TONES = {
	success:
		'text-[#15803d] bg-[#ecfdf3] dark:text-[#4ade80] dark:bg-[#0f2318]',
	danger: 'text-[#e11d48] bg-[#fff1f3] dark:text-[#fda4af] dark:bg-[#2b141a]',
	warning:
		'text-[#b45309] bg-[#fff7ed] dark:text-[#fdba74] dark:bg-[#2a1c0e]',
	info: 'text-[#1d4ed8] bg-[#eff6ff] dark:text-[#93c5fd] dark:bg-[#15233a]',
	neutral:
		'text-[#71717a] bg-[#f4f4f6] dark:text-ink-muted dark:bg-ink-subtle',
};

export default function Badge( {
	tone = 'neutral',
	className = '',
	children,
} ) {
	return (
		<span
			className={ `text-[9.5px] font-extrabold py-[2px] px-2 rounded-full flex-none ${
				TONES[ tone ] || TONES.neutral
			} ${ className }` }
		>
			{ children }
		</span>
	);
}
