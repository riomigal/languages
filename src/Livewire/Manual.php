<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Component;

class Manual extends Component
{
    public string $manualHtml = '';
    /**
     * @var array<int, array{level:int, id:string, title:string}>
     */
    public array $manualSections = [];

    public function mount(): void
    {
        $path = dirname(__DIR__, 2) . '/docs/APPLICATION_MANUAL.md';

        if (!File::exists($path)) {
            $this->manualHtml = Str::markdown('# Application Manual' . PHP_EOL . PHP_EOL . 'Manual file not found.');
            return;
        }

        $markdown = File::get($path);
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = preg_replace('/<h1\b[^>]*>.*?<\/h1>/is', '', $html, 1) ?? $html;
        $this->manualHtml = $this->addHeadingAnchorsAndSections($html);
    }

    protected function addHeadingAnchorsAndSections(string $html): string
    {
        $sections = [];
        $usedIds = [];

        $updatedHtml = preg_replace_callback('/<h([23])\b[^>]*>(.*?)<\/h\1>/is', function (array $matches) use (&$sections, &$usedIds): string {
            $level = (int) $matches[1];
            $innerHtml = trim($matches[2]);
            $title = trim(preg_replace('/\s+/', ' ', strip_tags($innerHtml)) ?? '');

            if ($title === '') {
                return $matches[0];
            }

            $baseId = Str::slug($title);
            if ($baseId === '') {
                $baseId = 'section';
            }

            $id = $baseId;
            $suffix = 2;
            while (isset($usedIds[$id])) {
                $id = $baseId . '-' . $suffix;
                $suffix++;
            }
            $usedIds[$id] = true;

            $sections[] = [
                'level' => $level,
                'id' => $id,
                'title' => $title,
            ];

            return sprintf(
                '<h%d id="%s">%s</h%d>',
                $level,
                e($id),
                $innerHtml,
                $level
            );
        }, $html) ?? $html;

        $this->manualSections = $sections;

        return $updatedHtml;
    }

    public function render(): View
    {
        return view('languages::manual')->layout('languages::layouts.app');
    }
}
