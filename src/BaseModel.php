<?php

namespace Musonza\Chat;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $prefix = 'mc_';

    protected $connection;

    public function __construct()
    {
        $config = app('musonza_chat');

        $this->connection = $config['db_connection'];
    }
}
