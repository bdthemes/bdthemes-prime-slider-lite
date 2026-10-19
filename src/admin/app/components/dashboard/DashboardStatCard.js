/**
 * Dashboard stat card — mockup Variant A stat row (index.html 286–299):
 * 18px radius, 40px icon tile top-left, factual pill top-right, 34px/900
 * value, 12.5px label, hover lift.
 */
import IconTile from '../ui/IconTile';

const PILL_TONES = {
	green: 'text-[#15803d] bg-[#ecfdf3] dark:bg-[#0f2318] dark:text-[#4ade80]',
	blue: 'text-[#fc6a2a] bg-[#eff6ff] dark:bg-[#15233a]',
	amber: 'text-[#b45309] bg-[#fff7ed] dark:bg-[#2a1c0e] dark:text-[#fdba74]',
	neutral: 'text-[#a1a1aa] bg-[#f4f4f5] dark:bg-ink-subtle',
};

export default function DashboardStatCard( {
	icon,
	accent,
	value,
	label,
	pill,
	pillTone = 'neutral',
} ) {
	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-[18px] shadow-resting transition-all duration-200 hover:-translate-y-[3px] hover:shadow-[0_12px_26px_-14px_rgba(0,0,0,.22)]">
			<div className="flex items-center justify-between">
				<IconTile icon={ icon } accent={ accent } size="2xl" iconSize={ 20 } />
				{ pill ? (
					<span
						className={ `text-[11.5px] font-bold py-[3px] px-2 rounded-full ${
							PILL_TONES[ pillTone ] || PILL_TONES.neutral
						}` }
					>
						{ pill }
					</span>
				) : null }
			</div>
			<div className="text-[34px] font-black tracking-[-.03em] mt-[14px] text-[#0f1115] dark:text-ink-text">
				{ value }
			</div>
			<div className="text-[12.5px] text-[#71717a] dark:text-ink-muted mt-[8px] font-medium">
				{ label }
			</div>
		</div>
	);
}
