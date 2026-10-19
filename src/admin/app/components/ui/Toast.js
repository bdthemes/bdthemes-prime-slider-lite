/**
 * Toast provider + hook (mockup lines 1509–1516): dark pill, gradient check
 * tile, bottom-right, auto-dismiss.
 *
 * Usage:
 *   const showToast = useToast();
 *   showToast( __( 'Saved.', ... ) );
 */
import {
	createContext,
	useContext,
	useState,
	useCallback,
	useRef,
} from '@wordpress/element';
import { CheckIcon, WarnIcon } from './Icons';

const ToastContext = createContext( () => {} );

export function useToast() {
	return useContext( ToastContext );
}

export function ToastProvider( { children } ) {
	const [ toasts, setToasts ] = useState( [] );
	const counter = useRef( 0 );

	const showToast = useCallback( ( message, { tone = 'success' } = {} ) => {
		counter.current += 1;
		const id = counter.current;
		setToasts( ( current ) => [ ...current, { id, message, tone } ] );
		window.setTimeout( () => {
			setToasts( ( current ) =>
				current.filter( ( toast ) => toast.id !== id )
			);
		}, 3200 );
	}, [] );

	return (
		<ToastContext.Provider value={ showToast }>
			{ children }
			<div className="fixed bottom-6 right-6 z-[60] flex flex-col items-end gap-2">
				{ toasts.map( ( toast ) => (
					<div
						key={ toast.id }
						className="flex items-center gap-3 bg-[#18181b] text-white py-[13px] px-[18px] rounded-xl shadow-[0_18px_40px_-12px_rgba(0,0,0,.5)] text-[13.5px] font-medium animate-ps-pop"
					>
						<span
							className={ `w-6 h-6 rounded-full grid place-items-center flex-none ${
								toast.tone === 'error'
									? 'bg-[#f43f5e]'
									: 'bg-gradient-brand'
							}` }
						>
							{ toast.tone === 'error' ? (
								<WarnIcon size={ 13 } strokeWidth={ 2.4 } />
							) : (
								<CheckIcon size={ 14 } />
							) }
						</span>
						{ toast.message }
					</div>
				) ) }
			</div>
		</ToastContext.Provider>
	);
}
