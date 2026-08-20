<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

class YfmCutStartParser implements BlockStartParserInterface
{
    protected const OPEN_PATTERN = '/^\{% cut "([^"]*)" %\}[ \t]*$/';

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

        preg_match(self::OPEN_PATTERN, $openingTag, $matches);
        $block = new YfmCutBlock(trim($openingTag), ['title' => $matches[1]]);

        return BlockStart::of(new YfmBlockParser($block, '/^[ \t]*\{% endcut %\}[ \t]*$/'))->at($cursor);
    }
}
