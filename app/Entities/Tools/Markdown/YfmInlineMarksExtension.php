<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Strikethrough\Strikethrough;

class YfmInlineMarksExtension implements ExtensionInterface
{
    /**
     * Подключает инлайн-разметку Gravity/YFM: monospace, underline, mark, sup/sub, цвет.
     */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addDelimiterProcessor(new YfmExactDelimiterProcessor('#', 2, 'samp'));
        $environment->addDelimiterProcessor(new YfmExactDelimiterProcessor('+', 2, 'u'));
        $environment->addDelimiterProcessor(new YfmExactDelimiterProcessor('=', 2, 'mark'));
        $environment->addDelimiterProcessor(new YfmExactDelimiterProcessor('^', 1, 'sup'));
        $environment->addDelimiterProcessor(new YfmTildeDelimiterProcessor());
        $environment->addRenderer(YfmInlineMark::class, new YfmInlineMarkRenderer());
        $environment->addRenderer(Strikethrough::class, new CustomStrikethroughRenderer());
        $environment->addInlineParser(new YfmColorParser());
        $environment->addRenderer(YfmColor::class, new YfmColorRenderer());
    }
}
