<?php

namespace Riomigal\Languages\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Riomigal\Languages\Models\Language;

class PendingTranslationsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $message;

    protected int $total = 0;

    /**
     * @param Language $language The language whose pending translations are counted.
     */
    public function __construct(protected ?Language $language)
    {
        /** @var string|null $queue Configured queue name stored by Queueable. */
        $queue = config('languages.queue_name');
        $this->queue = $queue;
        /** @var Language $language Required by the constructor contract; null remains unsupported at runtime. */
        $language = $this->language;
        $this->total = $language->translations()->needsTranslation()->count();
        $this->message = __('languages::pending-translations-notification.message', ['total' => $this->total]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if($this->total > 0) {
            return ['database', 'mail'];
        } else {
            return [];
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var string $appName Configured application name. */
        $appName = config('app.name');
        return (new MailMessage)
            ->subject($appName . ' - ' . __('languages::pending-translations-notification.subject'))
            ->line($this->message)
            ->action(__('languages::pending-translations-notification.button'), route('languages.login'));
    }


}
