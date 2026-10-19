/**
 * Page heading block: 31px/800 title, muted subtitle, right-side actions slot.
 * Replaces the legacy Topbar (theme toggle now lives in the app bar).
 */
export default function PageHeader( { title, subtitle, actions } ) {
	return (
		<div className="flex items-end justify-between gap-4 flex-wrap mb-[22px]">
			<div>
				<h1 className="text-[31px] leading-[1.15] font-extrabold tracking-[-.025em] text-[#0f1115] dark:text-ink-text">
					{ title }
				</h1>
				{ subtitle ? (
					<p className="text-[14.5px] text-[#6c6c75] dark:text-ink-muted mt-[6px]">
						{ subtitle }
					</p>
				) : null }
			</div>
			{ actions ? (
				<div className="flex items-center gap-[10px]">{ actions }</div>
			) : null }
		</div>
	);
}
