<?php

namespace Riomigal\Languages\Jobs\Traits;


use Riomigal\Languages\Exceptions\ExportFileException;
use Riomigal\Languages\Exceptions\ExportTranslationException;
use Riomigal\Languages\Exceptions\ImportTranslationsException;
use Riomigal\Languages\Exceptions\MassCreateTranslationsException;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\FlashMessage;

trait HandlesFailedJobs
{
    /**
     * Handle a job failure. Notifies all admin translators about failed job
     */
    public function failed(\Throwable $e): void
    {
        Translator::query()->admin()->each(function (Translator $translator) use ($e) {
            if (in_array($e::class, [ImportTranslationsException::class, ExportTranslationException::class, MassCreateTranslationsException::class, ExportFileException::class])) {
                $translator->notify(new FlashMessage($e->getPublicMessage()));
            } else {
                $translator->notify(new FlashMessage(__('languages::global.something_wrong')));
            }
        });
    }
}
