/**
 * Widget catalog for one settings section (Core Widgets, 3rd Party Widgets,
 * Extensions): search, All/New/Free/Pro, usage and category filters, a grid of
 * switches, bulk actions and a sticky save bar.
 */
import { useMemo, useState, useEffect } from '@wordpress/element';
import { useSearchParams } from 'react-router-dom';
import { __, sprintf } from '@wordpress/i18n';
import { useSection } from '../hooks/useSection';
import { CATEGORIES, categoryKeyForModule, isNew } from '../utils/widgetCategories';
import PageHeader from '../components/shell/PageHeader';
import EmptyState from '../components/EmptyState';
import FilterBar from '../components/widgets/FilterBar';
import WidgetGrid from '../components/widgets/WidgetGrid';
import BulkBar from '../components/widgets/BulkBar';
import SaveBar from '../components/ui/SaveBar';
import Alert from '../components/ui/Alert';
import Button from '../components/ui/Button';
import { useToast } from '../components/ui/Toast';
import config from '../utils/config';

export default function SectionPage( { section, title, subtitle } ) {
	const { fields, switchable, draft, toggle, setAll, isDirty, activeCount, save, reset, isSaving, error } =
		useSection( section );
	const [ search, setSearch ] = useState( '' );
	const [ type, setType ] = useState( 'all' );
	const [ usage, setUsage ] = useState( 'all' );
	const [ searchParams ] = useSearchParams();
	const [ category, setCategory ] = useState( () => searchParams.get( 'category' ) || 'all' );

	// Links such as the old "3rd Party Widgets" submenu entry open a category.
	useEffect( () => {
		setCategory( searchParams.get( 'category' ) || 'all' );
	}, [ searchParams ] );
	const showToast = useToast();

	const showUsage = fields.some( ( m ) => m.has_usage ) && fields.some( ( m ) => m.usage_count > 0 );

	const counts = useMemo( () => {
		const c = { all: fields.length, free: 0, pro: 0, new: 0, used: 0, unused: 0 };
		fields.forEach( ( m ) => {
			c[ m.widget_type === 'pro' ? 'pro' : 'free' ] += 1;
			if ( isNew( m ) ) {
				c.new += 1;
			}
			c[ m.usage_count > 0 ? 'used' : 'unused' ] += 1;
		} );
		return c;
	}, [ fields ] );

	const categories = useMemo( () => {
		const tally = {};
		fields.forEach( ( m ) => {
			const key = categoryKeyForModule( m );
			tally[ key ] = ( tally[ key ] || 0 ) + 1;
		} );
		const keys = Object.keys( CATEGORIES ).filter( ( key ) => tally[ key ] > 0 );
		return keys.length > 1
			? keys.map( ( key ) => ( { key, label: CATEGORIES[ key ].label, count: tally[ key ] } ) )
			: [];
	}, [ fields ] );

	const filtered = useMemo( () => {
		const q = search.trim().toLowerCase();
		return fields.filter( ( m ) => {
			if ( type === 'new' && ! isNew( m ) ) {
				return false;
			}
			if ( ( type === 'free' || type === 'pro' ) && m.widget_type !== type ) {
				return false;
			}
			if ( category !== 'all' && categoryKeyForModule( m ) !== category ) {
				return false;
			}
			if ( showUsage && usage === 'used' && ! ( m.usage_count > 0 ) ) {
				return false;
			}
			if ( showUsage && usage === 'unused' && m.usage_count > 0 ) {
				return false;
			}
			return ! q || m.label.toLowerCase().includes( q );
		} );
	}, [ fields, type, usage, search, category, showUsage ] );

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
				title={ title }
				subtitle={
					subtitle ||
					sprintf(
						/* translators: 1: active count, 2: total count */
						__( '%1$d of %2$d enabled · disable unused items for faster pages.', 'bdthemes-prime-slider-lite' ),
						activeCount,
						switchable.length
					)
				}
				actions={
					fields.length ? (
						<BulkBar
							activeCount={ activeCount }
							total={ switchable.length }
							scopeCount={ filtered.length === fields.length ? null : filtered.length }
							isSaving={ isSaving }
							onActivateAll={ () => setAll( true, filtered.filter( ( m ) => m.plugin_active !== false ) ) }
							onDeactivateAll={ () => setAll( false, filtered ) }
						/>
					) : null
				}
			/>

			{ ! config.isPro && counts.pro > 0 ? (
				<Alert tone="info" className="mb-4">
					{ __( 'Widgets marked PRO are part of the separate Prime Slider Pro plugin and switch on once it is active.', 'bdthemes-prime-slider-lite' ) }{ ' ' }
					<a href={ config.pricingUrl } target="_blank" rel="noreferrer" className="font-bold underline">
						{ __( 'See Prime Slider Pro', 'bdthemes-prime-slider-lite' ) }
					</a>
				</Alert>
			) : null }

			{ error ? (
				<Alert tone="error" className="mb-4">
					{ error.message }
				</Alert>
			) : null }

			<FilterBar
				search={ search }
				onSearch={ setSearch }
				searchPlaceholder={
					section === 'prime_slider_elementor_extend'
						? __( 'Search extensions…', 'bdthemes-prime-slider-lite' )
						: __( 'Search widgets…', 'bdthemes-prime-slider-lite' )
				}
				type={ type }
				onType={ setType }
				usage={ usage }
				onUsage={ setUsage }
				showUsage={ showUsage }
				counts={ counts }
				categories={ categories }
				category={ category }
				onCategory={ setCategory }
			/>

			{ filtered.length === 0 ? (
				<EmptyState
					title={ __( 'No widgets match your filters', 'bdthemes-prime-slider-lite' ) }
					action={
						search ? (
							<Button variant="ghost" onClick={ () => setSearch( '' ) }>
								{ __( 'Clear search', 'bdthemes-prime-slider-lite' ) }
							</Button>
						) : null
					}
				/>
			) : (
				<WidgetGrid modules={ filtered } draft={ draft } onToggle={ toggle } />
			) }

			{ switchable.length === 0 ? null : (
				<SaveBar visible={ isDirty } saving={ isSaving } onSave={ onSave } onReset={ reset } />
			) }
		</div>
	);
}
