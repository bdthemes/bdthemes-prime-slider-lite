/**
 * Other Plugins, laid out like Element Pack's: one card per BdThemes plugin
 * with its icon, version, description, status, and Install / Activate /
 * Details actions.
 *
 * Data and installs go through the existing nonce- and capability-checked
 * AJAX actions (`bdtps_get_plugins`, `bdtps_install_plugin`); activation uses
 * WordPress core's own nonce-signed activate link. Remote text is rendered as
 * text, and remote URLs are used only when they are http(s).
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PageHeader from '../components/shell/PageHeader';
import Spinner from '../components/Spinner';
import EmptyState from '../components/EmptyState';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import Alert from '../components/ui/Alert';
import { useToast } from '../components/ui/Toast';
import { PuzzleIcon, ExternalIcon } from '../components/ui/Icons';
import { accentFor } from '../utils/accents';
import config from '../utils/config';

function safeUrl( url ) {
	try {
		const parsed = new window.URL( String( url || '' ), window.location.href );
		return parsed.protocol === 'https:' || parsed.protocol === 'http:' ? parsed.href : '';
	} catch ( e ) {
		return '';
	}
}

/** "Ultimate Post Kit – Elementor Post Grid, …" → "Ultimate Post Kit", as Element Pack lists them. */
function shortName( name ) {
	const text = String( name || '' );
	const cut = text.split( /\s[–—-]\s|:\s/ )[ 0 ].trim();
	return cut || text;
}

async function post( action, fields ) {
	const body = new window.FormData();
	body.append( 'action', action );
	Object.keys( fields ).forEach( ( key ) => body.append( key, fields[ key ] ) );
	const response = await window.fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } );
	let json = null;
	try {
		json = await response.json();
	} catch ( e ) {
		json = null;
	}
	if ( ! json || ! json.success ) {
		throw new Error(
			( json && json.data && json.data.message ) ||
				__( 'Something went wrong. Reload the page and try again.', 'bdthemes-prime-slider-lite' )
		);
	}
	return json.data || {};
}

function PluginIcon( { icon, slug } ) {
	const [ failed, setFailed ] = useState( false );
	const src = safeUrl( icon );

	if ( src && ! failed ) {
		return (
			<img
				src={ src }
				alt=""
				width="48"
				height="48"
				onError={ () => setFailed( true ) }
				className="w-12 h-12 rounded-tile flex-none object-cover"
			/>
		);
	}

	return (
		<span className={ `w-12 h-12 rounded-tile flex-none grid place-items-center ${ accentFor( slug ) }` } aria-hidden="true">
			<PuzzleIcon size={ 22 } />
		</span>
	);
}

const STATUS = {
	active: { tone: 'success', label: __( 'Active', 'bdthemes-prime-slider-lite' ) },
	installed: { tone: 'info', label: __( 'Installed', 'bdthemes-prime-slider-lite' ) },
	not_installed: { tone: 'neutral', label: __( 'Not installed', 'bdthemes-prime-slider-lite' ) },
};

export default function OtherPluginsPage() {
	const settings = config.plugins || {};
	const showToast = useToast();
	const [ plugins, setPlugins ] = useState( null );
	const [ loadingMessage, setLoadingMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ busy, setBusy ] = useState( { slug: '', action: '' } );

	const load = useCallback( async () => {
		setError( '' );
		try {
			const data = await post( 'bdtps_get_plugins', { nonce: settings.nonce || '' } );
			if ( data.loading ) {
				setLoadingMessage( data.message || __( 'Fetching latest plugin data…', 'bdthemes-prime-slider-lite' ) );
				window.setTimeout( load, 3000 );
				return;
			}
			setLoadingMessage( '' );
			// This plugin is the one you are looking at.
			setPlugins( ( data.plugins || [] ).filter( ( p ) => p.slug !== 'bdthemes-prime-slider-lite' ) );
		} catch ( e ) {
			setError( e.message );
			setPlugins( ( current ) => current || [] );
		}
	}, [ settings.nonce ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const onInstall = async ( plugin ) => {
		setBusy( { slug: plugin.slug, action: 'install' } );
		try {
			const data = await post( 'bdtps_install_plugin', { plugin_slug: plugin.slug, nonce: settings.installNonce || '' } );
			showToast( data.message || __( 'Plugin installed.', 'bdthemes-prime-slider-lite' ) );
			await load();
		} catch ( e ) {
			showToast( e.message, { tone: 'error' } );
		} finally {
			setBusy( { slug: '', action: '' } );
		}
	};

	const onActivate = async ( plugin ) => {
		setBusy( { slug: plugin.slug, action: 'activate' } );
		try {
			const url = `${ config.adminUrl }plugins.php?action=activate&plugin=${ encodeURIComponent( plugin.plugin_file ) }&_wpnonce=${ encodeURIComponent( plugin.activate_nonce ) }`;
			const response = await window.fetch( url, { credentials: 'same-origin' } );
			if ( ! response.ok ) {
				throw new Error( __( 'The plugin could not be activated.', 'bdthemes-prime-slider-lite' ) );
			}
			showToast( __( 'Plugin activated.', 'bdthemes-prime-slider-lite' ) );
			await load();
		} catch ( e ) {
			showToast( e.message, { tone: 'error' } );
		} finally {
			setBusy( { slug: '', action: '' } );
		}
	};

	return (
		<div>
			<PageHeader
				title={ __( 'Other Plugins', 'bdthemes-prime-slider-lite' ) }
				subtitle={ __( 'Install companion plugins from BdThemes to extend your stack.', 'bdthemes-prime-slider-lite' ) }
			/>

			{ plugins === null && ! error ? (
				<Spinner full label={ loadingMessage || __( 'Loading plugins…', 'bdthemes-prime-slider-lite' ) } />
			) : (
				<div className="flex flex-col gap-4">
					{ loadingMessage ? <Alert tone="info">{ loadingMessage }</Alert> : null }
					{ error ? (
						<Alert tone="error" onDismiss={ () => setError( '' ) }>
							{ error }
						</Alert>
					) : null }

					{ ! plugins || plugins.length === 0 ? (
						<EmptyState
							title={ __( 'No plugins available', 'bdthemes-prime-slider-lite' ) }
							description={ __( 'Try again in a moment.', 'bdthemes-prime-slider-lite' ) }
							action={
								<Button variant="outline" onClick={ load }>
									{ __( 'Retry', 'bdthemes-prime-slider-lite' ) }
								</Button>
							}
						/>
					) : (
						<div className="grid grid-cols-3 max-[1280px]:grid-cols-2 max-[760px]:grid-cols-1 gap-3">
							{ plugins.map( ( plugin ) => {
								const status = STATUS[ plugin.status ] || STATUS.not_installed;
								const homepage = safeUrl( plugin.homepage );
								const isBusy = busy.slug === plugin.slug;
								return (
									<Card
										key={ plugin.slug }
										className="group p-5 flex flex-col gap-3 transition-all duration-200 hover:border-[#fdd3bf] dark:hover:border-[#5a3524] hover:shadow-[0_10px_24px_-14px_rgba(252,106,42,.35)] hover:-translate-y-[2px]"
									>
										<div className="flex items-center gap-3">
											<PluginIcon icon={ plugin.logo } slug={ plugin.slug } />
											<div className="min-w-0">
												<h2 className="m-0 text-[15px] font-extrabold text-[#0f1115] dark:text-ink-text tracking-[-.01em] truncate transition-colors group-hover:text-brand">
													{ homepage ? (
														<a href={ homepage } target="_blank" rel="noreferrer" className="text-inherit hover:underline">
															{ shortName( plugin.name ) }
														</a>
													) : (
														shortName( plugin.name )
													) }
												</h2>
												{ plugin.version ? (
													<span className="text-[12px] font-semibold text-[#6d6d74] dark:text-ink-muted">{ `v${ plugin.version }` }</span>
												) : null }
											</div>
										</div>

										{ plugin.description ? (
											<p className="m-0 text-[13px] leading-relaxed text-[#6c6c75] dark:text-ink-muted flex-1 overflow-hidden [display:-webkit-box] [-webkit-line-clamp:3] [-webkit-box-orient:vertical]">
												{ plugin.description }
											</p>
										) : null }

										<div className="flex items-center gap-2 flex-wrap pt-3 mt-auto border-t border-[#f3f3f6] dark:border-ink-border">
											<Badge tone={ status.tone }>{ status.label }</Badge>
											<span className="ml-auto flex items-center gap-2">
												{ plugin.status === 'not_installed' && settings.canInstall ? (
													<Button variant="gradient" size="sm" disabled={ !! busy.slug } onClick={ () => onInstall( plugin ) }>
														{ isBusy && busy.action === 'install'
															? __( 'Installing…', 'bdthemes-prime-slider-lite' )
															: __( 'Install', 'bdthemes-prime-slider-lite' ) }
													</Button>
												) : null }
												{ plugin.status === 'installed' && settings.canActivate && plugin.plugin_file && plugin.activate_nonce ? (
													<Button variant="outline" size="sm" disabled={ !! busy.slug } onClick={ () => onActivate( plugin ) }>
														{ isBusy && busy.action === 'activate'
															? __( 'Activating…', 'bdthemes-prime-slider-lite' )
															: __( 'Activate', 'bdthemes-prime-slider-lite' ) }
													</Button>
												) : null }
												{ homepage ? (
													<a href={ homepage } target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-[13px] font-bold text-brand hover:underline">
														{ __( 'Details', 'bdthemes-prime-slider-lite' ) }
														<ExternalIcon size={ 13 } />
													</a>
												) : null }
											</span>
										</div>
									</Card>
								);
							} ) }
						</div>
					) }
				</div>
			) }
		</div>
	);
}
