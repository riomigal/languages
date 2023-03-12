<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\ImportTranslationService;

class ImportTranslations extends Command
{
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
        $totalTranslationsBefore = Translation::count();

        $this->info('Existing Translations: ' . $totalTranslationsBefore . '.');

        $this->info('Importing translations...');
        $importTranslationService->importTranslations();
        $total = Translation::count() - $totalTranslationsBefore;
        $this->info('New translations imported: ' . $total . '.');
    }
}
