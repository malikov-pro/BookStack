<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class YfmTableRenderer implements NodeRendererInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    /**
     * Собирает HTML `<table>` из разобранного YFM-блока `#| … |#`.
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        YfmTableBlock::assertInstanceOf($node);
        /** @var YfmTableBlock $node */

        if (!$node->isClosed()) {
            return htmlspecialchars($node->getFallbackMarkdown(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $parsed = (new YfmTableContentParser())->parse($node->getContent());
        if (count($parsed['rows']) === 0) {
            return htmlspecialchars($node->getFallbackMarkdown() . "\n|#", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $headerRows = $parsed['headerRows'];
        $tableAttributes = $headerRows > 0
            ? ' data-header-rows="' . $headerRows . '"'
            : '';
        $rowsHtml = '';

        foreach ($parsed['rows'] as $rowIndex => $row) {
            $isHeaderRow = $headerRows > 0 && $rowIndex < $headerRows;
            $cellTag = $isHeaderRow ? 'th' : 'td';
            $rowAttributes = $isHeaderRow ? ' data-header="true"' : '';
            $cellsHtml = '';

            foreach ($row as $cell) {
                if ($cell['skip']) {
                    continue;
                }

                $attributes = $this->cellAttributes($cell, $isHeaderRow);
                $content = $cell['text'] === ''
                    ? ''
                    : ($this->renderMarkdown)($cell['text']);
                $cellsHtml .= '<' . $cellTag . $attributes . '>' . $content . '</' . $cellTag . '>';
            }

            $rowsHtml .= '<tr' . $rowAttributes . '>' . $cellsHtml . '</tr>';
        }

        return '<table' . $tableAttributes . '><tbody>' . $rowsHtml . '</tbody></table>';
    }

    /**
     * Собирает HTML-атрибуты ячейки: scope, span, выравнивание и фон.
     *
     * @param array{text: string, attrs: array<string, string>, skip: bool, colspan: int, rowspan: int} $cell
     */
    protected function cellAttributes(array $cell, bool $isHeaderRow): string
    {
        $attributes = [];
        if ($isHeaderRow) {
            $attributes[] = 'scope="col"';
        }

        if ($cell['colspan'] > 1) {
            $attributes[] = 'colspan="' . $cell['colspan'] . '"';
        }

        if ($cell['rowspan'] > 1) {
            $attributes[] = 'rowspan="' . $cell['rowspan'] . '"';
        }

        $classes = [];
        if (($cell['attrs']['class'] ?? '') !== '') {
            $classes[] = $cell['attrs']['class'];
        }

        if (($cell['attrs']['align'] ?? '') !== '') {
            $align = $cell['attrs']['align'];
            $attributes[] = 'data-align="' . htmlspecialchars($align, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            $classes[] = 'cell-align-' . $align;
        }

        if (($cell['attrs']['bg'] ?? '') !== '') {
            $background = $cell['attrs']['bg'];
            $attributes[] = 'data-bg="' . htmlspecialchars($background, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            $classes[] = 'cell-bg-' . $background;
        }

        $classes = array_filter(array_unique($classes));
        if (count($classes) > 0) {
            $attributes[] = 'class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return count($attributes) === 0 ? '' : ' ' . implode(' ', $attributes);
    }
}
