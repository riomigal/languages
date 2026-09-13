<?php

namespace Riomigal\Languages\Services;

class Toast
{
    public const MESSAGE_TYPES = ['SUCCESS', 'DELETED', 'INFO', 'WARNING'];

    public static function flash(string $message, string $type = 'SUCCESS', int $duration = 3000): void
    {
        session()->flash('toast', compact('message', 'type', 'duration'));
    }
}
