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
        if($this->anotherJobIsRunning(true)) return;
        try {
            Setting::setJobsRunning();
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
