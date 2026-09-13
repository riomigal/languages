<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Livewire\LanguagesToastMessage;
use Riomigal\Languages\Livewire\Translations;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Tests\BaseTestCase;

class LivewireComponentTest extends BaseTestCase
{
    use RefreshDatabase;

    #[Test]
    public function toast_messages_dispatch_the_requested_close_delay(): void
    {
        Livewire::test(LanguagesToastMessage::class)
            ->call('show', 'Saved', 'SUCCESS', 4500)
            ->assertSet('message', 'Saved')
            ->assertSet('showMessage', true)
            ->assertDispatched('closeToastMessage', duration: 4500)
            ->call('close')
            ->assertSet('showMessage', false);
    }

    #[Test]
    public function editing_a_translation_preserves_modal_events_and_the_saved_state(): void
    {
        $translation = $this->createTranslation('original');
        $admin = Translator::query()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(Translations::class, ['language' => $translation->language])
            ->call('showTranslateModal', $translation->id)
            ->assertDispatched('showTranslationModal')
            ->assertSet('translatedValue', 'original')
            ->set('translatedValue', 'updated')
            ->call('updateTranslation')
            ->assertDispatched('hideTranslationModal')
            ->assertDispatched('showToast',
                message: __('languages::translations.update_success_message'),
                type: 'SUCCESS', duration: 4000);

        $translation->refresh();
        $this->assertSame('updated', $translation->value);
        $this->assertSame('original', $translation->old_value);
        $this->assertSame($admin->id, $translation->updated_by);
        $this->assertTrue($translation->updated_translation);
        $this->assertFalse($translation->approved);
        $this->assertFalse($translation->needs_translation);
        $this->assertFalse($translation->exported);
    }

    #[Test]
    #[DataProvider('booleanFilters')]
    public function translation_filters_distinguish_true_false_and_null(string $filter): void
    {
        $enabled = $this->createTranslation('enabled');
        $disabled = $this->createTranslation('disabled');
        $enabled->update([$filter => true]);
        $disabled->update([$filter => false]);

        $component = new Translations();
        $component->language = $enabled->language;

        $this->assertSame(2, $component->query()->total());
        $component->updateThreeStatesFilter($filter);
        $this->assertSame([$enabled->id], $component->query()->getCollection()->modelKeys());
        $component->updateThreeStatesFilter($filter);
        $this->assertSame([$disabled->id], $component->query()->getCollection()->modelKeys());
        $component->updateThreeStatesFilter($filter);
        $this->assertSame(2, $component->query()->total());
    }

    /** @return array<string, array{string}> */
    public static function booleanFilters(): array
    {
        return [
            'needs translation' => ['needs_translation'],
            'approved' => ['approved'],
            'updated translation' => ['updated_translation'],
            'vendor' => ['is_vendor'],
            'exported' => ['exported'],
        ];
    }

    private function createTranslation(string $value): Translation
    {
        $language = Language::query()->firstOrFail();

        return $language->translations()->create([
            'language_code' => $language->code,
            'relative_path' => '',
            'relative_pathname' => $language->code . '.json',
            'shared_identifier' => 'json::' . $value,
            'file' => $language->code . '.json',
            'type' => 'json',
            'key' => $value,
            'value' => $value,
            'approved' => true,
            'needs_translation' => false,
        ]);
    }
}
