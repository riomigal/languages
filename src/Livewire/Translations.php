<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\ExportTranslationJob;
use Riomigal\Languages\Livewire\Traits\HasBatchProcess;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
use Riomigal\Languages\Services\ExportTranslationService;

class Translations extends AuthComponent
{
    use HasBatchProcess;

    /**
     * @var Language
     */
    public Language $language;

    /**
     * @var Translation|null
     */
    public Translation|null $translation = null;

    /**
     * @var Translation|null
     */
    public Translation|null $translationExample = null;

    /**
     * @var int
     */
    public int $translateLanguageExampleId;

    /**
     * @var int
     */
    public int $translateLanguageFallbackExampleId;

    /**
     * @var array
     */
    public array $checkboxFilters = [];

    /**
     * @var string
     */
    public string $translatedValue = '';

    /**
     * @var array
     */
    public array $languages;

    /**
     * @var string[]
     */
    protected $queryString = ['search', 'page', 'checkboxFilters'];

    /**
     * @param Language $language
     * @return void
     */
    public function mount(Language $language): void
    {
        parent::init();
        $this->language = $language;
        if (!auth()->user()->admin) {
            $languages = auth()->user()->languages()->pluck('languages.id')->all();
            if (!in_array($this->language->id, $languages)) {
                abort(403);
            }
        }
        $this->translateLanguageFallbackExampleId = Language::query()->where('code', config('app.fallback_locale'))->firstOrFail()->id;
        $this->translateLanguageExampleId = $this->translateLanguageFallbackExampleId;
        $this->languages = Language::query()->get()->toArray();
    }

    /**
     * @return LengthAwarePaginator
     */
    public function query(): LengthAwarePaginator
    {
        return $this->language->translations()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where(
                        'relative_pathname', 'LIKE', '%' . $this->search . '%'
                    )
                        ->orWhere('key', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('value', 'LIKE', '%' . $this->search . '%');
                });
            })->when(in_array('needs_translation', $this->checkboxFilters), function ($query) {
                $query->where(function ($query) {
                    $query->needsTranslation();
                });
            })->when(in_array('approved', $this->checkboxFilters), function ($query) {
                $query->where(function ($query) {
                    $query->approved();

                });
            })->when(in_array('updated_translation', $this->checkboxFilters), function ($query) {
                $query->where(function ($query) {
                    $query->isUpdated();
                });
            })->when(in_array('doesnt_need_translation', $this->checkboxFilters), function ($query) {
                $query->where(function ($query) {
                    $query->needsTranslation(false);
                });
            })->when(in_array('not_approved', $this->checkboxFilters), function ($query) {
                $query->where(function ($query) {
                    $query->approved(false);
                });
            })->when(in_array('not_updated_translation', $this->checkboxFilters), function ($query) {
                $query->where(function ($query) {
                    $query->isUpdated(false);
                });
            })
            ->paginate(10);
    }

    /**
     * @param int $id
     * @return void
     */
    public function showTranslateModal(int $id): void
    {
        $this->translation = Translation::findOrFail($id);
        $this->translationExample = Translation::where('shared_identifier', $this->translation->shared_identifier)
            ->where('language_id', $this->translateLanguageExampleId)->first();
        if (!$this->translationExample || !$this->translationExample->value) {
            $this->translationExample = Translation::where('shared_identifier', $this->translation->shared_identifier)
                ->where('language_id', $this->translateLanguageFallbackExampleId)->first();
        }
        $this->translatedValue = $this->translation->value;
        $this->dispatchBrowserEvent('showTranslationModal');
    }

    /**
     * @param int $id
     * @return void
     */
    public function requestTranslation(int $id): void
    {
        Translation::findOrFail($id)->update(['needs_translation' => true]);
    }

    /**
     * @return void
     */
    public function hideTranslationModal(): void
    {
        $this->dispatchBrowserEvent('hideTranslationModal');
    }

    /**
     * @return void
     */
    public function updateTranslation(): void
    {
        if ($this->translation->value != $this->translatedValue) {

            $this->translation->value = $this->translatedValue;
            $this->translation->updated_translation = true;
            $this->translation->approved = false;
            $this->translation->save();
            $this->hideTranslationModal();
            $this->emit('showToast', __('languages::translations.update_success_message'), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], 4000);
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public function approveTranslation(int $id): void
    {
        Translation::findOrFail($id)->update([
            'approved' => true
        ]);
    }

    /**
     *
     * @return void
     */
    public function approveAllTranslations(): void
    {
        $this->language->translations()->where('approved', false)->update([
            'approved' => true
        ]);
    }

    /**
     * @param ExportTranslationService $exportTranslationService
     * @param BatchProcessor $batchProcessor
     * @return void
     */
    public function exportTranslationsForLanguage(ExportTranslationService $exportTranslationService, BatchProcessor $batchProcessor): void
    {
        if ($this->anotherJobIsRunning()) return;

        $updatedTranslationsTotal = $this->language->translations()
            ->isUpdated()
            ->approved()->count();

        if ($updatedTranslationsTotal) {
            $batchArray = [
                new ExportTranslationJob($exportTranslationService, $this->language)
            ];

            $total = Translation::query()->where('language_id', $this->language->id)
                ->isUpdated()
                ->approved()
                ->count();
            $language = $this->language;
            $finally = function () use (&$total, &$language) {
                Translator::query()->each(function (Translator $translator) use ($total, $language) {
                    $translator->notify(new FlashMessage($total ? __('languages::translations.export_language_success', ['language' => $language->name, 'total' => $total]) . __('languages::global.reload_suggestion') : __('languages::translations.nothing_exported')));
                });
            };

            $this->emit('startBatchProgress', $batchProcessor->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);
        } else {
            $this->authUser->notify(new FlashMessage(__('languages::translations.nothing_exported')));
        }
    }

    /**
     * @param ExportTranslationService $exportTranslationService
     * @param BatchProcessor $batchProcessor
     * @return void
     */
    public function exportTranslationsForAllLanguages(ExportTranslationService $exportTranslationService, BatchProcessor $batchProcessor): void
    {
        if ($this->anotherJobIsRunning()) return;

        $languages = Language::find(Translation::query()
            ->isUpdated()
            ->approved()->distinct()->pluck('language_id')->toArray());

        if ($languages->count() > 0) {

            $batchArray = [];
            $languages->each(function (Language $language) use (&$batchArray, $exportTranslationService) {
                $batchArray[] = new ExportTranslationJob($exportTranslationService, $language);
            });

            $total = Translation::query()
                ->isUpdated()
                ->approved()
                ->count();

            $finally = function () use (&$total, &$languages) {
                Translator::query()->each(function (Translator $translator) use ($total, $languages) {
                    $translator->notify(new FlashMessage($total ? __('languages::translations.export_languages_success', ['languages' => implode(', ', $languages->pluck('name')->all()), 'total' => $total]) . __('languages::global.reload_suggestion') : __('languages::translations.nothing_exported')));
                });
            };
            $this->emit('startBatchProgress', $batchProcessor->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);

        } else {
            $this->authUser->notify(new FlashMessage(__('languages::translations.nothing_exported')));
        }
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::translations', [
            'data' => $this->query()
        ])
            ->layout('languages::layouts.app');
    }
}
