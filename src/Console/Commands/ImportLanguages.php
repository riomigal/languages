<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Livewire\Traits\ChecksForRunningJobs;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\ImportLanguageService;

class ImportLanguages extends Command
{
    use ChecksForRunningJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:import-languages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all the languages which are in the filesystem.';

    /**
     * Execute the console command.
     */
    public function handle(ImportLanguageService $importLanguageService): void
    {
        if($this->anotherJobIsRunning(true)) return;
        try {
            Setting::setJobsRunning();

            $languages = Language::all();
            if($languages->count()) {
                $this->info('Existing languages: ' . implode(', ', $languages->pluck('name')->all()) . '.');
            }

            $languages = $languages->pluck('id')->all();
            $importLanguageService->importLanguages();
            $newLanguages = Translator::notifyAdminImportedLanguages($languages);

            if($newLanguages) {
                $this->info('New languages imported: ' . implode(', ', $newLanguages) . '.');
            } else {
                $this->info('Nothing imported.');
            }
            Language::notifyAdminImportedLanguages($languages);

            Setting::setJobsRunning(false);
        } catch(\Exception $e) {
            Setting::setJobsRunning(false);
            throw $e;
        }
    }
}
