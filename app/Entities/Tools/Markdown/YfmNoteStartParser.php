<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

class YfmNoteStartParser implements BlockStartParserInterface
{
    protected const OPEN_PATTERN = '/^\{% note (info|tip|warning|important)(?:\s+"([^"]*)")?\s*%\}[ \t]*$/';

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
        $attributes = [
            'type'     => $matches[1],
            'title'    => array_key_exists(2, $matches) ? $matches[2] : '',
            'hasTitle' => array_key_exists(2, $matches) ? '1' : '0',
        ];
        $block = new YfmNoteBlock(trim($openingTag), $attributes);

        return BlockStart::of(new YfmBlockParser($block, '/^[ \t]*\{% endnote %\}[ \t]*$/'))->at($cursor);
    }
}
