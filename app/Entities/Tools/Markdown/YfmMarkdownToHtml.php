<?php

namespace BookStack\Entities\Tools\Markdown;

use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

class YfmMarkdownToHtml
{
    public function __construct(protected string $markdown)
    {
    }

    public function convert(): string
    {
        $renderMarkdown = static fn(string $markdown): string => (new self($markdown))->convert();

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());
        $environment->addExtension(new DescriptionListExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new YfmInlineMarksExtension());
        $environment->addExtension(new YfmNoteExtension($renderMarkdown));
        $environment->addExtension(new YfmCutExtension($renderMarkdown));
        $environment->addExtension(new YfmTabsExtension($renderMarkdown));
        $environment->addExtension(new YfmTableExtension($renderMarkdown));
        $environment = Theme::dispatch(ThemeEvents::COMMONMARK_ENVIRONMENT_CONFIGURE, $environment) ?? $environment;
        $environment->addRenderer(ListItem::class, new CustomListItemRenderer(), 10);
        $converter = new MarkdownConverter($environment);

        return $converter->convert($this->rewriteYfmImageSizes($this->markdown))->getContent();
    }

    /**
     * Переписывает YFM-размер картинки ` =ШxВ` в атрибуты CommonMark `{width height}`.
     */
    protected function rewriteYfmImageSizes(string $markdown): string
    {
        $rewritten = preg_replace_callback(
            '/(!\[[^\]]*\]\([^)]*?)\s+=([0-9]*)x([0-9]*)(\))/',
            static function (array $matches): string {
                $attributes = [];
                if ($matches[2] !== '') {
                    $attributes[] = 'width="' . $matches[2] . '"';
                }

                if ($matches[3] !== '') {
                    $attributes[] = 'height="' . $matches[3] . '"';
                }

                if (count($attributes) === 0) {
                    return $matches[1] . $matches[4];
                }

                return $matches[1] . $matches[4] . '{' . implode(' ', $attributes) . '}';
            },
            $markdown
        );

        return is_string($rewritten) ? $rewritten : $markdown;
    }
}
