/**
 * Pill toggle switch (mockup spec: 46×26 track, 21px knob; sm: 40×23/18).
 * Track: brand when on, zinc when off. Both keep 3:1 against the card
 * (WCAG 1.4.11), so the off state is darker than a plain light grey.
 */
const SIZES = {
	md: {
		track: 'w-[46px] h-[26px]',
		knob: 'w-[21px] h-[21px]',
		travel: 'translate-x-[20px]',
	},
	sm: {
		track: 'w-10 h-[23px]',
		knob: 'w-[18px] h-[18px]',
		travel: 'translate-x-[17px]',
	},
};

export default function Toggle( {
	checked = false,
	onChange,
	size = 'md',
	disabled = false,
	label,
	describedBy,
} ) {
	const dims = SIZES[ size ] || SIZES.md;

	return (
		<button
			type="button"
			role="switch"
			aria-checked={ checked }
			aria-label={ label }
			aria-describedby={ describedBy }
			disabled={ disabled }
			onClick={ () => onChange && onChange( ! checked ) }
			className={ `relative ${
				dims.track
			} rounded-full border-0 cursor-pointer p-0 flex-none transition-colors duration-[250ms] disabled:opacity-50 disabled:cursor-not-allowed ${
				checked ? 'bg-brand' : 'bg-[#919194] dark:bg-[#60666f]'
			}` }
		>
			<span
				className={ `absolute top-[2.5px] left-[2.5px] ${
					dims.knob
				} rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,.3)] transition-transform duration-[250ms] ease-ps ${
					checked ? dims.travel : 'translate-x-0'
				}` }
			/>
		</button>
	);
}
