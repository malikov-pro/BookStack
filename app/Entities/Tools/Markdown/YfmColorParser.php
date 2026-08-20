<?php

namespace BookStack\Entities\Tools\Markdown;

use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

class YfmColorParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\{([a-zA-Z][\w-]*)\}\(');
    }

    /**
     * Разбирает конструкцию `{color}(текст)` в узел цвета.
     *
     * @return bool
     */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $previousState = $cursor->saveState();
        $color = $inlineContext->getMatches()[1] ?? '';
        if ($color === '') {
            return false;
        }

        $cursor->advanceBy($inlineContext->getFullMatchLength());
        $inner = $this->readBalancedParentheses($cursor->getRemainder());
        if ($inner === null) {
            $cursor->restoreState($previousState);

            return false;
        }

        $cursor->advanceBy(mb_strlen($inner, 'UTF-8') + 1);
        $node = new YfmColor($color);
        if ($inner !== '') {
            $node->appendChild(new Text($inner));
        }

        $inlineContext->getContainer()->appendChild($node);

        return true;
    }

    /**
     * Читает содержимое до закрывающей `)`, учитывая вложенные скобки.
     */
    protected function readBalancedParentheses(string $text): ?string
    {
        $depth = 1;
        $length = strlen($text);
        for ($index = 0; $index < $length; $index++) {
            $char = $text[$index];
            if ($char === '\\') {
                $index++;
                continue;
            }

            if ($char === '(') {
                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, 0, $index);
                }
            }
        }

        return null;
    }
}
