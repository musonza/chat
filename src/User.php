<?php

namespace Musonza\Chat;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Traits\Messageable;

class User extends Model
{
    use Messageable;
    protected $table = 'mc_users';
    protected $primaryKey = 'uid';
}

class Client extends Model
{
    use Messageable;
    protected $table = 'mc_clients';
    protected $primaryKey = 'client_id';
}