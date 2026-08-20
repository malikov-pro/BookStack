<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

class YfmTabsStartParser implements BlockStartParserInterface
{
    protected const OPEN_PATTERN = '/^\{% list tabs(?:\s+([^}]*\S))?\s*%\}[ \t]*$/';

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
        $properties = $matches[1] ?? '';
        preg_match('/(?:^|\s)group=([^\s]+)/', $properties, $groupMatch);
        $block = new YfmTabsBlock(trim($openingTag), ['group' => $groupMatch[1] ?? '']);

        return BlockStart::of(new YfmBlockParser($block, '/^[ \t]*\{% endlist %\}[ \t]*$/'))->at($cursor);
    }
}
