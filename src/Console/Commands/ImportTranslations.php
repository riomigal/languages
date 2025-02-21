<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ImportTranslationService;

class ImportTranslations extends Command
{
    use ChecksForRunningJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:import-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all the translations from in the filesystem.';

    /**
     * Execute the console command.
     */
    public function handle(ImportTranslationService $importTranslationService): void
    {
        if($this->anotherJobIsRunning(true)) return;

        try {
            Setting::setJobsRunning();

            $totalTranslationsBefore = Translation::count();

            $this->info('Existing Translations: ' . $totalTranslationsBefore . '.');

            $this->info('Importing translations...');
            $importTranslationService->importTranslations();
//            Translator::notifyAdminImportedTranslations($totalTranslationsBefore);
            $total = Translation::count() - $totalTranslationsBefore;
            $this->info('New translations imported: ' . $total . '.');

            Setting::setJobsRunning(false);
        } catch(\Exception $e) {
            Setting::setJobsRunning(false);
            throw $e;
        }
    }
}
