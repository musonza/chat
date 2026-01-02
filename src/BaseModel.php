<?php

namespace Musonza\Chat;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $tablePrefix = 'chat_';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($connection = config('musonza_chat.database_connection')) {
            $this->setConnection($connection);
        }
    }
}
