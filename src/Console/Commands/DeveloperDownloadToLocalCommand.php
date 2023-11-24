<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translation;
use Riomigal\Languages\Services\ExportTranslationService;

class DeveloperDownloadToLocalCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:developer-download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download the content of the live DB on local and updates as well the filesystem.';

    protected array $apiKeyParams = [];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->apiKeyParams = ['api_key' => config('languages.api_shared_api_key')];

        $DB = DB::connection(config('languages.db_connection'));
        try {
            $DB->statement('SET FOREIGN_KEY_CHECKS=0;');
            $DB->beginTransaction();

            $this->info('Request all languages.');
            $response = Http::post(config('languages.main_server_domain') . route('languages.api.get-languages', [], false), $this->apiKeyParams);
            if ($response->status() !== 200) {
                throw new \Exception('DeveloperDownloadToLocalCommand: Couldn\'t import languages');
            }
            $this->info('Deleting all languages in DB');
            Language::query()->delete();
            $this->info('Inserting all languages in DB');
            Language::insert($response['data']);

            $this->info('Requesting Translations 1');
            $response = $this->sendGetPaginatedTranslationsRequest();
            if ($response->status() !== 200) {
                $this->throwException();
            }
            $this->info('Deleting All Translations in DB');
            Translation::query()->delete();

            $this->info('Inserting Translations 1 in DB');
            Translation::insert($response['data']);
            for($page = 2; $page <= $response['meta']['last_page']; $page++) {
                $this->info('Requesting Translations 2');
                $response = $this->sendGetPaginatedTranslationsRequest(['page' => $page]);
                if ($response->status() !== 200) {
                    $this->throwException($page);
                }
                $this->info('Inserting Translations 2 in DB');
                Translation::insert($response['data']);
            }
            $DB->commit();
            $this->info('All translation inserted');
            $DB->statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch(\Exception $e) {
            $DB->rollBack();
            $DB->statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Something went wrong resetting database.');
            throw $e;
        }
        $this->info('Start language export to file.');
        $exportTranslationService = resolve(ExportTranslationService::class);
        Language::query()->each(function (Language $language) use ($exportTranslationService) {
            $this->info('Exporting language : ' . $language->code . ' to file.');
            $exportTranslationService->forceExportTranslationForLanguage($language, null, (bool) Setting::getCached()->db_loader);
        });
        $this->info('Download finished.');
    }

    protected function sendGetPaginatedTranslationsRequest(array $params = []): Response
    {
        return Http::post(config('languages.main_server_domain') . route('languages.api.get-paginated-translations', $params, false), $this->apiKeyParams);
    }

    protected function throwException(int $page = 1): void
    {
        throw new \Exception('DeveloperDownloadToLocalCommand: Couldn\'t import page -> ' . $page);
    }
}
