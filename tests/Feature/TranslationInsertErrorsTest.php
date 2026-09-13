<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Services\ImportTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;
use RuntimeException;

class TranslationInsertErrorsTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_insert_failure_with_string_keys_preserves_the_database_error(): void
    {
        Log::spy();
        $service = new ImportTranslationService();
        $language = Language::query()->firstOrFail();
        $row = (new ReflectionMethod($service, 'getTranslationArray'))->invoke(
            $service, $language->id, $language->code, 'identifier', 'php', 'key', 'Value', '', 'messages', false
        );
        // Violate a real NOT NULL constraint while retaining the associative row key.
        $row['key'] = null;

        try {
            (new ReflectionMethod($service, 'massInsertTranslations'))->invoke($service, ['identifier' => $row]);
            $this->fail('Expected a mass insertion exception.');
        } catch (MassCreateTranslationsException $e) {
            $this->assertStringContainsString('NOT NULL constraint failed', $e->getMessage());
            $this->assertStringContainsString($language->code, $e->getPublicMessage());
            Log::shouldHaveReceived('error')->once()->withArgs(
                fn (string $message, array $context): bool => str_contains($message, $language->code)
                    && $context['shared_identifier'] === ['identifier']
            );
        }
    }

    #[Test]
    #[DataProvider('translationPaths')]
    public function creation_failures_report_the_actual_source_path(bool $vendor, string $type, string $relativePath): void
    {
        Log::spy();
        $service = new class extends ImportTranslationService {
            protected function massInsertTranslations(array $translations): void
            {
                throw new RuntimeException('Insert unavailable');
            }
        };
        $language = Language::query()->firstOrFail();

        try {
            (new ReflectionMethod($service, 'massCreateTranslations'))->invoke(
                $service, ['key' => 'Value'], $type, $language->id, 'en', 'package', $type === 'json' ? '' : 'messages', $vendor
            );
            $this->fail('Expected a creation exception.');
        } catch (MassCreateTranslationsException $e) {
            $path = App::langPath($relativePath);
            $this->assertSame('Insert unavailable', $e->getMessage());
            Log::shouldHaveReceived('error')->once()->withArgs(
                fn (string $message, array $context): bool => $context['relativePathname'] === $path
            );
        }
    }

    public static function translationPaths(): array
    {
        return [
            'application PHP' => [false, 'php', 'en/messages.php'],
            'vendor PHP' => [true, 'php', 'vendor/package/en/messages.php'],
            'application JSON' => [false, 'json', 'en.json'],
            'vendor JSON' => [true, 'json', 'vendor/package/en.json'],
        ];
    }
}
