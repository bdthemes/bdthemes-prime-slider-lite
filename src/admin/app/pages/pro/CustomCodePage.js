/**
 * Custom CSS & JS (Prime Slider Pro) — port of Element Pack's Custom Code
 * page: header and footer code injection (CSS + JS editors side by side) and
 * the page/post exclusion list.
 *
 * Data comes from Pro's `prime_slider/admin/react_pages` entry
 * (PrimeSliderPro\Custom_Code::react_page()); saving posts the same fields as
 * the classic panel to its `ps_save_custom_code` AJAX action:
 * - header: ps_custom_css / ps_custom_js   (printed in wp_head)
 * - footer: ps_custom_css_2 / ps_custom_js_2 (printed in wp_footer)
 * - ps_excluded_pages: post IDs where none of the above is printed.
 */
import { useMemo, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import config from '../../utils/config';
import { accentFor } from '../../utils/accents';
import PageHeader from '../../components/shell/PageHeader';
import { SectionCard } from '../../components/ui/Card';
import Alert from '../../components/ui/Alert';
import SaveBar from '../../components/ui/SaveBar';
import { useToast } from '../../components/ui/Toast';
import { CodeIcon } from '../../components/ui/Icons';
import CodeEditor, { CodeEditorStyles } from './custom-code/CodeEditor';
import FieldRow from './custom-code/FieldRow';
import TokenField from './custom-code/TokenField';

const CODE_FIELDS = [ 'customCss', 'customJs', 'customCss2', 'customJs2' ];

function toIds( list ) {
	return ( Array.isArray( list ) ? list : [] )
		.map( ( id ) => parseInt( id, 10 ) )
		.filter( ( id ) => id > 0 );
}

function initialState( data ) {
	return {
		customCss: data.customCss || '',
		customJs: data.customJs || '',
		customCss2: data.customCss2 || '',
		customJs2: data.customJs2 || '',
		excludedPages: toIds( data.excludedPages ),
	};
}

function sameIds( a, b ) {
	if ( a.length !== b.length ) {
		return false;
	}
	const sorted = [ ...b ].sort( ( x, y ) => x - y );
	return [ ...a ].sort( ( x, y ) => x - y ).every( ( id, i ) => id === sorted[ i ] );
}

const LABEL_CLASS =
	'block text-[12px] font-extrabold text-[#52525b] dark:text-ink-muted mb-[6px]';

export default function CustomCodePage( { data = {} } ) {
	const showToast = useToast();
	const [ saved, setSaved ] = useState( () => initialState( data ) );
	const [ draft, setDraft ] = useState( saved );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );

	const editors = data.codeEditor || {};
	const excludable = useMemo(
		() => ( Array.isArray( data.excludable ) ? data.excludable : [] ).map( ( item ) => ( {
			...item,
			id: parseInt( item.id, 10 ),
		} ) ),
		[ data.excludable ]
	);

	const isDirty =
		CODE_FIELDS.some( ( key ) => draft[ key ] !== saved[ key ] ) ||
		! sameIds( draft.excludedPages, saved.excludedPages );

	const set = ( key ) => ( next ) =>
		setDraft( ( current ) => ( { ...current, [ key ]: next } ) );

	const onReset = () => {
		setDraft( saved );
		setError( '' );
	};

	const onSave = async () => {
		const allEmpty = CODE_FIELDS.every( ( key ) => ! draft[ key ] );
		const hadCode = CODE_FIELDS.some( ( key ) => saved[ key ] );
		if (
			allEmpty &&
			hadCode &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'No content detected in any editor. Do you want to save empty content (this will clear all custom code)?',
					'bdthemes-prime-slider-lite'
				)
			)
		) {
			return;
		}

		const snapshot = draft;
		const body = new window.FormData();
		body.append( 'action', data.action || 'ps_save_custom_code' );
		body.append( 'nonce', data.nonce || '' );
		body.append( 'custom_css', snapshot.customCss );
		body.append( 'custom_js', snapshot.customJs );
		body.append( 'custom_css_2', snapshot.customCss2 );
		body.append( 'custom_js_2', snapshot.customJs2 );
		snapshot.excludedPages.forEach( ( id ) => body.append( 'excluded_pages[]', String( id ) ) );

		setSaving( true );
		setError( '' );

		try {
			const response = await window.fetch( config.ajaxUrl || window.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				body,
			} );
			let json = null;
			try {
				json = await response.json();
			} catch ( e ) {
				json = null;
			}

			if ( ! json || ! json.success ) {
				throw new Error(
					( json && json.data && json.data.message ) ||
						__( 'Failed to save custom code. Please try again.', 'bdthemes-prime-slider-lite' )
				);
			}

			setSaved( snapshot );
			const count = json.data && json.data.excluded_count ? json.data.excluded_count : 0;
			showToast(
				count
					? sprintf(
							/* translators: %d: number of excluded pages and posts. */
							_n(
								'Changes saved. %d page excluded.',
								'Changes saved. %d pages excluded.',
								count,
								'bdthemes-prime-slider-lite'
							),
							count
					  )
					: __( 'Changes saved.', 'bdthemes-prime-slider-lite' )
			);
		} catch ( e ) {
			setError( e.message );
			showToast( e.message, { tone: 'error' } );
		} finally {
			setSaving( false );
		}
	};

	const editorPair = ( cssKey, jsKey, place ) => (
		<div className="grid grid-cols-2 max-[860px]:grid-cols-1 gap-4">
			<div>
				<span className={ LABEL_CLASS }>{ __( 'CSS', 'bdthemes-prime-slider-lite' ) }</span>
				<CodeEditor
					settings={ editors.css || null }
					ariaLabel={ sprintf(
						/* translators: %s: Header or Footer. */
						__( '%s CSS', 'bdthemes-prime-slider-lite' ),
						place
					) }
					value={ draft[ cssKey ] }
					onChange={ set( cssKey ) }
				/>
			</div>
			<div>
				<span className={ LABEL_CLASS }>{ __( 'JavaScript', 'bdthemes-prime-slider-lite' ) }</span>
				<CodeEditor
					settings={ editors.js || null }
					ariaLabel={ sprintf(
						/* translators: %s: Header or Footer. */
						__( '%s JavaScript', 'bdthemes-prime-slider-lite' ),
						place
					) }
					value={ draft[ jsKey ] }
					onChange={ set( jsKey ) }
				/>
			</div>
		</div>
	);

	return (
		<div>
			<CodeEditorStyles />

			<PageHeader
				title={ __( 'Custom CSS & JS', 'bdthemes-prime-slider-lite' ) }
				subtitle={ __(
					'Inject custom CSS/JS into your site and exclude pages from loading it.',
					'bdthemes-prime-slider-lite'
				) }
			/>

			<div className="flex flex-col gap-4">
				{ error ? (
					<Alert tone="error" onDismiss={ () => setError( '' ) }>
						{ error }
					</Alert>
				) : null }

				<SectionCard
					icon={ CodeIcon }
					iconClassName={ accentFor( 'extra-options' ) }
					title={ __( 'Custom Code', 'bdthemes-prime-slider-lite' ) }
				>
					<FieldRow
						label={ __( 'Header code injection', 'bdthemes-prime-slider-lite' ) }
						description={ __(
							'Raw CSS/JS injected into the <head> of your site (no wrapper tags).',
							'bdthemes-prime-slider-lite'
						) }
						stacked
					>
						{ editorPair( 'customCss', 'customJs', __( 'Header', 'bdthemes-prime-slider-lite' ) ) }
					</FieldRow>

					<FieldRow
						label={ __( 'Footer code injection', 'bdthemes-prime-slider-lite' ) }
						stacked
					>
						{ editorPair( 'customCss2', 'customJs2', __( 'Footer', 'bdthemes-prime-slider-lite' ) ) }
					</FieldRow>

					<FieldRow
						label={ __( 'Exclude pages & posts', 'bdthemes-prime-slider-lite' ) }
						description={ __(
							'Selected items will not load any custom CSS or JavaScript.',
							'bdthemes-prime-slider-lite'
						) }
						stacked
					>
						<TokenField
							items={ excludable }
							value={ draft.excludedPages }
							onChange={ set( 'excludedPages' ) }
							ariaLabel={ __( 'Exclude pages & posts', 'bdthemes-prime-slider-lite' ) }
							help={ __(
								'Type to search, then press Enter or click a suggestion to add it.',
								'bdthemes-prime-slider-lite'
							) }
						/>
					</FieldRow>
				</SectionCard>

				<SaveBar visible={ isDirty } saving={ saving } onSave={ onSave } onReset={ onReset } />
			</div>
		</div>
	);
}
