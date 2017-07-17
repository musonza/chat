<?php

namespace Musonza\Chat\Eventing;

use Illuminate\Events\Dispatcher;
use Illuminate\Log\Writer;

class EventDispatcher
{
    protected $event;

    protected $log;

    public function __construct(Dispatcher $event, Writer $log)
    {
        $this->event = $event;
        $this->log = $log;
    }

    public function dispatch(array $events)
    {
        foreach ($events as $event) {
            $eventName = $this->getEventName($event);

            $this->event->fire($eventName, $event);

            //$this->log->info("$eventName was fired.");
        }
    }

    public function getEventName($event)
    {
        return str_replace('\\', '.', get_class($event));
    }
}
