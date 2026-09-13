<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\OpenAITranslationService;
use Riomigal\Languages\Tests\BaseTestCase;

class HttpUiTest extends BaseTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Translator::firstOrFail());
    }

    #[Test]
    public function editing_a_translation_preserves_the_previous_state_and_flashes_a_timed_toast(): void
    {
        $translation = $this->createTranslation('original');
        $parameters = ['language' => $translation->language, 'id' => $translation->id];
        $this->getJson(route('languages.translations.modal', $parameters))->assertOk()->assertJsonPath('value', 'original');
        $this->post(route('languages.translations.update', $parameters), ['translatedValue' => 'updated'])->assertRedirect()
            ->assertSessionHas('toast', ['message' => __('languages::translations.update_success_message'), 'type' => 'SUCCESS', 'duration' => 4000]);
        $translation->refresh();
        $this->assertSame('updated', $translation->value);
        $this->assertSame('original', $translation->old_value);
        $this->assertSame(Translator::firstOrFail()->id, $translation->updated_by);
        $this->assertTrue($translation->updated_translation);
        $this->assertFalse($translation->approved);
        $this->assertFalse($translation->needs_translation);
        $this->assertFalse($translation->exported);
        $this->get(route('languages.translations', $translation->language))->assertOk()->assertSee('"duration":4000', false);
    }

    #[Test]
    #[DataProvider('booleanFilters')]
    public function translation_filters_distinguish_true_false_and_null(string $filter): void
    {
        $enabled = $this->createTranslation('enabled');
        $disabled = $this->createTranslation('disabled');
        $enabled->update([$filter => true]);
        $disabled->update([$filter => false]);
        foreach ([null => [$enabled->id, $disabled->id], 'true' => [$enabled->id], 'false' => [$disabled->id]] as $value => $ids) {
            $this->get(route('languages.translations', ['language' => $enabled->language, $filter => $value]))->assertOk()
                ->assertViewHas('data', fn ($data) => $data->getCollection()->modelKeys() === $ids);
        }
    }

    public static function booleanFilters(): array
    {
        return array_map(fn ($key) => [$key], ['needs_translation', 'approved', 'updated_translation', 'is_vendor', 'exported']);
    }

    #[Test]
    public function suggestions_use_the_fallback_example_and_never_save(): void
    {
        $root = $this->createTranslation('root text');
        $german = Language::create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch']);
        $target = $german->translations()->create(['language_code' => 'de', 'type' => 'json', 'key' => 'target', 'shared_identifier' => $root->shared_identifier, 'value' => '', 'needs_translation' => false]);
        Setting::firstOrFail()->update(['enable_open_ai_translations' => true]);
        Setting::getFreshCached();
        $parameters = ['language' => $german, 'id' => $target->id, 'example_language' => $german->id];
        $this->getJson(route('languages.translations.modal', $parameters))->assertJsonPath('example.value', 'root text')->assertJsonPath('example.language_id', $root->language_id);
        $this->mock(OpenAITranslationService::class)->shouldReceive('translateString')->once()
            ->withArgs(fn ($from, $to, $value) => $from->id === $root->language_id && $to->id === $german->id && $value === 'root text')->andReturn('Vorschlag');
        $before = $target->fresh()->getAttributes();
        $this->postJson(route('languages.translations.suggest', $parameters), ['translatedValue' => 'unsaved draft'])->assertOk()->assertJsonPath('value', 'Vorschlag');
        $this->assertSame($before, $target->fresh()->getAttributes());
    }

    #[Test]
    public function an_existing_batch_is_rendered_on_load_and_progress_reports_completion(): void
    {
        $batch = Bus::batch([])->name(config('languages.batch_name'))->dispatch();
        DB::table('job_batches')->where('id', $batch->id)->update(['total_jobs' => 2, 'pending_jobs' => 1, 'finished_at' => null]);
        $this->get(route('languages.languages'))->assertOk()->assertSee('data-batch-id="' . $batch->id . '"', false);
        $this->getJson(route('languages.batch.progress'))->assertOk()->assertJsonPath('id', $batch->id)->assertJsonPath('finished', false)->assertJsonPath('progress', 50);
        DB::table('job_batches')->where('id', $batch->id)->update(['finished_at' => now()->timestamp, 'pending_jobs' => 0]);
        $this->getJson(route('languages.batch.progress', ['id' => $batch->id]))->assertOk()->assertJsonPath('finished', true)->assertJsonPath('progress', 100);
    }

    #[Test]
    public function row_approval_and_updates_are_blocked_while_a_job_runs(): void
    {
        $translation = $this->createTranslation('original');
        $translation->update(['approved' => false]);
        $before = $translation->fresh()->getAttributes();
        DB::table('jobs')->insert(['queue' => config('languages.queue_name'), 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time()]);
        foreach (['approve', 'update', 'request', 'restore-request', 'restore'] as $action) {
            $this->post(route('languages.translations.' . $action, ['language' => $translation->language, 'id' => $translation->id]), ['translatedValue' => 'changed'])
                ->assertRedirect()->assertSessionHas('toast.type', 'WARNING');
            $this->assertSame($before, $translation->fresh()->getAttributes());
        }
    }

    #[Test]
    public function forms_have_named_controls_and_the_layout_has_no_framework_runtime(): void
    {
        $admin = Translator::firstOrFail();
        foreach ([
            route('languages.languages', ['create' => 1]),
            route('languages.translators', ['create' => 1]),
            route('languages.translators.edit', $admin),
            route('languages.translators.edit', ['translator' => $admin, 'password' => 1]),
            route('languages.translations', Language::firstOrFail()),
            route('languages.settings'),
        ] as $url) {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($response->getContent());
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//input[not(@name)] | //select[not(@name)] | //textarea[not(@name)]')->length, $url);
            foreach ($xpath->query('//form[@method="POST"]') as $form) {
                $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)->length, $url);
            }
            $response->assertDontSee('/livewire/')->assertDontSee('wire:')->assertDontSee('x-data');
        }
        $this->assertArrayNotHasKey('Livewire\\LivewireServiceProvider', $this->app->getLoadedProviders());
    }

    #[Test]
    public function updates_preserve_whitespace_and_restore_the_first_saved_value(): void
    {
        $translation = $this->createTranslation('0');
        $parameters = ['language' => $translation->language, 'id' => $translation->id];
        foreach (["  edited :name \n", 'second edit'] as $value) {
            $this->post(route('languages.translations.update', $parameters), ['translatedValue' => $value])->assertRedirect();
            $this->assertSame($value, $translation->fresh()->value);
            $this->assertSame('0', $translation->fresh()->old_value);
        }
        $this->post(route('languages.translations.restore', $parameters))->assertRedirect();
        $this->assertSame('0', $translation->fresh()->value);
        $this->assertTrue($translation->fresh()->approved);
        $this->assertNull($translation->fresh()->old_value);
        $this->post(route('languages.translations.request', $parameters))->assertRedirect();
        $this->assertTrue($translation->fresh()->needs_translation);
        $this->assertFalse($translation->fresh()->approved);
        $this->post(route('languages.translations.restore-request', $parameters))->assertRedirect();
        $this->assertFalse($translation->fresh()->needs_translation);
        $this->assertTrue($translation->fresh()->approved);
        $this->post(route('languages.translations.update', $parameters), ['translatedValue' => 'approved edit'])->assertRedirect();
        $this->post(route('languages.translations.approve', $parameters))->assertRedirect();
        $this->assertTrue($translation->fresh()->approved);
        $this->assertFalse($translation->fresh()->updated_translation);
        $this->assertNull($translation->fresh()->old_value);
        $this->assertSame(Translator::firstOrFail()->id, $translation->fresh()->approved_by);
    }

    #[Test]
    public function pagination_preserves_all_query_filters_and_mutations_return_to_them(): void
    {
        $admin = Translator::firstOrFail();
        for ($i = 0; $i < 22; $i++) {
            $row = $this->createTranslation('matching-' . $i);
            $row->update(['updated_by' => $admin->id, 'approved_by' => $admin->id, 'is_vendor' => true]);
        }
        $filters = ['search' => 'matching', 'approved' => 'true', 'is_vendor' => 'true', 'types' => ['json'], 'updatedBy' => [$admin->id], 'approvedBy' => [$admin->id]];
        $this->get(route('languages.translations', ['language' => $row->language] + $filters))->assertOk()
            ->assertViewHas('data', function ($data) use ($filters) {
                parse_str(parse_url($data->nextPageUrl(), PHP_URL_QUERY), $query);
                foreach ($filters as $key => $value) {
                    $this->assertEquals($value, $query[$key]);
                }
                return $data->total() === 22 && $data->count() === 20;
            });
        $this->post(route('languages.translations.request', ['language' => $row->language, 'id' => $row->id, 'page' => 2] + $filters))
            ->assertRedirect(route('languages.translations', ['language' => $row->language, 'page' => 2] + $filters));
    }

    #[Test]
    public function bulk_actions_dispatch_batches_and_flash_their_ids(): void
    {
        Bus::fake();
        $row = $this->createTranslation('pending approval');
        $row->update(['approved' => false]);
        foreach (['import-languages', 'import-translations', 'find-missing', 'approve'] as $action) {
            $this->post(route('languages.languages.' . $action))->assertRedirect()->assertSessionHas('batch_id');
        }
        Bus::assertBatchCount(4);
        foreach ([
            \Riomigal\Languages\Jobs\ImportLanguagesJob::class,
            \Riomigal\Languages\Jobs\ImportTranslationsJob::class,
            \Riomigal\Languages\Jobs\FindMissingTranslationsJob::class,
            \Riomigal\Languages\Jobs\ApproveLanguagesJob::class,
        ] as $job) {
            Bus::assertBatched(fn ($batch) => $batch->name === config('languages.batch_name') && $batch->jobs->first() instanceof $job);
        }
    }

    private function createTranslation(string $value): Translation
    {
        $language = Language::firstOrFail();
        return $language->translations()->create([
            'language_code' => $language->code, 'type' => 'json', 'shared_identifier' => 'json::' . $value,
            'key' => $value, 'value' => $value, 'approved' => true, 'needs_translation' => false,
        ]);
    }
}
