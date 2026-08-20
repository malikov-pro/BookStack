import * as DrawIO from '../services/drawio';
import type {EntitySelectorPopup, ImageManager} from '../components';
import type {MarkdownYfmEditor} from './index';

interface ImageManagerImage {
    id: number;
    name: string;
    thumbs?: {display: string};
    url: string;
}

export interface MarkdownYfmOptions {
    container: HTMLElement;
    drawioUrl: string;
    pageId: string;
    text: {
        imageUploadError: string;
        serverUploadLimit: string;
    };
}

export class Actions {
    constructor(
        protected readonly editor: MarkdownYfmEditor,
        protected readonly textarea: HTMLTextAreaElement,
        protected readonly options: MarkdownYfmOptions,
    ) {
    }

    showImageInsert(): void {
        const imageManager = window.$components.first('image-manager') as ImageManager|null;
        if (!imageManager) {
            return;
        }

        imageManager.show((image: ImageManagerImage) => {
            const imageUrl = image.thumbs?.display || image.url;
            this.replaceSelection(`[![${image.name}](${imageUrl})](${image.url})`);
        }, 'gallery');
    }

    showLinkSelector(): void {
        const selector = window.$components.first('entity-selector-popup') as EntitySelectorPopup|null;
        if (!selector) {
            return;
        }

        selector.show((entity: {name: string; link: string}) => {
            this.replaceSelection(`[${entity.name}](${entity.link})`);
        }, {
            initialValue: '',
            searchEndpoint: '/search/entity-selector',
            entityTypes: 'page,book,chapter,bookshelf',
            entityPermission: 'view',
        });
    }

    startDrawing(): void {
        if (!this.options.drawioUrl) {
            return;
        }

        DrawIO.show(this.options.drawioUrl, () => Promise.resolve(''), async pngData => {
            try {
                const response = await window.$http.post('/images/drawio', {
                    image: pngData,
                    uploaded_to: Number(this.options.pageId),
                });
                this.insertDrawing(response.data as ImageManagerImage);
                DrawIO.close();
            } catch (error) {
                this.handleDrawingUploadError(error);
                throw error;
            }
        });
    }

    fullScreen(): void {
        const alreadyFullscreen = this.options.container.classList.contains('fullscreen');
        this.options.container.classList.toggle('fullscreen', !alreadyFullscreen);
        document.body.classList.toggle('markdown-fullscreen', !alreadyFullscreen);
    }

    insertContent(markdown: string): void {
        this.replaceSelection(markdown);
    }

    focus(): void {
        this.editor.focus();
    }

    focusOnHeader(index: number): void {
        const lines = this.editor.getValue().split('\n');
        let headerIndex = -1;

        for (let line = 0; line < lines.length; line++) {
            if (/^#{1,6}\s+/.test(lines[line])) {
                headerIndex++;
                if (headerIndex === index) {
                    this.editor.moveCursor({line});
                    this.editor.focus();
                    return;
                }
            }
        }
    }

    protected insertDrawing(image: ImageManagerImage): void {
        this.replaceSelection(`<div drawio-diagram="${image.id}"><img src="${image.url}"></div>`);
    }

    protected replaceSelection(markdown: string): void {
        if (this.editor) {
            this.editor.insert(markdown);
            this.editor.focus();
            return;
        }

        const selectionStart = this.textarea.selectionStart;
        const selectionEnd = this.textarea.selectionEnd;
        this.textarea.setRangeText(markdown, selectionStart, selectionEnd, 'end');
        this.textarea.dispatchEvent(new Event('input', {bubbles: true}));
        this.textarea.dispatchEvent(new Event('change', {bubbles: true}));
    }

    protected handleDrawingUploadError(error: unknown): void {
        const status = (error as {status?: number}).status;
        const message = status === 413
            ? this.options.text.serverUploadLimit
            : this.options.text.imageUploadError;
        window.$events.emit('error', message);
        console.error(error);
    }
}
