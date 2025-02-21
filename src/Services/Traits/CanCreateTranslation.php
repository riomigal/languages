<?php

namespace Riomigal\Languages\Services\Traits;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Jobs\MassCreateEloquentTranslationsJob;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\OpenAITranslationService;

trait CanCreateTranslation
{
    /**
     * @var Collection
     */
    protected Collection $missingLanguages;

    /**
     * @param Collection $languages
     * @param Language $rootLanguage
     * @return void
     * @throws MassCreateTranslationsException
     */
    public function findMissingTranslationsByLanguage(Collection $languages, Language $rootLanguage, ?Batch $batch = null): void
    {
        $this->missingLanguages = $languages->reject(fn($language) => $language->id == $rootLanguage->id);

        Translation::query()
            ->where('language_code', $rootLanguage->code)
            ->chunkById(100,
                function ($records) use ($rootLanguage, $batch) {
                    foreach ($this->missingLanguages as $language) {

                        // Get array of all identifier
                        $identifierArray = $records->pluck('shared_identifier')->all();

                        // Get array of language identifier found
                        $identifierArrayTwo = Translation::query()
                            ->where('language_id', $language->id)
                            ->whereIn('shared_identifier', $identifierArray)->pluck('shared_identifier')->all();

                        // Get missing identifier for language
                        $missingIdentifier = array_diff($identifierArray, $identifierArrayTwo);

                        $fromLanguageCode = $rootLanguage->code;
                        Translation::query()
                            ->whereIn('shared_identifier', $missingIdentifier)
                            ->where('language_code', $fromLanguageCode)
                            ->chunkById(Setting::getCached()->enable_open_ai_translations ? config('languages.max_open_ai_missing_trans') : 400,
                                function ($translations) use ($language, $batch, $rootLanguage) {
                                    $translationIds = $translations->pluck('id')->all();
                                    if($batch) {
                                        $batch->add(new MassCreateEloquentTranslationsJob($translationIds, $language->id, $rootLanguage->id));
                                    } else {
                                        $this->massCreateEloquentTranslations($translationIds, $language, $rootLanguage);
                                    }
                                }
                            );
                    }
                }
            );

    }

    /**
     * @throws MassCreateTranslationsException
     */
    protected function massCreateTranslations(array $content, string $type, int $languageId, string $languageCode, string $namespace, string $group, bool $isVendor): void
    {
        try {
            $translationsArray = [];
            if($type == 'model') {
                $namespace = $group;
            }
            foreach ($content as $key => $value) {
                $sharedIdentifier = $type . $namespace . $group . $key;
                $sharedIdentifier = base64_encode($sharedIdentifier);
                if($type == 'model') {
                    [$group, $key] = explode('.', $key);
                }
                $translationsArray[$sharedIdentifier] = $this->getTranslationArray($languageId, $languageCode, $sharedIdentifier, $type, $key, $value, $namespace, $group, $isVendor);
            }

            $existingKeys = Translation::select('shared_identifier', 'language_code')
                ->where('language_code', $languageCode)
                ->whereIn('shared_identifier', array_keys($translationsArray))
                ->pluck('shared_identifier')->all();

            $translationsArray = array_filter($translationsArray, function($translation, $key) use ($existingKeys) {
                return !in_array($key, $existingKeys);
            }, ARRAY_FILTER_USE_BOTH);

            $translationsArray = array_filter($translationsArray);
            if (count($translationsArray) > 0) {
                $this->massInsertTranslations($translationsArray);
            }
            Translation::unsetCachedTranslation($languageCode, $group ?? null, $namespace ?? null);
        } catch (\Exception|MassCreateTranslationsException $e) {
            if ($e::class == MassCreateTranslationsException::class) {
                throw $e;
            } else {
                if($isVendor) {
                    $relativePathname = App::langPath('vendor/' . $namespace . '/' . $languageCode . '/' . $group . '.' . $type);
                } else {
                    $relativePathname = App::langPath('vendor/' . $namespace . '/' . $languageCode . '/' . $group . '.' . $type);
                }
                Log::error('Something went wrong while mass creating translations.', ['relativePathname' => $relativePathname, 'array' => $translationsArray]);
                throw new MassCreateTranslationsException($e->getMessage(), __('languages::exceptions.mass_create_fails', ['relativePathname' => $relativePathname]));
            }
        }
    }

    /**
     * @param array $translationIds
     * @param Language $language
     * @param Language $rootLanguage
     * @return void
     * @throws MassCreateTranslationsException
     */
    protected function massCreateEloquentTranslations(array $translationIds, Language $language, Language $rootLanguage): void
    {
        try {
            $translationsArray = [];
            $openTranslateService = resolve(OpenAITranslationService::class);
            $translations = Translation::whereIn('id', $translationIds)->get()->toArray();
            foreach ($translations as $translation) {
                if (!$this->translationExists($translation['shared_identifier'], $language->code)) {
                    $generatedTranslation = $this->getTranslationArray(
                        $language->id,
                        $language->code,
                        $translation['shared_identifier'],
                        $translation['type'],
                        $translation['key'],
                        $translation['value'],
                        $translation['namespace'],
                        $translation['group'],
                        $translation['is_vendor'],
                        false,
                        true
                    );
                    $generatedTranslation['exported'] = false;
                    $translationsArray[] = $generatedTranslation;
                }
            }
            try {
                $tempTranslationsArray = json_decode(json_encode($translationsArray) ,true);

                // Get Open Api translated array
                $translatedArrayResult = [];
                $translatedArrayResult = $openTranslateService->translateArray(
                    $rootLanguage,
                    $language,
                    collect($translationsArray)->mapWithKeys(function($translation, $index) {
                        return ['t_' . $index => $translation['value']];
                    })->toArray()
                );
                if(empty($translatedArrayResult)) throw new \Exception('Open AI returned null value');

                $translationsArray = collect($translationsArray)->map(function ($translation, $index) use (
                    $translatedArrayResult,
                    $openTranslateService,
                    $rootLanguage,
                    $language
                ) {
                    if(isset($translatedArrayResult['t_' . $index])) {
                        $translation['value'] = $translatedArrayResult['t_' . $index];
                    } else {
                        $res = $openTranslateService->translateString($rootLanguage, $language,  $translation['value']);
                        if(!empty($res)) {
                            $translation['value'] = $res;
                        }
                    }
                    return $translation;
                })->toArray();
            } catch(\Exception $e) {
                Log::warning('CanCreateTranslation::massCreateEloquentTranslations() ArrayTranslations failed -> ' . $e->getMessage(), [
                    'translationsArray' => $translationsArray,
                    'translatedArrayResult' => $translatedArrayResult
                ]);
                $translationsArray = $tempTranslationsArray;

                    $translationsArray = collect($translationsArray)->map(function ($translation, $index) use ($openTranslateService, $rootLanguage,
                        $language) {
                        try {
                            $tempValue =  $translation['value'];
                            $translation['value'] = $openTranslateService->translateString($rootLanguage, $language,  $translation['value']);
                            return $translation;
                        } catch(\Exception $e) {
                            Log::warning('CanCreateTranslation::massCreateEloquentTranslations() StringTranslations failed -> ' . $e->getMessage(), [
                                'value' => $translation['value'],
                            ]);
                            $translation['value'] = $tempValue;
                        }
                    })->toArray();

            }

            $this->massInsertTranslations($translationsArray);
        } catch (\Exception|MassCreateTranslationsException $e) {
            $errorId = Str::random();
            Log::error('Something went wrong while mass creating eloquent translations: ', [
                'error_id' => $errorId,
                'message' => $e->getMessage(),
                'fromLanguage' => $rootLanguage->code,
                'toLanguage' => $language->code,
                'shared_identifier' => collect($translations)->pluck('shared_identifier')->all()
            ]);
            throw new MassCreateTranslationsException($e->getMessage(), __('languages::exceptions.mass_create_eloquent_fails', ['errorId' => $errorId]));
        }

    }

    protected function getNewTranslation(int $languageId, string $languageCode, string $sharedIdentifier, string $type, string $key, string $value, string $namespace, string $group, bool $isVendor): array
    {
        if (!$this->translationExists($sharedIdentifier, $languageCode)) {
            return $this->getTranslationArray($languageId, $languageCode, $sharedIdentifier, $type, $key, $value, $namespace, $group, $isVendor);
        }
        return [];
    }

    protected function getTranslationArray(int $languageId, string $languageCode, string $sharedIdentifier, string $type, string $key, string $value, string $namespace, string $group, bool $isVendor, bool $approved = true, ?bool $needsTranslation = null): array
    {
        return [
            'language_id' => $languageId,
            'language_code' => $languageCode,
            'shared_identifier' => $sharedIdentifier,
            'type' => $type,
            'namespace' => $namespace,
            'is_vendor' => $isVendor,
            'group' => $group,
            'key' => $key,
            'value' => $value,
            'approved' => $approved,
            'needs_translation' => ($needsTranslation !== null) ? $needsTranslation : !$value,
            'updated_translation' => false,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Checks if a record exists
     *
     * @param string $sharedIdentifier
     * @param string $languageCode
     * @return bool
     */
    protected function translationExists(
        string $sharedIdentifier,
        string $languageCode
    ): bool
    {
        return Translation::where(
            [
                ['shared_identifier', '=', $sharedIdentifier],
                ['language_code', '=', $languageCode]
            ],
        )->exists();
    }


    /**
     * @param array $translations
     * @return void
     * @throws MassCreateTranslationsException
     */
    protected function massInsertTranslations(array $translations): void
    {
        try {
            $translations = array_filter($translations);
            Translation::insert($translations);
        } catch (\Exception $e) {
            $errorId = Str::random();
            $languageCode = $translations[0]['language_code'];
            Log::error('Couldn\'t mass insert translations language: ' . $languageCode, [
                'error_id' => $errorId,
                'shared_identifier' => collect($translations)->pluck('shared_identifier')->all()
            ]);
            throw new MassCreateTranslationsException($e->getMessage(), __('languages::exceptions.invalid_translation_array', ['errorId' => $errorId, 'languageCode' => $languageCode]));
        }
    }
}
