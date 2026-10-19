/**
 * Label + colored status dot + value row (mockup system-status card).
 * `tone` picks the dot/value color: good (green), warn (amber), bad (red).
 */
const TONES = {
	good: 'text-[#1a7d3d] dark:text-[#4ade80]',
	warn: 'text-[#f59e0b]',
	bad: 'text-[#f43f5e]',
};

const DOTS = {
	good: 'bg-[#22a550]',
	warn: 'bg-[#f59e0b]',
	bad: 'bg-[#f43f5e]',
};

export default function StatusDotRow( { label, value, tone = 'good' } ) {
	return (
		<div className="flex items-center justify-between py-2 border-t border-[#f1f1f4] dark:border-ink-border">
			<span className="text-[13px] text-[#52525b] dark:text-ink-muted">
				{ label }
			</span>
			<span
				className={ `text-[12px] font-bold flex items-center gap-[6px] ${
					TONES[ tone ] || TONES.good
				}` }
			>
				<span
					className={ `w-[7px] h-[7px] rounded-full ${
						DOTS[ tone ] || DOTS.good
					}` }
				/>
				{ value }
			</span>
		</div>
	);
}
