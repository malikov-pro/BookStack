<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class YfmNoteExtension implements ExtensionInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new YfmNoteStartParser(), 100);
        $environment->addRenderer(YfmNoteBlock::class, new YfmNoteRenderer($this->renderMarkdown));
    }
}
