/**
 * Panel card (18px radius, subtle border + shadow) and SectionCard — a card
 * with the mockup's grouped-settings header: tinted icon tile, title, count.
 */
export function Card( { className = '', children, ...rest } ) {
	return (
		<div
			className={ `bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card shadow-resting ${ className }` }
			{ ...rest }
		>
			{ children }
		</div>
	);
}

export function SectionCard( {
	icon: SectionIcon,
	iconClassName = 'bg-[#fff4ee] text-brand dark:bg-[#2a1a12]',
	title,
	count,
	actions,
	className = '',
	children,
} ) {
	return (
		<Card className={ `overflow-hidden ${ className }` }>
			<div className="flex items-center gap-3 py-[18px] px-7 max-[640px]:px-5">
				{ SectionIcon ? (
					<span
						className={ `w-[38px] h-[38px] rounded-tile flex-none grid place-items-center ${ iconClassName }` }
					>
						<SectionIcon size={ 20 } />
					</span>
				) : null }
				<span className="text-base font-extrabold text-[#0f1115] dark:text-ink-text tracking-[-.01em]">
					{ title }
				</span>
				{ count !== undefined ? (
					<span className="text-[11px] font-extrabold text-[#6d6d74] dark:text-ink-muted bg-[#f4f4f6] dark:bg-ink-subtle py-[3px] px-[9px] rounded-full">
						{ count }
					</span>
				) : null }
				{ actions ? (
					<span className="ml-auto flex items-center gap-2">
						{ actions }
					</span>
				) : null }
			</div>
			{ children }
		</Card>
	);
}

export default Card;
