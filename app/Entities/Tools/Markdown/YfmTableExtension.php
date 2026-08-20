<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class YfmTableExtension implements ExtensionInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    /**
     * Подключает парсер и рендерер YFM-таблиц.
     */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new YfmTableStartParser(), 110);
        $environment->addRenderer(YfmTableBlock::class, new YfmTableRenderer($this->renderMarkdown));
    }
}
