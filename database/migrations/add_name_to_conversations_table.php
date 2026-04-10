<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

class AddNameToConversationsTable extends Migration
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
        $this->schema()->table(ConfigurationManager::CONVERSATIONS_TABLE, function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table(ConfigurationManager::CONVERSATIONS_TABLE, function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropColumn('name');
        });
    }
}
