import {Component} from './component';

export class MarkdownYfmEditor extends Component {

    setup() {
        this.container = this.$el;
        this.textarea = this.$refs.input;
        this.mount = this.$refs.mount;
        this.editor = null;

        this.setupToolbar();
        this.setupFormSync();

        window.importVersioned('markdown-yfm').then(markdownYfm => {
            return markdownYfm.init(this.mount, this.textarea, {
                container: this.container,
                drawioUrl: this.getDrawioUrl(),
                pageId: this.$opts.pageId,
                text: {
                    imageUploadError: this.$opts.imageUploadErrorText,
                    serverUploadLimit: this.$opts.serverUploadLimitText,
                },
            });
        }).then(editor => {
            this.editor = editor;
            this.setupEditorEvents();
        });
    }

    setupToolbar() {
        this.container.addEventListener('click', event => {
            const button = event.target.closest('button[data-action]');
            if (!button || !this.editor) {
                return;
            }

            const action = button.getAttribute('data-action');
            if (action === 'insertImage') this.editor.actions.showImageInsert();
            if (action === 'insertLink') this.editor.actions.showLinkSelector();
            if (action === 'insertDrawing') this.editor.actions.startDrawing();
            if (action === 'fullscreen') this.editor.actions.fullScreen();
        });
    }

    setupFormSync() {
        this.container.closest('form')?.addEventListener('submit', () => {
            if (this.editor) {
                this.textarea.value = this.editor.getValue();
            }
        }, {capture: true});
    }

    setupEditorEvents() {
        const contentToMarkdown = ({html, markdown}) => markdown || html;

        window.$events.listen('editor::replace', content => {
            this.editor.replace(contentToMarkdown(content));
        });
        window.$events.listen('editor::append', content => {
            this.editor.append(contentToMarkdown(content));
        });
        window.$events.listen('editor::prepend', content => {
            this.editor.prepend(contentToMarkdown(content));
        });
        window.$events.listen('editor::insert', content => {
            this.editor.actions.insertContent(contentToMarkdown(content));
        });
        window.$events.listen('editor::focus', () => this.editor.actions.focus());
        window.$events.listen('editor::focus-heading', ({index}) => {
            this.editor.actions.focusOnHeader(index);
        });
    }

    getDrawioUrl() {
        return document.querySelector('[drawio-url]')?.getAttribute('drawio-url') || '';
    }

    /**
     * Возвращает Markdown страницы; HTML формируется на сервере.
     *
     * @returns {Promise<{html: string, markdown: string}>}
     */
    async getContent() {
        return {
            markdown: this.editor?.getValue() || this.textarea.value,
            html: '',
        };
    }

}
