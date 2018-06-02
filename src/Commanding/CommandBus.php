<?php

namespace Musonza\Chat\Commanding;

use Illuminate\Foundation\Application;

class CommandBus
{
    private $app;

    protected $commandTranslator;

    public function __construct(Application $app, CommandTranslator $commandTranslator)
    {
        $this->commandTranslator = $commandTranslator;
        $this->app = $app;
    }

    public function execute($command)
    {
        $handler = $this->commandTranslator->toCommandHandler($command);
        return $this->app->make($handler)->handle($command);
    }
}
