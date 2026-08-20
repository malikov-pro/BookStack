<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class YfmCutExtension implements ExtensionInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new YfmCutStartParser(), 100);
        $environment->addRenderer(YfmCutBlock::class, new YfmCutRenderer($this->renderMarkdown));
    }
}
