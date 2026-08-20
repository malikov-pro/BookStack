<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

class YfmTableStartParser implements BlockStartParserInterface
{
    protected const OPEN_PATTERN = '/^#\|/';

    /**
     * Начинает блок YFM-таблицы по маркеру `#|`.
     *
     * @return BlockStart|null
     */
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();
        $openingTag = $cursor->match(self::OPEN_PATTERN);
        if ($openingTag === null) {
            return BlockStart::none();
        }

        $block = new YfmTableBlock('#|');

        return BlockStart::of(new YfmBlockParser($block, '/^[ \t]*\|#[ \t]*$/'))->at($cursor);
    }
}
