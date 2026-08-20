<?php

namespace BookStack\Entities\Tools;

use BookStack\Entities\Models\Page;

enum PageEditorType: string
{
    case WysiwygTinymce = 'wysiwyg';
    case WysiwygLexical = 'wysiwyg2024';
    case Markdown = 'markdown';
    case MarkdownYfm = 'markdown2026';

    public function isHtmlBased(): bool
    {
        return match ($this) {
            self::WysiwygTinymce, self::WysiwygLexical => true,
            self::Markdown, self::MarkdownYfm => false,
        };
    }

    public function isMarkdownBased(): bool
    {
        return match ($this) {
            self::Markdown, self::MarkdownYfm => true,
            self::WysiwygTinymce, self::WysiwygLexical => false,
        };
    }

    public static function fromRequestValue(string $value): static|null
    {
        $exactEditor = static::tryFrom($value);
        if ($exactEditor) {
            return $exactEditor;
        }

        $editor = explode('-', $value)[0];
        return static::tryFrom($editor);
    }

    public static function forPage(Page $page): static|null
    {
        return static::tryFrom($page->editor);
    }

    public static function getSystemDefault(): static
    {
        $setting = setting('app-editor');
        return static::tryFrom($setting) ?? static::WysiwygTinymce;
    }
}
