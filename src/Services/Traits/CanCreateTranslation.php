<?php

namespace Riomigal\Languages\Services\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Models\Translation;

trait CanCreateTranslation
{

    /**
     * @param array $content
     * @param string $relativePath
     * @param string $relativePathname
     * @param $sharedRelativePathname
     * @param string $type
     * @param int $languageId
     * @param string $languageCode
     * @return void
     * @throws MassCreateTranslationsException
     */
    protected function massCreateTranslations(array $content, string $relativePath, string $relativePathname, $sharedRelativePathname, string $type, int $languageId, string $languageCode): void
    {

        try {
            DB::beginTransaction();
            $translationsArray = [];
            $file = File::basename($relativePathname);
            foreach ($content as $key => $value) {
                $sharedIdentifier = base64_encode(str_replace(base_path(), '', $sharedRelativePathname . $key));
                $translationsArray[] = $this->getNewTranslation($languageId, $languageCode, $relativePath, $relativePathname, $sharedIdentifier, $file, $type, $key, $value);
            }

            $translationsArray = array_filter($translationsArray);
            if (count($translationsArray) > 0) {
                $this->massInsertTranslations($translationsArray);
            }
            DB::commit();;
        } catch (\Exception|MassCreateTranslationsException $e) {
            DB::rollBack();
            if ($e::class == MassCreateTranslationsException::class) {
                throw $e;
            } else {
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
                $translationsArray[] = $this->getTranslationArray($languageId, $languageCode, $translation['relative_path'], $translation['relative_pathname'], $translation['shared_identifier'], $translation['file'], $translation['type'], $translation['key'], '');
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
     * @param string $relativePath
     * @param string $relativePathname
     * @param string $sharedIdentifier
     * @param string $file
     * @param string $type
     * @param string $key
     * @param string $value
     * @return array
     */
    protected function getNewTranslation(int $languageId, string $languageCode, string $relativePath, string $relativePathname, string $sharedIdentifier, string $file, string $type, string $key, string $value): array
    {
        if (!$this->translationExists($sharedIdentifier, $languageCode)) {
            return $this->getTranslationArray($languageId, $languageCode, $relativePath, $relativePathname, $sharedIdentifier, $file, $type, $key, $value);
        }
        return [];

    }


    /**
     * Gets array record
     *
     * @param int $languageId
     * @param string $languageCode
     * @param string $relativePath
     * @param string $relativePathname
     * @param string $sharedIdentifier
     * @param string $file
     * @param string $type
     * @param string $key
     * @param string $value
     * @return array
     */
    protected function getTranslationArray(int $languageId, string $languageCode, string $relativePath, string $relativePathname, string $sharedIdentifier, string $file, string $type, string $key, string $value): array
    {
        return [
            'language_id' => $languageId,
            'language_code' => $languageCode,
            'relative_path' => $relativePath,
            'relative_pathname' => $relativePathname,
            'shared_identifier' => $sharedIdentifier,
            'file' => $file,
            'type' => $type,
            'key' => $key,
            'value' => $value,
            'approved' => true,
            'needs_translation' => !$value,
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
