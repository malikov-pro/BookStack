<?php

namespace BookStack\Entities\Tools\Markdown;

use Closure;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class YfmTabsRenderer implements NodeRendererInterface
{
    public function __construct(protected Closure $renderMarkdown)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        YfmTabsBlock::assertInstanceOf($node);
        /** @var YfmTabsBlock $node */

        if (!$node->isClosed()) {
            return htmlspecialchars($node->getFallbackMarkdown(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $tabs = $this->parseTabs($node->getContent());
        if (count($tabs) === 0) {
            return htmlspecialchars($node->getFallbackMarkdown() . "\n{% endlist %}", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $groupId = substr(hash('sha256', $node->getContent() . ':' . ($node->getStartLine() ?? 0)), 0, 12);
        $group = $node->getAttribute('group');
        $groupAttribute = $group === ''
            ? ''
            : ' data-diplodoc-group="' . htmlspecialchars($group, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        $tabListHtml = '';
        $panelsHtml = '';

        foreach ($tabs as $index => $tab) {
            $isActive = $index === 0;
            $tabId = 'yfm-tab-' . $groupId . '-' . $index;
            $panelId = 'yfm-tab-panel-' . $groupId . '-' . $index;
            $title = htmlspecialchars($tab['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $activeClass = $isActive ? ' active' : '';

            $tabListHtml .= '<div id="' . $tabId . '" class="yfm-tab yfm-tab-group' . $activeClass . '"'
                . ' role="tab" aria-controls="' . $panelId . '"'
                . ' aria-selected="' . ($isActive ? 'true' : 'false') . '"'
                . ' tabindex="' . ($isActive ? '0' : '-1') . '"'
                . ' data-diplodoc-id="' . $tabId . '"'
                . ' data-diplodoc-key="' . $title . '"'
                . ' data-diplodoc-is-active="' . ($isActive ? 'true' : 'false') . '">'
                . $title
                . '</div>';

            $panelsHtml .= '<div id="' . $panelId . '" class="yfm-tab-panel' . $activeClass . '"'
                . ' role="tabpanel" aria-labelledby="' . $tabId . '" data-title="' . $title . '">'
                . ($this->renderMarkdown)($tab['content'])
                . '</div>';
        }

        return '<div class="yfm-tabs" data-diplodoc-variant="regular"' . $groupAttribute . '>'
            . '<div class="yfm-tab-list" role="tablist">' . $tabListHtml . '</div>'
            . $panelsHtml
            . '</div>';
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    protected function parseTabs(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content, "\n")) ?: [];
        $tabs = [];
        $currentTitle = null;
        $currentContent = [];

        foreach ($lines as $line) {
            if (preg_match('/^-\s+(.+?)\s*$/', $line, $matches)) {
                if ($currentTitle !== null) {
                    $tabs[] = [
                        'title'   => $currentTitle,
                        'content' => $this->normaliseContent($currentContent),
                    ];
                }

                $currentTitle = $matches[1];
                $currentContent = [];
                continue;
            }

            if ($currentTitle !== null) {
                $currentContent[] = $line;
            }
        }

        if ($currentTitle !== null) {
            $tabs[] = [
                'title'   => $currentTitle,
                'content' => $this->normaliseContent($currentContent),
            ];
        }

        return $tabs;
    }

    protected function normaliseContent(array $lines): string
    {
        while (($lines[0] ?? null) === '') {
            array_shift($lines);
        }

        while (($lines[count($lines) - 1] ?? null) === '') {
            array_pop($lines);
        }

        foreach ($lines as &$line) {
            if (str_starts_with($line, '  ')) {
                $line = substr($line, 2);
            } elseif (str_starts_with($line, "\t")) {
                $line = substr($line, 1);
            }
        }
        unset($line);

        return implode("\n", $lines);
    }
}
