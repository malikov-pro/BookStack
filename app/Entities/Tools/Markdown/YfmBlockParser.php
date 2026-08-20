<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

class YfmBlockParser extends AbstractBlockContinueParser
{
    protected array $lines = [];

    public function __construct(
        protected YfmBlock $block,
        protected string $closingPattern,
    ) {
    }

    public function getBlock(): YfmBlock
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->match($this->closingPattern) !== null) {
            $this->block->markClosed();

            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    public function addLine(string $line): void
    {
        $this->lines[] = $line;
    }

    public function closeBlock(): void
    {
        $this->block->setContent(implode("\n", $this->lines));
    }
}
