/**
 * Loading spinner. `full` centers it in a tall flex container for route-level
 * Suspense / data fetching states.
 */
export default function Spinner( { full = false, label } ) {
	return (
		<div
			className={ `flex flex-col items-center justify-center gap-3 ${
				full ? 'min-h-[320px]' : 'py-6'
			}` }
		>
			<span className="w-8 h-8 rounded-full border-[3px] border-[#e4e7ec] dark:border-ink-border border-t-brand animate-ps-spin" />
			{ label ? (
				<span className="text-[13px] font-semibold text-[#71717a] dark:text-ink-muted">
					{ label }
				</span>
			) : null }
		</div>
	);
}
