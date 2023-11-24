<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Riomigal\Languages\Jobs\Batch\BatchProcessor;
use Riomigal\Languages\Jobs\ExportTranslationJob;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Services\OpenAITranslationService;

class Translations extends AuthComponent
{
    use ChecksForRunningJobs;

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
    public int $currentLanguageId;

    /**
     * @var int
     */
    public int $translateLanguageExampleId;

    /**
     * @var int
     */
    public int $translateLanguageFallbackExampleId;

    /**
     * @var int
     */
    public ?int $openAiTranslateLanguageId = null;

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
    protected $queryString = ['search', 'page', 'needs_translation', 'approved', 'updated_translation', 'types'];


    /**
     * @var bool|null
     */
    public bool|null $approved = null;

    /**
     * @var bool|null
     */
    public bool|null $needs_translation = null;

    /**
     * @var bool|null
     */
    public bool|null $updated_translation = null;

    /**
     * @var bool|null
     */
    public bool|null $is_vendor = null;

    /**
     * @var array
     */
    public array $types = [];

    /**
     * @param Language $language
     * @return void
     */
    public function mount(Language $language): void
    {
        parent::init();
        $this->language = $language;
        $this->currentLanguageId = $this->language->id;
        if (!$this->isAdministrator) {
            $languages = $this->authUser->languages()->pluck(config('languages.table_languages') . '.id')->all();
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
            ->with('approvedBy', 'updatedBy')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where(
                        'namespace', 'LIKE', '%' . $this->search . '%'
                    )
                        ->orWhere('group', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('key', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('value', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('old_value', 'LIKE', '%' . $this->search . '%');
                });
            })->when($this->needs_translation !== null, function ($query) {
                $query->where(function ($query) {
                    $query->needsTranslation($this->needs_translation);
                });
            })->when($this->approved !== null, function ($query) {
                $query->where(function ($query) {
                    $query->approved($this->approved);
                });
            })->when($this->updated_translation !== null, function ($query) {
                $query->where(function ($query) {
                    $query->isUpdated($this->updated_translation);
                });
            })->when($this->is_vendor !== null, function ($query) {
                $query->where(function ($query) {
                    $query->isVendor($this->is_vendor);
                });
            })->when(!empty($this->types), function ($query) {
                $query->where(function ($query) {
                    $query->type($this->types);
                });
            })
            ->paginate(20);
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
        $this->openAiTranslateLanguageId = $this->translateLanguageExampleId;
        if (!$this->translationExample || !$this->translationExample->value) {
            $this->translationExample = Translation::where('shared_identifier', $this->translation->shared_identifier)
                ->where('language_id', $this->translateLanguageFallbackExampleId)->first();
            $this->openAiTranslateLanguageId = $this->translateLanguageExampleId;
        }
        $this->translatedValue = $this->translation->value;
        $this->dispatchBrowserEvent('showTranslationModal');
    }

    /**
     * @return void
     */
    public function openAITranslate(): void
    {

        if(!$this->translationExample?->value) return;
        $languageCodeFrom = Language::find($this->openAiTranslateLanguageId)?->code;
        if(!$languageCodeFrom) return;
        $languageCodeTo = Language::find($this->currentLanguageId)?->code;
        if(!$languageCodeTo) return;
        try {
            $this->translatedValue = resolve(OpenAITranslationService::class)->translateString(
                $languageCodeFrom,
                $languageCodeTo,
                $this->translationExample->value
            );
        } catch(\Exception $e) {

        }
    }

    /**
     * @param int $id
     * @return void
     */
    public function restoreRequestTranslation(int $id): void
    {
        Translation::findOrFail($id)->update(['needs_translation' => false, 'approved' => true]);
    }

    /**
     * @param int $id
     * @return void
     */
    public function requestTranslation(int $id): void
    {
        Translation::findOrFail($id)->update(['needs_translation' => true,'approved' => false]);
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
            $this->translation->exported = false;
            $this->translation->needs_translation = false;
            if(!$this->translation->updated_translation) {
                $this->translation->previous_approved_by = $this->translation->approved_by;
                $this->translation->previous_updated_by = $this->translation->updated_by;
                $this->translation->old_value = $this->translation->value;
            }
            $this->translation->approved_by = null;
            $this->translation->updated_by = $this->authUser->id;
            $this->translation->updated_translation = true;
            $this->translation->value = $this->translatedValue;
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
        $translation =  Translation::findOrFail($id);
        $translation->update($this->approvedTranslationUpdateArray());
        $this->resetTranslationCache($translation);
    }

    /**
     * @return void
     */
    public function approveAllTranslations(): void
    {
        $this->language->translations()->where('approved', false)
            ->chunkById(200, function($translations) {
                Translation::query()->whereIn('id', $translations->pluck('id')->all())->update($this->approvedTranslationUpdateArray());
                foreach ($translations as $translation) {
                    $this->resetTranslationCache($translation);
                }
            });
    }

    protected function approvedTranslationUpdateArray(): array
    {
        return [
            'approved' => true,
            'updated_translation' => false,
            'needs_translation' => false,
            'old_value' => null,
            'approved_by' => $this->authUser->id,
            'previous_updated_by' => null,
            'previous_approved_by' => null,
        ];
    }

    protected function resetTranslationCache(Translation $translation): void
    {
        Translation::unsetCachedTranslation($translation->language_code, $translation->group ?? null, $translation->namespace ?? null);
        Translation::getCachedTranslations($translation->language_code, $translation->group ?? null, $translation->namespace ?? null);
    }

    /**
     * @param int $id
     * @return void
     */
    public function restoreTranslation(int $id): void
    {
        $translation =  Translation::findOrFail($id);
        if($translation->old_value && !$translation->approved) {
            $translation->value = $translation->old_value;
            $translation->old_value = null;
            $translation->approved_by = $translation->previous_approved_by;
            $translation->updated_by = $translation->previous_updated_by;
            $translation->previous_updated_by = null;
            $translation->previous_approved_by = null;
            $translation->approved = true;
            $translation->exported = true;
            $translation->updated_translation = false;
            $translation->save();
        }
    }

    /**
     * @param bool $exportOnlyModels
     * @return void
     */
    public function exportTranslationsForLanguage(bool $exportOnlyModels = false): void
    {
        if ($this->anotherJobIsRunning()) return;

        $updatedTranslationsTotal = $this->language->translations()
            ->isUpdated(false)->exported(false)
            ->when($exportOnlyModels, function($query) {
                $query->type('model');
            })
            ->approved()->count();

        if ($updatedTranslationsTotal) {
            $batchArray = [
                new ExportTranslationJob($this->language, $exportOnlyModels)
            ];

            $total = Translation::query()->where('language_id', $this->language->id)
                ->isUpdated(false)->exported(false)
                ->when($exportOnlyModels, function($query) {
                    $query->type('model');
                })
                ->approved()
                ->count();
            $language = $this->language;
            $finally = function () use (&$total, &$language) {
                Translator::notifyAdminExportedTranslationsPerLanguage($total, $language);
            };

            $this->emit('startBatchProgress', resolve(BatchProcessor::class)->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);
        } else {
            $this->authUser->notify(new FlashMessage(__('languages::translations.nothing_exported')));
        }
    }

    /**
     * @param bool $exportOnlyModels
     * @return void
     */
    public function exportTranslationsForAllLanguages(bool $exportOnlyModels = false): void
    {
        if ($this->anotherJobIsRunning()) return;

        $languages = Language::find(Translation::query()
            ->isUpdated(false)->exported(false)
            ->when($exportOnlyModels, function($query) {
                $query->type('model');
            })
            ->approved()->distinct()->pluck('language_id')->toArray());

        if ($languages->count() > 0) {

            $batchArray = [];
            $languages->each(function (Language $language) use (&$batchArray) {
                $batchArray[] = new ExportTranslationJob($language);
            });

            $total = Translation::query()
                ->isUpdated(false)->exported(false)
                ->when($exportOnlyModels, function($query) {
                    $query->type('model');
                })
                ->approved()
                ->count();

            $finally = function () use (&$total, &$languages) {
               Translator::notifyAdminExportedTranslationsAllLanguages($total, $languages);
            };
            $this->emit('startBatchProgress', resolve(BatchProcessor::class)->execute($batchArray, null, null, $finally)->dispatchAfterResponse()->id);

        } else {
            $this->authUser->notify(new FlashMessage(__('languages::translations.nothing_exported')));
        }
    }

    /**
     * @param string $key
     * @return void
     */
    public function updateThreeStatesFilter(string $key): void
    {
        if($this->{$key} === null) {
            $this->{$key} = true;
            $this->queryString[$key] = true;
        } else if($this->{$key} === true) {
            $this->{$key} = false;
            $this->queryString[$key] = false;
        } else {
            $this->{$key} = null;
            $this->queryString[$key] = null;
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
