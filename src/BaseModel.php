<?php

namespace Musonza\Chat;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $tablePrefix = 'chat_';
    
    public function getConnectionName()
    {
        return config('musonza_chat.database_connection', config('database.default'));
    }
}
