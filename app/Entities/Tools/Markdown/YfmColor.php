<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

class YfmColor extends AbstractInline
{
    public function __construct(protected string $color)
    {
        parent::__construct();
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
