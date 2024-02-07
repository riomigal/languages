<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ExportTranslationService;

class ExportTranslations extends Command
{
    use ChecksForRunningJobs;

    /**
     * The name and signature of the console command.
     * --force: it will export all files even if exported is true in the translations record
     *
     * @var string
     */
    protected $signature = 'languages:export-translations {--force=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports all approved translations.';

    /**
     * Execute the console command.
     */
    public function handle(ExportTranslationService $exportTranslationService): void
    {
        $forceExport = (bool) $this->option('force');

        if($this->anotherJobIsRunning(true)) return;
        try {
            Setting::setJobsRunning();
            $languages = Language::find(Translation::query()
                ->isUpdated(false)
                ->when(!$forceExport, function($query) {
                    $query->exported(false);
                })
                ->approved()->distinct()->pluck('language_id')->toArray());

            if (count($languages)) {
                $total = Translation::query()
                    ->isUpdated(false)
                    ->when(!$forceExport, function($query) {
                        $query->exported(false);
                    })
                    ->approved()
                    ->count();
                $this->info('Exporting translations...');
                Language::query()->each(function (Language $language) use ($exportTranslationService, $forceExport) {
                    if($forceExport) {
                        $exportTranslationService->forceExportTranslationForLanguage($language, null, Setting::first()->db_loader);
                    } else {
                        $exportTranslationService->exportTranslationForLanguage($language, null, Setting::first()->db_loader);
                    }
                });
                Translator::notifyAdminExportedTranslationsAllLanguages($total, $languages);
                $total -= Translation::query()
                    ->isUpdated(false)->exported(false)
                    ->approved()
                    ->count();
                $this->info('Total translations exported: ' . $total . '.');
            } else {
                $this->info('Nothing to export.');
            }
            Setting::setJobsRunning(false);
        } catch(\Exception $e) {
            Setting::setJobsRunning(false);
            throw $e;
        }
    }
}
