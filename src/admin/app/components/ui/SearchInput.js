/**
 * Search input with icon prefix and brand focus ring (mockup lines 741–746).
 */
import { __ } from '@wordpress/i18n';
import { SearchIcon, XIcon } from './Icons';

export default function SearchInput( {
	value,
	onChange,
	placeholder,
	className = '',
} ) {
	return (
		<div className={ `relative ${ className }` }>
			<span className="absolute left-[14px] top-1/2 -translate-y-1/2 text-[#6d6d74] dark:text-ink-muted pointer-events-none">
				<SearchIcon size={ 16 } />
			</span>
			<input
				type="text"
				value={ value || '' }
				onChange={ ( event ) => onChange( event.target.value ) }
				placeholder={ placeholder }
				className="w-full py-[11px] pr-9 pl-10 border border-[#e2e2e6] dark:border-[#2a313c] rounded-full text-sm bg-white dark:bg-ink-card text-[#18181b] dark:text-ink-text outline-none transition-shadow placeholder:text-[#6d6d74] dark:placeholder:text-ink-muted focus:border-brand focus:shadow-[0_0_0_3px_rgba(252,106,42,.15)]"
			/>
			{ value ? (
				<button
					type="button"
					onClick={ () => onChange( '' ) }
					aria-label={ __( 'Clear search', 'bdthemes-prime-slider-lite' ) }
					className="absolute right-[10px] top-1/2 -translate-y-1/2 text-[#6d6d74] dark:text-ink-muted cursor-pointer hover:text-[#52525b] dark:hover:text-ink-text"
				>
					<XIcon size={ 14 } />
				</button>
			) : null }
		</div>
	);
}
