/**
 * Settings row inside a SectionCard (port of Element Pack's FieldRow): label
 * (+ EDITED badge), muted description, and a control slot on the right.
 * Kept local to the White Label page; the shared UI kit has no FieldRow.
 */
import { __ } from '@wordpress/i18n';

/** Text input styling (Element Pack's INPUT_CLASS, Prime Slider focus ring). */
export const INPUT_CLASS =
	'w-full py-[10px] px-[14px] border border-[#e2e2e6] dark:border-[#2a313c] rounded-btn text-sm bg-white dark:bg-ink-card text-[#18181b] dark:text-ink-text outline-none placeholder:text-[#6d6d74] dark:placeholder:text-ink-muted focus:border-brand focus:shadow-[0_0_0_3px_rgba(252,106,42,.15)] disabled:opacity-60 disabled:cursor-not-allowed';

export default function FieldRow( {
	label,
	description,
	edited = false,
	children,
	stacked = false,
} ) {
	return (
		<div
			className={ `${
				stacked
					? 'flex flex-col gap-3'
					: 'flex flex-wrap items-center justify-between gap-x-6 gap-y-3'
			} py-[18px] px-7 max-[640px]:px-5 border-t border-[#f3f3f6] dark:border-ink-border transition-colors hover:bg-[#fbfbfd] dark:hover:bg-[#1a1f27]` }
		>
			{ /* The label keeps a readable width; a control too wide to sit
			     beside it on a narrow screen wraps below instead of
			     squeezing the label to one word per line. */ }
			<div className="flex-1 min-w-[200px]">
				<div className="flex items-center gap-[9px]">
					<span className="text-[15.5px] font-bold text-[#1a1a1f] dark:text-ink-text tracking-[-.01em]">
						{ label }
					</span>
					{ edited ? (
						<span className="text-[9.5px] font-extrabold tracking-[.05em] text-[#b45309] bg-[#fff7ed] dark:bg-[#2a1c0e] py-[2px] px-[7px] rounded-full">
							{ __( 'EDITED', 'bdthemes-prime-slider-lite' ) }
						</span>
					) : null }
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
