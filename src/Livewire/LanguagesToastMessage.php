<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class LanguagesToastMessage extends Component
{
    const MESSAGE_TYPES = [
        'SUCCESS' => 'SUCCESS',
        'DELETED' => 'DELETED',
        'INFO' => 'INFO',
        'WARNING' => 'WARNING',
    ];

    public string $type = 'SUCCESS';

    /**
     * @var string
     */
    public string $message = '';

    /**
     * @var bool
     */
    public bool $showMessage = false;

    /**
     * @var string[]
     */
    protected $listeners = [
        'showToast' => 'show',
    ];

    public function show(string $message, string $type = '', int $duration = 3000): void
    {
        $this->message = $message;
        if ($type) {
            $this->type = self::MESSAGE_TYPES[$type];
        }
        $this->showMessage = true;

        $this->emit('closeToastMessage', $duration);
    }

    public function close(): void
    {
        $this->reset();
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::languages-toast-message');
    }
}
