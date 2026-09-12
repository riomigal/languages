<?php

namespace Riomigal\Languages\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use RuntimeException;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

/**
 * Exporting must never destroy translations already on disk. Two paths used to
 * do exactly that: a json_encode failure wrote an empty string over the file,
 * and a malformed existing file decoded to null, became [], and was replaced by
 * only the newly exported keys.
 */
class ExportFileSafetyTest extends BaseTestCase
{
    use RefreshDatabase;

    private string $path;

    public function setUp(): void
    {
        parent::setUp();
        $this->path = storage_path('framework/testing/export-safety.json');
        File::ensureDirectoryExists(dirname($this->path));
    }

    protected function tearDown(): void
    {
        File::delete($this->path);
        parent::tearDown();
    }

    /**
     * @param array<array-key, string|null> $translations
     */
    private function updateFileContent(array $translations, string $type = 'json'): void
    {
        $service = new ExportTranslationService();
        $method = new ReflectionMethod($service, 'updateFileContent');
        $method->setAccessible(true);
        $method->invoke($service, $translations, $this->path, $type);
    }

    #[Test]
    public function a_malformed_existing_file_is_not_silently_replaced(): void
    {
        File::put($this->path, '{"existing.key": "Existing value",,,}');

        try {
            $this->updateFileContent(['new.key' => 'New value']);
            $this->fail('Export overwrote a file whose existing JSON could not be parsed.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('malformed', $e->getMessage());
        }

        // The original bytes must still be there for the operator to recover.
        $this->assertStringContainsString('Existing value', File::get($this->path));
    }

    #[Test]
    public function an_unencodable_value_does_not_truncate_the_file(): void
    {
        File::put($this->path, '{"existing.key":"Existing value"}');

        // Invalid UTF-8 cannot be encoded, so json_encode returns false.
        try {
            $this->updateFileContent(['broken' => "\xB1\x31"]);
            $this->fail('Export wrote a file whose content could not be encoded.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('json_encode failed', $e->getMessage());
        }

        $this->assertStringContainsString('Existing value', File::get($this->path));
    }

    #[Test]
    public function a_valid_export_still_merges_into_the_existing_file(): void
    {
        File::put($this->path, '{"existing.key":"Existing value"}');

        $this->updateFileContent(['new.key' => 'New value']);

        $content = json_decode(File::get($this->path), true);

        $this->assertSame('Existing value', $content['existing.key']);
        $this->assertSame('New value', $content['new.key']);
    }
}
