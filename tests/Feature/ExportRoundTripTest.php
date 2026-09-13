<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

class ExportRoundTripTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('phpFiles')]
    public function exported_php_keys_round_trip_through_laravels_file_loader(bool $vendor, bool $existing): void
    {
        $language = Language::query()->firstOrFail();
        $namespace = $vendor ? 'roundtrip' : '';
        $path = App::langPath(($vendor ? 'vendor/roundtrip/' : '') . $language->code . '/roundtrip.php');

        if ($existing) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, "<?php return ['a' => ['b' => 'Old', 'sibling' => 'Keep'], 'a.b' => 'Legacy'];");
        }

        foreach (['a.b' => 'New', 'items.0.label' => 'First'] as $key => $value) {
            Translation::query()->create([
                'language_id' => $language->id,
                'language_code' => $language->code,
                'shared_identifier' => base64_encode($key),
                'type' => 'php',
                'namespace' => $namespace,
                'group' => 'roundtrip',
                'is_vendor' => $vendor,
                'key' => $key,
                'value' => $value,
                'approved' => true,
                'needs_translation' => false,
                'updated_translation' => false,
                'exported' => false,
            ]);
        }

        (new ExportTranslationService())->exportTranslationForLanguage($language);

        $loader = new FileLoader(app('files'), App::langPath());
        $loader->addNamespace('roundtrip', App::langPath('vendor/roundtrip'));
        $lines = $loader->load($language->code, 'roundtrip', $vendor ? $namespace : null);
        $this->assertSame('New', $lines['a']['b'] ?? null);
        $this->assertSame('First', $lines['items'][0]['label'] ?? null);
        $this->assertArrayNotHasKey('a.b', $lines);
        if ($existing) {
            $this->assertSame('Keep', $lines['a']['sibling']);
        }
        $translator = new Translator($loader, $language->code);
        $prefix = $vendor ? 'roundtrip::' : '';
        $this->assertSame('New', $translator->get($prefix . 'roundtrip.a.b'));
        $this->assertSame('First', $translator->get($prefix . 'roundtrip.items.0.label'));
        $this->assertSame(0, Translation::query()->where('exported', false)->count());
    }

    public static function phpFiles(): array
    {
        return [
            'new application file' => [false, false],
            'existing application file' => [false, true],
            'new vendor file' => [true, false],
            'existing vendor file' => [true, true],
        ];
    }
}
