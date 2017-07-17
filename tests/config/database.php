<?php

return [

    'fetch' => PDO::FETCH_CLASS,

    'default' => 'sqlite',

    'connections' => [

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/../database/testing.sqlite',
            'prefix'   => '',
        ],

    ],

];
