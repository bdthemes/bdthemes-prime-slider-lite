/**
 * Filter toolbar for module tier pages.
 *
 * Row 1: search on the left; joined segmented controls (All / Free / Pro,
 * plus usage when tracking is on) on the right — mockup's segmented-pill
 * pattern. Row 2: category chips.
 */
import { __ } from '@wordpress/i18n';
import SearchInput from '../ui/SearchInput';
import Chip from '../ui/Chip';

function Segmented( { value, onChange, options } ) {
	return (
		<div className="flex items-center gap-[2px] bg-[#f4f5f7] dark:bg-ink-subtle border border-[#e6e6ea] dark:border-ink-border rounded-full p-1">
			{ options.map( ( option ) => {
				const active = value === option.value;
				return (
					<button
						key={ option.value }
						type="button"
						onClick={ () => onChange( option.value ) }
						className={ `flex items-center gap-[6px] border-0 rounded-full py-[7px] px-[14px] font-sans text-[12.5px] cursor-pointer transition-colors ${
							active
								? 'bg-brand text-white font-bold shadow-[0_2px_8px_rgba(252,106,42,.35)]'
								: 'bg-transparent text-[#71717a] dark:text-ink-muted font-semibold hover:text-[#18181b] dark:hover:text-ink-text'
						}` }
					>
						{ option.label }
						{ option.count !== undefined ? (
							<span
								className={ `text-[10.5px] font-extrabold ${
									active
										? 'text-white/80'
										: 'text-[#a1a1aa]'
								}` }
							>
								{ option.count }
							</span>
						) : null }
					</button>
				);
			} ) }
		</div>
	);
}

export default function FilterBar( {
	search,
	onSearch,
	searchPlaceholder,
	type,
	onType,
	usage,
	onUsage,
	showUsage,
	counts = {},
	categories,
	category,
	onCategory,
} ) {
	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card mb-[18px] overflow-hidden">
			<div className="flex items-center justify-between gap-3 flex-wrap py-[13px] px-[16px]">
				<SearchInput
					value={ search }
					onChange={ onSearch }
					placeholder={ searchPlaceholder }
					className="flex-1 min-w-[220px] max-w-[380px]"
				/>

				<div className="flex items-center gap-[10px] flex-wrap">
					<Segmented
						value={ type }
						onChange={ onType }
						options={ [
							{
								value: 'all',
								label: __( 'All', 'bdthemes-prime-slider-lite' ),
								count: counts.all,
							},
							// Only when the tier actually has new widgets.
							...( counts.new
								? [
										{
											value: 'new',
											label: __(
												'New',
												'bdthemes-prime-slider-lite'
											),
											count: counts.new,
										},
								  ]
								: [] ),
							{
								value: 'free',
								label: __( 'Free', 'bdthemes-prime-slider-lite' ),
								count: counts.free,
							},
							{
								value: 'pro',
								label: __( 'Pro', 'bdthemes-prime-slider-lite' ),
								count: counts.pro,
							},
						] }
					/>

					{ showUsage ? (
						<Segmented
							value={ usage }
							onChange={ onUsage }
							options={ [
								{
									value: 'all',
									label: __(
										'Any usage',
										'bdthemes-prime-slider-lite'
									),
								},
								{
									value: 'used',
									label: __(
										'Used',
										'bdthemes-prime-slider-lite'
									),
									count: counts.used,
								},
								{
									value: 'unused',
									label: __(
										'Unused',
										'bdthemes-prime-slider-lite'
									),
									count: counts.unused,
								},
							] }
						/>
					) : null }
				</div>
			</div>

			{ categories && categories.length > 0 ? (
				<div className="flex gap-2 flex-wrap items-center py-[11px] px-[16px] border-t border-[#f1f1f4] dark:border-ink-border bg-[#fafbfc] dark:bg-ink-subtle">
					<Chip
						active={ category === 'all' }
						onClick={ () => onCategory( 'all' ) }
					>
						{ __( 'All categories', 'bdthemes-prime-slider-lite' ) }
					</Chip>
					{ categories.map( ( cat ) => (
						<Chip
							key={ cat.key }
							active={ category === cat.key }
							onClick={ () => onCategory( cat.key ) }
							count={ cat.count }
						>
							{ cat.label }
						</Chip>
					) ) }
				</div>
			) : null }
		</div>
	);
}
