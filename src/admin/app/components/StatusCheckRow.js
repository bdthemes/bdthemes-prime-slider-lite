/**
 * Single pass/fail row for the system status report.
 */
import { __ } from '@wordpress/i18n';
import Badge from './ui/Badge';

export default function StatusCheckRow( { check: row } ) {
	return (
		<div className="flex items-center justify-between gap-6 py-[14px] px-7 max-[640px]:px-5 border-t border-[#f3f3f6] dark:border-ink-border transition-colors hover:bg-[#fbfbfd] dark:hover:bg-[#1a1f27]">
			<div className="flex-1 min-w-0">
				<div className="text-[14px] font-bold text-[#1a1a1f] dark:text-ink-text tracking-[-.01em]">
					{ row.label }
				</div>
				{ row.value && (
					<div className="text-[12.5px] text-[#6d6d74] dark:text-ink-muted mt-[2px]">
						{ row.value }
					</div>
				) }
				{ ! row.pass && row.recommendation && (
					<div className="text-[12.5px] font-medium text-[#b45309] dark:text-[#fdba74] mt-[3px]">
						{ row.recommendation }
					</div>
				) }
			</div>
			<Badge tone={ row.pass ? 'success' : 'danger' }>
				{ row.pass
					? __( 'Pass', 'bdthemes-prime-slider-lite' )
					: __( 'Fail', 'bdthemes-prime-slider-lite' ) }
			</Badge>
		</div>
	);
}
