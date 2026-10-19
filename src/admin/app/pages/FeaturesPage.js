/**
 * Features (prime_slider_other_settings), laid out like Element Pack's
 * Features page: a header bulk bar, a search toolbar card with a count, and
 * the same card grid as the widget pages. Each feature's description shows as
 * a hover tooltip. Asset Optimization has its own Performance page.
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useSection } from '../hooks/useSection';
import PageHeader from '../components/shell/PageHeader';
import SearchInput from '../components/ui/SearchInput';
import WidgetGrid from '../components/widgets/WidgetGrid';
import BulkBar from '../components/widgets/BulkBar';
import SaveBar from '../components/ui/SaveBar';
import Alert from '../components/ui/Alert';
import { useToast } from '../components/ui/Toast';
import { ASSET_FIELD } from './PerformancePage';

const SECTION = 'prime_slider_other_settings';

/** Category line shown under each feature name. */
const FEATURE_CATEGORY = {
	'live-copy': { label: __( 'Editor', 'bdthemes-prime-slider-lite' ), accent: 'blue' },
	duplicator: { label: __( 'Content', 'bdthemes-prime-slider-lite' ), accent: 'violet' },
	'reveal-effects': { label: __( 'Animation', 'bdthemes-prime-slider-lite' ), accent: 'rose' },
};

export default function FeaturesPage() {
	const section = useSection( SECTION );
	const { draft, toggle, setAll, isDirty, save, reset, isSaving, error } = section;
	const [ query, setQuery ] = useState( '' );
	const showToast = useToast();

	const toggles = section.fields
		.filter( ( f ) => f.name !== ASSET_FIELD )
		.map( ( f ) => {
			const look = FEATURE_CATEGORY[ f.name ] || { label: __( 'Feature', 'bdthemes-prime-slider-lite' ), accent: 'amber' };
			return {
				...f,
				label: ( f.group && f.group.title ) || f.label,
				category_label: look.label,
				accent: look.accent,
			};
		} );
	const switchable = toggles.filter( ( f ) => ! f.is_locked );
	const activeCount = switchable.filter( ( f ) => draft[ f.key ] ).length;

	const q = query.trim().toLowerCase();
	const visible = toggles.filter(
		( f ) => ! q || [ f.label, f.description ].some( ( text ) => String( text || '' ).toLowerCase().includes( q ) )
	);

	const onSave = async () => {
		try {
			await save();
			showToast( __( 'Changes saved.', 'bdthemes-prime-slider-lite' ) );
		} catch ( e ) {
			showToast( e.message, { tone: 'error' } );
		}
	};

	return (
		<div>
			<PageHeader
				title={ __( 'Features', 'bdthemes-prime-slider-lite' ) }
				subtitle={ __( 'Enable bundled features that extend Prime Slider beyond individual widgets.', 'bdthemes-prime-slider-lite' ) }
				actions={
					<BulkBar
						activeCount={ activeCount }
						total={ switchable.length }
						isSaving={ isSaving }
						onActivateAll={ () => setAll( true, toggles ) }
						onDeactivateAll={ () => setAll( false, toggles ) }
					/>
				}
			/>

			<div className="flex flex-col gap-4">
				{ /* Toolbar card — matches FilterBar on the widget pages. */ }
				<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card py-[13px] px-[16px] flex items-center justify-between gap-3 flex-wrap">
					<SearchInput
						value={ query }
						onChange={ setQuery }
						placeholder={ __( 'Search features…', 'bdthemes-prime-slider-lite' ) }
						className="flex-1 min-w-[220px] max-w-[380px]"
					/>
					<span className="text-[12.5px] font-semibold text-[#6d6d74] dark:text-ink-muted">
						{ sprintf(
							/* translators: %d: feature count */
							__( '%d features', 'bdthemes-prime-slider-lite' ),
							visible.length
						) }
					</span>
				</div>

				{ error ? <Alert tone="error">{ error.message }</Alert> : null }

				{ visible.length > 0 ? (
					<WidgetGrid modules={ visible } draft={ draft } onToggle={ toggle } />
				) : (
					<div className="text-center py-[60px] px-5 bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card">
						<div className="text-[15px] font-bold text-[#6c6c75] dark:text-ink-muted">
							{ sprintf(
								/* translators: %s: search query */
								__( 'No features match “%s”', 'bdthemes-prime-slider-lite' ),
								query
							) }
						</div>
						<button type="button" onClick={ () => setQuery( '' ) } className="mt-3 text-brand font-bold cursor-pointer text-[13px]">
							{ __( 'Clear search', 'bdthemes-prime-slider-lite' ) }
						</button>
					</div>
				) }

				<SaveBar visible={ isDirty } saving={ isSaving } onSave={ onSave } onReset={ reset } />
			</div>
		</div>
	);
}
