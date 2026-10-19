/**
 * Special Features (prime_slider_other_settings): one card per feature group
 * with its description and switch — Live Copy, Duplicator, Asset
 * Optimization, and whatever add-ons contribute (Reveal Effects in Pro).
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSection } from '../hooks/useSection';
import PageHeader from '../components/shell/PageHeader';
import EmptyState from '../components/EmptyState';
import SaveBar from '../components/ui/SaveBar';
import Toggle from '../components/ui/Toggle';
import IconTile from '../components/ui/IconTile';
import Alert from '../components/ui/Alert';
import { ProBadge } from '../components/ui/Badge';
import { useToast } from '../components/ui/Toast';
import { CopyIcon, LayoutIcon, SparklesIcon, SlidersIcon, ExternalIcon, LockIcon } from '../components/ui/Icons';
import config from '../utils/config';
import { ASSET_FIELD } from './PerformancePage';

const FEATURE_LOOK = {
	'live-copy': { icon: CopyIcon, accent: 'blue' },
	duplicator: { icon: LayoutIcon, accent: 'violet' },
	'reveal-effects': { icon: SparklesIcon, accent: 'rose' },
};

const SECTION = 'prime_slider_other_settings';

export default function FeaturesPage() {
	const section = useSection( SECTION );
	const { draft, toggle, isDirty, save, reset, isSaving, error } = section;
	// Asset Optimization has its own Performance page, as in Element Pack.
	const fields = section.fields.filter( ( f ) => f.name !== ASSET_FIELD );
	const switchable = section.switchable.filter( ( f ) => f.name !== ASSET_FIELD );
	const activeCount = switchable.filter( ( f ) => draft[ f.key ] ).length;
	const showToast = useToast();

	const onSave = async () => {
		try {
			await save();
			showToast( __( 'Settings saved.', 'bdthemes-prime-slider-lite' ) );
		} catch ( e ) {
			showToast( e.message, { tone: 'error' } );
		}
	};

	return (
		<div>
			<PageHeader
				title={ __( 'Features', 'bdthemes-prime-slider-lite' ) }
				subtitle={ sprintf(
					/* translators: 1: active count, 2: total count */
					__( '%1$d of %2$d features active.', 'bdthemes-prime-slider-lite' ),
					activeCount,
					switchable.length
				) }
			/>

			{ error ? (
				<Alert tone="error" className="mb-4">
					{ error.message }
				</Alert>
			) : null }

			{ fields.length === 0 ? (
				<EmptyState title={ __( 'No features available', 'bdthemes-prime-slider-lite' ) } />
			) : (
				<div className="grid grid-cols-2 max-[900px]:grid-cols-1 gap-4">
					{ fields.map( ( field ) => {
						const look = FEATURE_LOOK[ field.name ] || { icon: SlidersIcon, accent: 'amber' };
						const title = ( field.group && field.group.title ) || field.label;
						return (
							<div
								key={ field.name }
								className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-5 flex flex-col gap-3"
							>
								<div className="flex items-start gap-3">
									<IconTile icon={ look.icon } accent={ look.accent } size="xl" />
									<div className="min-w-0 flex-1">
										<div className="flex items-center gap-2">
											<span className="text-[15.5px] font-extrabold text-[#0f1115] dark:text-ink-text tracking-[-.01em]">
												{ title }
											</span>
											{ field.widget_type === 'pro' ? <ProBadge /> : null }
										</div>
										{ field.group && field.group.desc ? (
											<p
												className="ps-rich-desc text-[13px] leading-[1.6] text-[#71717a] dark:text-ink-muted mt-[6px]"
												dangerouslySetInnerHTML={ { __html: field.group.desc } }
											/>
										) : null }
									</div>
									<div className="flex-none pt-1">
										{ field.is_locked ? (
											<a href={ config.pricingUrl } target="_blank" rel="noreferrer" className="text-[#a1a1aa] hover:text-brand">
												<LockIcon size={ 18 } />
											</a>
										) : (
											<Toggle checked={ !! draft[ field.key ] } onChange={ () => toggle( field.key ) } label={ title } />
										) }
									</div>
								</div>
								{ field.demo_url || field.video_url ? (
									<div className="flex items-center gap-4 pl-[50px] text-[12px] font-semibold text-[#9a9aa3]">
										{ field.demo_url ? (
											<a href={ field.demo_url } target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 hover:text-brand">
												{ __( 'Demo', 'bdthemes-prime-slider-lite' ) }
												<ExternalIcon size={ 11 } />
											</a>
										) : null }
										{ field.video_url ? (
											<a href={ field.video_url } target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 hover:text-brand">
												{ __( 'Video', 'bdthemes-prime-slider-lite' ) }
												<ExternalIcon size={ 11 } />
											</a>
										) : null }
									</div>
								) : null }
							</div>
						);
					} ) }
				</div>
			) }

			<SaveBar visible={ isDirty } saving={ isSaving } onSave={ onSave } onReset={ reset } />
		</div>
	);
}
