/**
 * Pill toggle switch (mockup spec: 46×26 track, 21px knob; sm: 40×23/18).
 * Track: brand blue when on, zinc when off.
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
} ) {
	const dims = SIZES[ size ] || SIZES.md;

	return (
		<button
			type="button"
			role="switch"
			aria-checked={ checked }
			aria-label={ label }
			disabled={ disabled }
			onClick={ () => onChange && onChange( ! checked ) }
			className={ `relative ${
				dims.track
			} rounded-full border-0 cursor-pointer p-0 flex-none transition-colors duration-[250ms] disabled:opacity-50 disabled:cursor-not-allowed ${
				checked ? 'bg-brand' : 'bg-[#d4d4d8] dark:bg-[#3a414c]'
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
