/**
 * Syntax-highlighted code field backed by WordPress's bundled CodeMirror
 * (`wp.codeEditor`), ported from Element Pack's CodeEditor. Per-language
 * settings come from Prime Slider Pro's page data (`codeEditor.{css,js}`,
 * the result of wp_enqueue_code_editor()). Falls back to a plain textarea
 * when CodeMirror is unavailable or the user disabled syntax highlighting in
 * their profile (the settings are then `false`).
 *
 * The editor skin (Element Pack's `.ep-code-editor` rules, incl. the dark
 * theme) is not in the shared stylesheet, so <CodeEditorStyles /> prints it,
 * scoped under `#ps-admin-app .ps-code-editor`. Render it once per page.
 */
import { useRef, useEffect } from '@wordpress/element';

export const TEXTAREA_CLASS =
	'w-full py-[10px] px-[14px] border border-[#e2e2e6] dark:border-[#2a313c] rounded-btn text-sm bg-white dark:bg-ink-card text-[#18181b] dark:text-ink-text outline-none placeholder:text-[#6d6d74] dark:placeholder:text-ink-muted focus:border-brand focus:shadow-[0_0_0_3px_rgba(252,106,42,.15)] font-mono text-[13px] leading-relaxed min-h-[140px]';

const ROOT = '#ps-admin-app .ps-code-editor';
const DARK = "#ps-admin-app[data-ps-theme='dark'] .ps-code-editor";

/*
 * Element Pack's editor rules with Prime Slider's brand focus ring. The app's
 * scoped reset zeroes every border inside #ps-admin-app (`border-width: 0` at
 * id specificity), which also removes CodeMirror's own cursor and search
 * dialog borders, so those are restored here.
 */
const EDITOR_CSS = `
${ ROOT } .CodeMirror {
	border: 1px solid #e2e2e6;
	border-radius: 11px;
	min-height: 190px;
	height: auto;
	font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
	font-size: 12.5px;
	line-height: 1.6;
	transition: border-color 0.15s, box-shadow 0.15s;
}
${ ROOT } .CodeMirror-scroll {
	min-height: 190px;
}
${ ROOT } .CodeMirror-focused {
	border-color: #fc6a2a;
	box-shadow: 0 0 0 3px rgba(252, 106, 42, 0.15);
}
${ ROOT } .CodeMirror-gutters {
	border-top-left-radius: 10px;
	border-bottom-left-radius: 10px;
	border-right: 1px solid #eef0f3;
	background: #fafbfc;
}
${ ROOT } .CodeMirror-cursor {
	border-left: 1px solid #000;
	border-right: 0;
}
${ ROOT } .CodeMirror-dialog-top {
	border-bottom: 1px solid #eee;
}
${ ROOT } .CodeMirror-dialog-bottom {
	border-top: 1px solid #eee;
}
${ ROOT } .CodeMirror-dialog input {
	border: 0;
	background: transparent;
	color: inherit;
}

${ DARK } .CodeMirror {
	background: #12161c;
	color: #e4e4e7;
	border-color: #262c36;
}
${ DARK } .CodeMirror-gutters {
	background: #161a21;
	border-right-color: #262c36;
}
${ DARK } .CodeMirror-linenumber {
	color: #5b6270;
}
${ DARK } .CodeMirror-cursor {
	border-left-color: #e4e4e7;
}
${ DARK } .CodeMirror-selected,
${ DARK } .CodeMirror-focused .CodeMirror-selected {
	background: rgba(252, 106, 42, 0.28);
}
${ DARK } .CodeMirror-activeline-background {
	background: rgba(255, 255, 255, 0.045);
}
${ DARK } .CodeMirror-matchingbracket {
	color: #84ed92 !important;
	background: rgba(132, 237, 146, 0.15);
}
${ DARK } .CodeMirror-dialog {
	background: #161a21;
	color: #e4e4e7;
	border-color: #262c36;
}
${ DARK } .cm-comment { color: #8b949e; }
${ DARK } .cm-string,
${ DARK } .cm-string-2 { color: #a5d6ff; }
${ DARK } .cm-number { color: #f2cc60; }
${ DARK } .cm-keyword { color: #ff7b72; }
${ DARK } .cm-operator { color: #ff7b72; }
${ DARK } .cm-def { color: #d2a8ff; }
${ DARK } .cm-variable { color: #e4e4e7; }
${ DARK } .cm-variable-2,
${ DARK } .cm-variable-3,
${ DARK } .cm-property { color: #79c0ff; }
${ DARK } .cm-atom { color: #56d364; }
${ DARK } .cm-qualifier { color: #d2a8ff; }
${ DARK } .cm-builtin { color: #ffa657; }
${ DARK } .cm-tag { color: #7ee787; }
${ DARK } .cm-attribute { color: #79c0ff; }
${ DARK } .cm-meta { color: #ffa657; }
${ DARK } .cm-error { color: #ffa198; }
`;

export function CodeEditorStyles() {
	return <style>{ EDITOR_CSS }</style>;
}

export default function CodeEditor( {
	value,
	onChange,
	settings,
	ariaLabel,
	placeholder,
	rows = 8,
} ) {
	const textareaRef = useRef( null );
	const cmRef = useRef( null );
	const onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;

	useEffect( () => {
		const textarea = textareaRef.current;
		const wpCodeEditor = window.wp && window.wp.codeEditor;

		if ( ! textarea || ! wpCodeEditor || ! settings ) {
			return undefined;
		}

		let cm = null;

		const init = () => {
			cm = wpCodeEditor.initialize( textarea, settings ).codemirror;
			cmRef.current = cm;

			cm.on( 'change', () => {
				if ( onChangeRef.current ) {
					onChangeRef.current( cm.getValue() );
				}
			} );
		};

		// The app mounts from a footer script, before DOMContentLoaded. When
		// this page is the first one shown, wp.codeEditor would warn that it
		// "ran too early", so wait for the document in that case.
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', init, { once: true } );
		} else {
			init();
		}

		return () => {
			document.removeEventListener( 'DOMContentLoaded', init );
			if ( cm ) {
				cm.toTextArea();
			}
			cmRef.current = null;
		};
	}, [ settings ] );

	// Push external updates (reset / save) into the live editor.
	useEffect( () => {
		const cm = cmRef.current;
		if ( cm && ( value ?? '' ) !== cm.getValue() ) {
			cm.setValue( value ?? '' );
		}
	}, [ value ] );

	return (
		<div className="ps-code-editor">
			<textarea
				ref={ textareaRef }
				aria-label={ ariaLabel }
				placeholder={ placeholder }
				rows={ rows }
				value={ value || '' }
				onChange={ ( event ) => onChange( event.target.value ) }
				className={ TEXTAREA_CLASS }
			/>
		</div>
	);
}
