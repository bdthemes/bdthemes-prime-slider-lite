/**
 * Dashboard card: the latest releases from readme.txt as a timeline.
 */
import { __, sprintf } from '@wordpress/i18n';
import config from '../../utils/config';

const DOT_COLORS = [
	{ dot: '#22a550', ring: '#ecfdf3' },
	{ dot: '#fc6a2a', ring: '#fff4ee' },
	{ dot: '#8b5cf6', ring: '#f3f0ff' },
	{ dot: '#f59e0b', ring: '#fff7ed' },
	{ dot: '#14b8a6', ring: '#f0fdfa' },
];

const SECTION_TONES = {
	added: 'text-[#15803d] bg-[#ecfdf3] dark:bg-[#0f2318] dark:text-[#4ade80]',
	fixed: 'text-[#408efa] bg-[#eff6ff] dark:bg-[#15233a]',
	updated: 'text-[#b45309] bg-[#fff7ed] dark:bg-[#2a1c0e] dark:text-[#fdba74]',
	removed: 'text-[#be123c] bg-[#fff1f2] dark:bg-[#2a0e14] dark:text-[#fda4af]',
};

const SECTION_LABELS = {
	/* translators: %d: number of changelog entries */
	added: ( n ) => sprintf( __( '%d added', 'bdthemes-prime-slider-lite' ), n ),
	/* translators: %d: number of changelog entries */
	fixed: ( n ) => sprintf( __( '%d fixed', 'bdthemes-prime-slider-lite' ), n ),
	/* translators: %d: number of changelog entries */
	updated: ( n ) => sprintf( __( '%d updated', 'bdthemes-prime-slider-lite' ), n ),
	/* translators: %d: number of changelog entries */
	removed: ( n ) => sprintf( __( '%d removed', 'bdthemes-prime-slider-lite' ), n ),
};

export default function ActivityCard() {
	const releases = Array.isArray( config.releases ) ? config.releases : [];

	if ( ! releases.length ) {
		return null;
	}

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
			<div className="flex items-center justify-between mb-[14px]">
				<div className="text-[17px] font-extrabold text-[#0f1115] dark:text-ink-text">
					{ __( 'Recent activity', 'bdthemes-prime-slider-lite' ) }
				</div>
				<a href={ config.whatsNewUrl } target="_blank" rel="noreferrer" className="text-[12.5px] font-semibold text-brand">
					{ __( 'View all', 'bdthemes-prime-slider-lite' ) }
				</a>
			</div>
			<div className="flex flex-col">
				{ releases.map( ( release, index ) => {
					const colors = DOT_COLORS[ index % DOT_COLORS.length ];
					const isLast = index === releases.length - 1;
					return (
						<div key={ release.version } className="flex gap-[11px]">
							<span className="flex flex-col items-center flex-none">
								<span
									className="w-[9px] h-[9px] rounded-full flex-none mt-[5px]"
									style={ { background: colors.dot, boxShadow: `0 0 0 3px ${ colors.ring }` } }
								/>
								{ ! isLast ? <span className="w-px flex-1 mt-[6px] bg-[#ececf0] dark:bg-ink-border" /> : null }
							</span>
							<div className={ `min-w-0 flex-1 ${ isLast ? '' : 'pb-4' }` }>
								<div className="flex items-center gap-2 flex-wrap">
									<span className="text-[13px] font-extrabold text-[#18181b] dark:text-ink-text">v{ release.version }</span>
									{ release.is_current ? (
										<span className="text-[9.5px] font-extrabold tracking-[.05em] uppercase text-white bg-gradient-brand py-[2px] px-[7px] rounded-full">
											{ __( 'Current', 'bdthemes-prime-slider-lite' ) }
										</span>
									) : null }
									<span className="text-[11px] text-[#a1a1aa] font-medium ml-auto">{ release.date }</span>
								</div>
								{ release.highlights && release.highlights[ 0 ] ? (
									<div className="text-[12px] text-[#71717a] dark:text-ink-muted leading-[1.45] mt-[3px] overflow-hidden [display:-webkit-box] [-webkit-line-clamp:2] [-webkit-box-orient:vertical]">
										{ release.highlights[ 0 ] }
									</div>
								) : null }
								<div className="flex items-center gap-[5px] flex-wrap mt-[6px]">
									{ Object.entries( release.sections || {} ).map( ( [ section, count ] ) =>
										SECTION_LABELS[ section ] ? (
											<span key={ section } className={ `text-[10.5px] font-bold py-[2px] px-[8px] rounded-full ${ SECTION_TONES[ section ] }` }>
												{ SECTION_LABELS[ section ]( count ) }
											</span>
										) : null
									) }
								</div>
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
}
