<?php

namespace Riomigal\Languages\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\PendingTranslationsNotification;

class SendAutomaticPendingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languages:send-automatic-pending-translations-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends pending translations notifications to the translators..';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if(Setting::getCached()->enable_automatic_pending_notifications) {
            Translator::query()->each(function (Translator $translator) {
                $translator->languages()->each(function (Language $language) use ($translator) {
                    $translator->notify(new PendingTranslationsNotification($language));
                });
            });
        }
    }
}
