<?php

namespace Musonza\Chat\Facades;

use Illuminate\Support\Facades\Facade;

class ChatFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected static function getFacadeAccessor()
    {
        return 'chat';
    }
}
