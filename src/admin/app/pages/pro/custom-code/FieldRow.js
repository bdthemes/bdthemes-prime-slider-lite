/**
 * Settings row inside a SectionCard, ported from Element Pack's FieldRow:
 * label, muted description, and a control slot (to the right, or below when
 * `stacked`).
 */
export default function FieldRow( { label, description, children, stacked = false } ) {
	return (
		<div
			className={ `${
				stacked
					? 'flex flex-col gap-3'
					: 'flex flex-wrap items-center justify-between gap-x-6 gap-y-3'
			} py-[18px] px-7 max-[640px]:px-5 border-t border-[#f3f3f6] dark:border-ink-border transition-colors hover:bg-[#fbfbfd] dark:hover:bg-[#1a1f27]` }
		>
			<div className="flex-1 min-w-[200px]">
				<div className="flex items-center gap-[9px]">
					<span className="text-[15.5px] font-bold text-[#1a1a1f] dark:text-ink-text tracking-[-.01em]">
						{ label }
					</span>
				</div>
				{ description ? (
					<div className="ps-rich-desc text-[13px] text-[#6d6d74] dark:text-ink-muted mt-[3px]">
						{ description }
					</div>
				) : null }
			</div>
			{ children ? (
				<div className={ stacked ? '' : 'flex-none' }>{ children }</div>
			) : null }
		</div>
	);
}
