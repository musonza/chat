<?php

namespace Musonza\Chat;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /* protected $connection = ConfigurationManager::$DB_CONNECTION; */
    protected $tablePrefix = 'chat_';

    public function getConnectionName()
    {
        return ConfigurationManager::defaultConnection();
    }
}
