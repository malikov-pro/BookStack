<?php

namespace Tests\Entity;

use BookStack\Entities\Tools\Markdown\MarkdownToHtml;
use BookStack\Entities\Tools\Markdown\YfmMarkdownToHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YfmMarkdownToHtmlTest extends TestCase
{
    public function test_standard_converter_does_not_render_yfm_notes(): void
    {
        $html = (new MarkdownToHtml('{% note info %}x{% endnote %}'))->convert();

        static::assertStringNotContainsString('yfm-note', $html);
        static::assertStringContainsString('{% note info %}x{% endnote %}', $html);
    }

    #[DataProvider('noteTypeProvider')]
    public function test_yfm_converter_renders_note_types(string $type, string $title): void
    {
        $markdown = "{% note {$type} %}\n\nNote body\n\n{% endnote %}";
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString("class=\"yfm-note yfm-accent-{$type}\"", $html);
        static::assertStringContainsString("note-type=\"{$type}\"", $html);
        static::assertStringContainsString("<p class=\"yfm-note-title\">{$title}</p>", $html);
        static::assertStringContainsString('<div class="yfm-note-content"><p>Note body</p>', $html);
    }

    public static function noteTypeProvider(): array
    {
        return [
            'info'      => ['info', 'Info'],
            'tip'       => ['tip', 'Tip'],
            'warning'   => ['warning', 'Warning'],
            'important' => ['important', 'Important'],
        ];
    }

    public function test_yfm_converter_renders_cut_and_escapes_title(): void
    {
        $markdown = "{% cut \"<script>alert(1)</script>\" %}\n\nCut body\n\n{% endcut %}";
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('<div class="yfm-cut">', $html);
        static::assertStringContainsString('<div class="yfm-cut-title" tabindex="0">&lt;script&gt;alert(1)&lt;/script&gt;</div>', $html);
        static::assertStringContainsString('<div class="yfm-cut-content"><p>Cut body</p>', $html);
        static::assertStringNotContainsString('<script>', $html);
    }

    public function test_yfm_converter_renders_tabs_and_panels(): void
    {
        $markdown = <<<'MARKDOWN'
{% list tabs %}

- First tab

  First panel.

- Second tab

  Second panel with **bold**.

{% endlist %}
MARKDOWN;
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('class="yfm-tabs"', $html);
        static::assertStringContainsString('class="yfm-tab-list"', $html);
        static::assertSame(2, substr_count($html, 'class="yfm-tab yfm-tab-group'));
        static::assertSame(2, substr_count($html, 'class="yfm-tab-panel'));
        static::assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_note_content_supports_nested_markdown(): void
    {
        $markdown = <<<'MARKDOWN'
{% note info %}

This is **bold**.

- First
- Second

{% endnote %}
MARKDOWN;
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('<strong>bold</strong>', $html);
        static::assertStringContainsString('<ul>', $html);
        static::assertStringContainsString('<li>First</li>', $html);
    }

    public function test_unclosed_note_falls_back_without_throwing(): void
    {
        $html = (new YfmMarkdownToHtml("{% note info %}\n\nUnclosed"))->convert();

        static::assertStringNotContainsString('class="yfm-note', $html);
        static::assertStringContainsString('{% note info %}', $html);
        static::assertStringContainsString('Unclosed', $html);
    }

    public function test_standard_markdown_and_tables_still_render(): void
    {
        $markdown = "# Heading\n\n| A | B |\n|---|---|\n| 1 | 2 |";
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('<h1>Heading</h1>', $html);
        static::assertStringContainsString('<table>', $html);
        static::assertStringContainsString('<th>A</th>', $html);
        static::assertStringContainsString('<td>1</td>', $html);
    }

    public function test_standard_converter_does_not_render_yfm_tables(): void
    {
        $markdown = "#|\n|| A | B ||\n|| 1 | 2 ||\n|#";
        $html = (new MarkdownToHtml($markdown))->convert();

        static::assertStringNotContainsString('<td>A</td>', $html);
        static::assertStringContainsString('#|', $html);
    }

    public function test_yfm_converter_renders_compact_yfm_table(): void
    {
        $markdown = "#|\n|| A | B ||\n|| 1 | **2** ||\n|#";
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('<table>', $html);
        static::assertStringContainsString('<td><p>A</p>', $html);
        static::assertStringContainsString('<td><p>B</p>', $html);
        static::assertStringContainsString('<td><p>1</p>', $html);
        static::assertStringContainsString('<strong>2</strong>', $html);
    }

    public function test_yfm_converter_renders_gravity_multiline_table_with_header_rows(): void
    {
        $markdown = <<<'MARKDOWN'
#|
|:{header-rows="1"}
||
Header 1

|
Header 2

||
||
Cell 1

|
Cell 2

||
|#
MARKDOWN;
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('data-header-rows="1"', $html);
        static::assertStringContainsString('<tr data-header="true">', $html);
        static::assertStringContainsString('<th scope="col"><p>Header 1</p>', $html);
        static::assertStringContainsString('<th scope="col"><p>Header 2</p>', $html);
        static::assertStringContainsString('<td><p>Cell 1</p>', $html);
        static::assertStringContainsString('<td><p>Cell 2</p>', $html);
    }

    public function test_yfm_table_supports_colspan_and_cell_background(): void
    {
        $markdown = <<<'MARKDOWN'
#|
||::{bg="gray"} Wide | > ||
|#
MARKDOWN;
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('colspan="2"', $html);
        static::assertStringContainsString('data-bg="gray"', $html);
        static::assertStringContainsString('cell-bg-gray', $html);
        static::assertStringContainsString('Wide', $html);
    }

    public function test_yfm_inline_marks_and_color(): void
    {
        $markdown = '##mono## ++under++ ==mark== ^sup^ ~sub~ ~~strike~~ {red}(цвет)';
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('<samp>mono</samp>', $html);
        static::assertStringContainsString('<u>under</u>', $html);
        static::assertStringContainsString('<mark>mark</mark>', $html);
        static::assertStringContainsString('<sup>sup</sup>', $html);
        static::assertStringContainsString('<sub>sub</sub>', $html);
        static::assertStringContainsString('<s>strike</s>', $html);
        static::assertStringContainsString('class="yfm-colorify yfm-colorify--red"', $html);
        static::assertStringContainsString('data-color="red"', $html);
        static::assertStringContainsString('цвет', $html);
    }

    public function test_yfm_image_size_becomes_width_and_height(): void
    {
        $html = (new YfmMarkdownToHtml('![logo](/img.png =120x80)'))->convert();

        static::assertStringContainsString('src="/img.png"', $html);
        static::assertStringContainsString('width="120"', $html);
        static::assertStringContainsString('height="80"', $html);
    }

    public function test_yfm_definition_list_renders(): void
    {
        $markdown = "Term\n: Definition";
        $html = (new YfmMarkdownToHtml($markdown))->convert();

        static::assertStringContainsString('<dl>', $html);
        static::assertStringContainsString('<dt>Term</dt>', $html);
        static::assertStringContainsString('<dd>Definition</dd>', $html);
    }
}
