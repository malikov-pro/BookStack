<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\CacheableDelimiterProcessorInterface;
use League\CommonMark\Node\Inline\AbstractStringContainer;

class YfmExactDelimiterProcessor implements CacheableDelimiterProcessorInterface
{
    /**
     * @param array<string, string> $htmlAttributes
     */
    public function __construct(
        protected string $character,
        protected int $length,
        protected string $tag,
        protected array $htmlAttributes = [],
    ) {
    }

    public function getOpeningCharacter(): string
    {
        return $this->character;
    }

    public function getClosingCharacter(): string
    {
        return $this->character;
    }

    public function getMinLength(): int
    {
        return $this->length;
    }

    /**
     * Возвращает число символов разделителя, если обе стороны набрали нужную длину.
     */
    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer): int
    {
        if ($opener->getLength() < $this->length || $closer->getLength() < $this->length) {
            return 0;
        }

        return $this->length;
    }

    /**
     * Оборачивает содержимое между разделителями в `YfmInlineMark`.
     */
    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse): void
    {
        $mark = new YfmInlineMark($this->tag, str_repeat($this->character, $delimiterUse), $this->htmlAttributes);
        $current = $opener->next();
        while ($current !== null && $current !== $closer) {
            $next = $current->next();
            $mark->appendChild($current);
            $current = $next;
        }

        $opener->insertAfter($mark);
    }

    public function getCacheKey(DelimiterInterface $closer): string
    {
        return $this->character . $this->length;
    }
}
