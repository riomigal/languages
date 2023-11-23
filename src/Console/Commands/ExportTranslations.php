<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\ExportTranslationService;
use Riomigal\Languages\Services\MissingTranslationService;

class ExportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:export-translations';

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
        try {
            Setting::setJobsRunning(true);
            $languages = Language::find(Translation::query()
                ->isUpdated(false)->exported(false)
                ->approved()->distinct()->pluck('language_id')->toArray());

            if (count($languages)) {
                $total = Translation::query()
                    ->isUpdated(false)->exported(false)
                    ->approved()
                    ->count();
                $this->info('Exporting translations...');
                $exportTranslationService->exportAllTranslations(Language::all());
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
