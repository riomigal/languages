<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Riomigal\Languages\Services\GitHubPullRequestService;
use Riomigal\Languages\Tests\BaseTestCase;

class GitHubPullRequestServiceTest extends BaseTestCase
{
    public function test_it_passes_the_language_directory_to_pint(): void
    {
        $tempDirectory = sys_get_temp_dir().'/languages-pr-service-test-'.Str::uuid();
        $langDirectory = $tempDirectory.'/lang';
        $pintPath = $tempDirectory.'/pint';

        File::makeDirectory($langDirectory, 0755, true);
        File::put($pintPath, '');

        try {
            $service = new TestableGitHubPullRequestService($tempDirectory, $pintPath);

            $service->formatLanguageFilesForTest();

            $this->assertSame([$pintPath, $langDirectory], $service->pintArguments);
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }
}

class TestableGitHubPullRequestService extends GitHubPullRequestService
{
    /**
     * @var array{0: string, 1: string}
     */
    public array $pintArguments = [];

    public function __construct(string $tempDirectory, protected string $pintPath)
    {
        $this->tempDir = $tempDirectory;
        $this->langPathInRepo = 'lang';
    }

    public function formatLanguageFilesForTest(): void
    {
        $this->formatLanguageFiles();
    }

    protected function getPintPath(): string
    {
        return $this->pintPath;
    }

    /**
     * @return array{0: array<int, string>, 1: int}
     */
    protected function executePint(string $pintPath, string $langPath): array
    {
        $this->pintArguments = [$pintPath, $langPath];

        return [[], 0];
    }
}
