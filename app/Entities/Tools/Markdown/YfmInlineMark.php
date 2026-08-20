<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\DelimitedInterface;

class YfmInlineMark extends AbstractInline implements DelimitedInterface
{
    /**
     * @param array<string, string> $htmlAttributes
     */
    public function __construct(
        protected string $tag,
        protected string $delimiter,
        protected array $htmlAttributes = [],
    ) {
        parent::__construct();
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @return array<string, string>
     */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    public function getOpeningDelimiter(): string
    {
        return $this->delimiter;
    }

    public function getClosingDelimiter(): string
    {
        return $this->delimiter;
    }
}
