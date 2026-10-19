/**
 * White label branding settings: port of Element Pack's WhiteLabelPanel
 * (branding card with the mode switch and fields, live preview card on the
 * right), driven by the data Prime Slider Pro hands over through the
 * `prime_slider/admin/react_pages` filter and saved through Pro's existing
 * `ps_save_white_label` AJAX action, exactly as the classic PHP view did.
 */
import { useState, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import config from '../../../utils/config';
import { SectionCard } from '../../../components/ui/Card';
import Toggle from '../../../components/ui/Toggle';
import Alert from '../../../components/ui/Alert';
import SaveBar from '../../../components/ui/SaveBar';
import { useToast } from '../../../components/ui/Toast';
import { BrushIcon } from '../../../components/ui/Icons';
import { accentClass } from '../../../utils/accents';
import FieldRow, { INPUT_CLASS } from './FieldRow';
import { openMediaPicker } from './mediaPicker';
import { UploadIcon, TrashIcon, PrimeSliderMark } from './icons';

/** The name the admin shows without a white label title (BDTPS_CORE_TITLE). */
const BRAND_NAME = 'Prime Slider';

/** Delay before the reload / redirect, as in the classic view. */
const RELOAD_DELAY = 1500;

const LINK_CLASS = 'font-bold underline hover:no-underline';

/**
 * Live mini WP-admin preview driven by the real draft: the white-label
 * title/icon re-render the fake sidebar + page instantly.
 */
function LivePreview( { draft } ) {
	const name = draft.enabled && draft.title ? draft.title : BRAND_NAME;

	const menuIcon = draft.icon_url ? (
		<img
			src={ draft.icon_url }
			alt=""
			className="w-3 h-3 object-contain"
		/>
	) : (
		<PrimeSliderMark size={ 12 } />
	);

	return (
		<div className="bg-white dark:bg-ink-card border border-[#e9e9ed] dark:border-ink-border rounded-card p-[18px] shadow-resting">
			<div className="flex items-center justify-between mb-3">
				<span className="text-[11px] font-bold tracking-[.1em] uppercase text-[#6d6d74] dark:text-ink-muted">
					{ __( 'Live preview', 'bdthemes-prime-slider-lite' ) }
				</span>
				<span className="flex items-center gap-[6px] text-[10.5px] font-extrabold text-[#15803d] bg-[#ecfdf3] dark:bg-[#0f2318] dark:text-[#4ade80] py-[3px] px-[9px] rounded-full">
					<span className="w-[6px] h-[6px] rounded-full bg-[#22c55e]" />
					{ __( 'Updating live', 'bdthemes-prime-slider-lite' ) }
				</span>
			</div>
			<div className="border border-[#e2e2e6] dark:border-ink-border rounded-[12px] overflow-hidden">
				<div className="flex items-center justify-between py-[9px] px-3 bg-brand text-white">
					<div className="flex items-center gap-2 text-[12.5px] font-bold">
						<span className="w-[18px] h-[18px] rounded-[5px] bg-white/[.22] grid place-items-center">
							{ menuIcon }
						</span>
						{ name }
					</div>
					<span className="text-[9.5px] font-bold bg-black/25 py-[2px] px-2 rounded-full">
						{ __( 'PRO', 'bdthemes-prime-slider-lite' ) }
					</span>
				</div>
				<div className="flex">
					<div className="w-[120px] flex-none bg-[#1d2327] py-2">
						<div className="flex items-center gap-2 py-[7px] px-3 text-[#c3c4c7] text-[11.5px]">
							<svg
								width="12"
								height="12"
								viewBox="0 0 24 24"
								fill="#c3c4c7"
							>
								<rect x="3" y="4" width="18" height="3" rx="1" />
								<rect x="3" y="10" width="18" height="3" rx="1" />
								<rect x="3" y="16" width="18" height="3" rx="1" />
							</svg>
							{ __( 'Pages', 'bdthemes-prime-slider-lite' ) }
						</div>
						<div className="flex items-center gap-2 py-2 px-3 text-white text-[11.5px] font-bold bg-brand border-l-[3px] border-white">
							<span className="w-[14px] h-[14px] grid place-items-center text-white">
								{ menuIcon }
							</span>
							<span className="truncate">{ name }</span>
						</div>
						<div className="flex items-center gap-2 py-[7px] px-3 text-[#c3c4c7] text-[11.5px]">
							<svg
								width="12"
								height="12"
								viewBox="0 0 24 24"
								fill="#c3c4c7"
							>
								<circle cx="12" cy="8" r="4" />
								<path d="M4 21a8 8 0 0 1 16 0Z" />
							</svg>
							{ __( 'Users', 'bdthemes-prime-slider-lite' ) }
						</div>
					</div>
					<div className="flex-1 bg-[#f0f0f1] p-3">
						<div className="text-[13px] font-extrabold text-[#1d2327] truncate">
							{ name }
							{ ' · ' }
							{ __( 'Dashboard', 'bdthemes-prime-slider-lite' ) }
						</div>
						<div className="h-[7px] w-[70%] rounded-[3px] bg-[#d8d8dd] mt-[9px]" />
						<div className="h-[7px] w-[52%] rounded-[3px] bg-[#e3e3e7] mt-[7px]" />
						<div className="flex gap-[7px] mt-[11px]">
							<div className="h-[30px] flex-1 rounded-md bg-white border border-[#e2e2e6]" />
							<div className="h-[30px] flex-1 rounded-md bg-white border border-[#e2e2e6]" />
						</div>
					</div>
				</div>
			</div>
			<div className="text-xs text-[#6d6d74] dark:text-ink-muted mt-[14px] leading-[1.55]">
				{ draft.enabled
					? __(
							'Clients see your branding — no mention of Prime Slider in the admin.',
							'bdthemes-prime-slider-lite'
					  )
					: __(
							'Enable white label mode to replace Prime Slider branding.',
							'bdthemes-prime-slider-lite'
					  ) }
			</div>
		</div>
	);
}

/**
 * Media picker tile: dashed dropzone when empty; image preview with a
 * hover overlay (Replace / Remove) when set.
 */
function MediaField( { label, hint, url, onPick, onRemove, imgClassName } ) {
	return (
		<div className="flex-1 min-w-[200px]">
			<div className="text-[12px] font-extrabold text-[#52525b] dark:text-ink-muted mb-[7px]">
				{ label }
			</div>

			{ url ? (
				<div className="relative group/media w-full h-[104px] border border-[#e9e9ed] dark:border-ink-border rounded-[13px] bg-[#fafbfc] dark:bg-ink-subtle grid place-items-center overflow-hidden">
					<img src={ url } alt="" className={ imgClassName } />
					<div className="absolute inset-0 bg-[rgba(15,17,21,.55)] opacity-0 group-hover/media:opacity-100 transition-opacity duration-150 grid place-items-center">
						<div className="flex items-center gap-2">
							<button
								type="button"
								onClick={ onPick }
								className="flex items-center gap-[5px] bg-white text-[#18181b] border-0 py-[6px] px-3 rounded-full text-[11.5px] font-bold cursor-pointer"
							>
								<UploadIcon size={ 12 } />
								{ __( 'Replace', 'bdthemes-prime-slider-lite' ) }
							</button>
							<button
								type="button"
								onClick={ onRemove }
								aria-label={ __(
									'Remove image',
									'bdthemes-prime-slider-lite'
								) }
								className="flex items-center gap-[5px] bg-[#e11d48] text-white border-0 py-[6px] px-3 rounded-full text-[11.5px] font-bold cursor-pointer"
							>
								<TrashIcon size={ 12 } />
								{ __( 'Remove', 'bdthemes-prime-slider-lite' ) }
							</button>
						</div>
					</div>
				</div>
			) : (
				<button
					type="button"
					onClick={ onPick }
					className="w-full h-[104px] border-[1.5px] border-dashed border-[#d4d4d8] dark:border-ink-border rounded-[13px] bg-transparent grid place-items-center cursor-pointer text-[#6d6d74] dark:text-ink-muted transition-colors duration-150 hover:border-brand hover:text-brand hover:bg-[#fff8f4] dark:hover:bg-[#2a1a12]"
				>
					<span className="flex flex-col items-center gap-[5px]">
						<UploadIcon size={ 18 } />
						<span className="text-[12px] font-bold">
							{ __( 'Upload image', 'bdthemes-prime-slider-lite' ) }
						</span>
						{ hint ? (
							<span className="text-[10.5px] font-medium opacity-90">
								{ hint }
							</span>
						) : null }
					</span>
				</button>
			) }
		</div>
	);
}

const DEFAULT_DRAFT = {
	enabled: false,
	hide_license: false,
	bdtps_hide: false,
	title: '',
	icon_url: '',
	icon_id: 0,
	logo_url: '',
	logo_id: 0,
};

/**
 * POST the draft to Pro's `ps_save_white_label` handler with the field names
 * and values the classic view sent (checkboxes as 1/0).
 *
 * @param {Object} data  Page data from PHP.
 * @param {Object} draft Values to save.
 * @return {Promise<Object>} The handler's `data`: message, bdtps_hide, email_sent, access_url.
 */
async function saveWhiteLabel( data, draft ) {
	const body = new window.FormData();
	body.append(
		'action',
		( data.actions && data.actions.save ) || 'ps_save_white_label'
	);
	body.append( 'nonce', data.nonce || '' );
	body.append( 'ps_white_label_enabled', draft.enabled ? '1' : '0' );
	body.append( 'ps_white_label_title', draft.title || '' );
	body.append( 'ps_white_label_icon', draft.icon_url || '' );
	body.append(
		'ps_white_label_icon_id',
		draft.icon_id ? String( draft.icon_id ) : ''
	);
	body.append( 'ps_white_label_hide_license', draft.hide_license ? '1' : '0' );
	body.append( 'ps_white_label_bdtps_hide', draft.bdtps_hide ? '1' : '0' );
	body.append( 'ps_white_label_logo', draft.logo_url || '' );
	body.append(
		'ps_white_label_logo_id',
		draft.logo_id ? String( draft.logo_id ) : ''
	);

	let response;
	let json = null;

	try {
		response = await window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		} );
		json = await response.json();
	} catch ( e ) {
		json = null;
	}

	if ( ! json || typeof json !== 'object' ) {
		throw new Error(
			sprintf(
				/* translators: %s: HTTP status code or error text. */
				__(
					'Failed to save settings. Please try again. (%s)',
					'bdthemes-prime-slider-lite'
				),
				response ? response.status : 'error'
			)
		);
	}

	if ( ! json.success ) {
		throw new Error(
			( json.data && json.data.message ) ||
				__( 'Unknown error occurred', 'bdthemes-prime-slider-lite' )
		);
	}

	return json.data || {};
}

export default function WhiteLabelPanel( { data = {} } ) {
	// Saved values: PHP's, then the last successful save.
	const [ base, setBase ] = useState( () => ( {
		...DEFAULT_DRAFT,
		...( data.values || {} ),
	} ) );
	// Local edits only; the rendered draft is saved values + overrides.
	const [ overrides, setOverrides ] = useState( {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	// After a save: `reloading` until the page reloads, or the access link
	// when the recovery email could not be sent.
	const [ done, setDone ] = useState( null );
	const showToast = useToast();

	const licenseActive = !! data.license_active;
	const eligible = !! data.license_eligible;
	const unlocked = licenseActive && eligible;

	const draft = useMemo(
		() => ( { ...base, ...overrides } ),
		[ base, overrides ]
	);

	// Only edits that differ from the saved values count: switching a
	// toggle back off is not an unsaved change.
	const dirty =
		unlocked &&
		! done &&
		Object.keys( overrides ).some(
			( key ) => overrides[ key ] !== base[ key ]
		);

	const setField = ( key, value ) => {
		setOverrides( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const leave = ( hidden ) => {
		if ( hidden ) {
			window.location.href =
				data.redirect_url || `${ config.adminUrl || '' }index.php`;
		} else {
			window.location.reload();
		}
	};

	const onToggleHide = ( value ) => {
		if (
			value &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'WARNING: ADVANCED FEATURE\n\nEnabling BDTPS_CORE_HIDE will activate advanced white label mode that:\n\n• Hides ALL Prime Slider branding and menus\n• Makes these settings difficult to access later\n• Requires the special access link to return\n\nAn email with access instructions will be sent if you proceed.\n\nAre you sure you want to enable this advanced mode?',
					'bdthemes-prime-slider-lite'
				)
			)
		) {
			return;
		}
		setField( 'bdtps_hide', value );
	};

	const onSave = async () => {
		if (
			draft.enabled &&
			draft.bdtps_hide &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				sprintf(
					/* translators: %s: license email address. */
					__(
						'FINAL CONFIRMATION\n\nYou are about to save settings with BDTPS_CORE_HIDE enabled.\n\nThis will:\n• Hide Prime Slider from WordPress admin immediately\n• Send access instructions to your email addresses\n• Require the special link to modify these settings\n\nEmail will be sent to:\n• License email: %s\n\nAre you absolutely sure you want to proceed?',
						'bdthemes-prime-slider-lite'
					),
					data.license_email || ''
				)
			)
		) {
			return;
		}

		setIsSaving( true );
		setError( '' );

		let result;
		try {
			result = await saveWhiteLabel( data, draft );
		} catch ( e ) {
			setIsSaving( false );
			setError( e.message );
			showToast( e.message, { tone: 'error' } );
			return;
		}

		// Clears the save bar (and its leave-page guard) before reloading.
		setBase( draft );
		setOverrides( {} );
		setIsSaving( false );

		let message =
			result.message ||
			__( 'White label settings saved.', 'bdthemes-prime-slider-lite' );

		if ( result.bdtps_hide && result.email_sent ) {
			message = `${ message } ${ __(
				'Access email sent: check your inbox for the link to these settings.',
				'bdthemes-prime-slider-lite'
			) }`;
		} else if ( result.bdtps_hide && ! result.access_url ) {
			message = `${ message } ${ __(
				'The access email could not be sent. Check your email settings or contact support.',
				'bdthemes-prime-slider-lite'
			) }`;
		}

		showToast( message );

		// The recovery email did not go out (localhost, no SMTP): this link is
		// the only way back once the menus are hidden, so it stays on screen
		// until the administrator continues.
		if ( result.bdtps_hide && ! result.email_sent && result.access_url ) {
			setDone( { accessUrl: result.access_url, hidden: true } );
			return;
		}

		setDone( { reloading: true } );
		window.setTimeout( () => leave( !! result.bdtps_hide ), RELOAD_DELAY );
	};

	const pickImage = ( type ) => {
		const opened = openMediaPicker( {
			title:
				type === 'icon'
					? __( 'Select Icon', 'bdthemes-prime-slider-lite' )
					: __( 'Select Logo', 'bdthemes-prime-slider-lite' ),
			buttonText:
				type === 'icon'
					? __( 'Use This Icon', 'bdthemes-prime-slider-lite' )
					: __( 'Use This Logo', 'bdthemes-prime-slider-lite' ),
			onSelect: ( attachment ) => {
				if ( type === 'icon' ) {
					setField( 'icon_url', attachment.url );
					setField( 'icon_id', attachment.id );
				} else {
					setField( 'logo_url', attachment.url );
					setField( 'logo_id', attachment.id );
				}
			},
		} );

		if ( ! opened ) {
			// eslint-disable-next-line no-alert
			window.alert(
				__(
					'Media library is not available.',
					'bdthemes-prime-slider-lite'
				)
			);
		}
	};

	let modeDescription = __(
		'Hide Prime Slider branding from the admin and widgets.',
		'bdthemes-prime-slider-lite'
	);
	if ( ! licenseActive ) {
		modeDescription = __(
			'This feature requires an active Prime Slider license. Please activate your license first.',
			'bdthemes-prime-slider-lite'
		);
	} else if ( ! eligible ) {
		modeDescription = __(
			'This feature requires an eligible license (Agency, Extended, Developer, AppSumo Lifetime, etc.). Upgrade your license to access white label functionality.',
			'bdthemes-prime-slider-lite'
		);
	}

	const hideNotice =
		draft.enabled && draft.bdtps_hide && ! base.bdtps_hide
			? __(
					'Saving hides Prime Slider and emails an access link',
					'bdthemes-prime-slider-lite'
			  )
			: undefined;

	return (
		<div className="flex flex-col gap-4">
			{ ! licenseActive ? (
				<Alert tone="error">
					<strong>
						{ __(
							'License Not Activated.',
							'bdthemes-prime-slider-lite'
						) }
					</strong>{ ' ' }
					{ __(
						'Activate your Prime Slider license to use white label features.',
						'bdthemes-prime-slider-lite'
					) }{ ' ' }
					<a href={ data.license_url } className={ LINK_CLASS }>
						{ __( 'Activate License', 'bdthemes-prime-slider-lite' ) }
					</a>
					{ ' · ' }
					<a
						href={ data.upgrade_url }
						target="_blank"
						rel="noreferrer"
						className={ LINK_CLASS }
					>
						{ __( 'Get License', 'bdthemes-prime-slider-lite' ) }
					</a>
				</Alert>
			) : null }

			{ licenseActive && ! eligible ? (
				<Alert tone="warning">
					<strong>
						{ __(
							'Eligible License Required.',
							'bdthemes-prime-slider-lite'
						) }
					</strong>{ ' ' }
					{ __(
						'White label is available for Agency, Extended, Developer, AppSumo Lifetime, and other eligible license holders.',
						'bdthemes-prime-slider-lite'
					) }{ ' ' }
					<a
						href={ data.upgrade_url }
						target="_blank"
						rel="noreferrer"
						className={ LINK_CLASS }
					>
						{ __( 'Upgrade License', 'bdthemes-prime-slider-lite' ) }
					</a>
				</Alert>
			) : null }

			{ error ? (
				<Alert tone="error" onDismiss={ () => setError( '' ) }>
					{ error }
				</Alert>
			) : null }

			{ done && done.reloading ? (
				<Alert tone="success">
					{ __(
						'White label settings saved. Reloading…',
						'bdthemes-prime-slider-lite'
					) }
				</Alert>
			) : null }

			{ done && done.accessUrl ? (
				<Alert tone="success">
					<strong>
						{ __(
							'White label settings saved.',
							'bdthemes-prime-slider-lite'
						) }
					</strong>{ ' ' }
					{ __(
						'The access email could not be sent from this site. Save this link: you need it to change these settings while BDTPS_CORE_HIDE is active.',
						'bdthemes-prime-slider-lite'
					) }{ ' ' }
					<a
						href={ done.accessUrl }
						target="_blank"
						rel="noreferrer"
						className={ `${ LINK_CLASS } break-all` }
					>
						{ __(
							'Access white label settings',
							'bdthemes-prime-slider-lite'
						) }
					</a>
					{ ' · ' }
					<button
						type="button"
						onClick={ () => leave( true ) }
						className={ `${ LINK_CLASS } bg-transparent border-0 p-0 cursor-pointer text-inherit` }
					>
						{ __( 'Continue', 'bdthemes-prime-slider-lite' ) }
					</button>
				</Alert>
			) : null }

			{ unlocked && base.bdtps_hide && ! done ? (
				<Alert tone="warning">
					<strong>
						{ __(
							'BDTPS_CORE_HIDE Currently Active.',
							'bdthemes-prime-slider-lite'
						) }
					</strong>{ ' ' }
					{ __(
						'Advanced white label mode is enabled. Prime Slider menus are hidden from the admin interface.',
						'bdthemes-prime-slider-lite'
					) }
				</Alert>
			) : null }

			<div className="grid grid-cols-[1.4fr_1fr] max-[1024px]:grid-cols-1 gap-4 items-start">
			<SectionCard
				icon={ BrushIcon }
				iconClassName={ accentClass( 'violet' ) }
				title={ __( 'Branding', 'bdthemes-prime-slider-lite' ) }
			>
				<FieldRow
					label={ __(
						'Enable white label mode',
						'bdthemes-prime-slider-lite'
					) }
					description={ modeDescription }
				>
					<Toggle
						checked={ !! draft.enabled }
						onChange={ ( v ) => setField( 'enabled', v ) }
						disabled={ ! unlocked }
						label={ __(
							'Enable white label mode',
							'bdthemes-prime-slider-lite'
						) }
					/>
				</FieldRow>

				{ unlocked && draft.enabled ? (
					<>
						<FieldRow
							label={ __(
								'White label title',
								'bdthemes-prime-slider-lite'
							) }
							description={ __(
								'Replaces “Prime Slider” branding throughout the plugin.',
								'bdthemes-prime-slider-lite'
							) }
						>
							<input
								type="text"
								value={ draft.title }
								placeholder={ __(
									'e.g. Site Slider',
									'bdthemes-prime-slider-lite'
								) }
								onChange={ ( event ) =>
									setField( 'title', event.target.value )
								}
								className={ `${ INPUT_CLASS } !w-[250px] max-w-full` }
							/>
						</FieldRow>

						<FieldRow
							label={ __(
								'Brand images',
								'bdthemes-prime-slider-lite'
							) }
							description={ __(
								'The icon replaces the sidebar menu icon; the logo replaces the Prime Slider logo in the admin header.',
								'bdthemes-prime-slider-lite'
							) }
							stacked
						>
							<div className="flex flex-wrap gap-4">
								<MediaField
									label={ __(
										'Plugin icon',
										'bdthemes-prime-slider-lite'
									) }
									hint={ __(
										'Square · 20×20px · JPG, PNG or SVG',
										'bdthemes-prime-slider-lite'
									) }
									url={ draft.icon_url }
									imgClassName="w-12 h-12 rounded-[10px] object-contain"
									onPick={ () => pickImage( 'icon' ) }
									onRemove={ () => {
										setField( 'icon_url', '' );
										setField( 'icon_id', 0 );
									} }
								/>
								<MediaField
									label={ __(
										'Plugin logo',
										'bdthemes-prime-slider-lite'
									) }
									hint={ __(
										'Wide · 200×40px · JPG, PNG or SVG',
										'bdthemes-prime-slider-lite'
									) }
									url={ draft.logo_url }
									imgClassName="max-h-10 max-w-[85%] object-contain"
									onPick={ () => pickImage( 'logo' ) }
									onRemove={ () => {
										setField( 'logo_url', '' );
										setField( 'logo_id', 0 );
									} }
								/>
							</div>
						</FieldRow>

						<FieldRow
							label={ __(
								'Hide license menu',
								'bdthemes-prime-slider-lite'
							) }
							description={ __(
								'Hides the License menu from the admin sidebar while white label mode is on.',
								'bdthemes-prime-slider-lite'
							) }
						>
							<Toggle
								checked={ !! draft.hide_license }
								onChange={ ( v ) =>
									setField( 'hide_license', v )
								}
								label={ __(
									'Hide license menu',
									'bdthemes-prime-slider-lite'
								) }
							/>
						</FieldRow>

						<FieldRow
							label={ __(
								'Enable BDTPS_CORE_HIDE constant',
								'bdthemes-prime-slider-lite'
							) }
							description={
								<>
									{ __(
										'Hides Prime Slider menus and branding throughout the admin. An access link is emailed to your license address so you can get back in:',
										'bdthemes-prime-slider-lite'
									) }{ ' ' }
									<strong>{ data.license_email }</strong>
								</>
							}
						>
							<Toggle
								checked={ !! draft.bdtps_hide }
								onChange={ onToggleHide }
								label={ __(
									'Enable BDTPS_CORE_HIDE constant',
									'bdthemes-prime-slider-lite'
								) }
							/>
						</FieldRow>
					</>
				) : null }
			</SectionCard>

			<LivePreview draft={ draft } />
			</div>

			<SaveBar
				visible={ dirty }
				saving={ isSaving }
				onSave={ onSave }
				onReset={ () => setOverrides( {} ) }
				message={ hideNotice }
				saveLabel={ __(
					'Save white label',
					'bdthemes-prime-slider-lite'
				) }
			/>
		</div>
	);
}
