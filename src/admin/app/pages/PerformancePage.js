/**
 * Asset Optimization (the `asset-manager` switch of the Special Features
 * section), laid out like Element Pack's Performance page: a feature card with
 * the switch, and a status strip of quick facts along the bottom.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSection } from '../hooks/useSection';
import PageHeader from '../components/shell/PageHeader';
import Toggle from '../components/ui/Toggle';
import Alert from '../components/ui/Alert';
import SaveBar from '../components/ui/SaveBar';
import EmptyState from '../components/EmptyState';
import { useToast } from '../components/ui/Toast';
import { GaugeIcon } from '../components/ui/Icons';
import { accentClass } from '../utils/accents';
import config from '../utils/config';

const SECTION = 'prime_slider_other_settings';
export const ASSET_FIELD = 'asset-manager';

function Fact( { label, children } ) {
	return (
		<div className="py-[14px] px-6 max-[640px]:px-5">
			<div className="text-[10.5px] font-extrabold tracking-[.1em] uppercase text-[#a1a1aa]">{ label }</div>
			{ children }
		</div>
	);
}

export default function PerformancePage() {
	const { fields, draft, toggle, isDirty, save, reset, isSaving, error } = useSection( SECTION );
	const showToast = useToast();
	const field = fields.find( ( f ) => f.name === ASSET_FIELD );
	const assets = config.assets || {};
	// The saved state, which is what the site serves until changes are saved.
	const active = field ? !! field.enabled : !! assets.enabled;
	const files = ( Number( assets.css ) || 0 ) + ( Number( assets.js ) || 0 );

	const onSave = async () => {
		try {
			await save();
			showToast( __( 'Performance settings saved.', 'bdthemes-prime-slider-lite' ) );
		} catch ( e ) {
			showToast( e.message, { tone: 'error' } );
		}
	};

	return (
		<div>
			<PageHeader
				title={ __( 'Asset Optimization', 'bdthemes-prime-slider-lite' ) }
				subtitle={ __( 'Join the CSS and JS of the widgets a page uses into one file each, stored in your uploads folder.', 'bdthemes-prime-slider-lite' ) }
			/>

			{ ! field ? (
				<EmptyState title={ __( 'Asset Optimization is not available.', 'bdthemes-prime-slider-lite' ) } />
			) : (
				<div className="flex flex-col gap-4">
					{ error ? <Alert tone="error">{ error.message }</Alert> : null }

					<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card overflow-hidden">
						<div className="flex items-center gap-[18px] p-6 max-[640px]:p-5 flex-wrap">
							<span className={ `w-[52px] h-[52px] rounded-[14px] grid place-items-center flex-none ${ accentClass( 'teal' ) }` }>
								<GaugeIcon size={ 26 } />
							</span>
							<div className="flex-1 min-w-[220px]">
								<div className="flex items-center gap-2 flex-wrap">
									<span className="text-[16px] font-extrabold text-[#0f1115] dark:text-ink-text tracking-[-.01em]">
										{ ( field.group && field.group.title ) || field.label }
									</span>
									{ draft[ field.key ] !== !! field.enabled ? (
										<span className="text-[10px] font-extrabold uppercase tracking-[.05em] text-[#b45309] bg-[#fff7ed] dark:bg-[#2a1c0e] dark:text-[#fdba74] py-[2px] px-[8px] rounded-full">
											{ __( 'Edited', 'bdthemes-prime-slider-lite' ) }
										</span>
									) : null }
								</div>
								{ field.group && field.group.desc ? (
									<p
										className="ps-rich-desc text-[13px] text-[#71717a] dark:text-ink-muted mt-[5px] leading-[1.55] max-w-[620px]"
										dangerouslySetInnerHTML={ { __html: field.group.desc } }
									/>
								) : null }
							</div>
							<Toggle checked={ !! draft[ field.key ] } onChange={ () => toggle( field.key ) } label={ field.label } />
						</div>

						<div className="grid grid-cols-3 max-[900px]:grid-cols-1 border-t border-[#f1f1f4] dark:border-ink-border divide-x divide-[#f1f1f4] dark:divide-ink-border max-[900px]:divide-x-0 max-[900px]:divide-y bg-[#fafbfc] dark:bg-ink-subtle">
							<Fact label={ __( 'Status', 'bdthemes-prime-slider-lite' ) }>
								<div className="flex items-center gap-[7px] mt-[6px] text-[13px] font-bold text-[#27272a] dark:text-ink-text">
									<span className={ `w-2 h-2 rounded-full ${ active ? 'bg-[#22c55e] shadow-[0_0_0_3px_rgba(34,197,94,.2)]' : 'bg-[#d4d4d8]' }` } />
									{ active ? __( 'Enabled', 'bdthemes-prime-slider-lite' ) : __( 'Disabled', 'bdthemes-prime-slider-lite' ) }
								</div>
							</Fact>
							<Fact label={ __( 'Combined files', 'bdthemes-prime-slider-lite' ) }>
								<div className="mt-[6px]">
									{ files ? (
										<span className="font-mono text-[12.5px] text-[#52525b] dark:text-ink-muted bg-[#eef0f3] dark:bg-ink-card py-[3px] px-[9px] rounded-md">
											{ sprintf(
												/* translators: 1: CSS file count, 2: JS file count, 3: total size */
												__( '%1$d CSS · %2$d JS · %3$s', 'bdthemes-prime-slider-lite' ),
												Number( assets.css ) || 0,
												Number( assets.js ) || 0,
												assets.size
											) }
										</span>
									) : (
										<span className="text-[13px] font-semibold text-[#a1a1aa]">—</span>
									) }
								</div>
							</Fact>
							<Fact label={ __( 'Auto-regenerate', 'bdthemes-prime-slider-lite' ) }>
								<div className="text-[12.5px] text-[#71717a] dark:text-ink-muted mt-[6px] leading-[1.5]">
									{ __( 'A page builds its combined files the first time it needs them, and every update starts fresh ones. Elementor’s Clear Files & Data removes them.', 'bdthemes-prime-slider-lite' ) }
								</div>
							</Fact>
						</div>
					</div>

					<SaveBar visible={ isDirty } saving={ isSaving } onSave={ onSave } onReset={ reset } />
				</div>
			) }
		</div>
	);
}
