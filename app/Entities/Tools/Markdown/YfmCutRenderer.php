<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class YfmCutRenderer implements NodeRendererInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        YfmCutBlock::assertInstanceOf($node);
        /** @var YfmCutBlock $node */

        if (!$node->isClosed()) {
            return htmlspecialchars($node->getFallbackMarkdown(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $title = htmlspecialchars($node->getAttribute('title'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contentHtml = ($this->renderMarkdown)($node->getContent());

        return '<div class="yfm-cut">'
            . '<div class="yfm-cut-title" tabindex="0">' . $title . '</div>'
            . '<div class="yfm-cut-content">' . $contentHtml . '</div>'
            . '</div>';
    }
}
