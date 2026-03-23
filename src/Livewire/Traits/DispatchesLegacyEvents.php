<?php

namespace Riomigal\Languages\Livewire\Traits;

use Livewire\Component;
use ReflectionException;
use ReflectionMethod;

trait DispatchesLegacyEvents
{
    /**
     * Livewire v2/v3 compatibility shim.
     *
     * Signature intentionally matches Livewire 2's Component::emit().
     *
     * @param mixed $event
     * @param mixed ...$params
     * @return mixed
     */
    public function emit($event, ...$params)
    {
        // Livewire 3+: emit was replaced by dispatch().
        if (method_exists(Component::class, 'dispatch')) {
            return match ($event) {
                'showToast' => $this->dispatch(
                    $event,
                    message: $params[0] ?? '',
                    type: $params[1] ?? '',
                    duration: $params[2] ?? 3000
                ),
                'startBatchProgress' => $this->dispatch(
                    $event,
                    id: $params[0] ?? null
                ),
                'closeToastMessage' => $this->dispatch(
                    $event,
                    duration: $params[0] ?? 3000
                ),
                default => $this->dispatch($event),
            };
        }

        // Livewire 2.x fallback.
        if (method_exists(Component::class, 'emit')) {
            try {
                $emit = new ReflectionMethod(Component::class, 'emit');
                return $emit->invoke($this, $event, ...$params);
            } catch (ReflectionException) {
                return null;
            }
        }

        return null;
    }

    /**
     * Livewire v2/v3 compatibility shim for browser events.
     *
     * Signature intentionally matches Livewire 2's Component::dispatchBrowserEvent().
     *
     * @param mixed $event
     * @param mixed $data
     * @return mixed
     */
    public function dispatchBrowserEvent($event, $data = null)
    {
        // Livewire 3+: dispatchBrowserEvent was replaced by dispatch().
        if (method_exists(Component::class, 'dispatch')) {
            if ($data === null || $data === []) {
                return $this->dispatch($event);
            }

            if (is_array($data)) {
                return $this->dispatch($event, ...$data);
            }

            return $this->dispatch($event, $data);
        }

        // Livewire 2.x fallback.
        if (method_exists(Component::class, 'dispatchBrowserEvent')) {
            try {
                $dispatchBrowserEvent = new ReflectionMethod(Component::class, 'dispatchBrowserEvent');
                return $dispatchBrowserEvent->invoke($this, $event, $data);
            } catch (ReflectionException) {
                return null;
            }
        }

        return null;
    }
}
