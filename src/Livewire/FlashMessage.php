<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FlashMessage extends Component
{
    /**
     * @var array<string, string> Notification UUIDs mapped to JSON data.
     */
    public array $notifications = [];

    /**
     * @var int
     */
    public int $notifiable;

    /**
     * @var bool
     */
    public bool $showMessages = false;

    /**
     * @var int
     */
    public int $totalNotifications = 0;

    /**
     * @return void
     */
    public function mount(): void
    {
        $guard = config('languages.translator_guard');
        if ($guard !== null && !is_string($guard)) {
            throw new \TypeError('languages.translator_guard must be a string or null.');
        }
        $notifiable = auth($guard)->id();
        if ($notifiable === null) {
            throw new \TypeError('Cannot assign null to property ' . self::class . '::$notifiable of type int');
        }
        if (is_string($notifiable) && !is_numeric($notifiable)) {
            throw new \TypeError('The notification recipient ID must be numeric.');
        }
        $this->notifiable = (int) $notifiable;
        $this->updateNotifications();
    }

    /**
     * @return void
     */
    public function toggleMessages(): void {
        $this->showMessages = !$this->showMessages;
    }

    /**
     * @return void
     */
    public function markAllAsRead(): void {
        DB::connection($this->databaseConnection())->table('notifications')->whereIn('id', array_keys($this->notifications))->update([
            'read_at' => now()
        ]);
        $this->updateNotifications();
        $this->toggleMessages();
    }

    /**
     * @return void
     */
    public function updateNotifications(): void
    {
        /** @var array<string, string> $notifications The data column is text, keyed by UUID. */
        $notifications = DB::connection($this->databaseConnection())->table('notifications')->where('notifiable_id', $this->notifiable)
            ->whereNull('read_at')->orderBy('created_at', 'DESC')->pluck('data', 'id')->all();
        $this->notifications = $notifications;

        $this->totalNotifications = count($this->notifications);
    }

    /**
     * @param string $id
     * @return void
     */
    public function markAsRead(string $id): void
    {
       DB::connection($this->databaseConnection())->table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $this->notifiable)
            ->update(
            [
                'read_at' => now()
            ]
        );
        $this->updateNotifications();
        if($this->totalNotifications == 0) {
            $this->toggleMessages();
        }
    }

    private function databaseConnection(): string|\UnitEnum|null
    {
        $connection = config('languages.db_connection');
        if ($connection !== null && !is_string($connection) && !$connection instanceof \UnitEnum) {
            throw new \TypeError('languages.db_connection must be a string, UnitEnum or null.');
        }

        return $connection;
    }

    /**
     * @return View
     */
    public function render(): View
    {
        /** @phpstan-var view-string $viewName */
        $viewName = 'languages::flash-message';
        $view = view($viewName);
        $view->layout('languages::layouts.app');

        return $view;
    }
}
