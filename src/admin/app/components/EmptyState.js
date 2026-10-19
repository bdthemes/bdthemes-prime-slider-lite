/**
 * Generic empty state placeholder (mockup no-results pattern).
 */
export default function EmptyState( { title, description, action } ) {
	return (
		<div className="text-center py-[60px] px-5">
			<div className="text-[15px] font-bold text-[#71717a] dark:text-ink-muted">
				{ title }
			</div>
			{ description ? (
				<p className="text-[13px] text-[#a1a1aa] mt-2">
					{ description }
				</p>
			) : null }
			{ action ? (
				<div className="mt-3 flex justify-center">{ action }</div>
			) : null }
		</div>
	);
}
