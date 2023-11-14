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

    public function __construct(protected ?Language $language)
    {
        $this->queue = config('languages.queue_name');
        $this->total = $this->language->translations()->needsTranslation()->count();
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
        return (new MailMessage)
            ->subject(config('app.name') . ' - ' . __('languages::pending-translations-notification.subject'))
            ->line($this->message)
            ->action(__('languages::pending-translations-notification.button'), route('languages.login'));
    }


}
