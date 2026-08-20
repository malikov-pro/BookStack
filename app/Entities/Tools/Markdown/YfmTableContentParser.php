<?php

namespace BookStack\Entities\Tools\Markdown;

class YfmTableContentParser
{
    protected const COLSPAN_SYMBOL = '>';
    protected const ROWSPAN_SYMBOL = '^';

    /**
     * Разбирает тело YFM-таблицы между `#|` и `|#`.
     *
     * @return array{headerRows: int, rows: array<int, array<int, array{text: string, attrs: array<string, string>}> >}
     */
    public function parse(string $content): array
    {
        $position = 0;
        $length = strlen($content);
        $headerRows = $this->consumeTableHeaderRows($content, $position, $length);
        $rows = $this->scanRows($content, $position, $length);

        return [
            'headerRows' => $headerRows,
            'rows'       => $this->applySpans($rows),
        ];
    }

    /**
     * Читает строки вида `|:{header-rows="1"}` до первой строки таблицы.
     */
    protected function consumeTableHeaderRows(string $source, int &$position, int $length): int
    {
        $headerRows = 0;

        while ($position < $length) {
            $this->skipSpaces($source, $position, $length);
            if ($position >= $length) {
                break;
            }

            if ($this->isAt($source, $position, '||') || $this->isAt($source, $position, '|#')) {
                break;
            }

            if (!$this->isAt($source, $position, '|:')) {
                break;
            }

            $lineEnd = $this->lineEnd($source, $position, $length);
            $line = substr($source, $position, $lineEnd - $position);
            if (preg_match('/header-rows\s*=\s*"?(\d+)"?/', $line, $matches)) {
                $headerRows = max(0, (int) $matches[1]);
            }

            $position = $lineEnd;
            $this->skipNewline($source, $position, $length);
        }

        return $headerRows;
    }

    /**
     * Сканирует строки и ячейки по правилам Diplodoc: `||` открывает/закрывает ряд, `|` делит ячейки.
     *
     * @return array<int, array<int, array{text: string, attrs: array<string, string>, skip: bool, colspan: int, rowspan: int}>>
     */
    protected function scanRows(string $source, int $position, int $length): array
    {
        $rows = [];
        $currentRow = [];
        $currentCell = '';
        $pendingAttrs = [];
        $insideRow = false;
        $insideFence = false;
        $nestLevel = 0;

        while ($position < $length) {
            if (!$insideFence && $this->isAt($source, $position, '```')) {
                $insideFence = true;
                $currentCell .= '```';
                $position += 3;
                continue;
            }

            if ($insideFence && $this->isAt($source, $position, '```')) {
                $insideFence = false;
                $currentCell .= '```';
                $position += 3;
                continue;
            }

            if ($insideFence) {
                $currentCell .= $source[$position];
                $position++;
                continue;
            }

            $skippedCode = $this->skipInlineCode($source, $position, $length);
            if ($skippedCode !== null) {
                $currentCell .= $skippedCode['text'];
                $position = $skippedCode['end'];
                continue;
            }

            if ($this->isAt($source, $position, '#|') && !$this->isEscaped($source, $position)) {
                $nestLevel++;
                $currentCell .= '#|';
                $position += 2;
                continue;
            }

            if ($this->isAt($source, $position, '|#') && !$this->isEscaped($source, $position)) {
                if ($nestLevel > 0) {
                    $nestLevel--;
                    $currentCell .= '|#';
                    $position += 2;
                    continue;
                }

                break;
            }

            if ($nestLevel > 0) {
                $currentCell .= $source[$position];
                $position++;
                continue;
            }

            if ($this->isAt($source, $position, '||') && !$this->isEscaped($source, $position)) {
                if ($insideRow) {
                    $this->pushCell($currentRow, $currentCell, $pendingAttrs);
                    if (count($currentRow) > 0) {
                        $rows[] = $currentRow;
                    }
                    $currentRow = [];
                    $currentCell = '';
                    $pendingAttrs = [];
                    $insideRow = false;
                } else {
                    $position += 2;
                    $pendingAttrs = $this->consumeInlineAttrs($source, $position, $length, '::');
                    $insideRow = true;
                    $currentCell = '';
                    continue;
                }

                $position += 2;
                continue;
            }

            if (
                $insideRow
                && $this->isAt($source, $position, '|')
                && !$this->isEscaped($source, $position)
            ) {
                $this->pushCell($currentRow, $currentCell, $pendingAttrs);
                $position++;
                $pendingAttrs = $this->consumeInlineAttrs($source, $position, $length, '::');
                $currentCell = '';
                continue;
            }

            $currentCell .= $source[$position];
            $position++;
        }

        if ($insideRow) {
            $this->pushCell($currentRow, $currentCell, $pendingAttrs);
            if (count($currentRow) > 0) {
                $rows[] = $currentRow;
            }
        }

        return $rows;
    }

    /**
     * Добавляет ячейку в текущий ряд после нормализации текста и атрибутов выравнивания.
     *
     * @param array<int, array{text: string, attrs: array<string, string>, skip: bool, colspan: int, rowspan: int}> $row
     * @param array<string, string> $attrs
     */
    protected function pushCell(array &$row, string $text, array $attrs): void
    {
        $text = $this->extractLegacyAlign($text, $attrs);
        $row[] = [
            'text'    => trim($text),
            'attrs'   => $attrs,
            'skip'    => false,
            'colspan' => 1,
            'rowspan' => 1,
        ];
    }

    /**
     * Переносит устаревший маркер `{.cell-align-*}` из текста ячейки в атрибуты.
     *
     * @param array<string, string> $attrs
     */
    protected function extractLegacyAlign(string $text, array &$attrs): string
    {
        if (preg_match('/\{(\.cell-align-[a-z-]+|\.cell-bg-[a-z0-9-]+)\}\s*$/', $text, $matches)) {
            $class = ltrim($matches[1], '.');
            $attrs['class'] = trim(($attrs['class'] ?? '') . ' ' . $class);
            $text = substr($text, 0, -strlen($matches[0]));
        }

        return $text;
    }

    /**
     * Применяет символы `>` и `^` как colspan/rowspan и помечает служебные ячейки.
     *
     * @param array<int, array<int, array{text: string, attrs: array<string, string>, skip: bool, colspan: int, rowspan: int}>> $rows
     * @return array<int, array<int, array{text: string, attrs: array<string, string>, skip: bool, colspan: int, rowspan: int}>>
     */
    protected function applySpans(array $rows): array
    {
        $rowCount = count($rows);
        if ($rowCount === 0) {
            return $rows;
        }

        $columnCount = 0;
        foreach ($rows as $row) {
            $columnCount = max($columnCount, count($row));
        }

        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            while (count($rows[$rowIndex]) < $columnCount) {
                $rows[$rowIndex][] = [
                    'text'    => '',
                    'attrs'   => [],
                    'skip'    => false,
                    'colspan' => 1,
                    'rowspan' => 1,
                ];
            }
        }

        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $symbol = $rows[$rowIndex][$columnIndex]['text'];
                if ($symbol === self::COLSPAN_SYMBOL && $columnIndex > 0) {
                    $rows[$rowIndex][$columnIndex]['skip'] = true;
                    $span = 2;
                    for ($lookBehind = $columnIndex - 1; $lookBehind >= 0; $lookBehind--) {
                        if ($rows[$rowIndex][$lookBehind]['text'] === self::COLSPAN_SYMBOL) {
                            $span++;
                            $rows[$rowIndex][$lookBehind]['skip'] = true;
                            continue;
                        }

                        $rows[$rowIndex][$lookBehind]['colspan'] = $span;
                        break;
                    }
                }

                if ($symbol === self::ROWSPAN_SYMBOL && $rowIndex > 0) {
                    $rows[$rowIndex][$columnIndex]['skip'] = true;
                    $span = 2;
                    for ($lookAbove = $rowIndex - 1; $lookAbove >= 0; $lookAbove--) {
                        if ($rows[$lookAbove][$columnIndex]['text'] === self::ROWSPAN_SYMBOL) {
                            $span++;
                            $rows[$lookAbove][$columnIndex]['skip'] = true;
                            continue;
                        }

                        $rows[$lookAbove][$columnIndex]['rowspan'] = $span;
                        break;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Читает атрибуты ячейки вида `::{bg="gray"}` сразу после разделителя.
     *
     * @return array<string, string>
     */
    protected function consumeInlineAttrs(string $source, int &$position, int $length, string $prefix): array
    {
        if (!$this->isAt($source, $position, $prefix . '{')) {
            return [];
        }

        $position += strlen($prefix);
        $close = strpos($source, '}', $position);
        if ($close === false || $close >= $this->lineEnd($source, $position, $length)) {
            return [];
        }

        $raw = substr($source, $position + 1, $close - $position - 1);
        $position = $close + 1;
        $attrs = [];
        if (preg_match_all('/([a-zA-Z][\w-]*)\s*=\s*"([^"]*)"/', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrs[$match[1]] = $match[2];
            }
        }

        return $attrs;
    }

    /**
     * Пропускает инлайн-код в обратных кавычках, чтобы `|` внутри него не делил ячейки.
     *
     * @return array{text: string, end: int}|null
     */
    protected function skipInlineCode(string $source, int $position, int $length): ?array
    {
        if ($position >= $length || $source[$position] !== '`' || $this->isEscaped($source, $position)) {
            return null;
        }

        $start = $position;
        while ($position < $length && $source[$position] === '`') {
            $position++;
        }

        $markerLength = $position - $start;
        $searchFrom = $position;
        while ($searchFrom < $length) {
            $match = strpos($source, str_repeat('`', $markerLength), $searchFrom);
            if ($match === false) {
                return null;
            }

            $end = $match + $markerLength;
            if (($source[$end] ?? '') === '`') {
                $searchFrom = $end;
                continue;
            }

            return [
                'text' => substr($source, $start, $end - $start),
                'end'  => $end,
            ];
        }

        return null;
    }

    protected function isAt(string $source, int $position, string $needle): bool
    {
        return substr($source, $position, strlen($needle)) === $needle;
    }

    protected function isEscaped(string $source, int $position): bool
    {
        $slashes = 0;
        $index = $position - 1;
        while ($index >= 0 && $source[$index] === '\\') {
            $slashes++;
            $index--;
        }

        return $slashes % 2 === 1;
    }

    protected function skipSpaces(string $source, int &$position, int $length): void
    {
        while ($position < $length && ($source[$position] === ' ' || $source[$position] === "\t")) {
            $position++;
        }
    }

    protected function skipNewline(string $source, int &$position, int $length): void
    {
        if ($position < $length && $source[$position] === "\r") {
            $position++;
        }

        if ($position < $length && $source[$position] === "\n") {
            $position++;
        }
    }

    protected function lineEnd(string $source, int $position, int $length): int
    {
        $end = strpos($source, "\n", $position);
        if ($end === false) {
            return $length;
        }

        if ($end > 0 && $source[$end - 1] === "\r") {
            return $end - 1;
        }

        return $end;
    }
}
