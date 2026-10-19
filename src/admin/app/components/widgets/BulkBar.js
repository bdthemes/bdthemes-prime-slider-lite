/**
 * Toolbar row for module pages: green active-count pill + bulk
 * enable/disable buttons (mockup extensions toolbar). Save/reset lives in
 * the shared sticky SaveBar rendered by ModulesPage.
 *
 * The buttons act on the rows the filters currently leave on screen. When that
 * is a subset, `scopeCount` says how many, so "Enable all" never silently
 * means more than the list shows.
 */
import { __, sprintf } from '@wordpress/i18n';
import Button from '../ui/Button';

export default function BulkBar( {
	activeCount,
	total,
	scopeCount = null,
	isSaving,
	onActivateAll,
	onDeactivateAll,
} ) {
	const scoped = typeof scopeCount === 'number';
	const scopeTitle = scoped
		? sprintf(
				/* translators: %d: number of widgets matching the filters */
				__(
					'Applies to the %d items matching your filters.',
					'bdthemes-prime-slider-lite'
				),
				scopeCount
		  )
		: undefined;
	const count = scoped ? (
		<span className="text-[11px] font-extrabold opacity-90">
			{ scopeCount }
		</span>
	) : null;

	return (
		<div className="flex items-center gap-[9px] flex-wrap">
			<span className="flex items-center gap-[7px] text-[13px] font-bold text-[#15803d] dark:text-[#4ade80] bg-[#ecfdf3] dark:bg-[#0f2318] border border-[#c6f0d4] dark:border-[#1d4030] py-2 px-[13px] rounded-full">
				<span className="w-2 h-2 rounded-full bg-[#22c55e] shadow-[0_0_0_3px_rgba(34,197,94,.2)]" />
				{ sprintf(
					/* translators: 1: active count, 2: total count */
					__( '%1$d of %2$d active', 'bdthemes-prime-slider-lite' ),
					activeCount,
					total
				) }
			</span>
			<Button
				variant="outline"
				size="sm"
				onClick={ onActivateAll }
				disabled={ isSaving || scopeCount === 0 }
				title={ scopeTitle }
			>
				{ scoped
					? __( 'Enable filtered', 'bdthemes-prime-slider-lite' )
					: __( 'Enable all', 'bdthemes-prime-slider-lite' ) }
				{ count }
			</Button>
			<Button
				variant="danger"
				size="sm"
				onClick={ onDeactivateAll }
				disabled={ isSaving || scopeCount === 0 }
				title={ scopeTitle }
			>
				{ scoped
					? __( 'Disable filtered', 'bdthemes-prime-slider-lite' )
					: __( 'Disable all', 'bdthemes-prime-slider-lite' ) }
				{ count }
			</Button>
		</div>
	);
}
