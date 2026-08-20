<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\CacheableDelimiterProcessorInterface;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\AbstractStringContainer;

class YfmTildeDelimiterProcessor implements CacheableDelimiterProcessorInterface
{
    public function getOpeningCharacter(): string
    {
        return '~';
    }

    public function getClosingCharacter(): string
    {
        return '~';
    }

    public function getMinLength(): int
    {
        return 1;
    }

    /**
     * Выбирает `~~` как зачёркивание и одиночную `~` как нижний индекс.
     */
    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer): int
    {
        if ($opener->getLength() >= 2 && $closer->getLength() >= 2) {
            return 2;
        }

        if ($opener->getLength() === 1 && $closer->getLength() === 1) {
            return 1;
        }

        return 0;
    }

    /**
     * Оборачивает фрагмент в `<s>` или `<sub>` в зависимости от длины тильды.
     */
    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse): void
    {
        $mark = $delimiterUse === 2
            ? new Strikethrough('~~')
            : new YfmInlineMark('sub', '~');

        $this->wrapBetween($opener, $closer, $mark);
    }

    public function getCacheKey(DelimiterInterface $closer): string
    {
        return '~' . $closer->getLength();
    }

    protected function wrapBetween(AbstractStringContainer $opener, AbstractStringContainer $closer, AbstractInline $mark): void
    {
        $current = $opener->next();
        while ($current !== null && $current !== $closer) {
            $next = $current->next();
            $mark->appendChild($current);
            $current = $next;
        }

        $opener->insertAfter($mark);
    }
}
