<?php

namespace Riomigal\Languages\Services\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;

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
    public function findMissingTranslationsByLanguage(Collection $languages, Language $rootLanguage): void
    {
        $this->missingLanguages = $languages->reject(fn($language) => $language->id == $rootLanguage->id);

        Translation::query()->select('shared_identifier')->where('language_code', $rootLanguage->code)
            ->groupBy('shared_identifier')
            ->orderBy('shared_identifier')->chunk(400,
                function ($records) use ($rootLanguage) {
                    foreach ($this->missingLanguages as $language) {

                        // Get array of all identifier
                        $identifierArray = $records->pluck('shared_identifier')->all();

                        // Get array of language identifier found
                        $identifierArrayTwo = Translation::query()->select('shared_identifier')
                            ->where('language_id', $language->id)
                            ->whereIn('shared_identifier', $identifierArray)->pluck('shared_identifier')->all();

                        // Get missing identifier for language
                        $missingIdentifier = array_diff($identifierArray, $identifierArrayTwo);

                        Translation::query()
                            ->select('shared_identifier', 'namespace', 'group', 'is_vendor', 'type', 'key', 'value')
                            ->whereIn('shared_identifier', $missingIdentifier)
                            ->where('language_code', $rootLanguage->code)
                            ->groupBy('shared_identifier', 'namespace', 'group', 'is_vendor', 'type', 'key', 'value')
                            ->orderBy('shared_identifier')
                            ->chunk(400, function ($translations) use ($language) {
                                $this->massCreateEloquentTranslations($translations->toArray(), $language->id, $language->code);
                            });
                    }
                }
            );

    }

    /**
     * @param array $content
     * @param string $sharedRelativePathname
     * @param string $type
     * @param int $languageId
     * @param string $languageCode
     * @param string $namespace
     * @param string $group
     * @param bool $isVendor
     * @return void
     * @throws MassCreateTranslationsException
     */
    protected function massCreateTranslations(array $content, string $sharedRelativePathname, string $type, int $languageId, string $languageCode, string $namespace, string $group, bool $isVendor): void
    {
        try {
            DB::beginTransaction();
            $translationsArray = [];
            $namespace = $group;
            foreach ($content as $key => $value) {
                $sharedIdentifier = $type . $namespace . $group . $key;
                $sharedIdentifier = base64_encode($sharedIdentifier);
                if($type == 'model') {
                    [$group, $key] = explode('.', $key);
                }
                $translationsArray[] = $this->getNewTranslation($languageId, $languageCode, $sharedIdentifier, $type, $key, $value, $namespace, $group, $isVendor);
            }
            $translationsArray = array_filter($translationsArray);
            if (count($translationsArray) > 0) {
                $this->massInsertTranslations($translationsArray);
            }
            DB::commit();
            Translation::unsetCachedTranslation($languageCode, $group ?? null, $namespace ?? null);
        } catch (\Exception|MassCreateTranslationsException $e) {
            DB::rollBack();
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
     * @param array $translations
     * @param int $languageId
     * @param string $languageCode
     * @return void
     * @throws MassCreateTranslationsException
     */
    protected function massCreateEloquentTranslations(array $translations, int $languageId, string $languageCode): void
    {
        try {
            DB::beginTransaction();
            $translationsArray = [];
            foreach ($translations as $translation) {
                $translationsArray[] = $this->getTranslationArray(
                    $languageId,
                    $languageCode,
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
            }
            $this->massInsertTranslations($translationsArray);
            DB::commit();
        } catch (\Exception|MassCreateTranslationsException $e) {
            DB::rollBack();
            if ($e::class == MassCreateTranslationsException::class) {
                throw $e;
            } else {
                Log::error('Something went wrong while mass creating eloquent translations.', ['relativePathname' => $translations[0]['relative_pathname'], 'array' => $translationsArray]);
                throw new MassCreateTranslationsException($e->getMessage(), __('languages::exceptions.mass_create_eloquent_fails', ['relativePathname' => $translations[0]['relative_pathname']]));
            }
        }

    }


    /**
     * Gets new array record for mass insert if record non-existent
     *
     * @param int $languageId
     * @param string $languageCode
     * @param string $sharedIdentifier
     * @param string $type
     * @param string $key
     * @param string $value
     * @param string $namespace
     * @param string $group
     * @param bool $isVendor
     * @return array
     */
    protected function getNewTranslation(int $languageId, string $languageCode, string $sharedIdentifier, string $type, string $key, string $value, string $namespace, string $group, bool $isVendor): array
    {
        if (!$this->translationExists($sharedIdentifier, $languageCode)) {
            return $this->getTranslationArray($languageId, $languageCode, $sharedIdentifier, $type, $key, $value, $namespace, $group, $isVendor);
        }
        return [];
    }


    /**
     * Gets array record
     *
     * @param int $languageId
     * @param string $languageCode
     * @param string $sharedIdentifier
     * @param string $type
     * @param string $key
     * @param string $value
     * @param string $namespace
     * @param string $group
     * @param bool $isVendor
     * @return array
     */
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
            Log::error('Couldn\'t mass insert translations for path: ' . $translations[0]['relative_pathname']);
            throw new MassCreateTranslationsException($e->getMessage(), __('languages::exceptions.invalid_translation_array', ['relativePathname' => $translations[0]['relative_pathname']]));
        }
    }
}
