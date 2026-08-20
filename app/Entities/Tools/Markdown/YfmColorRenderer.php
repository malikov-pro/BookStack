<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class YfmColorRenderer implements NodeRendererInterface
{
    /**
     * Рендерит цветной фрагмент как `span.yfm-colorify`.
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        YfmColor::assertInstanceOf($node);
        /** @var YfmColor $node */

        $color = htmlspecialchars($node->getColor(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return new HtmlElement(
            'span',
            [
                'class'      => 'yfm-colorify yfm-colorify--' . $color,
                'data-color' => $color,
            ],
            $childRenderer->renderNodes($node->children())
        );
    }
}
