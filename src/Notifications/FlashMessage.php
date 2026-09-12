<?php

namespace Riomigal\Languages\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FlashMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $message)
    {
        /** @var string|null $queue Configured queue name stored by Queueable. */
        $queue = config('languages.queue_name');
        $this->queue = $queue;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
            'date_time' => Carbon::now()->toDateTimeString()
        ];
    }


}
