<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

class AddIsEncryptedToMessagesTable extends Migration
{
    protected function schema()
    {
        $connection = config('musonza_chat.database_connection');

        return $connection ? Schema::connection($connection) : Schema::getFacadeRoot();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table(ConfigurationManager::MESSAGES_TABLE, function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table(ConfigurationManager::MESSAGES_TABLE, function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }
}
