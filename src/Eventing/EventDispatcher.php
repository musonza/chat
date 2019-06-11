<?php

namespace Musonza\Chat\Eventing;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Musonza\Chat\Chat;

class EventDispatcher
{
    protected $event;

    public function __construct(DispatcherContract $event)
    {
        $this->event = $event;
    }

    public function dispatch(array $events)
    {
        if (Chat::broadcasts()) {
            foreach ($events as $event) {
                $eventName = $this->getEventName($event);
                $this->event->dispatch($eventName, $event);
            }
        }
    }

    public function getEventName($event)
    {
        return str_replace('\\', '.', get_class($event));
    }
}
