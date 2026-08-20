<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class YfmNoteRenderer implements NodeRendererInterface
{
    protected const DEFAULT_TITLES = [
        'info'      => 'Info',
        'tip'       => 'Tip',
        'warning'   => 'Warning',
        'important' => 'Important',
    ];

    public function __construct(protected Closure $renderMarkdown)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        YfmNoteBlock::assertInstanceOf($node);
        /** @var YfmNoteBlock $node */

        if (!$node->isClosed()) {
            return htmlspecialchars($node->getFallbackMarkdown(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $type = $node->getAttribute('type');
        $title = $node->getAttribute('hasTitle') === '1'
            ? $node->getAttribute('title')
            : self::DEFAULT_TITLES[$type];
        $titleHtml = $title === ''
            ? ''
            : '<p class="yfm-note-title">' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        $contentHtml = ($this->renderMarkdown)($node->getContent());

        return '<div class="yfm-note yfm-accent-' . $type . '" note-type="' . $type . '">'
            . $titleHtml
            . '<div class="yfm-note-content">' . $contentHtml . '</div>'
            . '</div>';
    }
}
