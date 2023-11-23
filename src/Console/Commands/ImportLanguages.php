<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Services\ImportLanguageService;

class ImportLanguages extends Command
{
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
        try {
            Setting::setJobsRunning();

            $languages = Language::all();
            if($languages->count()) {
                $this->info('Existing languages: ' . implode(',', $languages->pluck('code')->all()) . '.');
            }
            $languages = $languages->pluck('id')->all();
            $this->info('Importing languages...');
            $importLanguageService->importLanguages();
            $newLanguages = Language::all()
                ->reject(function (Language $language) use ($languages) {
                    return in_array($language->id, $languages);
                })->pluck('code')->all();
            if($newLanguages) {
                $this->info('New languages imported: ' . implode(', ', $newLanguages) . '.');
            } else {
                $this->info('Nothing imported.');
            }


            Setting::setJobsRunning(false);
        } catch(\Exception $e) {
            Setting::setJobsRunning(false);
            throw $e;
        }
    }
}
