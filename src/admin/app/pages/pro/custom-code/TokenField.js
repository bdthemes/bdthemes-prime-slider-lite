/**
 * Token picker for the page/post exclusion list. Element Pack uses
 * @wordpress/components' FormTokenField here; Prime Slider's bundle does not
 * load wp-components (script or stylesheet), so this reproduces that
 * control's look and keyboard behaviour natively: tokens + inline input in a
 * 40px bordered box, a suggestion list below, and a help line.
 *
 * Unlike FormTokenField it keys tokens by post ID, so two items with the same
 * title never collide, and only listed items can be added.
 */
import { useMemo, useRef, useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { XIcon } from '../../../components/ui/Icons';

export function itemLabel( item ) {
	const title = item.title || __( '(no title)', 'bdthemes-prime-slider-lite' );
	return item.type ? `${ title } (${ item.type })` : title;
}

function Highlight( { text, query } ) {
	const index = query ? text.toLowerCase().indexOf( query ) : -1;
	if ( index < 0 ) {
		return text;
	}
	return (
		<>
			{ text.slice( 0, index ) }
			<span className="underline">{ text.slice( index, index + query.length ) }</span>
			{ text.slice( index + query.length ) }
		</>
	);
}

export default function TokenField( { items = [], value = [], onChange, ariaLabel, help } ) {
	const [ query, setQuery ] = useState( '' );
	const [ open, setOpen ] = useState( false );
	const [ active, setActive ] = useState( -1 );
	const inputRef = useRef( null );
	const listRef = useRef( null );
	const listId = useRef( `ps-token-list-${ Math.random().toString( 36 ).slice( 2, 8 ) }` ).current;

	const byId = useMemo( () => {
		const map = new Map();
		items.forEach( ( item ) => map.set( item.id, item ) );
		return map;
	}, [ items ] );

	const needle = query.trim().toLowerCase();

	const suggestions = useMemo( () => {
		const chosen = new Set( value );
		return items.filter( ( item ) => {
			if ( chosen.has( item.id ) ) {
				return false;
			}
			if ( ! needle ) {
				return true;
			}
			return `${ itemLabel( item ) } ${ item.date || '' }`.toLowerCase().includes( needle );
		} );
	}, [ items, value, needle ] );

	const showList = open && suggestions.length > 0;

	// Keep the keyboard-highlighted suggestion in view.
	useEffect( () => {
		if ( ! listRef.current || active < 0 ) {
			return;
		}
		const node = listRef.current.children[ active ];
		if ( node && node.scrollIntoView ) {
			node.scrollIntoView( { block: 'nearest' } );
		}
	}, [ active ] );

	const add = ( id ) => {
		if ( ! value.includes( id ) ) {
			onChange( [ ...value, id ] );
		}
		setQuery( '' );
		setActive( -1 );
		setOpen( true );
		if ( inputRef.current ) {
			inputRef.current.focus();
		}
	};

	const remove = ( id ) => {
		onChange( value.filter( ( current ) => current !== id ) );
		if ( inputRef.current ) {
			inputRef.current.focus();
		}
	};

	const onKeyDown = ( event ) => {
		switch ( event.key ) {
			case 'ArrowDown':
				event.preventDefault();
				setOpen( true );
				setActive( ( current ) => Math.min( current + 1, suggestions.length - 1 ) );
				break;
			case 'ArrowUp':
				event.preventDefault();
				setActive( ( current ) => Math.max( current - 1, 0 ) );
				break;
			case 'Enter':
			case ',': {
				const pick = active >= 0 ? suggestions[ active ] : needle && suggestions[ 0 ];
				if ( event.key === 'Enter' || pick ) {
					event.preventDefault();
				}
				if ( pick ) {
					add( pick.id );
				}
				break;
			}
			case 'Backspace':
				if ( ! query && value.length ) {
					remove( value[ value.length - 1 ] );
				}
				break;
			case 'Escape':
				if ( open ) {
					event.preventDefault();
					setOpen( false );
					setActive( -1 );
				}
				break;
			default:
				break;
		}
	};

	return (
		<div>
			{ /* eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions */ }
			<div
				onClick={ () => inputRef.current && inputRef.current.focus() }
				className="flex flex-wrap items-center gap-1 min-h-[40px] py-[4px] px-2 border border-[#949494] dark:border-[#2a313c] rounded-[2px] bg-white dark:bg-ink-card cursor-text transition-shadow focus-within:border-brand focus-within:shadow-[0_0_0_.5px_#fc6a2a]"
			>
				{ value.map( ( id ) => {
					const item = byId.get( id );
					const label = item ? itemLabel( item ) : `#${ id }`;
					return (
						<span key={ id } className="inline-flex items-stretch text-[13px] leading-6 text-[#1e1e1e] dark:text-ink-text max-w-full">
							<span
								className="bg-[#ddd] dark:bg-[#262c36] rounded-l-[2px] pl-2 pr-1 truncate"
								title={ item && item.date ? `${ label } — ${ item.date }` : label }
							>
								{ label }
							</span>
							<button
								type="button"
								onClick={ ( event ) => {
									event.stopPropagation();
									remove( id );
								} }
								aria-label={ sprintf(
									/* translators: %s: page or post title. */
									__( 'Remove %s', 'bdthemes-prime-slider-lite' ),
									label
								) }
								className="bg-[#ddd] dark:bg-[#262c36] rounded-r-[2px] w-6 grid place-items-center text-[#1e1e1e] dark:text-ink-muted hover:text-[#cc1818] dark:hover:text-[#f87171]"
							>
								<XIcon size={ 12 } strokeWidth={ 2.4 } />
							</button>
						</span>
					);
				} ) }
				<input
					ref={ inputRef }
					type="text"
					role="combobox"
					aria-label={ ariaLabel }
					aria-expanded={ showList }
					aria-controls={ listId }
					aria-autocomplete="list"
					aria-activedescendant={ showList && active >= 0 ? `${ listId }-${ active }` : undefined }
					autoComplete="off"
					value={ query }
					onChange={ ( event ) => {
						setQuery( event.target.value );
						setOpen( true );
						setActive( event.target.value.trim() ? 0 : -1 );
					} }
					onFocus={ () => setOpen( true ) }
					onBlur={ () => {
						setOpen( false );
						setActive( -1 );
					} }
					onKeyDown={ onKeyDown }
					className="flex-1 min-w-[60px] h-[30px] min-h-0 m-0 p-0 px-1 border-0 rounded-none bg-transparent shadow-none focus:shadow-none outline-none focus:outline-none text-[13px] text-[#1e1e1e] dark:text-ink-text"
				/>
				{ showList ? (
					<ul
						ref={ listRef }
						id={ listId }
						role="listbox"
						className="basis-full min-w-full max-h-[128px] overflow-y-auto border-t border-[#949494] dark:border-[#2a313c] -mx-2 -mb-[4px] mt-1"
					>
						{ suggestions.map( ( item, index ) => (
							<li
								key={ item.id }
								id={ `${ listId }-${ index }` }
								role="option"
								aria-selected={ index === active }
								onMouseDown={ ( event ) => event.preventDefault() }
								onMouseEnter={ () => setActive( index ) }
								onClick={ () => add( item.id ) }
								className={ `flex items-center justify-between gap-3 min-h-[32px] py-1 px-3 text-[13px] cursor-pointer ${
									index === active
										? 'bg-brand text-white'
										: 'text-[#1e1e1e] dark:text-ink-text'
								}` }
							>
								<span className="truncate">
									<Highlight text={ itemLabel( item ) } query={ needle } />
								</span>
								{ item.date ? (
									<span className={ `flex-none text-[12px] ${ index === active ? 'text-white/80' : 'text-[#757575] dark:text-ink-muted' }` }>
										{ item.date }
									</span>
								) : null }
							</li>
						) ) }
					</ul>
				) : null }
			</div>
			{ help ? (
				<p className="text-[12px] text-[#757575] dark:text-ink-muted mt-2">{ help }</p>
			) : null }
		</div>
	);
}
