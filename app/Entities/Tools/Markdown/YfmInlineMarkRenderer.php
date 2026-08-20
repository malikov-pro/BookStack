<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class YfmInlineMarkRenderer implements NodeRendererInterface
{
    /**
     * Рендерит инлайн-метку YFM в HTML-тег (`samp`, `u`, `mark`, `sup`, `sub`).
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        YfmInlineMark::assertInstanceOf($node);
        /** @var YfmInlineMark $node */

        return new HtmlElement(
            $node->getTag(),
            $node->getHtmlAttributes(),
            $childRenderer->renderNodes($node->children())
        );
    }
}
