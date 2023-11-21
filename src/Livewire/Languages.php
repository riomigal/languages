<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\FindMissingTranslationsJob;
use Riomigal\Languages\Jobs\ImportLanguagesJob;
use Riomigal\Languages\Jobs\ImportTranslationsJob;
use Riomigal\Languages\Livewire\Traits\HasBatchProcess;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;

class Languages extends AuthComponent
{
    use HasBatchProcess;

    /**
     * @var bool
     */
    public bool $showForm = false;

    /**
     * @var string
     */
    public string $language;

    /**
     * @var array
     */
    public array $languageCodes;

    /**
     * @var bool
     */
    public bool $hasImportedLanguages;


    /**
     * @return void
     */
    public function mount(): void
    {
        parent::init();
        $this->checkImportedLanguages();;
        $this->languageCodes = array_map(function ($language) {
            return $language['code'];
        }, Language::LANGUAGES);
    }

    /**
     * Validates language codes
     *
     * @return array
     */
    public function getRules(): array
    {
        return [
            'language' => [
                Rule::in($this->languageCodes), Rule::notIn(Language::query()->pluck('code')->all()), Rule::unique(config('languages.table_languages'), 'code')
            ]
        ];
    }

    public function deleteJobs(): bool
    {
        $jobs = DB::table('jobs')->where('queue', config('languages.queue_name'))
                ->delete();
        $batches = DB::table('job_batches')->where('name', config('languages.batch_name'))
                ->whereNull('cancelled_at')
                ->whereNull('finished_at')
                ->delete();
        if (($jobs + $batches)) {
            $this->emit('showToast', __('languages::global.jobs.delete_success', ['batches' => $batches, 'jobs' => $jobs]), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS']);
            $this->emit('startBatchProgress', null);
            return true;
        }
        $this->emit('showToast', __('languages::global.jobs.delete_not_found'), LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
        $this->emit('startBatchProgress', null);
        return false;
    }

    /**
     * Creates new languages from folders present in root "lang" folder. (Folder names must be valid language codes)
     *
     * @param BatchProcessor $batchProcessor
     * @return void
     * @throws \Throwable
     */
    public function importLanguages(BatchProcessor $batchProcessor): void
    {
        if ($this->anotherJobIsRunning()) return;

        $batchArray = [
            new ImportLanguagesJob()
        ];

        $languages = Language::pluck('id')->toArray();
        $finally = function () use (&$languages) {
            $newLanguages = Language::all()
                ->reject(function (Language $language) use ($languages) {
                    return in_array($language->id, $languages);
                })->pluck('name')->all();
            Translator::query()->admin()->each(function (Translator $translator) use ($newLanguages) {
                $translator->notify(new FlashMessage($newLanguages ? __('languages::languages.import_languages_success', ['languages' => implode(', ', $newLanguages)]) . __('languages::global.reload_suggestion') : __('languages::global.import.nothing_imported')));
            });
        };

        $this->emit('startBatchProgress', $batchProcessor->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);
    }

    /**
     * @param BatchProcessor $batchProcessor
     * @return void
     * @throws \Throwable
     */
    public function importTranslations(BatchProcessor $batchProcessor): void
    {
        if ($this->anotherJobIsRunning()) return;

        $batchArray = [
            new ImportTranslationsJob()
        ];

        $totalTranslationsBefore = Translation::count();
        $finally = function () use (&$totalTranslationsBefore) {

            $total = Translation::count() - $totalTranslationsBefore;
            Translator::query()->admin()->each(function (Translator $translator) use ($total) {
                $translator->notify(new FlashMessage($total ? __('languages::languages.import_translations_success', ['total' => $total]) . __('languages::global.reload_suggestion') : __('languages::global.import.nothing_imported')));
            });
        };

        $this->emit('startBatchProgress', $batchProcessor->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);
    }

    /**
     * Finds Missing Translations for every language shared identifier
     *
     * @param BatchProcessor $batchProcessor
     * @return void
     */
    public function findMissingTranslations(BatchProcessor $batchProcessor): void
    {
        if ($this->anotherJobIsRunning()) return;

        $total =Translation::selectRaw('count(*) as total')->groupBy('language_id')->orderBy('language_id')->pluck('total')->all();

        Language::query()->whereDoesntHave('translations')->each(function(Language $language) use (&$total) {
                $total[] = -1;
        });

        $total = count(array_unique($total));

        if ($total > 1) {
            $batchArray = [
                new FindMissingTranslationsJob()
            ];

            $totalTranslationsBefore = Translation::count();
            $finally = function () use (&$totalTranslationsBefore) {

                $total = Translation::count() - $totalTranslationsBefore;
                Translator::query()->admin()->where('admin', true)->each(function (Translator $translator) use ($total) {
                    $translator->notify(new FlashMessage($total ? __('languages::languages.find_missing_translations_success', ['total' => $total]) . __('languages::global.reload_suggestion') : __('languages::global.import.nothing_imported')));
                });
            };

            $this->emit('startBatchProgress', $batchProcessor->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);
        } else {
            $this->authUser->notify(new FlashMessage(__('languages::translations.nothing_exported')));
        }
    }


    /**
     * Deletes a language (only admin)
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if (parent::delete($id)) {
            $language = Language::query()->findOrFail($id);
            $language->translators()->detach();
            $language->delete();
            $this->emit('showToast', __('languages::languages.deleted'), LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
            return true;
        }
        return false;
    }

    /**
     * Adds new languages in the DB (only admin)
     *
     * @return bool
     */
    public function create(): bool
    {
        if (parent::create()) {
            $data = $this->validate();
            $languageCodes = array_flip($this->languageCodes);
            $language = Language::query()->create(Language::LANGUAGES[$languageCodes[$data['language']]]);
            $langPath = App::langPath($language->code);
            if(!File::exists($langPath)) {
                File::makeDirectory($langPath);
            }
            $this->emit('showToast', __('languages::languages.created', ['language' => $language->name]), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS']);
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    public function showForm(): void
    {
        $this->showForm = true;
    }

    /**
     * @return void
     */
    public function closeForm(): void
    {
        $this->showForm = false;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function query(): LengthAwarePaginator
    {
        if ($this->authUser?->admin) {
            $query = Language::query();
        } else {
            $query = $this->authUser->languages();

        }
        return $query
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where(
                        'code', 'LIKE', '%' . $this->search . '%'
                    )
                        ->orWhere('name', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('native_name', 'LIKE', '%' . $this->search . '%');
                });
            })
            ->orderBy('code')->paginate(10);
    }


    /**
     * Checks if there are any existing languages in the DB
     *
     * @return void
     */
    protected function checkImportedLanguages(): void
    {
        $this->hasImportedLanguages = (bool) Language::first() > 1;
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::languages',
            ['data' => $this->query(),
                'languages' => Language::LANGUAGES])
            ->layout('languages::layouts.app');
    }
}
