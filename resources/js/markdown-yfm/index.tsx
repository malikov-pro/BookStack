import React, {useEffect} from 'react';
import {createRoot, Root} from 'react-dom/client';
import {
    configure as configureMarkdownEditor,
    MarkdownEditorInstance,
    MarkdownEditorView,
    MarkupString,
    useMarkdownEditor,
} from '@gravity-ui/markdown-editor';
import {
    configure as configureUIKit,
    ThemeProvider,
    Toaster,
    ToasterComponent,
    ToasterProvider,
} from '@gravity-ui/uikit';
import '@gravity-ui/uikit/styles/fonts.css';
import '@gravity-ui/uikit/styles/styles.css';
import '@gravity-ui/markdown-editor/styles/styles.css';
import './styles.css';
import {Actions, MarkdownYfmOptions} from './actions';

configureMarkdownEditor({lang: 'ru'});
configureUIKit({lang: 'ru'});

const toaster = new Toaster();

export interface MarkdownYfmEditor {
    actions: Actions;
    append(markdown: string): void;
    focus(): void;
    getValue(): string;
    insert(markdown: string): void;
    moveCursor(position: 'start' | 'end' | {line: number}): void;
    prepend(markdown: string): void;
    replace(markdown: string): void;
    unmount(): void;
}

interface EditorAppProps {
    initialValue: string;
    onChange: (markdown: string) => void;
    onReady: (editor: MarkdownEditorInstance) => void;
}

function EditorApp({initialValue, onChange, onReady}: EditorAppProps) {
    const editor = useMarkdownEditor({
        initial: {
            markup: initialValue as MarkupString,
            mode: 'wysiwyg',
        },
        md: {
            html: true,
            linkify: true,
        },
    });

    useEffect(() => {
        onReady(editor);
        const changeHandler = () => onChange(editor.getValue());
        editor.on('change', changeHandler);

        return () => editor.off('change', changeHandler);
    }, [editor, onChange, onReady]);

    return <MarkdownEditorView editor={editor} stickyToolbar={false} />;
}

function syncTextarea(textarea: HTMLTextAreaElement, markdown: string): void {
    textarea.value = markdown;
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
    textarea.dispatchEvent(new Event('change', {bubbles: true}));
    window.$events.emit('editor-markdown-change', '');
}

/**
 * Монтирует Gravity UI Markdown Editor и связывает его с полем формы BookStack.
 */
export function init(
    mountEl: HTMLElement,
    textarea: HTMLTextAreaElement,
    options: MarkdownYfmOptions,
): Promise<MarkdownYfmEditor> {
    return new Promise(resolve => {
        const root: Root = createRoot(mountEl);
        const isDarkMode = document.documentElement.classList.contains('dark-mode');
        let gravityEditor: MarkdownEditorInstance;

        const onChange = (markdown: string) => syncTextarea(textarea, markdown);
        const onReady = (editor: MarkdownEditorInstance) => {
            gravityEditor = editor;
            const controller: MarkdownYfmEditor = {
                actions: undefined as unknown as Actions,
                append: markdown => gravityEditor.append(markdown as MarkupString),
                focus: () => gravityEditor.focus(),
                getValue: () => gravityEditor.getValue(),
                insert: markdown => gravityEditor.insert(markdown as MarkupString),
                moveCursor: position => gravityEditor.moveCursor(position),
                prepend: markdown => gravityEditor.prepend(markdown as MarkupString),
                replace: markdown => gravityEditor.replace(markdown as MarkupString),
                unmount: () => root.unmount(),
            };
            controller.actions = new Actions(controller, textarea, options);
            syncTextarea(textarea, gravityEditor.getValue());
            resolve(controller);
        };

        root.render(
            <ThemeProvider theme={isDarkMode ? 'dark' : 'light'} lang="ru">
                <ToasterProvider toaster={toaster}>
                    <ToasterComponent />
                    <EditorApp
                        initialValue={textarea.value}
                        onChange={onChange}
                        onReady={onReady}
                    />
                </ToasterProvider>
            </ThemeProvider>,
        );
    });
}
