/**
 * Dashboard card: the first four server checks, linking to System Status.
 */
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import StatusDotRow from '../ui/StatusDotRow';
import config from '../../utils/config';

export default function SystemStatusCard() {
	const checks = Array.isArray( config.system ) ? config.system.slice( 0, 4 ) : [];

	if ( ! checks.length ) {
		return null;
	}

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
			<div className="flex items-center justify-between mb-[14px]">
				<div className="text-[17px] font-extrabold text-[#0f1115] dark:text-ink-text">
					{ __( 'System status', 'bdthemes-prime-slider-lite' ) }
				</div>
				<Link to="/system-status" className="text-[12px] font-bold text-brand">
					{ __( 'View full report', 'bdthemes-prime-slider-lite' ) }
				</Link>
			</div>
			{ checks.map( ( row ) => (
				<StatusDotRow
					key={ row.id }
					label={ row.label }
					value={ `${ __( 'Currently:', 'bdthemes-prime-slider-lite' ) } ${ row.value }` }
					tone={ row.pass ? 'good' : 'bad' }
				/>
			) ) }
		</div>
	);
}
