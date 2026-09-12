<?php

namespace Riomigal\Languages\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\ImportTranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

/**
 * shared_identifier is how a translation is matched to its counterparts in other
 * languages. massCreateTranslations() reassigned its $group parameter inside the
 * loop for model translations, so every key after the first was hashed with the
 * previous key's group and could never be linked across languages.
 */
class SharedIdentifierTest extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, string> $content
     */
    private function massCreate(array $content, string $type, Language $language, string $group): void
    {
        $service = new ImportTranslationService();

        $method = new ReflectionMethod($service, 'massCreateTranslations');
        $method->setAccessible(true);
        $method->invoke(
            $service,
            $content,
            $type,
            $language->id,
            $language->code,
            'App\\Models\\Post',
            $group,
            false
        );
    }

    #[Test]
    public function model_translations_get_identifiers_independent_of_iteration_order(): void
    {
        $language = Language::query()->where('code', config('app.fallback_locale'))->firstOrFail();

        $this->massCreate([
            'title.one'   => 'One',
            'body.two'    => 'Two',
            'footer.three' => 'Three',
        ], 'model', $language, 'posts');

        $identifiers = Translation::query()
            ->where('language_id', $language->id)
            ->pluck('shared_identifier')
            ->map(fn (string $identifier): string => base64_decode($identifier))
            ->all();

        /**
         * Every identifier must be built from the group passed in ("posts"), not
         * from the group parsed out of the preceding key ("title", "body").
         */
        foreach ($identifiers as $identifier) {
            $this->assertStringContainsString(
                'posts',
                $identifier,
                'shared_identifier was built from a previous row\'s group: ' . $identifier
            );
        }

        $this->assertCount(3, array_unique($identifiers));
    }

    #[Test]
    public function a_model_key_without_a_separator_does_not_error(): void
    {
        $language = Language::query()->where('code', config('app.fallback_locale'))->firstOrFail();

        $this->massCreate(['no_separator_here' => 'Value'], 'model', $language, 'posts');

        $this->assertDatabaseCount(config('languages.table_translations'), 1);
    }
}
