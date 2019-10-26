<?php

namespace Musonza\Chat\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Traits\Messageable;

class User extends Model
{
    use Messageable;
    protected $table = 'mc_users';
}

class Client extends Model
{
    use Messageable;
    protected $table = 'mc_clients';
    protected $primaryKey = 'client_id';
}

class Bot extends Model
{
    use Messageable;
    protected $table = 'mc_bots';
    protected $primaryKey = 'bot_id';
}