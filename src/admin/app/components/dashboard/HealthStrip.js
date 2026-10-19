/**
 * Performance-health strip — mockup Variant A (index.html 300–316). Four
 * 16px-radius cards: 34px icon tile, 22px/900 value, 6px progress bar, sub.
 * Values are real activation/usage ratios; bar colors are data-driven.
 */
import IconTile from '../ui/IconTile';
import ProgressBar from '../ui/ProgressBar';

export default function HealthStrip( { metrics } ) {
	// Usage tracking adds a fourth metric; without it three cards should fill
	// the row rather than leave a gap.
	const columns =
		metrics.length === 3 ? 'grid-cols-3' : 'grid-cols-4';

	return (
		<div
			className={ `grid ${ columns } max-[1024px]:grid-cols-2 max-[640px]:grid-cols-1 gap-[14px] mt-[14px]` }
		>
			{ metrics.map( ( metric ) => (
				<div
					key={ metric.label }
					className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-panel py-4 px-[18px] shadow-resting"
				>
					<div className="flex items-center gap-[10px]">
						<IconTile
							icon={ metric.icon }
							accent={ metric.accent }
							size="md"
						/>
						<div className="min-w-0">
							<div className="text-[22px] font-black tracking-[-.02em] text-[#0f1115] dark:text-ink-text leading-none">
								{ metric.value }
							</div>
							<div className="text-xs font-semibold text-[#52525b] dark:text-ink-muted mt-[3px]">
								{ metric.label }
							</div>
						</div>
					</div>
					<ProgressBar
						percent={ metric.percent }
						fill={ metric.barColor }
						height={ 6 }
						className="mt-[13px]"
					/>
					<div className="text-[11px] text-[#a1a1aa] dark:text-ink-muted mt-[7px]">
						{ metric.sub }
					</div>
				</div>
			) ) }
		</div>
	);
}
