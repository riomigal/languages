<?php

namespace Riomigal\Languages\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Riomigal\Languages\Livewire\Translations;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Tests\BaseTestCase;

/**
 * A translator may only act on translations belonging to a language they are
 * assigned to. mount() guards page access, but the row actions used to resolve
 * their model with a bare Translation::findOrFail($id), so any id was reachable
 * by anyone who could open any translations page.
 */
class TranslationAuthorizationTest extends BaseTestCase
{
    use RefreshDatabase;

    private Language $permittedLanguage;

    private Language $forbiddenLanguage;

    private Translator $translator;

    private Translation $forbiddenTranslation;

    public function setUp(): void
    {
        parent::setUp();

        // The fallback language is created by a package migration; reuse it
        // rather than inserting a duplicate code.
        $this->permittedLanguage = Language::query()
            ->where('code', config('app.fallback_locale'))
            ->firstOrFail();

        $german = collect(Language::LANGUAGES)->firstWhere('code', 'de');
        $this->forbiddenLanguage = Language::query()->create([
            'name' => $german['name'],
            'native_name' => $german['native_name'],
            'code' => $german['code'],
        ]);

        $permitted = new Collection([$this->permittedLanguage]);
        $this->translator = $this->createUser($permitted);

        $this->forbiddenTranslation = Translation::query()->create([
            'language_id' => $this->forbiddenLanguage->id,
            'language_code' => $this->forbiddenLanguage->code,
            'relative_path' => '',
            'relative_pathname' => 'de.json',
            'shared_identifier' => 'json::secret.key',
            'file' => 'de.json',
            'type' => 'json',
            'key' => 'secret.key',
            'value' => 'Geheim',
            'approved' => true,
            'needs_translation' => false,
        ]);
    }

    #[Test]
    public function a_translator_cannot_approve_a_translation_of_another_language(): void
    {
        $this->assertActionIsDenied('approveTranslation');

        $this->assertDatabaseHas(config('languages.table_translations'), [
            'id' => $this->forbiddenTranslation->id,
            'approved_by' => null,
        ]);
    }

    #[Test]
    public function a_translator_cannot_request_a_translation_of_another_language(): void
    {
        $this->assertActionIsDenied('requestTranslation');

        $this->assertDatabaseHas(config('languages.table_translations'), [
            'id' => $this->forbiddenTranslation->id,
            'needs_translation' => false,
        ]);
    }

    #[Test]
    public function a_translator_cannot_open_the_modal_for_another_language(): void
    {
        $this->assertActionIsDenied('showTranslateModal');
    }

    #[Test]
    public function a_translator_cannot_restore_a_translation_of_another_language(): void
    {
        $this->assertActionIsDenied('restoreTranslation');
    }

    #[Test]
    public function a_translator_may_still_act_on_their_own_language(): void
    {
        $own = Translation::query()->create([
            'language_id' => $this->permittedLanguage->id,
            'language_code' => $this->permittedLanguage->code,
            'relative_path' => '',
            'relative_pathname' => 'en.json',
            'shared_identifier' => 'json::own.key',
            'file' => 'en.json',
            'type' => 'json',
            'key' => 'own.key',
            'value' => 'Mine',
            'approved' => false,
            'needs_translation' => false,
        ]);

        Livewire::actingAs($this->translator)
            ->test(Translations::class, ['language' => $this->permittedLanguage])
            ->call('approveTranslation', $own->id)
            ->assertOk();

        $this->assertDatabaseHas(config('languages.table_translations'), [
            'id' => $own->id,
            'approved' => true,
        ]);
    }

    /**
     * The row action must refuse to resolve a translation outside the
     * component's language. Scoping through $language->translations() makes
     * findOrFail throw, which is the guarantee under test.
     */
    private function assertActionIsDenied(string $action): void
    {
        $thrown = false;

        try {
            Livewire::actingAs($this->translator)
                ->test(Translations::class, ['language' => $this->permittedLanguage])
                ->call($action, $this->forbiddenTranslation->id);
        } catch (ModelNotFoundException) {
            $thrown = true;
        }

        $this->assertTrue(
            $thrown,
            "{$action}() resolved a translation belonging to another language."
        );
    }
}
