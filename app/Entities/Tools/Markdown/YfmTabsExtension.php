<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class YfmTabsExtension implements ExtensionInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new YfmTabsStartParser(), 100);
        $environment->addRenderer(YfmTabsBlock::class, new YfmTabsRenderer($this->renderMarkdown));
    }
}
