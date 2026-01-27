<?php

namespace Riomigal\Languages\Services;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitHubPullRequestService
{
    protected string $tempDir;
    protected string $repository;
    protected string $token;
    protected string $baseBranch;
    protected string $branchPrefix;
    protected string $langPathInRepo;
    protected string $gitUserName;
    protected string $gitUserEmail;

    public function __construct()
    {
        $this->repository = config('languages.github_pr.repository');
        $this->token = config('languages.github_pr.token');
        $this->baseBranch = config('languages.github_pr.base_branch');
        $this->branchPrefix = config('languages.github_pr.branch_prefix');
        $this->langPathInRepo = config('languages.github_pr.lang_path_in_repo');
        $this->gitUserName = config('languages.github_pr.git_user_name');
        $this->gitUserEmail = config('languages.github_pr.git_user_email');
    }

    /**
     * Check if GitHub PR integration is enabled and properly configured.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return config('languages.github_pr.enabled')
            && !empty(config('languages.github_pr.repository'))
            && !empty(config('languages.github_pr.token'));
    }

    /**
     * Create a pull request with the exported translation files.
     *
     * @param array $languageCodes
     * @param int $translationsCount
     * @return string|null The PR URL if successful, null otherwise
     * @throws Exception
     */
    public function createPullRequestForExport(array $languageCodes, int $translationsCount): ?string
    {
        if (!self::isEnabled()) {
            Log::warning('GitHubPullRequestService: PR integration is not enabled or not properly configured.');
            return null;
        }

        $this->tempDir = sys_get_temp_dir() . '/lang-export-' . Str::uuid();
        $branchName = $this->branchPrefix . now()->format('Y-m-d-His');

        try {
            $this->cloneRepository();
            $this->createBranch($branchName);
            $this->copyLanguageFiles($languageCodes);

            if (!$this->commitChanges($this->buildCommitMessage($languageCodes, $translationsCount))) {
                Log::info('GitHubPullRequestService: No changes to commit.');
                return null;
            }

            $this->pushBranch($branchName);

            $prUrl = $this->createPullRequest(
                $branchName,
                $this->buildPrTitle($languageCodes),
                $this->buildPrBody($languageCodes, $translationsCount)
            );

            Log::info('GitHubPullRequestService: Pull request created successfully.', ['pr_url' => $prUrl]);

            return $prUrl;
        } catch (Exception $e) {
            Log::error('GitHubPullRequestService: Failed to create pull request.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Clone the repository to a temporary directory.
     *
     * @return void
     * @throws Exception
     */
    protected function cloneRepository(): void
    {
        $cloneUrl = $this->getAuthenticatedCloneUrl();

        $this->runGitCommand(sprintf(
            'clone --depth 1 --branch %s %s %s',
            escapeshellarg($this->baseBranch),
            escapeshellarg($cloneUrl),
            escapeshellarg($this->tempDir)
        ), sys_get_temp_dir());

        // Configure git user identity in the cloned repository
        $this->configureGitUser();
    }

    /**
     * Configure git user identity in the cloned repository.
     *
     * @return void
     * @throws Exception
     */
    protected function configureGitUser(): void
    {
        $this->runGitCommand(sprintf('config user.name %s', escapeshellarg($this->gitUserName)));
        $this->runGitCommand(sprintf('config user.email %s', escapeshellarg($this->gitUserEmail)));
    }

    /**
     * Create a new branch for the export.
     *
     * @param string $branchName
     * @return void
     * @throws Exception
     */
    protected function createBranch(string $branchName): void
    {
        $this->runGitCommand(sprintf('checkout -b %s', escapeshellarg($branchName)));
    }

    /**
     * Copy language files from the application to the cloned repository.
     *
     * @param array $languageCodes
     * @return void
     */
    protected function copyLanguageFiles(array $languageCodes): void
    {
        $sourceLangPath = App::langPath();
        $destLangPath = $this->tempDir . '/' . $this->langPathInRepo;

        // Ensure destination directory exists
        if (!File::isDirectory($destLangPath)) {
            File::makeDirectory($destLangPath, 0755, true);
        }

        foreach ($languageCodes as $languageCode) {
            // Copy language directory (e.g., lang/en/)
            $sourceDir = $sourceLangPath . '/' . $languageCode;
            $destDir = $destLangPath . '/' . $languageCode;

            if (File::isDirectory($sourceDir)) {
                File::copyDirectory($sourceDir, $destDir);
            }

            // Copy JSON file if exists (e.g., lang/en.json)
            $sourceJson = $sourceLangPath . '/' . $languageCode . '.json';
            $destJson = $destLangPath . '/' . $languageCode . '.json';

            if (File::exists($sourceJson)) {
                File::copy($sourceJson, $destJson);
            }
        }

        // Also copy vendor translations if they exist
        $sourceVendor = $sourceLangPath . '/vendor';
        $destVendor = $destLangPath . '/vendor';

        if (File::isDirectory($sourceVendor)) {
            File::copyDirectory($sourceVendor, $destVendor);
        }
    }

    /**
     * Commit all changes in the repository.
     *
     * @param string $message
     * @return bool True if changes were committed, false if nothing to commit
     * @throws Exception
     */
    protected function commitChanges(string $message): bool
    {
        $this->runGitCommand('add -A');

        // Check if there are changes to commit
        $status = $this->runGitCommand('status --porcelain');

        if (empty(trim($status))) {
            return false;
        }

        $this->runGitCommand(sprintf('commit -m %s', escapeshellarg($message)));

        return true;
    }

    /**
     * Push the branch to the remote repository.
     *
     * @param string $branchName
     * @return void
     * @throws Exception
     */
    protected function pushBranch(string $branchName): void
    {
        $this->runGitCommand(sprintf('push -u origin %s', escapeshellarg($branchName)));
    }

    /**
     * Create a pull request via the GitHub API.
     *
     * @param string $branchName
     * @param string $title
     * @param string $body
     * @return string The PR URL
     * @throws Exception
     */
    protected function createPullRequest(string $branchName, string $title, string $body): string
    {
        $response = Http::withToken($this->token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->post("https://api.github.com/repos/{$this->repository}/pulls", [
                'title' => $title,
                'body' => $body,
                'head' => $branchName,
                'base' => $this->baseBranch,
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create pull request: ' . $response->body());
        }

        return $response->json('html_url');
    }

    /**
     * Clean up the temporary directory.
     *
     * @return void
     */
    protected function cleanup(): void
    {
        if (isset($this->tempDir) && File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
    }

    /**
     * Get the authenticated clone URL for the repository.
     *
     * @return string
     */
    protected function getAuthenticatedCloneUrl(): string
    {
        return sprintf('https://%s@github.com/%s.git', $this->token, $this->repository);
    }

    /**
     * Run a git command.
     *
     * @param string $command
     * @param string|null $workingDir
     * @return string
     * @throws Exception
     */
    protected function runGitCommand(string $command, ?string $workingDir = null): string
    {
        $workingDir = $workingDir ?? $this->tempDir;
        $fullCommand = sprintf('cd %s && git %s 2>&1', escapeshellarg($workingDir), $command);

        exec($fullCommand, $output, $returnCode);

        $outputString = implode("\n", $output);

        if ($returnCode !== 0) {
            // Sanitize output to remove token from error messages
            $sanitizedOutput = str_replace($this->token, '[REDACTED]', $outputString);
            throw new Exception("Git command failed: {$sanitizedOutput}");
        }

        return $outputString;
    }

    /**
     * Build the commit message.
     *
     * @param array $languageCodes
     * @param int $translationsCount
     * @return string
     */
    protected function buildCommitMessage(array $languageCodes, int $translationsCount): string
    {
        return sprintf(
            'chore(translations): export %d translations for %s',
            $translationsCount,
            implode(', ', $languageCodes)
        );
    }

    /**
     * Build the PR title.
     *
     * @param array $languageCodes
     * @return string
     */
    protected function buildPrTitle(array $languageCodes): string
    {
        return sprintf('Translation export - %s', now()->format('Y-m-d'));
    }

    /**
     * Build the PR body.
     *
     * @param array $languageCodes
     * @param int $translationsCount
     * @return string
     */
    protected function buildPrBody(array $languageCodes, int $translationsCount): string
    {
        return sprintf(
            "## Translation Export\n\n" .
                "This PR contains exported translations from the Languages package.\n\n" .
                "### Details\n\n" .
                "- **Languages**: %s\n" .
                "- **Total translations**: %d\n" .
                "- **Exported at**: %s\n\n" .
                "---\n" .
                "*This PR was automatically created by the Languages package.*",
            implode(', ', $languageCodes),
            $translationsCount,
            now()->format('Y-m-d H:i:s T')
        );
    }
}
