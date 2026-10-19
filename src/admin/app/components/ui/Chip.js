/**
 * Filter chip with optional count (mockup: active = solid brand, idle =
 * white with zinc border).
 */
export default function Chip( { active = false, onClick, count, children } ) {
	return (
		<button
			type="button"
			onClick={ onClick }
			className={ `flex items-center gap-[7px] py-[7px] px-[14px] rounded-full text-[13px] font-semibold cursor-pointer border transition-all duration-150 ${
				active
					? 'bg-brand text-white border-brand'
					: 'bg-white dark:bg-ink-card text-[#52525b] dark:text-ink-muted border-[#e2e2e6] dark:border-[#2a313c] hover:border-brand hover:text-brand'
			}` }
		>
			{ children }
			{ count !== undefined ? (
				<span className="text-[11px] font-bold opacity-70">
					{ count }
				</span>
			) : null }
		</button>
	);
}
