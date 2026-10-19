/**
 * Button primitive matching the mockup's button styles.
 *
 * Variants:
 * - gradient: brand gradient CTA with glow
 * - outline:  white card button, brand accent on hover
 * - danger:   outline that turns rose on hover
 * - ghost:    borderless text button
 */
const VARIANTS = {
	gradient:
		'bg-gradient-brand text-white font-extrabold shadow-glow hover:-translate-y-px hover:shadow-glow-lg border-0',
	outline:
		'bg-white dark:bg-ink-card border border-[#e2e2e6] dark:border-[#2a313c] text-[#3f3f46] dark:text-[#cdd0d6] font-bold hover:border-brand hover:text-brand',
	danger: 'bg-white dark:bg-ink-card border border-[#e2e2e6] dark:border-[#2a313c] text-[#3f3f46] dark:text-[#cdd0d6] font-bold hover:border-[#f43f5e] hover:text-[#e11d48]',
	'danger-solid':
		'bg-white dark:bg-ink-card border-[1.5px] border-[#f43f5e] text-[#e11d48] font-extrabold hover:bg-[#e11d48] hover:text-white',
	ghost: 'bg-transparent border-0 text-brand font-bold hover:underline p-0',
};

const SIZES = {
	sm: 'py-2 px-[14px] text-[13px]',
	md: 'py-[10px] px-[15px] text-[13px]',
	lg: 'py-3 px-[22px] text-[13.5px]',
};

export default function Button( {
	variant = 'outline',
	size = 'md',
	icon: ButtonIcon,
	iconSize = 15,
	className = '',
	children,
	type = 'button',
	...rest
} ) {
	const sizing = variant === 'ghost' ? '' : SIZES[ size ] || SIZES.md;

	return (
		<button
			type={ type }
			className={ `inline-flex items-center justify-center gap-[7px] rounded-full cursor-pointer transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed ${
				VARIANTS[ variant ] || VARIANTS.outline
			} ${ sizing } ${ className }` }
			{ ...rest }
		>
			{ ButtonIcon ? <ButtonIcon size={ iconSize } /> : null }
			{ children }
		</button>
	);
}
