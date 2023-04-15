<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FlashMessage extends Component
{
    /**
     * @var array
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
        $this->notifiable = auth(config('languages.translator_guard'))->id();
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
        DB::table('notifications')->whereIn('id', array_keys($this->notifications))->update([
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
        $this->notifications = DB::table('notifications')->where('notifiable_id', $this->notifiable)
            ->whereNull('read_at')->orderBy('created_at', 'DESC')->pluck('data', 'id')->all();

        $this->totalNotifications = count($this->notifications);
    }

    /**
     * @param string $id
     * @return void
     */
    public function markAsRead(string $id): void
    {
       DB::table('notifications')->where('id', $id)->update(
            [
                'read_at' => now()
            ]
        );
        $this->updateNotifications();
        if($this->totalNotifications == 0) {
            $this->toggleMessages();
        }
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::flash-message')
            ->layout('languages::layouts.app');
    }
}
