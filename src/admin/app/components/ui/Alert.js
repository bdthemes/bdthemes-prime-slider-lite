/**
 * Persistent inline notice (replaces wp Notice for errors/warnings).
 */
import { __ } from '@wordpress/i18n';
import { WarnIcon, XIcon } from './Icons';

const TONES = {
	error: 'bg-[#fff1f3] border-[#fbd5dc] text-[#9f1239] dark:bg-[#2b141a] dark:border-[#4a2430] dark:text-[#fda4af]',
	warning:
		'bg-[#fff7ed] border-[#fed7aa] text-[#9a3412] dark:bg-[#2a1c0e] dark:border-[#4a3419] dark:text-[#fdba74]',
	info: 'bg-[#eff6ff] border-[#dbeafe] text-[#1e40af] dark:bg-[#15233a] dark:border-[#1e3a5f] dark:text-[#93c5fd]',
	success:
		'bg-[#ecfdf3] border-[#c6f0d4] text-[#15803d] dark:bg-[#0f2318] dark:border-[#1d4030] dark:text-[#4ade80]',
};

export default function Alert( {
	tone = 'info',
	onDismiss,
	className = '',
	children,
} ) {
	return (
		<div
			className={ `flex items-start gap-3 border rounded-panel py-3 px-4 text-[13.5px] font-medium leading-relaxed ${
				TONES[ tone ] || TONES.info
			} ${ className }` }
		>
			<WarnIcon size={ 17 } className="flex-none mt-px" />
			<div className="flex-1 min-w-0">{ children }</div>
			{ onDismiss ? (
				<button
					type="button"
					onClick={ onDismiss }
					aria-label={ __( 'Dismiss', 'bdthemes-prime-slider-lite' ) }
					className="flex-none cursor-pointer opacity-60 hover:opacity-100"
				>
					<XIcon size={ 14 } />
				</button>
			) : null }
		</div>
	);
}
