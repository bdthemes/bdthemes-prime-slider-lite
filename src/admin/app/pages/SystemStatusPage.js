/**
 * System Status: widget usage counts per section and the server checks the
 * classic dashboard showed (PHP, limits, uploads, GZip, multisite, debug).
 */
import { __, sprintf } from '@wordpress/i18n';
import PageHeader from '../components/shell/PageHeader';
import IconTile from '../components/ui/IconTile';
import ProgressBar from '../components/ui/ProgressBar';
import Alert from '../components/ui/Alert';
import { GridIcon, PlugIcon, ZapIcon, ServerIcon, CheckIcon, WarnIcon } from '../components/ui/Icons';
import config, { sectionFields } from '../utils/config';

function usageFor( ids ) {
	const fields = ids.flatMap( ( id ) => sectionFields( id ) );
	const used = fields.filter( ( f ) => f.usage_count > 0 ).length;
	const active = fields.filter( ( f ) => f.enabled ).length;
	return { total: fields.length, used, unused: fields.length - used, active };
}

function UsageCard( { title, icon, accent, data, color } ) {
	const percent = data.total ? Math.round( ( data.used / data.total ) * 100 ) : 0;
	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5">
			<div className="flex items-center gap-3">
				<IconTile icon={ icon } accent={ accent } size="md" />
				<div className="text-[15px] font-extrabold text-[#0f1115] dark:text-ink-text">{ title }</div>
			</div>
			<div className="text-[30px] font-black tracking-[-.03em] mt-3 text-[#0f1115] dark:text-ink-text">
				{ data.used }
				<span className="text-[15px] font-bold text-[#a1a1aa]"> / { data.total }</span>
			</div>
			<div className="text-[12px] text-[#71717a] dark:text-ink-muted font-medium">
				{ __( 'widgets in use on your site', 'bdthemes-prime-slider-lite' ) }
			</div>
			<ProgressBar percent={ percent } fill={ color } height={ 6 } className="mt-3" />
			<div className="flex justify-between text-[11.5px] text-[#a1a1aa] mt-2">
				<span>
					{ sprintf(
						/* translators: %d: number of unused widgets */
						__( '%d unused', 'bdthemes-prime-slider-lite' ),
						data.unused
					) }
				</span>
				<span>
					{ sprintf(
						/* translators: %d: number of enabled widgets */
						__( '%d enabled', 'bdthemes-prime-slider-lite' ),
						data.active
					) }
				</span>
			</div>
		</div>
	);
}

export default function SystemStatusPage() {
	const checks = Array.isArray( config.system ) ? config.system : [];
	const failing = checks.filter( ( c ) => ! c.pass ).length;
	const hasUsage = sectionFields( 'prime_slider_active_modules' ).some( ( f ) => f.has_usage );

	return (
		<div>
			<PageHeader
				title={ __( 'System Status', 'bdthemes-prime-slider-lite' ) }
				subtitle={
					failing
						? sprintf(
								/* translators: %d: number of failing checks */
								__( '%d checks need attention.', 'bdthemes-prime-slider-lite' ),
								failing
						  )
						: __( 'Your server meets every recommendation.', 'bdthemes-prime-slider-lite' )
				}
			/>

			<div className="grid grid-cols-3 max-[1024px]:grid-cols-1 gap-4">
				<UsageCard
					title={ __( 'All widgets', 'bdthemes-prime-slider-lite' ) }
					icon={ GridIcon }
					accent="amber"
					color="#f59e0b"
					data={ usageFor( [ 'prime_slider_active_modules', 'prime_slider_third_party_widget' ] ) }
				/>
				<UsageCard
					title={ __( 'Core widgets', 'bdthemes-prime-slider-lite' ) }
					icon={ ZapIcon }
					accent="rose"
					color="#fe506c"
					data={ usageFor( [ 'prime_slider_active_modules' ] ) }
				/>
				<UsageCard
					title={ __( '3rd party widgets', 'bdthemes-prime-slider-lite' ) }
					icon={ PlugIcon }
					accent="green"
					color="#22a550"
					data={ usageFor( [ 'prime_slider_third_party_widget' ] ) }
				/>
			</div>

			{ ! hasUsage ? (
				<Alert tone="warning" className="mt-4">
					{ __( 'Usage counts come from Elementor’s usage data, which is not available on this site.', 'bdthemes-prime-slider-lite' ) }
				</Alert>
			) : null }

			<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card mt-4 overflow-hidden">
				<div className="flex items-center gap-3 py-[18px] px-6">
					<IconTile icon={ ServerIcon } accent="blue" size="xl" />
					<span className="text-base font-extrabold text-[#0f1115] dark:text-ink-text">
						{ __( 'System requirements', 'bdthemes-prime-slider-lite' ) }
					</span>
				</div>
				<div className="grid grid-cols-2 max-[900px]:grid-cols-1 border-t border-[#f1f1f4] dark:border-ink-border">
					{ checks.map( ( check ) => (
						<div
							key={ check.id }
							className="flex items-center justify-between gap-3 py-[14px] px-6 border-b border-[#f1f1f4] dark:border-ink-border"
						>
							<span className="text-[13.5px] text-[#3f3f46] dark:text-ink-text font-medium">{ check.label }</span>
							<span
								className={ `flex items-center gap-[7px] text-[13px] font-bold ${
									check.pass ? 'text-[#15803d] dark:text-[#4ade80]' : 'text-[#e11d48] dark:text-[#fda4af]'
								}` }
								title={ check.pass ? undefined : check.hint }
							>
								<span
									className={ `w-5 h-5 rounded-full grid place-items-center text-white ${
										check.pass ? 'bg-[#22a550]' : 'bg-[#f43f5e]'
									}` }
								>
									{ check.pass ? <CheckIcon size={ 12 } strokeWidth={ 3 } /> : <WarnIcon size={ 12 } strokeWidth={ 2.6 } /> }
								</span>
								{ check.value }
								{ ! check.pass && check.hint ? (
									<span className="text-[11.5px] font-semibold text-[#a1a1aa]">({ check.hint })</span>
								) : null }
							</span>
						</div>
					) ) }
				</div>
				<p className="text-[12.5px] text-[#71717a] dark:text-ink-muted py-4 px-6">
					{ __( 'If you use several Elementor add-ons alongside Prime Slider, you may need to allocate more memory to them.', 'bdthemes-prime-slider-lite' ) }
				</p>
			</div>
		</div>
	);
}
