<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;

class YfmBlock extends AbstractBlock
{
    protected string $content = '';

    protected bool $closed = false;

    public function __construct(
        protected string $openingTag,
        protected array $attributes = [],
    ) {
    }

    public function setContent(string $content): void
    {
        $this->content = trim($content, "\n");
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function markClosed(): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function getAttribute(string $name, string $default = ''): string
    {
        return strval($this->attributes[$name] ?? $default);
    }

    public function getFallbackMarkdown(): string
    {
        return $this->openingTag . ($this->content === '' ? '' : "\n" . $this->content);
    }
}
