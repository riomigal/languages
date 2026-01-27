<?php

namespace Riomigal\Languages\Jobs;

use Exception;
use Illuminate\Support\Facades\Log;
use Riomigal\Languages\Jobs\Job\BaseJob;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Services\GitHubPullRequestService;

class CreateTranslationPullRequestJob extends BaseJob
{
    /**
     * @param array $languageCodes
     * @param int $translationsCount
     */
    public function __construct(
        protected array $languageCodes,
        protected int $translationsCount
    ) {
        parent::__construct();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        if (!GitHubPullRequestService::isEnabled()) {
            Log::info('CreateTranslationPullRequestJob: GitHub PR integration is not enabled, skipping.');
            return;
        }

        try {
            $prUrl = resolve(GitHubPullRequestService::class)
                ->createPullRequestForExport($this->languageCodes, $this->translationsCount);

            if ($prUrl) {
                Translator::notifyAdminPullRequestCreated($prUrl, $this->languageCodes, $this->translationsCount);
            }
        } catch (Exception $e) {
            Log::error('CreateTranslationPullRequestJob: Failed to create pull request.', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
